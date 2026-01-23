<?php

/**
 * Cloud OCR Services Integration
 * Alternative to local Tesseract for production deployments
 */

class CloudOCR
{
    private $provider;
    private $apiKey;
    private $config;

    public function __construct($provider = 'google', $apiKey = null)
    {
        $this->provider = $provider;
        $this->apiKey = $apiKey;
        $this->config = $this->getProviderConfig($provider);
    }

    /**
     * Extract text from receipt image using cloud OCR
     */
    public function extractReceiptData($imagePath)
    {
        switch ($this->provider) {
            case 'google':
                return $this->googleVisionOCR($imagePath);
            case 'azure':
                return $this->azureComputerVision($imagePath);
            case 'aws':
                return $this->awsTextract($imagePath);
            case 'tesseract':
                return $this->tesseractCloud($imagePath);
            default:
                throw new Exception("Unsupported OCR provider: {$this->provider}");
        }
    }

    /**
     * Google Cloud Vision API
     */
    private function googleVisionOCR($imagePath)
    {
        if (!$this->apiKey) {
            throw new Exception("Google Vision API key required");
        }

        $imageContent = base64_encode(file_get_contents($imagePath));
        
        $postData = [
            'requests' => [
                [
                    'image' => [
                        'content' => $imageContent
                    ],
                    'features' => [
                        [
                            'type' => 'TEXT_DETECTION',
                            'maxResults' => 10
                        ],
                        [
                            'type' => 'DOCUMENT_TEXT_DETECTION',
                            'maxResults' => 10
                        ]
                    ]
                ]
            ]
        ];

        $ch = curl_init('https://vision.googleapis.com/v1/images:annotate?key=' . $this->apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Google Vision API error: HTTP $httpCode");
        }

        $result = json_decode($response, true);
        return $this->parseGoogleVisionResult($result);
    }

    /**
     * Azure Computer Vision API
     */
    private function azureComputerVision($imagePath)
    {
        if (!$this->apiKey) {
            throw new Exception("Azure Computer Vision API key required");
        }

        $imageData = file_get_contents($imagePath);
        
        $ch = curl_init('https://' . $this->config['region'] . '.api.cognitive.microsoft.com/vision/v3.2/ocr?language=en&detectOrientation=true');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $imageData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Ocp-Apim-Subscription-Key: ' . $this->apiKey,
            'Content-Type: application/octet-stream'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Azure Computer Vision error: HTTP $httpCode");
        }

