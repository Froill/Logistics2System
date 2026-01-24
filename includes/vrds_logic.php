<?php
/////////////////////////////////////////START OF VRDS LOGIC
function vrds_logic($baseURL)
{

    $role = $_SESSION['role'] ?? '';
    $driverRecordId = null;
    if ($role === 'driver') {
        $currentUserEid = $_SESSION['eid'] ?? null;
        if ($currentUserEid && isset($GLOBALS['conn']) && ($stmt = $GLOBALS['conn']->prepare('SELECT id FROM drivers WHERE eid = ? LIMIT 1'))) {
            $stmt->bind_param('s', $currentUserEid);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($r = $res->fetch_assoc()) {
                $driverRecordId = (int)$r['id'];
            }
            $stmt->close();
        }
    }

    $hasDispatchConflict = function (int $vehicleId, int $driverId, string $startDate, string $endDate, int $requestId = 0): bool {
        $startDate = trim($startDate);
        $endDate = trim($endDate);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            return true;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $endDate = $startDate;
        }

        $statusWhere = "d.status NOT IN ('Completed','Cancelled')";
        $rangeWhere = "r.reservation_date <= ? AND COALESCE(r.expected_return, r.reservation_date) >= ?";
        $excludeWhere = $requestId > 0 ? " AND r.id <> ?" : '';

        if ($vehicleId > 0) {
            $row = fetchOneQuery(
                "SELECT COUNT(*) AS cnt\n                 FROM dispatches d\n                 JOIN vehicle_requests r ON r.id = d.request_id\n                 WHERE {$statusWhere}\n                   AND d.vehicle_id = ?\n                   AND {$rangeWhere}{$excludeWhere}",
                $requestId > 0
                    ? [(string)$vehicleId, $endDate, $startDate, (string)$requestId]
                    : [(string)$vehicleId, $endDate, $startDate]
            );
            if ((int)($row['cnt'] ?? 0) > 0) {
                return true;
            }
        }

        if ($driverId > 0) {
            $row = fetchOneQuery(
                "SELECT COUNT(*) AS cnt\n                 FROM dispatches d\n                 JOIN vehicle_requests r ON r.id = d.request_id\n                 WHERE {$statusWhere}\n                   AND d.driver_id = ?\n                   AND {$rangeWhere}{$excludeWhere}",
                $requestId > 0
                    ? [(string)$driverId, $endDate, $startDate, (string)$requestId]
                    : [(string)$driverId, $endDate, $startDate]
            );
            if ((int)($row['cnt'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    };

    // Clear all dispatch logs
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_dispatch_logs'])) {
        if ($role === 'driver') {
            $_SESSION['error_message'] = 'Access denied.';
            header("Location: {$baseURL}");
            exit;
        }
        global $conn;
        // Set all vehicles and drivers that are currently dispatched back to Active/Available
        $result = $conn->query("SELECT vehicle_id, driver_id FROM dispatches");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                updateData('fleet_vehicles', $row['vehicle_id'], ['status' => 'Active']);
                updateData('drivers', $row['driver_id'], ['status' => 'Available']);
            }
        }
        $conn->query("UPDATE dispatches SET status = 'Cancelled' WHERE status <> 'Completed'");
        log_audit_event('VRDS', 'cancel_all_dispatches', null, $_SESSION['full_name'] ?? 'unknown', 'All ongoing dispatches were cancelled');
        $_SESSION['success_message'] = 'All ongoing dispatches were cancelled.';
        header("Location: {$baseURL}");
        exit;
    }

    // 1. Requester submits trip request

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_vehicle'])) {

        if ($role === 'driver') {
            $_SESSION['error_message'] = 'Access denied. Drivers cannot request vehicles.';
            header("Location: {$baseURL}");
            exit;
        }

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

        $origin_lat = floatval($_POST['origin_lat'] ?? 0);

        $origin_lon = floatval($_POST['origin_lon'] ?? 0);

        $destination_lat = floatval($_POST['destination_lat'] ?? 0);

        $destination_lon = floatval($_POST['destination_lon'] ?? 0);

        $notes = trim($_POST['notes'] ?? '');

        global $conn;

        $sql = "INSERT INTO vehicle_requests (requester_id, request_date, reservation_date, expected_return, purpose, origin, destination, origin_lat, origin_lon, destination_lat, destination_lon, requested_vehicle_type, status, notes) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $_SESSION['error_message'] = 'Database error: ' . $conn->error;

            header("Location: {$baseURL}");

            exit;
        }

        $stmt->bind_param("isssssddddss", $requester_id, $reservation_date, $expected_return, $purpose, $origin, $destination, $origin_lat, $origin_lon, $destination_lat, $destination_lon, $requested_vehicle_type, $notes);

        $ok = $stmt->execute();

        if ($ok) {

            log_audit_event('VRDS', 'request_vehicle', $conn->insert_id, $_SESSION['full_name'] ?? 'unknown', 'New vehicle request submitted');

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

        if ($role === 'driver') {
            $_SESSION['error_message'] = 'Access denied.';
            header("Location: {$baseURL}");
            exit;
        }

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

        if ($vehicle_id <= 0 || $driver_id <= 0) {
            $_SESSION['error_message'] = 'Vehicle and driver are required.';
            header("Location: {$baseURL}");
            exit;
        }

        $vehicle = fetchById('fleet_vehicles', $vehicle_id);
        $driver = fetchById('drivers', $driver_id);
        if (!$vehicle || !$driver) {
            $_SESSION['error_message'] = 'Invalid vehicle or driver.';
            header("Location: {$baseURL}");
            exit;
        }
        if (function_exists('db_column_exists') && db_column_exists('fleet_vehicles', 'is_archived') && !empty($vehicle['is_archived'])) {
            $_SESSION['error_message'] = 'Selected vehicle is archived.';
            header("Location: {$baseURL}");
            exit;
        }
        if (($vehicle['status'] ?? '') !== 'Active') {
            $_SESSION['error_message'] = 'Selected vehicle is not available.';
            header("Location: {$baseURL}");
            exit;
        }
        if (($driver['status'] ?? '') !== 'Available') {
            $_SESSION['error_message'] = 'Selected driver is not available.';
            header("Location: {$baseURL}");
            exit;
        }

        $startDate = (string)($request['reservation_date'] ?? '');
        $endDate = (string)($request['expected_return'] ?? '');
        if ($endDate === '') {
            $endDate = $startDate;
        }
        if ($hasDispatchConflict($vehicle_id, $driver_id, $startDate, $endDate, $request_id)) {
            $_SESSION['error_message'] = 'Conflict detected: selected vehicle or driver is already assigned for the requested date range.';
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
            'origin_lat' => $request['origin_lat'],
            'origin_lon' => $request['origin_lon'],
            'destination_lat' => $request['destination_lat'],
            'destination_lon' => $request['destination_lon'],
            'notes' => '',
        ]);

        if ($ok4) {

            global $conn;

            $dispatch_id = $conn->insert_id;

            log_audit_event('VRDS', 'approve_dispatch', $dispatch_id, $_SESSION['full_name'] ?? 'unknown', 'Vehicle request approved and dispatch created');
        }

        // 5. Notify driver

        if ($driver && !empty($driver['email'])) {

            $msg = "You have been assigned a new trip. Purpose: {$request['purpose']}, Origin: {$request['origin']}, Destination: {$request['destination']}.";

            sendEmail($driver['email'], 'New Trip Assignment', $msg);
        }

        // 6. Notify requester

        $user = fetchById('users', $request['requester_id']);


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

        if ($role === 'driver') {
            $_SESSION['error_message'] = 'Access denied.';
            header("Location: {$baseURL}");
            exit;
        }

        $dispatch_id = (int) $_GET['delete'];

        $dispatch = fetchById('dispatches', $dispatch_id);

        if ($dispatch) {

            updateData('fleet_vehicles', $dispatch['vehicle_id'], ['status' => 'Active']);

            updateData('drivers', $dispatch['driver_id'], ['status' => 'Available']);

            updateData('vehicle_requests', $dispatch['request_id'], ['status' => 'Pending']);
        }

        if ($dispatch && ($dispatch['status'] ?? '') !== 'Completed') {
            updateData('dispatches', $dispatch_id, ['status' => 'Cancelled']);
        }

        log_audit_event('VRDS', 'cancel_dispatch', $dispatch_id, $_SESSION['full_name'] ?? 'unknown', 'Dispatch cancelled');

        $_SESSION['success_message'] = "Dispatch cancelled.";

        header("Location: {$baseURL}");

        exit;
    }



    // 8. Officer can complete dispatch

    if (isset($_GET['complete'])) {

        $dispatch_id = (int) $_GET['complete'];

        $dispatch = fetchById('dispatches', $dispatch_id);

        if ($role === 'driver') {
            if (!$dispatch || !$driverRecordId || (int)$dispatch['driver_id'] !== (int)$driverRecordId) {
                $_SESSION['error_message'] = 'Access denied.';
                header("Location: {$baseURL}");
                exit;
            }
        }

        if ($dispatch && ($dispatch['status'] ?? '') === 'Ongoing') {

            updateData('dispatches', $dispatch_id, ['status' => 'Completed']);

            updateData('fleet_vehicles', $dispatch['vehicle_id'], ['status' => 'Active']);

            updateData('drivers', $dispatch['driver_id'], ['status' => 'Available']);

            log_audit_event('VRDS', 'complete_dispatch', $dispatch_id, $_SESSION['full_name'] ?? 'unknown', 'Dispatch completed');

            $_SESSION['success_message'] = "Dispatch marked as completed.";
        } else {

            $_SESSION['error_message'] = "Dispatch not found or not ongoing.";
        }

        header("Location: {$baseURL}");

        exit;
    }
}


