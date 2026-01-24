<?php

//////////////////////////////////////////START OF TCAO LOGIC

require_once __DIR__ . '/receipt_ocr.php';
require_once __DIR__ . '/receipt_duplicate_detector.php';
require_once __DIR__ . '/cloud_ocr.php';

function tcao_logic($baseURL)
{

    $user = $_SESSION['full_name'] ?? 'unknown';
    global $conn;

    // Handle delete (admin only)
    if (isset($_GET['delete'])) {
        deleteData('transport_costs', $_GET['delete']);
        log_audit_event('TCAO', 'deleted', $_GET['delete'], $user, 'Cost entry deleted');
        header("Location: {$baseURL}");
        exit;
    }
    // Clear all cost logs
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cost_logs'])) {
        $allCosts = fetchAll('transport_costs');
        foreach ($allCosts as $cost) {
            log_audit_event('TCAO', 'deleted', $cost['id'], $user);
        }
        $conn->query("DELETE FROM transport_costs");
        $_SESSION['tcao_success'] = 'All cost logs cleared.';
        header("Location: {$baseURL}");
        exit;
    }

    // Handle status transitions (supervisor/accountant)
    if (isset($_GET['approve']) && isset($_GET['role'])) {
        $id = intval($_GET['approve']);
        $role = $_GET['role'];
        if ($role === 'supervisor') {
            $conn->query("UPDATE transport_costs SET status='supervisor_approved' WHERE id=$id");
            log_audit_event('TCAO', 'supervisor_approved', $id, $user, 'Approved by supervisor');
        } elseif ($role === 'accountant') {
            $conn->query("UPDATE transport_costs SET status='finalized' WHERE id=$id");
            log_audit_event('TCAO', 'finalized', $id, $user, 'Finalized by accountant');
        }
        header("Location: {$baseURL}");
        exit;
    }
    if (isset($_GET['return']) && isset($_GET['role'])) {
        $id = intval($_GET['return']);
        $role = $_GET['role'];
        $conn->query("UPDATE transport_costs SET status='returned' WHERE id=$id");
        log_audit_event('TCAO', 'returned_by_' . $role, $id, $user, 'Returned by ' . $role);
        header("Location: {$baseURL}");
        exit;
    }

    // Handle driver submission (with receipt upload)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trip_id'])) {
        $fuel  = floatval($_POST['fuel_cost'] ?: 0);
        $toll  = floatval($_POST['toll_fees'] ?: 0);
        $other = floatval($_POST['other_expenses'] ?: 0);
        $total = $fuel + $toll + $other;

        // Handle receipt upload with OCR processing and duplicate detection
        $receipt_path = null;
        $ocr_data = null;
        $duplicate_result = null;

        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
            $target = __DIR__ . '/../uploads/receipts_' . uniqid() . '.' . $ext;
            if (!is_dir(__DIR__ . '/../uploads')) mkdir(__DIR__ . '/../uploads');
            move_uploaded_file($_FILES['receipt']['tmp_name'], $target);
            $receipt_path = basename($target);

            // Check for duplicates before processing
            try {
                $detector = new ReceiptDuplicateDetector($conn);
                $duplicate_result = $detector->checkDuplicate($target, $_POST, $user);

                // Log the duplicate check for audit
                $detector->logDuplicateCheck($user, $target, $duplicate_result);

                // If duplicate detected, block submission and show warning
                if ($duplicate_result['is_duplicate']) {
                    $_SESSION['tcao_error'] = 'Potential duplicate receipt detected! This receipt appears to be similar to a previously submitted receipt.';
                    $_SESSION['duplicate_details'] = $duplicate_result;
                    unlink($target); // Remove the uploaded file
                    header("Location: {$baseURL}");
                    exit;
                }

                // Process receipt with OCR (cloud first, then local fallback)
                $ocr_data = null;

                // Try cloud OCR services first
                $cloudProviders = [
                    'tesseract' => 'YOUR_OCR_SPACE_API_KEY', // Free tier: 25,000 requests/month
                    'google' => 'YOUR_GOOGLE_VISION_API_KEY', // Paid: $1.50 per 1000 images
                    'azure' => 'YOUR_AZURE_VISION_KEY', // Paid: varies by region
                ];

                foreach ($cloudProviders as $provider => $apiKey) {
                    if (!empty($apiKey) && $apiKey !== 'YOUR_' . strtoupper($provider) . '_API_KEY') {
                        try {
                            $cloudOCR = new CloudOCR($provider, $apiKey);
                            if ($cloudOCR->isAvailable()) {
                                $ocr_data = $cloudOCR->extractReceiptData($target);
                                error_log("Cloud OCR success with provider: $provider");
                                break;
                            }
                        } catch (Exception $e) {
                            error_log("Cloud OCR failed with $provider: " . $e->getMessage());
                            continue;
                        }
                    }
                }

                // Fallback to local Tesseract if cloud OCR failed
                if (!$ocr_data) {
                    try {
                        $ocr = new ReceiptOCR();
                        if ($ocr->isAvailable()) {
                            $ocr_data = $ocr->extractReceiptData($target);
                            error_log("Local Tesseract OCR used as fallback");
                        }
                    } catch (Exception $e) {
                        error_log("Local OCR failed: " . $e->getMessage());
                    }
                }

                // Auto-fill amounts if OCR provides good data
                if ($ocr_data && ($ocr_data['confidence'] === 'high' || $ocr_data['confidence'] === 'medium')) {
                    if ($ocr_data['fuel_amount'] > 0 && $fuel == 0) {
                        $fuel = $ocr_data['fuel_amount'];
                    }
                    if ($ocr_data['toll_amount'] > 0 && $toll == 0) {
                        $toll = $ocr_data['toll_amount'];
                    }
                    if ($ocr_data['other_amount'] > 0 && $other == 0) {
                        $other = $ocr_data['other_amount'];
                    }
                    // Recalculate total
                    $total = $fuel + $toll + $other;
                }
            } catch (Exception $e) {
                // Log OCR error but don't fail upload
                error_log("OCR Processing Error: " . $e->getMessage());
            }
        }

        // Validate: check for duplicate trip_id
        $stmt = $conn->prepare("SELECT id FROM transport_costs WHERE trip_id=?");
        $stmt->bind_param('s', $_POST['trip_id']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $_SESSION['tcao_error'] = 'Trip already has a cost entry.';
            header("Location: {$baseURL}");
            exit;
        }

        // Insert with OCR data
        $ocr_json = $ocr_data ? json_encode($ocr_data) : null;
        $ocr_confidence = $ocr_data ? $ocr_data['confidence'] : null;
        $stmt = $conn->prepare("INSERT INTO transport_costs (trip_id, fuel_cost, toll_fees, other_expenses, total_cost, receipt, ocr_data, ocr_confidence, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'submitted', ?, NOW())");
        $stmt->bind_param('sdddssssss', $_POST['trip_id'], $fuel, $toll, $other, $total, $receipt_path, $ocr_json, $ocr_confidence, $user);
        $stmt->execute();
        $cost_id = $stmt->insert_id;

        // Add OCR success message if data was extracted
        if ($ocr_data && ($ocr_data['confidence'] === 'high' || $ocr_data['confidence'] === 'medium')) {
            $_SESSION['tcao_success'] = 'Cost entry submitted. OCR extracted data from receipt successfully.';
        } else {
            $_SESSION['tcao_success'] = 'Cost entry submitted.';
        }
        header("Location: {$baseURL}");
        exit;
    }
}
// Export Cost Analysis Report (by month)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_cost_report'])) {
    $costs = fetchAll('transport_costs');
    $allTrips = fetchAll('driver_trips');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="transport_cost_analysis_report.csv"');
    $output = fopen('php://output', 'w');
    // CSV header
    fputcsv($output, ['Month', 'Total Fuel Cost', 'Total Toll Fees', 'Total Other Expenses', 'Total Cost', 'Num Trips']);
    // Group costs by month
    $monthly = [];
    foreach ($costs as $c) {
        $trip = null;
        foreach ($allTrips as $t) {
            if ($t['id'] == $c['trip_id']) {
                $trip = $t;
                break;
            }
        }
        if (!$trip) continue;
        $month = date('Y-m', strtotime($trip['trip_date']));
        if (!isset($monthly[$month])) {
            $monthly[$month] = [
                'fuel' => 0,
                'toll' => 0,
                'other' => 0,
                'total' => 0,
                'trips' => 0
            ];
        }
        $monthly[$month]['fuel'] += floatval($c['fuel_cost']);
        $monthly[$month]['toll'] += floatval($c['toll_fees']);
        $monthly[$month]['other'] += floatval($c['other_expenses']);
        $monthly[$month]['total'] += floatval($c['fuel_cost']) + floatval($c['toll_fees']) + floatval($c['other_expenses']);
        $monthly[$month]['trips']++;
    }
    // Output rows
    foreach ($monthly as $month => $row) {
        fputcsv($output, [
            $month,
            number_format($row['fuel'], 2),
            number_format($row['toll'], 2),
            number_format($row['other'], 2),
            number_format($row['total'], 2),
            $row['trips']
        ]);
    }
    fclose($output);
    exit;
}
//////////////////////////////////////////END OF TCAO LOGIC