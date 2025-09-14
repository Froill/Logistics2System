<?php

/////////////////////////////////////////////START OF FVM LOGIC
function fvm_logic($baseURL)
{

    // Handle manual adjustment of next maintenance date
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_maintenance_vehicle_id']) && isset($_POST['next_maintenance_date'])) {
        $vehicleId = intval($_POST['adjust_maintenance_vehicle_id']);
        $nextDate = $_POST['next_maintenance_date'];
        // Insert a maintenance log with the selected date
        insertData('fleet_vehicle_logs', [
            'vehicle_id' => $vehicleId,
            'log_type'   => 'maintenance',
            'details'    => 'Scheduled maintenance adjusted to ' . $nextDate,
            'created_at' => $nextDate . ' 08:00:00' // Default to 8AM
        ]);
        // Optionally update vehicle status
        updateData('fleet_vehicles', $vehicleId, ['status' => 'Under Maintenance']);
        log_audit_event('FVM', 'adjust_maintenance', $vehicleId, $_SESSION['full_name'] ?? 'unknown');
        header("Location: {$baseURL}");
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



























/////////////////////////////////////////START OF VRDS LOGIC
function vrds_logic($baseURL) {

    // Clear all dispatch logs
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_dispatch_logs'])) {
        global $conn;
        // Set all vehicles and drivers that are currently dispatched back to Active/Available
        $result = $conn->query("SELECT vehicle_id, driver_id FROM dispatches");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                updateData('fleet_vehicles', $row['vehicle_id'], ['status' => 'Active']);
                updateData('drivers', $row['driver_id'], ['status' => 'Available']);
            }
        }
        $conn->query("DELETE FROM dispatches");
        log_audit_event('VRDS', 'clear_dispatch_logs', null, $_SESSION['full_name'] ?? 'unknown');
        $_SESSION['success_message'] = 'All dispatch logs cleared.';
        header("Location: {$baseURL}");
        exit;
    }

    // 1. Requester submits trip request

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_vehicle'])) {

    $requester_id = $_SESSION['user_id'] ?? 0;

        if (!$requester_id || !is_numeric($requester_id)) {

            $_SESSION['error_message'] = 'You must be logged in to request a vehicle.';

            header("Location: {$baseURL}");

            exit;

        }

        $purpose = trim($_POST['purpose'] ?? '');

        $origin = trim($_POST['origin'] ?? '');

        $destination = trim($_POST['destination'] ?? '');

        $requested_vehicle_type = trim($_POST['requested_vehicle_type'] ?? '');

        // Fix: use reservation_date and expected_return from form, not trip_date/trip_time

        $reservation_date = trim($_POST['reservation_date'] ?? ($_POST['trip_date'] ?? ''));

        $expected_return = trim($_POST['expected_return'] ?? '');

        $notes = trim($_POST['notes'] ?? '');

        global $conn;

        $sql = "INSERT INTO vehicle_requests (requester_id, request_date, reservation_date, expected_return, purpose, origin, destination, requested_vehicle_type, status, notes) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, 'Pending', ?)";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $_SESSION['error_message'] = 'Database error: ' . $conn->error;

            header("Location: {$baseURL}");

            exit;

        }

    $stmt->bind_param("isssssss", $requester_id, $reservation_date, $expected_return, $purpose, $origin, $destination, $requested_vehicle_type, $notes);

        $ok = $stmt->execute();

        if ($ok) {

            log_audit_event('VRDS', 'request_vehicle', $conn->insert_id, $_SESSION['full_name'] ?? 'unknown');

            // 2. System checks availability and recommends

            $rec = recommend_assignment($requested_vehicle_type);

            $vehicle = $rec['vehicle'];

            $driver = $rec['driver'];

            $recommendation = ($vehicle && $driver) ? "Vehicle: {$vehicle['vehicle_name']} / Driver: {$driver['driver_name']}" : 'No available match';

            // 3. Notify requester

            $user = fetchById('users', $requester_id);

            if ($vehicle && $driver) {

                $msg = "Your vehicle request has been received. Recommendation: $recommendation. Awaiting officer approval.";

                if ($user && !empty($user['email'])) sendEmail($user['email'], 'Vehicle Request Received', $msg);

                $_SESSION['success_message'] = $msg;

            } else {

                $msg = "Your vehicle request cannot be fulfilled at this time. No available vehicle/driver.";

                if ($user && !empty($user['email'])) sendEmail($user['email'], 'Vehicle Request Denied', $msg);

                $_SESSION['error_message'] = $msg;

            }

        } else {

            $_SESSION['error_message'] = 'Failed to submit request: ' . $stmt->error;

        }

        $stmt->close();

        header("Location: {$baseURL}");

        exit;

    }



    // 4. Officer approves/overrides

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_request'])) {

        $request_id = intval($_POST['request_id'] ?? 0);

        $vehicle_id = intval($_POST['vehicle_id'] ?? 0);

        $driver_id = intval($_POST['driver_id'] ?? 0);

    $officer_id = $_SESSION['user_id'] ?? 1;

        $request = fetchById('vehicle_requests', $request_id);

        if (!$request || $request['status'] !== 'Pending') {

            $_SESSION['error_message'] = "Request not found or already processed.";

            header("Location: {$baseURL}");

            exit;

        }

        // Approve and assign

        $ok1 = updateData('vehicle_requests', $request_id, ['status' => 'Approved']);

        $ok2 = updateData('fleet_vehicles', $vehicle_id, ['status' => 'Dispatched']);

        $ok3 = updateData('drivers', $driver_id, ['status' => 'Dispatched']);

        $ok4 = insertData('dispatches', [

            'request_id' => $request_id,

            'vehicle_id' => $vehicle_id,

            'driver_id' => $driver_id,

            'officer_id' => $officer_id,

            'dispatch_date' => date('Y-m-d H:i:s'),

            'status' => 'Ongoing',

            'origin' => $request['origin'],

            'destination' => $request['destination'],

            'purpose' => $request['purpose'],

            'notes' => '',

        ]);

        if ($ok4) {

            global $conn;

            $dispatch_id = $conn->insert_id;

            log_audit_event('VRDS', 'approve_dispatch', $dispatch_id, $_SESSION['full_name'] ?? 'unknown');

        }

        // 5. Notify driver

        $driver = fetchById('drivers', $driver_id);

        if ($driver && !empty($driver['email'])) {

            $msg = "You have been assigned a new trip. Purpose: {$request['purpose']}, Origin: {$request['origin']}, Destination: {$request['destination']}.";

            sendEmail($driver['email'], 'New Trip Assignment', $msg);

        }

        // 6. Notify requester

        $user = fetchById('users', $request['requester_id']);


        $vehicle = fetchById('fleet_vehicles', $vehicle_id);


        $driver = fetchById('drivers', $driver_id);

        if ($user && !empty($user['email'])) {


            $msg = "Your vehicle request has been approved and assigned. Vehicle: #$vehicle_id, Driver: #$driver_id.";


            $vehicleName = $vehicle ? $vehicle['vehicle_name'] : ("ID #$vehicle_id");


            $driverName = $driver ? $driver['driver_name'] : ("ID #$driver_id");


            $msg = "Your vehicle request has been approved and assigned. Vehicle: $vehicleName, Driver: $driverName.";

            sendEmail($user['email'], 'Vehicle Request Approved', $msg);

        }

        $_SESSION['success_message'] = "Request approved and dispatch created.";

        header("Location: {$baseURL}");

        exit;

    }



    // 7. Officer can cancel dispatch

    if (isset($_GET['delete'])) {

        $dispatch_id = (int) $_GET['delete'];

        $dispatch = fetchById('dispatches', $dispatch_id);

        if ($dispatch) {

            updateData('fleet_vehicles', $dispatch['vehicle_id'], ['status' => 'Active']);

            updateData('drivers', $dispatch['driver_id'], ['status' => 'Available']);

            updateData('vehicle_requests', $dispatch['request_id'], ['status' => 'Pending']);

        }

        deleteData('dispatches', $dispatch_id);

        log_audit_event('VRDS', 'delete_dispatch', $dispatch_id, $_SESSION['full_name'] ?? 'unknown');

        $_SESSION['success_message'] = "Dispatch cancelled.";

        header("Location: {$baseURL}");

        exit;

    }



    // 8. Officer can complete dispatch

    if (isset($_GET['complete'])) {

        $dispatch_id = (int) $_GET['complete'];

        $dispatch = fetchById('dispatches', $dispatch_id);

        if ($dispatch && $dispatch['status'] !== 'Completed') {

            updateData('dispatches', $dispatch_id, ['status' => 'Completed']);

            updateData('fleet_vehicles', $dispatch['vehicle_id'], ['status' => 'Active']);

            updateData('drivers', $dispatch['driver_id'], ['status' => 'Available']);

            log_audit_event('VRDS', 'complete_dispatch', $dispatch_id, $_SESSION['full_name'] ?? 'unknown');

            $_SESSION['success_message'] = "Dispatch marked as completed.";

        } else {

            $_SESSION['error_message'] = "Dispatch not found or already completed.";

        }

        header("Location: {$baseURL}");

        exit;

    }

}