        $result = json_decode($response, true);
        return $this->parseAzureResult($result);
    }

    /**
     * AWS Textract
     */
    private function awsTextract($imagePath)
    {
        // Requires AWS SDK for PHP
        if (!class_exists('Aws\Textract\TextractClient')) {
            throw new Exception("AWS SDK not installed. Run: composer require aws/aws-sdk-php");
        }

        $client = new Aws\Textract\TextractClient([
            'version' => 'latest',
            'region' => $this->config['region'],
            'credentials' => [
                'key' => $this->config['access_key'],
                'secret' => $this->config['secret_key']
            ]
        ]);

        $imageData = file_get_contents($imagePath);
        
        $result = $client->detectDocumentText([
            'Image' => [
                'Bytes' => $imageData
            ]
        ]);

        return $this->parseTextractResult($result->toArray());
    }

    /**
     * Tesseract Cloud Service (OCR.space)
     */
    private function tesseractCloud($imagePath)
    {
        $postData = [
            'apikey' => $this->apiKey,
            'language' => 'eng',
            'isOverlayRequired' => false,
            'detectOrientation' => true,
            'scale' => true,
            'OCREngine' => 2
        ];

        if (function_exists('curl_file_create')) {
            $postData['file'] = curl_file_create($imagePath);
        } else {
            $postData['file'] = '@' . $imagePath;
        }

        $ch = curl_init('https://api.ocr.space/parse/image');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("OCR.space error: HTTP $httpCode");
        }

        $result = json_decode($response, true);
        return $this->parseOCRSpaceResult($result);
    }

    /**
     * Parse Google Vision results
     */
    private function parseGoogleVisionResult($result)
    {
        $text = '';
        if (isset($result['responses'][0]['fullTextAnnotation']['text'])) {
            $text = $result['responses'][0]['fullTextAnnotation']['text'];
        }

        return $this->parseReceiptText($text, 'google');
    }

    /**
     * Parse Azure results
     */
    private function parseAzureResult($result)
    {
        $text = '';
        if (isset($result['regions'])) {
            foreach ($result['regions'] as $region) {
                foreach ($region['lines'] as $line) {
                    $words = [];
                    foreach ($line['words'] as $word) {
                        $words[] = $word['text'];
                    }
                    $text .= implode(' ', $words) . "\n";
                }
            }
        }

        return $this->parseReceiptText($text, 'azure');
    }

    /**
     * Parse AWS Textract results
     */
    private function parseTextractResult($result)
    {
        $text = '';
        if (isset($result['Blocks'])) {
            foreach ($result['Blocks'] as $block) {
                if ($block['BlockType'] === 'LINE') {
                    $text .= $block['Text'] . "\n";
                }
            }
        }

        return $this->parseReceiptText($text, 'aws');
    }

    /**
     * Parse OCR.space results
     */
    private function parseOCRSpaceResult($result)
    {
        $text = '';
        if (isset($result['ParsedResults'][0]['ParsedText'])) {
            $text = $result['ParsedResults'][0]['ParsedText'];
        }

        return $this->parseReceiptText($text, 'ocrspace');
    }

    /**
     * Common receipt text parsing (same as local Tesseract)
     */
    private function parseReceiptText($rawText, $provider)
    {
        $lines = array_filter(array_map('trim', explode("\n", $rawText)));
        $data = [
            'raw_text' => $rawText,
            'provider' => $provider,
            'merchant' => '',
            'date' => '',
            'total_amount' => 0,
            'fuel_amount' => 0,
            'toll_amount' => 0,
            'other_amount' => 0,
            'confidence' => 'high', // Cloud services typically have high accuracy
            'extracted_items' => []
        ];

        // Same parsing logic as local OCR
        $patterns = [
            'merchant' => [
                '/^(.*?GASOLINE.*?)/i',
                '/^(.*?PETRON.*?)/i',
                '/^(.*?SHELL.*?)/i',
                '/^(.*?CALTEX.*?)/i',
                '/^(.*?SEA OIL.*?)/i',
                '/^(.*?TOTAL.*?)/i'
            ],
            'amount' => [
                '/TOTAL[:\s]*\$?[\s,]*([\d,]+\.\d{2})/i',
                '/AMOUNT[:\s]*\$?[\s,]*([\d,]+\.\d{2})/i',
                '/PHP[\s,]*([\d,]+\.\d{2})/i',
                '/₱[\s,]*([\d,]+\.\d{2})/i'
            ],
            'fuel' => [
                '/FUEL[:\s]*\$?[\s,]*([\d,]+\.\d{2})/i',
                '/GASOLINE[:\s]*\$?[\s,]*([\d,]+\.\d{2})/i',
                '/DIESEL[:\s]*\$?[\s,]*([\d,]+\.\d{2})/i'
            ],
            'toll' => [
                '/TOLL[:\s]*\$?[\s,]*([\d,]+\.\d{2})/i',
                '/TOLL FEE[:\s]*\$?[\s,]*([\d,]+\.\d{2})/i'
            ]
        ];

        // Extract patterns (same as local OCR)
        foreach ($patterns['merchant'] as $pattern) {
            foreach ($lines as $line) {
                if (preg_match($pattern, $line, $matches)) {
                    $data['merchant'] = trim($matches[1]);
                    break 2;
                }
            }
        }

        foreach ($patterns['amount'] as $pattern) {
            if (preg_match($pattern, $rawText, $matches)) {
                $data['total_amount'] = (float) str_replace(',', '', $matches[1]);
                break;
            }
        }

        foreach ($patterns['fuel'] as $pattern) {
            if (preg_match($pattern, $rawText, $matches)) {
                $data['fuel_amount'] = (float) str_replace(',', '', $matches[1]);
                break;
            }
        }

        foreach ($patterns['toll'] as $pattern) {
            if (preg_match($pattern, $rawText, $matches)) {
                $data['toll_amount'] = (float) str_replace(',', '', $matches[1]);
                break;
            }
        }

        if ($data['total_amount'] > 0) {
            $knownAmounts = $data['fuel_amount'] + $data['toll_amount'];
            $data['other_amount'] = max(0, $data['total_amount'] - $knownAmounts);
        }

        preg_match_all('/[\$₱]?[\s,]*([\d,]+\.\d{2})/', $rawText, $amountMatches);
        $data['extracted_items'] = array_map(function($match) {
            return (float) str_replace(',', '', $match);
        }, $amountMatches[1]);

        return $data;
    }

    /**
     * Get provider configuration
     */
    private function getProviderConfig($provider)
    {
        $configs = [
            'google' => [
                'endpoint' => 'https://vision.googleapis.com/v1/images:annotate',
                'free_tier' => 1000 // requests per month
            ],
            'azure' => [
                'endpoint' => 'https://{region}.api.cognitive.microsoft.com/vision/v3.2/ocr',
                'region' => 'eastus',
                'free_tier' => 30 // requests per second
            ],
            'aws' => [
                'region' => 'us-east-1',
                'free_tier' => 1000 // pages per month
            ],
            'tesseract' => [
                'endpoint' => 'https://api.ocr.space/parse/image',
                'free_tier' => 25000 // requests per month
            ]
        ];

        return $configs[$provider] ?? [];
    }

    /**
     * Check if cloud OCR is available
     */
    public function isAvailable()
    {
        return !empty($this->apiKey) || $this->provider === 'tesseract';
    }

    /**
     * Get supported providers
     */
    public static function getSupportedProviders()
    {
        return [
            'google' => 'Google Cloud Vision API',
            'azure' => 'Azure Computer Vision',
            'aws' => 'AWS Textract',
            'tesseract' => 'OCR.space (Free)'
        ];
    }
}
