<?php

require_once __DIR__ . '/receipt_ocr.php';

/**
 * Receipt Duplicate Detection System
 * Uses OCR data and metadata to detect potential duplicate receipts
 */
class ReceiptDuplicateDetector
{
    private $conn;
    private $ocr;
    private $similarityThreshold = 85; // Percentage threshold for text similarity
    private $timeWindowDays = 7; // Look for duplicates within this time window
    private $amountThreshold = 0.01; // Amount difference threshold (1 peso)

    public function __construct($connection)
    {
        $this->conn = $connection;
        $this->ocr = new ReceiptOCR();
    }

    /**
     * Check if a receipt is a potential duplicate
     * @param string $imagePath Path to the receipt image
     * @param array $formData Form data (amounts, dates, etc.)
     * @param int $userId Current user ID
     * @return array Duplicate detection results
     */
    public function checkDuplicate($imagePath, $formData, $userId)
    {
        try {
            // Extract OCR data from the receipt
            $ocrData = $this->ocr->extractReceiptData($imagePath);
            
            // Get recent receipts for comparison
            $recentReceipts = $this->getRecentReceipts($userId, $this->timeWindowDays);
            
            $duplicates = [];
            $maxSimilarity = 0;
            
            foreach ($recentReceipts as $existing) {
                $existingOcr = json_decode($existing['ocr_data'], true);
                
                if (!$existingOcr) {
                    continue; // Skip receipts without OCR data
                }
                
                $similarity = $this->calculateSimilarity($ocrData, $existingOcr);
                $maxSimilarity = max($maxSimilarity, $similarity);
                
                if ($similarity >= $this->similarityThreshold) {
                    $duplicates[] = [
                        'id' => $existing['id'],
                        'trip_id' => $existing['trip_id'],
                        'similarity' => $similarity,
                        'created_at' => $existing['created_at'],
                        'total_amount' => $existingOcr['total_amount'] ?? 0,
                        'merchant' => $existingOcr['merchant'] ?? '',
                        'reason' => $this->getDuplicateReason($ocrData, $existingOcr)
                    ];
                }
            }
            
            // Check for exact amount duplicates
            $amountDuplicates = $this->checkAmountDuplicates($ocrData, $recentReceipts);
            
            // Check for merchant + amount + date pattern duplicates
            $patternDuplicates = $this->checkPatternDuplicates($ocrData, $recentReceipts);
            
            return [
                'is_duplicate' => !empty($duplicates) || !empty($amountDuplicates) || !empty($patternDuplicates),
                'ocr_data' => $ocrData,
                'text_duplicates' => $duplicates,
                'amount_duplicates' => $amountDuplicates,
                'pattern_duplicates' => $patternDuplicates,
                'max_similarity' => $maxSimilarity,
                'confidence' => $ocrData['confidence'] ?? 'low'
            ];
            
        } catch (Exception $e) {
            error_log("Duplicate detection error: " . $e->getMessage());
            return [
                'is_duplicate' => false,
                'error' => 'OCR processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate similarity between two OCR data sets
     */
    private function calculateSimilarity($ocr1, $ocr2)
    {
        $score = 0;
        $maxScore = 0;
        
        // Merchant similarity (40% weight)
        if (!empty($ocr1['merchant']) && !empty($ocr2['merchant'])) {
            $maxScore += 40;
            $score += 40 * $this->stringSimilarity($ocr1['merchant'], $ocr2['merchant']);
        }
        
        // Amount similarity (30% weight)
        if ($ocr1['total_amount'] > 0 && $ocr2['total_amount'] > 0) {
            $maxScore += 30;
            $amountDiff = abs($ocr1['total_amount'] - $ocr2['total_amount']);
            if ($amountDiff <= $this->amountThreshold) {
                $score += 30;
            } else {
                // Partial score based on amount difference
                $score += 30 * max(0, 1 - ($amountDiff / max($ocr1['total_amount'], $ocr2['total_amount'])));
            }
        }
        
        // Date similarity (20% weight)
        if (!empty($ocr1['date']) && !empty($ocr2['date'])) {
            $maxScore += 20;
            $date1 = $this->parseDate($ocr1['date']);
            $date2 = $this->parseDate($ocr2['date']);
            
            if ($date1 && $date2) {
                $daysDiff = abs($date1->diff($date2)->days);
                if ($daysDiff <= 1) {
                    $score += 20;
                } elseif ($daysDiff <= 7) {
                    $score += 20 * (1 - $daysDiff / 7);
                }
            }
        }
        
        // Raw text similarity (10% weight)
        if (!empty($ocr1['raw_text']) && !empty($ocr2['raw_text'])) {
            $maxScore += 10;
            $score += 10 * $this->stringSimilarity($ocr1['raw_text'], $ocr2['raw_text']);
        }
        
        return $maxScore > 0 ? ($score / $maxScore) * 100 : 0;
    }

    /**
     * Calculate string similarity using Levenshtein distance
     */
    private function stringSimilarity($str1, $str2)
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));
        
        if ($str1 === $str2) {
            return 1.0;
        }
        
        if (empty($str1) || empty($str2)) {
            return 0.0;
        }
        
        $distance = levenshtein($str1, $str2);
        $maxLen = max(strlen($str1), strlen($str2));
        
        return 1.0 - ($distance / $maxLen);
    }

