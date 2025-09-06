<?php
// VEHICLE RESERVATION AND DISPATCH SYSTEM (VRDS)
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/audit_log.php';

function recommend_assignment($vehicle_type = null)
{
    // Simple recommender: first available vehicle/driver, optionally by type
    $vehicles = fetchAll('fleet_vehicles');
    $drivers = fetchAll('drivers');
    $vehicle = null;
    foreach ($vehicles as $v) {
        if ($v['status'] === 'Active' && (!$vehicle_type || stripos($v['vehicle_name'], $vehicle_type) !== false)) {
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

function vrds_logic($baseURL)
{
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
            log_audit_event('VRDS', 'request_vehicle', $conn->insert_id, $_SESSION['username'] ?? 'unknown');
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
            log_audit_event('VRDS', 'approve_dispatch', $dispatch_id, $_SESSION['username'] ?? 'unknown');
        }
        // 5. Notify driver
        $driver = fetchById('drivers', $driver_id);
        if ($driver && !empty($driver['email'])) {
            $msg = "You have been assigned a new trip. Purpose: {$request['purpose']}, Origin: {$request['origin']}, Destination: {$request['destination']}.";
            sendEmail($driver['email'], 'New Trip Assignment', $msg);
        }
        // 6. Notify requester
        $user = fetchById('users', $request['requester_id']);
        if ($user && !empty($user['email'])) {
            $msg = "Your vehicle request has been approved and assigned. Vehicle: #$vehicle_id, Driver: #$driver_id.";
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
        log_audit_event('VRDS', 'delete_dispatch', $dispatch_id, $_SESSION['username'] ?? 'unknown');
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
            log_audit_event('VRDS', 'complete_dispatch', $dispatch_id, $_SESSION['username'] ?? 'unknown');
            $_SESSION['success_message'] = "Dispatch marked as completed.";
        } else {
            $_SESSION['error_message'] = "Dispatch not found or already completed.";
        }
        header("Location: {$baseURL}");
        exit;
    }
}

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

function vrds_view($baseURL)
{
    vrds_logic($baseURL);
    $requests = fetchAll('vehicle_requests');
    $dispatches = fetchAll('dispatches');
    $vehicles = fetchAll('fleet_vehicles');
    $drivers = fetchAll('drivers');
    // Get unique vehicle types for dropdown
    $vehicle_types = array_unique(array_filter(array_map(function ($v) {
        return $v['vehicle_type'];
    }, $vehicles)));
?>
    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert-error" style="color: #fff; background: #e3342f; padding: 10px; margin-bottom: 10px; border-radius: 4px;">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success" style="color: #155724; background: #d4edda; padding: 10px; margin-bottom: 10px; border-radius: 4px;">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <div>
        <h2 class="text-2xl font-bold mb-4">Vehicle Reservation & Dispatch</h2>
        <!-- Vehicle Request Form (Step 1) -->
        <button class="btn btn-primary mb-3" onclick="request_modal.showModal()">Request Vehicle</button>
        <dialog id="request_modal" class="modal">
            <div class="modal-box">
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="mb-6">
                    <input type="hidden" name="request_vehicle" value="1">
                    <div class="form-control mb-2">
                        <label class="label">Purpose</label>
                        <input type="text" name="purpose" class="input input-bordered" required>
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Origin</label>
                        <input type="text" name="origin" class="input input-bordered" required>
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Destination</label>
                        <input type="text" name="destination" class="input input-bordered" required>
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Requested Vehicle Type</label>
                        <select name="requested_vehicle_type" class="select select-bordered" required>
                            <option value="">Select vehicle type</option>
                            <?php foreach ($vehicle_types as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Reservation Date</label>
                        <input type="date" name="reservation_date" class="input input-bordered" required>
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Expected Return Date</label>
                        <input type="date" name="expected_return" class="input input-bordered" required>
                    </div>
                    <button class="btn btn-primary btn-outline mt-2 w-full">Submit Request</button>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>

        <!-- Pending Requests Table (For Transport Officer Approval) -->
        <h3 class="text-xl font-bold mt-6 mb-2">Pending Vehicle Requests</h3>
        <div class="overflow-x-auto mb-6">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Purpose</th>
                        <th>Origin</th>
                        <th>Destination</th>
                        <th>Requested Vehicle Type</th>
                        <th>Reservation Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Recommendation</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <?php if ($req['status'] === 'Pending'): ?>
                            <?php $rec = recommend_assignment($req['requested_vehicle_type']); ?>
                            <tr>
                                <td><?= htmlspecialchars($req['purpose']) ?></td>
                                <td><?= htmlspecialchars($req['origin']) ?></td>
                                <td><?= htmlspecialchars($req['destination']) ?></td>
                                <td><?= htmlspecialchars($req['requested_vehicle_type']) ?></td>
                                <td><?= htmlspecialchars($req['reservation_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($req['expected_return'] ?? '') ?></td>
                                <td><?= htmlspecialchars($req['status']) ?></td>
                                <td>
                                    <?php if ($rec['vehicle'] && $rec['driver']): ?>
                                        <?= htmlspecialchars($rec['vehicle']['vehicle_name']) ?> / <?= htmlspecialchars($rec['driver']['driver_name']) ?>
                                    <?php else: ?>
                                        <span class="text-error">No available match</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="flex gap-4">
                                        <button class="btn btn-primary btn-sm" onclick="assign_modal_<?= $req['id'] ?>.showModal()">Assign</button>
                                        <a href="<?= htmlspecialchars($baseURL . '&remove_request=' . $req['id']) ?>" class="btn btn-error btn-sm" style="margin-left: 0;" onclick="return confirm('Remove this vehicle request?')">Remove</a>
                                    </div>
                                    <dialog id="assign_modal_<?= $req['id'] ?>" class="modal">
                                        <div class="modal-box">
                                            <form method="dialog">
                                                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                                            </form>
                                            <h3 class="font-bold text-lg mb-4">Assign Vehicle & Driver</h3>
                                            <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="flex flex-col gap-4">
                                                <input type="hidden" name="approve_request" value="1">
                                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                <div class="form-control">
                                                    <label class="label">Vehicle:</label>
                                                    <select name="vehicle_id" class="select select-bordered w-full" required>
                                                        <option value="">Select a vehicle</option>
                                                        <?php foreach ($vehicles as $veh): ?>
                                                            <?php if ($veh['status'] === 'Active'): ?>
                                                                <option value="<?= $veh['id'] ?>"><?= htmlspecialchars($veh['vehicle_name']) ?></option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="form-control">
                                                    <label class="label">Driver:</label>
                                                    <select name="driver_id" class="select select-bordered w-full" required>
                                                        <option value="">Select a driver</option>
                                                        <?php foreach ($drivers as $drv): ?>
                                                            <?php if ($drv['status'] === 'Available'): ?>
                                                                <option value="<?= $drv['id'] ?>"><?= htmlspecialchars($drv['driver_name']) ?></option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn-success mt-4">Approve & Dispatch</button>
                                            </form>
                                        </div>
                                        <form method="dialog" class="modal-backdrop">
                                            <button>close</button>
                                        </form>
                                    </dialog>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Active & Past Dispatches Table -->
        <h3 class="text-xl font-bold mt-6 mb-2">Dispatch Log</h3>
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Driver</th>
                        <th>Dispatch Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dispatches as $d): ?>
                        <tr>
                            <td>
                                <?php
                                $vehName = '';
                                foreach ($vehicles as $veh) {
                                    if ($veh['id'] == $d['vehicle_id']) {
                                        $vehName = $veh['vehicle_name'];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($vehName);
                                ?>
                            </td>
                            <td>
                                <?php
                                $drvName = '';
                                foreach ($drivers as $drv) {
                                    if ($drv['id'] == $d['driver_id']) {
                                        $drvName = $drv['driver_name'];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($drvName);
                                ?>
                            </td>
                            <td><?= htmlspecialchars($d['dispatch_date']) ?></td>
                            <td><?= htmlspecialchars($d['status']) ?></td>
                            <td>
                                <?php if ($d['status'] === 'Ongoing'): ?>
                                    <a href="<?= htmlspecialchars($baseURL . '&complete=' . $d['id']) ?>" class="btn btn-sm btn-success" onclick="return confirm('Mark this dispatch as completed?')">
                                        <i data-lucide="check-circle" class="inline w-4 h-4"></i> Complete
                                    </a>
                                    <a href="<?= htmlspecialchars($baseURL . '&delete=' . $d['id']) ?>"
                                        class="btn btn-sm btn-error"
                                        onclick="return confirm('Cancel this dispatch? Vehicle & driver will be freed.')">Cancel</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
}
