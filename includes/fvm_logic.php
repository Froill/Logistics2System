<?php
/////////////////////////////////////////////START OF FVM LOGIC
function fvm_logic($baseURL)
{

    // Handle manual adjustment of next maintenance date
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_maintenance_vehicle_id']) && isset($_POST['next_maintenance_date'])) {
        $vehicleId = intval($_POST['adjust_maintenance_vehicle_id']);
        $nextDate = $_POST['next_maintenance_date'];
        $part = trim($_POST['maintenance_part']);
        $currentTime = new DateTime('now', new DateTimeZone('Asia/Manila'));
        // Insert a maintenance log with the selected date
        insertData('fleet_vehicle_logs', [
            'vehicle_id' => $vehicleId,
            'log_type'   => 'maintenance',
            'details'    => $part.' scheduled for maintenance',
            'created_at' => $nextDate . $currentTime->format(' H:i:s')
        ]);
        // Optionally update vehicle status
        updateData('fleet_vehicles', $vehicleId, ['status' => 'Under Maintenance']);
        log_audit_event('FVM', 'set_maintenance', $vehicleId, $_SESSION['full_name'] ?? 'unknown');
        header("Location: {$baseURL}");
        exit;
    }
// Handle set maintenance form submission (details part)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_maintenance_vehicle_id'], $_POST['next_maintenance_date'], $_POST['maintenance_part'])) {
    $vehicleId = intval($_POST['adjust_maintenance_vehicle_id']);
    $date = $_POST['next_maintenance_date'];
    $part = trim($_POST['maintenance_part']);
    if ($vehicleId && $date && $part) {
        // Save to fleet_vehicle_logs as a maintenance log
        $db = getDb();
        $stmt = $db->prepare("INSERT INTO fleet_vehicle_logs (vehicle_id, log_type, details, created_at) VALUES (?, 'maintenance', ?, ?)");
        $desc = $part . ' scheduled for maintenance';
        $stmt->execute([$vehicleId, $desc, $date]);
        $_SESSION['fvm_success'] = 'Maintenance scheduled for ' . htmlspecialchars($part) . '.';
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    } else {
        $_SESSION['fvm_error'] = 'Please fill out all maintenance details.';
    }
}