// 9. Officer can clear all dispatch logs
  if (isset($_GET['remove_request'])) {

        $remove_id = (int)$_GET['remove_request'];

        $req = fetchById('vehicle_requests', $remove_id);

        if ($req && $req['status'] === 'Pending') {

            deleteData('vehicle_requests', $remove_id);

            $_SESSION['success_message'] = "Vehicle request removed.";

        }

        header("Location: {$baseURL}");

        exit;

    }

    // VRDS Batch delete dispatch logs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected_dispatches']) && !empty($_POST['dispatch_ids'])) {
    $ids = array_map('intval', $_POST['dispatch_ids']);
    global $conn;
    foreach ($ids as $dispatch_id) {
        $dispatch = fetchById('dispatches', $dispatch_id);
        if ($dispatch) {
            updateData('fleet_vehicles', $dispatch['vehicle_id'], ['status' => 'Active']);
            updateData('drivers', $dispatch['driver_id'], ['status' => 'Available']);
            updateData('vehicle_requests', $dispatch['request_id'], ['status' => 'Pending']);
        }
        deleteData('dispatches', $dispatch_id);
        log_audit_event('VRDS', 'delete_dispatch', $dispatch_id, $_SESSION['full_name'] ?? 'unknown');
    }
    $_SESSION['success_message'] = count($ids) . " dispatch log(s) deleted.";
    header("Location: {$baseURL}");
    exit;
}
// VRDS Logic
function recommend_assignment($vehicle_type = null)
{
    // Simple recommender: first available vehicle/driver, optionally by type
    $vehicles = fetchAll('fleet_vehicles');
    $drivers = fetchAll('drivers');
    $vehicle = null;

    foreach ($vehicles as $v) {
        if ($v['status'] === 'Active' && (!$vehicle_type || stripos($v['vehicle_type'], $vehicle_type) !== false)) {
            $vehicle = $v;
            break;
        }
    }

    $driver = null;
    foreach ($drivers as $d) {
        if ($d['status'] === 'Available') {
            $driver = $d;
            break;
        }
    }
    return ['vehicle' => $vehicle, 'driver' => $driver];
}
//////////////////////////////////////////END OF VRDS LOGIC

