    /**
     * Get recent receipts for duplicate checking
     */
    private function getRecentReceipts($userId, $days)
    {
        $stmt = $this->conn->prepare("
            SELECT tc.*, t.driver_id 
            FROM transport_costs tc 
            LEFT JOIN driver_trips t ON tc.trip_id = t.id 
            WHERE tc.created_by = ? 
            AND tc.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            AND tc.ocr_data IS NOT NULL
            ORDER BY tc.created_at DESC
            LIMIT 100
        ");
        $stmt->bind_param('si', $userId, $days);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $receipts = [];
        while ($row = $result->fetch_assoc()) {
            $receipts[] = $row;
        }
        
        return $receipts;
    }

    /**
     * Check for exact amount duplicates
     */
    private function checkAmountDuplicates($ocrData, $recentReceipts)
    {
        if ($ocrData['total_amount'] <= 0) {
            return [];
        }
        
        $duplicates = [];
        foreach ($recentReceipts as $existing) {
            $existingOcr = json_decode($existing['ocr_data'], true);
            if (!$existingOcr) continue;
            
            if (abs($ocrData['total_amount'] - $existingOcr['total_amount']) <= $this->amountThreshold) {
                $duplicates[] = [
                    'id' => $existing['id'],
                    'trip_id' => $existing['trip_id'],
                    'amount' => $existingOcr['total_amount'],
                    'created_at' => $existing['created_at'],
                    'reason' => 'Exact amount match'
                ];
            }
        }
        
        return $duplicates;
    }

    /**
     * Check for pattern duplicates (merchant + amount + time pattern)
     */
    private function checkPatternDuplicates($ocrData, $recentReceipts)
    {
        $duplicates = [];
        
        foreach ($recentReceipts as $existing) {
            $existingOcr = json_decode($existing['ocr_data'], true);
            if (!$existingOcr) continue;
            
            // Same merchant and similar amount
            $merchantMatch = false;
            if (!empty($ocrData['merchant']) && !empty($existingOcr['merchant'])) {
                $merchantMatch = $this->stringSimilarity($ocrData['merchant'], $existingOcr['merchant']) > 0.8;
            }
            
            $amountMatch = false;
            if ($ocrData['total_amount'] > 0 && $existingOcr['total_amount'] > 0) {
                $amountMatch = abs($ocrData['total_amount'] - $existingOcr['total_amount']) <= $this->amountThreshold;
            }
            
            if ($merchantMatch && $amountMatch) {
                $duplicates[] = [
                    'id' => $existing['id'],
                    'trip_id' => $existing['trip_id'],
                    'merchant' => $existingOcr['merchant'],
                    'amount' => $existingOcr['total_amount'],
                    'created_at' => $existing['created_at'],
                    'reason' => 'Merchant and amount pattern match'
                ];
            }
        }
        
        return $duplicates;
    }

    /**
     * Get reason for duplicate detection
     */
    private function getDuplicateReason($ocr1, $ocr2)
    {
        $reasons = [];
        
        if (!empty($ocr1['merchant']) && !empty($ocr2['merchant'])) {
            if ($this->stringSimilarity($ocr1['merchant'], $ocr2['merchant']) > 0.8) {
                $reasons[] = 'Same merchant';
            }
        }
        
        if ($ocr1['total_amount'] > 0 && $ocr2['total_amount'] > 0) {
            if (abs($ocr1['total_amount'] - $ocr2['total_amount']) <= $this->amountThreshold) {
                $reasons[] = 'Same amount';
            }
        }
        
        if (!empty($ocr1['date']) && !empty($ocr2['date'])) {
            $date1 = $this->parseDate($ocr1['date']);
            $date2 = $this->parseDate($ocr2['date']);
            
            if ($date1 && $date2 && $date1->diff($date2)->days <= 1) {
                $reasons[] = 'Same date';
            }
        }
        
        return !empty($reasons) ? implode(', ', $reasons) : 'Similar content';
    }

    /**
     * Parse date string to DateTime object
     */
    private function parseDate($dateString)
    {
        try {
            // Try various date formats common in receipts
            $formats = [
                'Y-m-d', 'm/d/Y', 'd/m/Y', 'Y/m/d',
                'M d, Y', 'd M Y', 'Y M d',
                'm-d-Y', 'd-m-Y'
            ];
            
            foreach ($formats as $format) {
                $date = DateTime::createFromFormat($format, $dateString);
                if ($date) {
                    return $date;
                }
            }
            
            // Try strtotime as fallback
            $timestamp = strtotime($dateString);
            if ($timestamp !== false) {
                return new DateTime($dateString);
            }
            
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Log duplicate detection attempts for audit
     */
    public function logDuplicateCheck($userId, $imagePath, $result)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO receipt_duplicate_checks 
            (user_id, image_path, is_duplicate, max_similarity, check_data, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $isDuplicate = $result['is_duplicate'] ? 1 : 0;
        $maxSimilarity = $result['max_similarity'] ?? 0;
        $checkData = json_encode($result);
        
        $stmt->bind_param('issds', $userId, $imagePath, $isDuplicate, $maxSimilarity, $checkData);
        $stmt->execute();
    }
}
