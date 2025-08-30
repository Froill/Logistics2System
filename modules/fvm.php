<?php
function fvm_logic($baseURL)
{
    // Handle delete
    if (isset($_GET['delete'])) {
        deleteData('fleet_vehicles', $_GET['delete']);
        header("Location: {$baseURL}");
        exit;
    }

    // Handle insert vehicle
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vehicle_name']) && !isset($_POST['edit_vehicle_id'])) {
        insertData('fleet_vehicles', [
            'vehicle_name' => $_POST['vehicle_name'],
            'plate_number' => $_POST['plate_number'],
            'status'       => 'Available'
        ]);
        header("Location: {$baseURL}");
        exit;
    }

    // Handle update vehicle
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_vehicle_id'])) {
        updateData('fleet_vehicles', $_POST['edit_vehicle_id'], [
            'vehicle_name' => $_POST['vehicle_name'],
            'plate_number' => $_POST['plate_number'],
            'status'       => $_POST['status']
        ]);
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
            updateData('fleet_vehicles', $vehicleId, ['status' => 'Unavailable']);
        } else {
            // Check if there are any open maintenance logs for this vehicle
            $logs = fetchAllQuery("SELECT * FROM fleet_vehicle_logs WHERE vehicle_id = ? AND log_type = 'maintenance' ORDER BY created_at DESC", [$vehicleId]);
            if (empty($logs)) {
                updateData('fleet_vehicles', $vehicleId, ['status' => 'Available']);
            }
        }

        header("Location: {$baseURL}");
        exit;
    }
}

function fvm_view($baseURL)
{
    $vehicles = fetchAll('fleet_vehicles');
    // Fetch all logs for each vehicle
    $vehicle_logs = fetchAllQuery("SELECT l.*, v.vehicle_name FROM fleet_vehicle_logs l JOIN fleet_vehicles v ON l.vehicle_id = v.id ORDER BY l.created_at DESC");
?>
    <div>
        <h2 class="text-2xl font-bold mb-4">Fleet & Vehicle Management</h2>

        <div class="flex gap-2 mb-3">
            <!-- Add Vehicle Button -->
            <button class="btn btn-soft btn-primary" onclick="fvm_modal.showModal()">Add Vehicle</button>
            <!-- View Maintenance Logs Button -->
            <button class="btn btn-soft btn-info" onclick="maintenance_logs_modal.showModal()">View Maintenance Logs</button>
        </div>

        <!-- View Maintenance Logs Modal -->
        <dialog id="maintenance_logs_modal" class="modal">
            <div class="modal-box w-11/12 max-w-5xl">
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg mb-4">Vehicle Maintenance Logs</h3>
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Type</th>
                                <th>Details</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicle_logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['vehicle_name']) ?></td>
                                    <td>
                                        <span class="badge <?= $log['log_type'] === 'maintenance' ? 'badge-warning' : 'badge-info' ?>">
                                            <?= ucfirst(htmlspecialchars($log['log_type'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($log['details']) ?></td>
                                    <td><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>

        <!-- Add Vehicle Modal -->
        <dialog id="fvm_modal" class="modal">
            <div class="modal-box">
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="mb-6 flex flex-col">
                    <div class="form-control mb-2">
                        <label class="label">Vehicle Name</label>
                        <input type="text" name="vehicle_name" class="input input-bordered" required>
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Plate Number</label>
                        <input type="text" name="plate_number" class="input input-bordered" required>
                    </div>
                    <button class="btn btn-primary mt-2 btn-outline w-full">Add Vehicle</button>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>

        <!-- Vehicle Table -->
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Vehicle Name</th>
                        <th>Plate Number</th>
                        <th>Status</th>
                        <th>Actions</th>
                        <th>Logs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $v): ?>
                        <tr>
                            <td><?= htmlspecialchars($v['vehicle_name']) ?></td>
                            <td><?= htmlspecialchars($v['plate_number']) ?></td>
                            <td>
                                <?php if ($v['status'] === 'Inactive' or $v['status']==='Under Maintenance'): ?>
                                    <span class="badge badge-error">Unavailable</span>
                                <?php else :  ?>
                                    <span class="badge badge-success">Available</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="document.getElementById('manage_modal_<?= $v['id'] ?>').showModal()">
                                    Edit
                                </button>

                                <!-- Manage Vehicle Modal -->
                                <dialog id="manage_modal_<?= $v['id'] ?>" class="modal">
                                    <div class="modal-box">
                                        <form method="dialog">
                                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                                        </form>
                                        <h3 class="font-bold text-lg mb-4">Vehicle Details</h3>
                                        <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="flex flex-col gap-4">
                                            <input type="hidden" name="edit_vehicle_id" value="<?= $v['id'] ?>">
                                            <div class="form-control">
                                                <label class="label">Vehicle Name</label>
                                                <input type="text" name="vehicle_name" class="input input-bordered" 
                                                    value="<?= htmlspecialchars($v['vehicle_name']) ?>" required>
                                            </div>
                                            <div class="form-control">
                                                <label class="label">Plate Number</label>
                                                <input type="text" name="plate_number" class="input input-bordered" 
                                                    value="<?= htmlspecialchars($v['plate_number']) ?>" required>
                                            </div>
                                            <div class="form-control">
                                                <label class="label">Status</label>
                                                <select name="status" class="select select-bordered" required>
                                                    <option value="Active" <?= $v['status'] === 'Active' ? 'selected' : '' ?>>Available</option>
                                                    <option value="Inactive" <?= $v['status'] === 'Inactive' ? 'selected' : '' ?>>Unavailable</option>
                                                </select>
                                            </div>
                                            <div class="flex gap-2 mt-4">
                                                <button type="submit" class="btn btn-primary flex-1">Update Vehicle</button>
                                                <a href="<?= htmlspecialchars($baseURL . '&delete=' . $v['id']) ?>"
                                                    class="btn btn-error"
                                                    onclick="return confirm('Are you sure you want to delete this vehicle? This action cannot be undone.')">Delete</a>
                                            </div>
                                        </form>
                                    </div>
                                    <form method="dialog" class="modal-backdrop">
                                        <button>close</button>
                                    </form>
                                </dialog>
                            </td>
                            <td>
                                <!-- Add Log Button -->
                                <button class="btn btn-xs btn-info" onclick="document.getElementById('log_modal_<?= $v['id'] ?>').showModal()">Add Log</button>
                                <!-- Log Modal -->
                                <dialog id="log_modal_<?= $v['id'] ?>" class="modal">
                                    <div class="modal-box">
                                        <form method="dialog">
                                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                                        </form>
                                        <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="flex flex-col">
                                            <input type="hidden" name="log_vehicle_id" value="<?= $v['id'] ?>">
                                            <div class="form-control mb-2">
                                                <label class="label">Log Type</label>
                                                <select name="log_type" class="select select-bordered" required>
                                                    <option value="maintenance">Maintenance</option>
                                                    <option value="fuel">Fuel</option>
                                                </select>
                                            </div>
                                            <div class="form-control mb-2">
                                                <label class="label">Details</label>
                                                <textarea name="log_details" class="textarea textarea-bordered" required></textarea>
                                            </div>
                                            <button class="btn btn-primary mt-2 btn-outline w-full">Submit Log</button>
                                        </form>
                                    </div>
                                    <form method="dialog" class="modal-backdrop">
                                        <button>close</button>
                                    </form>
                                </dialog>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
}