////////////////////////////////START OF DRIVER TRIP LOGIC
// Driver/vehicle filter logic
// Fetch driver and vehicle lists for filters
$drivers = fetchAll('drivers');
$vehicles = fetchAll('fleet_vehicles');
$filterDriver = isset($_GET['filter_driver']) ? $_GET['filter_driver'] : '';
$filterVehicle = isset($_GET['filter_vehicle']) ? $_GET['filter_vehicle'] : '';
$where = [];
$params = [];
if ($filterDriver) {
    $where[] = 't.driver_id = ?';
    $params[] = $filterDriver;
}
if ($filterVehicle) {
    $where[] = 't.vehicle_id = ?';
    $params[] = $filterVehicle;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$sql = "SELECT t.*, d.driver_name, v.vehicle_name FROM driver_trips t JOIN drivers d ON t.driver_id = d.id JOIN fleet_vehicles v ON t.vehicle_id = v.id $whereSql ORDER BY t.created_at DESC";
$trips = fetchAllQuery($sql, $params);

// AJAX trip log modal rendering (must be before any HTML output)
if (isset($_GET['ajax_trip_log']) && isset($_GET['trip_page']) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    $page = max(1, intval($_GET['trip_page']));
    $perPage = 10;
    $totalTrips = count($trips);
    $totalPages = ceil($totalTrips / $perPage);
    $start = ($page - 1) * $perPage;
    $pagedTrips = array_slice($trips, $start, $perPage);
?>
    <div class="modal-box max-w-xl">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
        </form>
        <h3 class="font-bold text-lg mb-4">Trip Log</h3>
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <button type="button" class="btn btn-error mb-2" onclick="if(confirm('Clear all trip logs?')) { document.getElementById('clearTripLogsForm').submit(); }">Clear Log</button>
            <div class="overflow-x-auto">
                <form id="clearTripLogsForm" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                    <input type="hidden" name="clear_trip_logs" value="1">
                </form>
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Driver & Vehicle</th>
                            <th>Trip Details</th>
                            <th>Performance Metrics</th>
                            <th>Validation</th>
                            <th>Review Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagedTrips as $t): ?>
                            <tr class="hover">
                                <td>
                                    <div class="font-bold"><?= htmlspecialchars($t['driver_name']) ?></div>
                                    <div class="text-sm opacity-80"><?= htmlspecialchars($t['vehicle_name']) ?></div>
                                </td>
                                <td>
                                    <div>Date: <?= date('M d, Y', strtotime($t['trip_date'])) ?></div>
                                    <div class="text-sm">
                                        Time: <?= date('H:i', strtotime($t['start_time'])) ?> -
                                        <?= $t['end_time'] ? date('H:i', strtotime($t['end_time'])) : 'Ongoing' ?>
                                    </div>
                                    <div class="text-sm">Distance: <?= number_format($t['distance_traveled'], 1) ?> km</div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="radial-progress text-primary" style="--value:<?= $t['performance_score'] ?>; --size:2rem">
                                            <?= number_format($t['performance_score'], 0) ?>
                                        </div>
                                        <div class="flex flex-col text-sm">
                                            <span>Fuel: <?= number_format($t['fuel_consumed'], 1) ?>L</span>
                                            <span>Idle: <?= $t['idle_time'] ?? 0 ?>min</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($t['validation_status'] === 'valid'): ?>
                                        <div class="badge badge-success">Valid</div>
                                    <?php elseif ($t['validation_status'] === 'invalid'): ?>
                                        <div class="badge badge-error" title="<?= htmlspecialchars($t['validation_message']) ?>">
                                            Invalid
                                        </div>
                                    <?php else: ?>
                                        <div class="badge badge-warning">Pending</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($t['supervisor_review_status'] === 'pending'): ?>
                                        <button type="button" class="btn btn-sm btn-warning"
                                            onclick="document.getElementById('review_modal_<?= $t['id'] ?>').showModal()">
                                            Review
                                        </button>
                                    <?php elseif ($t['supervisor_review_status'] === 'approved'): ?>
                                        <div class="badge badge-success">Approved</div>
                                    <?php else: ?>
                                        <div class="badge badge-error">Rejected</div>
                                    <?php endif; ?>
                                    <!-- Review Modal -->
                                    <dialog id="review_modal_<?= $t['id'] ?>" class="modal">
                                        <div class="modal-box">
                                            <form method="dialog">
                                                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                                            </form>
                                            <h3 class="font-bold text-lg mb-4">Review Trip Record</h3>
                                            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="space-y-4">
                                                <input type="hidden" name="review_trip" value="1">
                                                <input type="hidden" name="trip_id" value="<?= $t['id'] ?>">
                                                <div class="form-control">
                                                    <label class="label">Review Status</label>
                                                    <select name="review_status" class="select select-bordered" required>
                                                        <option value="approved">Approve</option>
                                                        <option value="rejected">Reject</option>
                                                    </select>
                                                </div>
                                                <div class="form-control">
                                                    <label class="label">Remarks</label>
                                                    <textarea name="supervisor_remarks" class="textarea textarea-bordered"
                                                        placeholder="Enter your review comments..."><?= htmlspecialchars($t['supervisor_remarks'] ?? '') ?></textarea>
                                                </div>
                                                <div class="flex gap-2">
                                                    <button type="submit" class="btn btn-primary flex-1">Submit Review</button>
                                                </div>
                                            </form>
                                        </div>
                                        <form method="dialog" class="modal-backdrop">
                                            <button>close</button>
                                        </form>
                                    </dialog>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <button type="button" class="btn btn-sm btn-info"
                                            onclick="document.getElementById('details_modal_<?= $t['id'] ?>').showModal()">
                                            <i class="fas fa-info-circle mr-2"></i> View Details
                                        </button>
                                        <?php if ($_SESSION['role'] === 'admin'): ?>
                                            <a href="<?= htmlspecialchars($_SERVER['PHP_SELF'] . '&delete=' . $t['id']) ?>"
                                                class="btn btn-sm btn-error"
                                                onclick="return confirm('Are you sure you want to delete this trip record?')">
                                                <i class="fas fa-trash-alt mr-2"></i> Delete
                                            </a>
                                        <?php endif; ?>
                                        <!-- Details Modal -->
                                        <dialog id="details_modal_<?= $t['id'] ?>" class="modal">
                                            <div class="modal-box">
                                                <form method="dialog">
                                                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                                                </form>
                                                <h3 class="font-bold text-lg mb-4">Trip Details</h3>
                                                <div class="space-y-4">
                                                    <div class="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <div class="font-bold">Driver</div>
                                                            <div><?= htmlspecialchars($t['driver_name']) ?></div>
                                                        </div>
                                                        <div>
                                                            <div class="font-bold">Vehicle</div>
                                                            <div><?= htmlspecialchars($t['vehicle_name']) ?></div>
                                                        </div>
                                                        <div>
                                                            <div class="font-bold">Trip Date</div>
                                                            <div><?= date('M d, Y', strtotime($t['trip_date'])) ?></div>
                                                        </div>
                                                        <div>
                                                            <div class="font-bold">Duration</div>
                                                            <div>
                                                                <?= date('H:i', strtotime($t['start_time'])) ?> -
                                                                <?= $t['end_time'] ? date('H:i', strtotime($t['end_time'])) : 'Ongoing' ?>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="font-bold">Distance</div>
                                                            <div><?= number_format($t['distance_traveled'], 1) ?> km</div>
                                                        </div>
                                                        <div>
                                                            <div class="font-bold">Fuel Consumed</div>
                                                            <div><?= number_format($t['fuel_consumed'], 1) ?> L</div>
                                                        </div>
                                                        <div>
                                                            <div class="font-bold">Average Speed</div>
                                                            <div><?= number_format($t['average_speed'], 1) ?> km/h</div>
                                                        </div>
                                                        <div>
                                                            <div class="font-bold">Idle Time</div>
                                                            <div><?= $t['idle_time'] ?? 0 ?> minutes</div>
                                                        </div>
                                                    </div>
                                                    <div class="divider"></div>
                                                    <div>
                                                        <div class="font-bold">Performance Score</div>
                                                        <div class="flex items-center gap-4">
                                                            <div class="radial-progress text-primary" style="--value:<?= $t['performance_score'] ?>; --size:4rem">
                                                                <?= number_format($t['performance_score'], 0) ?>
                                                            </div>
                                                            <div>
                                                                <?php
                                                                if ($t['performance_score'] >= 90) echo "Excellent";
                                                                elseif ($t['performance_score'] >= 80) echo "Good";
                                                                elseif ($t['performance_score'] >= 70) echo "Average";
                                                                else echo "Needs Improvement";
                                                                ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php if ($t['supervisor_remarks']): ?>
                                                        <div>
                                                            <div class="font-bold">Supervisor Remarks</div>
                                                            <div class="text-sm"><?= nl2br(htmlspecialchars($t['supervisor_remarks'])) ?></div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <form method="dialog" class="modal-backdrop">
                                                <button>close</button>
                                            </form>
                                        </dialog>
                                    </div>
                                </td>
            </div>
            </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
    </div>
    </form>
    <!-- Pagination Controls -->
    <div class="flex justify-center mt-4 gap-2">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <button type="button" class="btn btn-xs <?= $p == $page ? 'btn-primary' : 'btn-outline' ?>" onclick="openTripLogPage(<?= $p ?>)">Page <?= $p ?></button>
        <?php endfor; ?>
    </div>
    </div>
<?php
    exit;
} {
    $errors = [];

    // Check for required fields
    $required_fields = ['driver_id', 'vehicle_id', 'trip_date', 'start_time', 'distance_traveled', 'fuel_consumed'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[] = "The {$field} field is required.";
        }
    }

    // Validate numeric values
    if (isset($data['distance_traveled']) && $data['distance_traveled'] <= 0) {
        $errors[] = "Distance traveled must be greater than 0.";
    }
    if (isset($data['fuel_consumed']) && $data['fuel_consumed'] <= 0) {
        $errors[] = "Fuel consumed must be greater than 0.";
    }
    if (isset($data['idle_time']) && $data['idle_time'] < 0) {
        $errors[] = "Idle time cannot be negative.";
    }

    // Validate dates
    if (isset($data['trip_date']) && strtotime($data['trip_date']) > time()) {
        $errors[] = "Trip date cannot be in the future.";
    }
    if (isset($data['start_time']) && strtotime($data['start_time']) > time()) {
        $errors[] = "Start time cannot be in the future.";
    }
    if (isset($data['end_time'])) {
        if (strtotime($data['end_time']) > time()) {
            $errors[] = "End time cannot be in the future.";
        }
        if (strtotime($data['end_time']) < strtotime($data['start_time'])) {
            $errors[] = "End time cannot be before start time.";
        }
    }
    return $errors;
}