// 9. Officer can clear all dispatch logs
if (isset($_GET['remove_request'])) {
    if ($role === 'driver') {
        $_SESSION['error_message'] = 'Access denied.';
        header("Location: {$baseURL}");
        exit;
    }
    $remove_id = (int)$_GET['remove_request'];
    $req = fetchById('vehicle_requests', $remove_id);
    // Only allow delete if status is Pending or Approved
    if ($req && ($req['status'] === 'Pending' || $req['status'] === 'Approved')) {
        // If approved, also delete any associated dispatches
        if ($req['status'] === 'Approved') {
            $dispatches = fetchAll('dispatches');
            foreach ($dispatches as $dispatch) {
                if ($dispatch['request_id'] == $remove_id) {
                    deleteData('dispatches', $dispatch['id']);
                }
            }
        }
        deleteData('vehicle_requests', $remove_id);
        $_SESSION['success_message'] = "Vehicle request removed.";
    }
    header("Location: {$baseURL}");
    exit;
}

// VRDS Batch delete dispatch logs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected_dispatches']) && !empty($_POST['dispatch_ids'])) {
    if (($_SESSION['role'] ?? '') === 'driver') {
        $_SESSION['error_message'] = 'Access denied.';
        header("Location: {$baseURL}");
        exit;
    }
    $ids = array_map('intval', $_POST['dispatch_ids']);
    global $conn;
    foreach ($ids as $dispatch_id) {
        $dispatch = fetchById('dispatches', $dispatch_id);
        if ($dispatch) {
            updateData('fleet_vehicles', $dispatch['vehicle_id'], ['status' => 'Active']);
            updateData('drivers', $dispatch['driver_id'], ['status' => 'Available']);
            updateData('vehicle_requests', $dispatch['request_id'], ['status' => 'Pending']);
        }
        if ($dispatch && ($dispatch['status'] ?? '') !== 'Completed') {
            updateData('dispatches', $dispatch_id, ['status' => 'Cancelled']);
        }
        log_audit_event('VRDS', 'cancel_dispatch', $dispatch_id, $_SESSION['full_name'] ?? 'unknown', 'Dispatch cancelled');
    }
    $_SESSION['success_message'] = count($ids) . " dispatch(es) cancelled.";
    header("Location: {$baseURL}");
    exit;
}
// VRDS Logic
function recommend_assignment($vehicle_type = null, $vehicles = null, $drivers = null)
{
    // Simple recommender: first available vehicle/driver, optionally by type
    if ($vehicles === null) {
        $vehicles = fetchAll('fleet_vehicles');
    }
    if ($drivers === null) {
        $drivers = fetchAll('drivers');
    }
    $vehicle = null;

    $hasArchivedCol = function_exists('db_column_exists') && db_column_exists('fleet_vehicles', 'is_archived');
    foreach ($vehicles as $v) {
        if ($hasArchivedCol && !empty($v['is_archived'])) {
            continue;
        }
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
