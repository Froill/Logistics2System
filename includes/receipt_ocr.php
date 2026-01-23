<?php

/**
 * OCR Receipt Processing for TCAO Module
 * Uses Tesseract OCR to extract text from receipt images
 */

class ReceiptOCR
{
    private $tesseractPath;
    private $tempDir;
    private $outputDir;

    public function __construct()
    {
        // Default Tesseract path - adjust based on your system
        $this->tesseractPath = 'tesseract'; // Assumes tesseract is in PATH
        $this->tempDir = __DIR__ . '/../temp/ocr';
        $this->outputDir = __DIR__ . '/../uploads/ocr_results';
        
        // Create directories if they don't exist
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    /**
     * Extract text from receipt image using OCR
     * @param string $imagePath Path to the receipt image
     * @return array Extracted data with confidence scores
     */
    public function extractReceiptData($imagePath)
    {
        if (!file_exists($imagePath)) {
            throw new Exception("Receipt file not found: $imagePath");
        }

        $tempFile = $this->tempDir . '/' . uniqid('receipt_') . '.' . pathinfo($imagePath, PATHINFO_EXTENSION);
        $outputBase = $this->tempDir . '/' . uniqid('ocr_output_');
        
        try {
            // Copy image to temp location
            copy($imagePath, $tempFile);
            
            // Preprocess image for better OCR accuracy
            $this->preprocessImage($tempFile);
            
            // Run Tesseract OCR
            $command = sprintf(
                '%s "%s" "%s" -l eng+phi --psm 6 --oem 3 txt',
                escapeshellcmd($this->tesseractPath),
                escapeshellarg($tempFile),
                escapeshellarg($outputBase)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception("OCR processing failed with return code: $returnCode");
            }
            
            // Read OCR result
            $ocrTextFile = $outputBase . '.txt';
            if (!file_exists($ocrTextFile)) {
                throw new Exception("OCR output file not generated");
            }
            
            $rawText = file_get_contents($ocrTextFile);
            
            // Parse and structure the extracted data
            $parsedData = $this->parseReceiptText($rawText);
            
            // Save OCR results for reference
            $this->saveOCRResult($imagePath, $rawText, $parsedData);
            
            return $parsedData;
            
        } finally {
            // Cleanup temporary files
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            if (file_exists($outputBase . '.txt')) {
                unlink($outputBase . '.txt');
            }
        }
    }

    /**
     * Preprocess image to improve OCR accuracy
     * @param string $imagePath Path to the image file
     */
    private function preprocessImage($imagePath)
    {
        // Check if ImageMagick is available
        if (!exec('which convert')) {
            return; // Skip preprocessing if ImageMagick is not available
        }
        
        $processedPath = $imagePath . '_processed.png';
        
        // ImageMagick commands for preprocessing
        $commands = [
            // Convert to grayscale and enhance contrast
            sprintf('convert "%s" -colorspace Gray -contrast-stretch 5%% "%s"', $imagePath, $processedPath),
            // Denoise
            sprintf('convert "%s" -despeckle "%s"', $processedPath, $processedPath),
            // Sharpen
            sprintf('convert "%s" -sharpen 0x1.5 "%s"', $processedPath, $processedPath)
        ];
        
        foreach ($commands as $cmd) {
            exec($cmd);
        }
        
        // Replace original with processed version
        if (file_exists($processedPath)) {
            rename($processedPath, $imagePath);
        }
    }

    /**
     * Parse raw OCR text to extract structured receipt data
     * @param string $rawText Raw text from OCR
     * @return array Parsed receipt data
     */
    private function parseReceiptText($rawText)
    {
        $lines = array_filter(array_map('trim', explode("\n", $rawText)));
        $data = [
            'raw_text' => $rawText,
            'merchant' => '',
            'date' => '',
            'total_amount' => 0,
            'fuel_amount' => 0,
            'toll_amount' => 0,
            'other_amount' => 0,
            'confidence' => 'medium',
            'extracted_items' => []
        ];

        // Common patterns for Philippine receipts
        $patterns = [
            'merchant' => [
                '/^(.*?GASOLINE.*?)/i',
                '/^(.*?PETRON.*?)/i',
                '/^(.*?SHELL.*?)/i',
                '/^(.*?CALTEX.*?)/i',
                '/^(.*?SEA OIL.*?)/i',
                '/^(.*?TOTAL.*?)/i',
                '/^(.*?PHILIPPINE.*?)/i'
            ],
            'amount' => [
                '/TOTAL[:\s]*\$?[\s,]*([\d,]+\.\d{2})/i',
                '/AMOUNT[:\s]*\$?[\s,]*([\d,]+\.\d{2})/i',
                '/SUM[:\s]*\$?[\s,]*([\d,]+\.\d{2})/i',
                '/PHP[\s,]*([\d,]+\.\d{2})/i',
                '/₱[\s,]*([\d,]+\.\d{2})/i',
                '/([\d,]+\.\d{2})\s*TOTAL/i'
            ],
            'fuel' => [
                '/FUEL[:\s]*\$?[\s,]*([\d,]+\.\d{2})/i',
                '/GASOLINE[:\s]*\$?[\s,]*([\d,]+\.\d{2})/i',
                '/DIESEL[:\s]*\$?[\s,]*([\d,]+\.\d{2})/i',
                '/LITERS?[:\s]*[\d.]+.*?([\d,]+\.\d{2})/i'
            ],
            'toll' => [
                '/TOLL[:\s]*\$?[\s,]*([\d,]+\.\d{2})/i',
                '/TOLL FEE[:\s]*\$?[\s,]*([\d,]+\.\d{2})/i',
                '/EXPRESSWAY[:\s]*\$?[\s,]*([\d,]+\.\d{2})/i'
            ],
            'date' => [
                '/(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/',
                '/(\d{2,4}[-\/]\d{1,2}[-\/]\d{1,2})/',
                '/(JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC)[a-z]*\s+\d{1,2},?\s+\d{2,4}/i'
            ]
        ];

        // Extract merchant name
        foreach ($patterns['merchant'] as $pattern) {
            foreach ($lines as $line) {
                if (preg_match($pattern, $line, $matches)) {
                    $data['merchant'] = trim($matches[1]);
                    break 2;
                }
            }
        }

        // Extract amounts
        foreach ($patterns['amount'] as $pattern) {
            if (preg_match($pattern, $rawText, $matches)) {
                $data['total_amount'] = (float) str_replace(',', '', $matches[1]);
                break;
            }
        }

        // Extract fuel amount
        foreach ($patterns['fuel'] as $pattern) {
            if (preg_match($pattern, $rawText, $matches)) {
                $data['fuel_amount'] = (float) str_replace(',', '', $matches[1]);
                break;
            }
        }

        // Extract toll amount
        foreach ($patterns['toll'] as $pattern) {
            if (preg_match($pattern, $rawText, $matches)) {
                $data['toll_amount'] = (float) str_replace(',', '', $matches[1]);
                break;
            }
        }

        // Extract date
        foreach ($patterns['date'] as $pattern) {
            if (preg_match($pattern, $rawText, $matches)) {
                $data['date'] = $matches[1];
                break;
            }
        }

        // Calculate other amount if total is found
        if ($data['total_amount'] > 0) {
            $knownAmounts = $data['fuel_amount'] + $data['toll_amount'];
            $data['other_amount'] = max(0, $data['total_amount'] - $knownAmounts);
        }

        // Set confidence based on data quality
        if ($data['total_amount'] > 0 && $data['merchant'] !== '') {
            $data['confidence'] = 'high';
        } elseif ($data['total_amount'] > 0) {
            $data['confidence'] = 'medium';
        } else {
            $data['confidence'] = 'low';
        }

        // Extract all monetary values for review
        preg_match_all('/[\$₱]?[\s,]*([\d,]+\.\d{2})/', $rawText, $amountMatches);
        $data['extracted_items'] = array_map(function($match) {
            return (float) str_replace(',', '', $match);
        }, $amountMatches[1]);

        return $data;
    }

    /**
     * Save OCR results for reference and debugging
     * @param string $imagePath Original image path
     * @param string $rawText Raw OCR text
     * @param array $parsedData Parsed data
     */
    private function saveOCRResult($imagePath, $rawText, $parsedData)
    {
        $resultFile = $this->outputDir . '/' . uniqid('ocr_result_') . '.json';
        $result = [
            'image_path' => $imagePath,
            'processed_at' => date('Y-m-d H:i:s'),
            'raw_text' => $rawText,
            'parsed_data' => $parsedData
        ];
        
        file_put_contents($resultFile, json_encode($result, JSON_PRETTY_PRINT));
    }

    /**
     * Check if Tesseract OCR is available
     * @return bool
     */
    public function isAvailable()
    {
        $output = [];
        $returnCode = 0;
        exec($this->tesseractPath . ' --version 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Get supported image formats
     * @return array
     */
    public function getSupportedFormats()
    {
        return ['jpg', 'jpeg', 'png', 'bmp', 'tiff', 'pdf'];
    }
}