function calculatePerformanceScore($tripData)
{
    // Calculate based on various metrics
    $score = 100; // Start with perfect score

    // Fuel efficiency (km/l)
    $fuelEfficiency = $tripData['distance_traveled'] / $tripData['fuel_consumed'];
    $expectedEfficiency = 10; // Example: 10 km/l is the baseline
    $score -= max(0, ($expectedEfficiency - $fuelEfficiency) * 5);

    // Idle time penalty
    if ($tripData['idle_time'] > 30) { // More than 30 minutes idle
        $score -= min(20, ($tripData['idle_time'] - 30) / 10);
    }

    // Speed compliance
    if ($tripData['average_speed'] > 80) { // Example: Speed limit is 80 km/h
        $score -= min(20, ($tripData['average_speed'] - 80) * 2);
    }

    return max(0, min(100, $score));
}

function driver_trip_logic($baseURL)
{ {
        if (isset($_GET['delete'])) {
            deleteData('driver_trips', $_GET['delete']);
            log_audit_event('DTP', 'delete_trip', $_GET['delete'], $_SESSION['full_name'] ?? 'unknown');
            header("Location: {$baseURL}");
            exit;
        }

        // Clear all trip logs
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_trip_logs'])) {
            global $conn;
            $conn->query("DELETE FROM driver_trips");
            log_audit_event('DTP', 'clear_trip_logs', null, $_SESSION['full_name'] ?? 'unknown');
            $_SESSION['success_message'] = 'All trip logs cleared.';
            header("Location: {$baseURL}");
            exit;
        }
        // Step 1 & 2: Driver Submits Data & System Validation
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_trip'])) {
            try {
                // Debug information
                error_log('Received POST data: ' . print_r($_POST, true));

                $tripData = [
                    'driver_id' => intval($_POST['driver_id']),
                    'vehicle_id' => intval($_POST['vehicle_id']),
                    'trip_date' => $_POST['trip_date'],
                    'start_time' => $_POST['start_time'],
                    'end_time' => !empty($_POST['end_time']) ? $_POST['end_time'] : null,
                    'distance_traveled' => floatval($_POST['distance_traveled']),
                    'fuel_consumed' => floatval($_POST['fuel_consumed']),
                    'idle_time' => !empty($_POST['idle_time']) ? intval($_POST['idle_time']) : 0,
                    'cargo_weight' => isset($_POST['cargo_weight']) ? floatval($_POST['cargo_weight']) : 0,
                    'vehicle_capacity' => isset($_POST['vehicle_capacity']) ? floatval($_POST['vehicle_capacity']) : 0
                ];

                // Calculate average speed only if we have both times
                if (!empty($_POST['end_time'])) {
                    $duration = abs(strtotime($_POST['end_time']) - strtotime($_POST['start_time']));
                    if ($duration > 0) {
                        $tripData['average_speed'] = ($tripData['distance_traveled'] / $duration) * 3600;
                    }
                }

                // Debug information
                error_log('Processed trip data: ' . print_r($tripData, true));

                // Validate the data
                $validationErrors = validateTripData($tripData);

                if (empty($validationErrors)) {
                    // Step 4: Data Storage and Processing
                    $tripData['validation_status'] = 'valid';
                    $tripData['performance_score'] = calculatePerformanceScore($tripData);

                    $result = insertData('driver_trips', $tripData);
                    if ($result) {
                        global $conn;
                        $id = $conn->insert_id;
                        log_audit_event('DTP', 'add_trip', $id, $_SESSION['full_name'] ?? 'unknown');
                        $_SESSION['success_message'] = "Trip data submitted successfully.";
                        error_log('Trip data inserted successfully');
                    } else {
                        $_SESSION['error_message'] = "Failed to save trip data. Please try again.";
                        error_log('Failed to insert trip data');
                    }
                } else {
                    // Step 3: Invalid Data Handling
                    $tripData['validation_status'] = 'invalid';
                    $tripData['validation_message'] = implode(", ", $validationErrors);
                    $_SESSION['error_message'] = "Please correct the following errors: " . implode(", ", $validationErrors);
                    error_log('Validation errors: ' . implode(", ", $validationErrors));
                }
            } catch (Exception $e) {
                error_log('Error processing trip data: ' . $e->getMessage());
                $_SESSION['error_message'] = "An error occurred while processing your request. Please try again.";
            }

            header("Location: {$baseURL}");
            exit;
        }

        // Step 5 & 6: Management Review
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_trip'])) {
            $tripId = $_POST['trip_id'];
            $reviewStatus = $_POST['review_status'];
            $remarks = $_POST['supervisor_remarks'];

            updateData('driver_trips', $tripId, [
                'supervisor_review_status' => $reviewStatus,
                'supervisor_remarks' => $remarks
            ]);

            $_SESSION['success_message'] = "Trip review updated successfully.";
            header("Location: {$baseURL}");
            exit;
        }
    }
}
////////////////////////////////END OF DRIVER TRIP LOGIC

