// Handle export monthly report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_fleet_report'], $_POST['export_month']) && isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin','manager'])) {
    $month = $_POST['export_month']; // format: YYYY-MM
    $start = $month . '-01';
    $end = date('Y-m-t', strtotime($start));
    $db = getDb();
    // Fuel consumption per vehicle
    $fuelStmt = $db->prepare("SELECT vehicle_id, SUM(fuel_consumed) as total_fuel FROM driver_trips WHERE trip_date BETWEEN ? AND ? GROUP BY vehicle_id");
    $fuelStmt->execute([$start, $end]);
    $fuelData = [];
    foreach ($fuelStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $fuelData[$row['vehicle_id']] = $row['total_fuel'];
    }
    // Number of trips per vehicle
    $tripStmt = $db->prepare("SELECT vehicle_id, COUNT(*) as trip_count FROM driver_trips WHERE trip_date BETWEEN ? AND ? GROUP BY vehicle_id");
    $tripStmt->execute([$start, $end]);
    $tripData = [];
    foreach ($tripStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $tripData[$row['vehicle_id']] = $row['trip_count'];
    }
    // On-time performance and completed trips per driver
    $driverStmt = $db->prepare("SELECT driver_id, COUNT(*) as total_trips, SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN on_time=1 THEN 1 ELSE 0 END) as on_time FROM driver_trips WHERE trip_date BETWEEN ? AND ? GROUP BY driver_id");
    $driverStmt->execute([$start, $end]);
    $driverData = [];
    foreach ($driverStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $driverData[$row['driver_id']] = $row;
    }
    // Vehicles
    $vehicles = fetchAll('fleet_vehicles');
    // Drivers
    $drivers = fetchAll('drivers');
    // Prepare CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="fleet_kpi_report_' . $month . '.csv"');
    $out = fopen('php://output', 'w');
    // Vehicle KPIs
    fputcsv($out, ['Vehicle Name', 'Plate', 'Type', 'Fuel Consumed (L)', 'Trips', 'Maintenance Cost (Est.)']);
    foreach ($vehicles as $v) {
        $fuel = $fuelData[$v['id']] ?? 0;
        $trips = $tripData[$v['id']] ?? 0;
        $maintCost = $trips * 500; // Placeholder: 500 per trip
        fputcsv($out, [$v['vehicle_name'], $v['plate_number'], $v['vehicle_type'], $fuel, $trips, $maintCost]);
    }
    // Driver KPIs
    fputcsv($out, []);
    fputcsv($out, ['Driver Name', 'Total Trips', 'Completed', 'On-Time']);
    foreach ($drivers as $d) {
        $row = $driverData[$d['id']] ?? ['total_trips'=>0,'completed'=>0,'on_time'=>0];
        fputcsv($out, [$d['driver_name'], $row['total_trips'], $row['completed'], $row['on_time']]);
    }
    fclose($out);
    exit;
}

    // Handle clear maintenance logs
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_maintenance_logs'])) {
        global $conn;
        $conn->query("DELETE FROM fleet_vehicle_logs WHERE log_type = 'maintenance'");
        log_audit_event('FVM', 'clear_maintenance_logs', null, $_SESSION['full_name'] ?? 'unknown');
        $_SESSION['fvm_success'] = 'All maintenance logs cleared.';
        header("Location: {$baseURL}");
        exit;
    }
    // Handle delete
    if (isset($_GET['delete'])) {
        $vehicleId = intval($_GET['delete']);
        global $conn;
        // Delete all related records in referencing tables
        $conn->query("DELETE FROM fleet_vehicle_logs WHERE vehicle_id = $vehicleId");
        $conn->query("DELETE FROM driver_trips WHERE vehicle_id = $vehicleId");
        $conn->query("DELETE FROM dispatches WHERE vehicle_id = $vehicleId");
        // Get vehicle image path (if any)
        $imgResult = $conn->query("SELECT vehicle_image FROM fleet_vehicles WHERE id = $vehicleId");
        $imgRow = $imgResult ? $imgResult->fetch_assoc() : null;
        $imgPath = $imgRow && !empty($imgRow['vehicle_image']) ? __DIR__ . '/../' . $imgRow['vehicle_image'] : null;
        // Delete vehicle
        $success = deleteData('fleet_vehicles', $vehicleId);
        if ($success) {
            // Remove image file if it exists
            if ($imgPath && file_exists($imgPath)) {
                @unlink($imgPath);
            }
            log_audit_event('FVM', 'delete_vehicle', $vehicleId, $_SESSION['full_name'] ?? 'unknown');
            $_SESSION['fvm_success'] = 'Vehicle deleted successfully.';
        } else {
            $_SESSION['fvm_error'] = 'Failed to delete vehicle.';
        }
        header("Location: {$baseURL}");
        exit;
    }

    // Handle insert vehicle (with car type and image upload)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vehicle_name']) && !isset($_POST['edit_vehicle_id'])) {
        $vehicleType = isset($_POST['vehicle_type']) ? $_POST['vehicle_type'] : null;
        $vehicleImagePath = null;
        // Handle file upload if image is provided and file was actually uploaded
        if (isset($_FILES['vehicle_image']) && is_uploaded_file($_FILES['vehicle_image']['tmp_name']) && $_FILES['vehicle_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileTmp = $_FILES['vehicle_image']['tmp_name'];
            $fileName = basename($_FILES['vehicle_image']['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($fileExt, $allowed)) {
                $newFileName = 'vehicle_' . time() . '_' . rand(1000, 9999) . '.' . $fileExt;
                $destPath = $uploadDir . $newFileName;
                if (move_uploaded_file($fileTmp, $destPath)) {
                    $vehicleImagePath = 'uploads/' . $newFileName;
                }
            }
        }
        $data = [
            'vehicle_name'    => $_POST['vehicle_name'],
            'plate_number'    => $_POST['plate_number'],
            'vehicle_type'    => $vehicleType,
            'status'          => 'Active',
            'weight_capacity' => $_POST['weight_capacity'] ?? null,
            'fuel_capacity'   => $_POST['fuel_capacity'] ?? null
        ];
        if ($vehicleImagePath) {
            $data['vehicle_image'] = $vehicleImagePath;
        }
        $result = insertData('fleet_vehicles', $data);
        global $conn;
        if ($result) {
            $id = $conn->insert_id;
            log_audit_event('FVM', 'add_vehicle', $id, $_SESSION['full_name'] ?? 'unknown');
            $_SESSION['fvm_success'] = 'Vehicle added successfully!';
        } else {
            $_SESSION['fvm_error'] = 'Vehicle insert failed.';
        }
        header("Location: {$baseURL}");
        exit;
    }

    // Handle update vehicle (with car type and image upload)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_vehicle_id'])) {
        $vehicleType = isset($_POST['vehicle_type']) ? $_POST['vehicle_type'] : null;
        $vehicleImagePath = null;
        $debugMsg = '';
        // Handle file upload if image is provided
        if (isset($_FILES['vehicle_image'])) {
            $debugMsg .= 'File info: ' . print_r($_FILES['vehicle_image'], true) . ' ';
            if ($_FILES['vehicle_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/';
                if (!is_dir($uploadDir)) {
                    $debugMsg .= 'Upload dir does not exist, creating... ';
                    if (mkdir($uploadDir, 0777, true)) {
                        $debugMsg .= 'Upload dir created. ';
                    } else {
                        $debugMsg .= 'Failed to create upload dir! ';
                    }
                } else {
                    $debugMsg .= 'Upload dir exists. ';
                }
                $fileTmp = $_FILES['vehicle_image']['tmp_name'];
                $fileName = basename($_FILES['vehicle_image']['name']);
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($fileExt, $allowed)) {
                    $newFileName = 'vehicle_' . time() . '_' . rand(1000, 9999) . '.' . $fileExt;
                    $destPath = $uploadDir . $newFileName;
                    $debugMsg .= "Moving file from $fileTmp to $destPath. ";
                    if (move_uploaded_file($fileTmp, $destPath)) {
                        $vehicleImagePath = 'uploads/' . $newFileName;
                        $debugMsg .= 'Image uploaded successfully. ';
                    } else {
                        $debugMsg .= 'Failed to move uploaded file. ';
                        if (!file_exists($fileTmp)) {
                            $debugMsg .= 'Temp file does not exist. ';
                        } else {
                            $debugMsg .= 'Temp file exists. ';
                        }
                        $debugMsg .= 'Permissions: ' . substr(sprintf('%o', fileperms($uploadDir)), -4) . '. ';
                    }
                } else {
                    $debugMsg .= 'Invalid file type: ' . $fileExt . '. ';
                }
            } else if ($_FILES['vehicle_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $debugMsg .= 'File upload error: ' . $_FILES['vehicle_image']['error'] . '. ';
            } else {
                $debugMsg .= 'No file uploaded. ';
            }
        } else {
            $debugMsg .= 'No vehicle_image in \\$_FILES. ';
        }
        $data = [
            'vehicle_name'    => $_POST['vehicle_name'],
            'plate_number'    => $_POST['plate_number'],
            'vehicle_type'    => $vehicleType,
            'status'          => $_POST['status'],
            'weight_capacity' => $_POST['weight_capacity'] ?? null,
            'fuel_capacity'   => $_POST['fuel_capacity'] ?? null
        ];
        if ($vehicleImagePath) {
            $data['vehicle_image'] = $vehicleImagePath;
        }
        $result = updateData('fleet_vehicles', $_POST['edit_vehicle_id'], $data);
        if ($result === false) {
            $debugMsg .= 'Database update failed.';
            if (function_exists('mysqli_error') && isset($conn)) {
                $debugMsg .= ' SQL Error: ' . mysqli_error($conn);
            }
        } else {
            $debugMsg .= 'Database updated.';
        }
        $debugMsg .= ' Data: ' . print_r($data, true);
        log_audit_event('FVM', 'edit_vehicle', $_POST['edit_vehicle_id'], $_SESSION['full_name'] ?? 'unknown');
        $_SESSION['fvm_debug'] = $debugMsg;
        header("Location: {$baseURL}");
        exit;
    }

    // Handle log submission (maintenance/fuel)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_vehicle_id'])) {
        $vehicleId = $_POST['log_vehicle_id'];
        $logType = $_POST['log_type'];
        $details = $_POST['log_details'];

        insertData('fleet_vehicle_logs', [
            'vehicle_id' => $vehicleId,
            'log_type'   => $logType,
            'details'    => $details,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Auto-update vehicle status
        if ($logType === 'maintenance') {
            updateData('fleet_vehicles', $vehicleId, ['status' => 'Under Maintenance']);
        } else {
            // Check if there are any open maintenance logs for this vehicle
            $logs = fetchAllQuery("SELECT * FROM fleet_vehicle_logs WHERE vehicle_id = ? AND log_type = 'maintenance' ORDER BY created_at DESC", [$vehicleId]);
            if (empty($logs)) {
                updateData('fleet_vehicles', $vehicleId, ['status' => 'Active']);
            }
        }

        header("Location: {$baseURL}");
        exit;
    }
}
// Handle check status button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_status_vehicle_id'])) {
    $vehicleId = intval($_POST['check_status_vehicle_id']);
    $vehicle = fetchById('fleet_vehicles', $vehicleId);
    if ($vehicle) {
        if ($vehicle['status'] === 'Active') {
            $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
            $nextDate = $now->modify('+1 month')->format('Y-m-d 08:00:00');
            insertData('fleet_vehicle_logs', [
                'vehicle_id' => $vehicleId,
                'log_type'   => 'maintenance',
                'details'    => 'Monthly Scheduled Maintenance',
                'created_at' => $nextDate
            ]);
            updateData('fleet_vehicles', $vehicleId, ['status' => 'Active']);
            $_SESSION['fvm_success'] = 'Vehicle Maintenance Complete! Maintenance successfully rescheduled for next month.';
        } else if ($vehicle['status'] === 'Under Maintenance') {
            $_SESSION['fvm_error'] = 'Vehicle is still Under Maintenance.';
        } else {
            $_SESSION['fvm_error'] = 'Vehicle status is not eligible for completion.';
        }
    }
    header("Location: {$baseURL}");
    exit;
}
/////////////////////////////////////////END OF FVM LOGIC