//////////////////////////////////////////START OF TCAO LOGIC

function tcao_logic($baseURL)
{

    $user = $_SESSION['full_name'] ?? 'unknown';
    global $conn;

    // Handle delete (admin only)
    if (isset($_GET['delete'])) {
        deleteData('transport_costs', $_GET['delete']);
        log_audit_event('TCAO', 'deleted', $_GET['delete'], $user);
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
            log_audit_event('TCAO', 'supervisor_approved', $id, $user);
        } elseif ($role === 'accountant') {
            $conn->query("UPDATE transport_costs SET status='finalized' WHERE id=$id");
            log_audit_event('TCAO', 'finalized', $id, $user);
        }
        header("Location: {$baseURL}");
        exit;
    }
    if (isset($_GET['return']) && isset($_GET['role'])) {
        $id = intval($_GET['return']);
        $role = $_GET['role'];
        $conn->query("UPDATE transport_costs SET status='returned' WHERE id=$id");
        log_audit_event('TCAO', 'returned_by_' . $role, $id, $user);
        header("Location: {$baseURL}");
        exit;
    }

    // Handle driver submission (with receipt upload)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trip_id'])) {
        $fuel  = floatval($_POST['fuel_cost'] ?: 0);
        $toll  = floatval($_POST['toll_fees'] ?: 0);
        $other = floatval($_POST['other_expenses'] ?: 0);
        $total = $fuel + $toll + $other;

        // Handle receipt upload
        $receipt_path = null;
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
            $target = __DIR__ . '/../uploads/receipts_' . uniqid() . '.' . $ext;
            if (!is_dir(__DIR__ . '/../uploads')) mkdir(__DIR__ . '/../uploads');
            move_uploaded_file($_FILES['receipt']['tmp_name'], $target);
            $receipt_path = basename($target);
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

        // Insert
        $stmt = $conn->prepare("INSERT INTO transport_costs (trip_id, fuel_cost, toll_fees, other_expenses, total_cost, receipt, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, 'submitted', ?, NOW())");
        $stmt->bind_param('sdddsss', $_POST['trip_id'], $fuel, $toll, $other, $total, $receipt_path, $user);
        $stmt->execute();
        $cost_id = $stmt->insert_id;
        log_audit_event('TCAO', 'submitted', $cost_id, $user);
        $_SESSION['tcao_success'] = 'Cost entry submitted.';
        header("Location: {$baseURL}");
        exit;
    }
}
//////////////////////////////////////////END OF TCAO LOGIC