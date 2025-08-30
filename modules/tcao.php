
<?php
// TRANSPORT COST ANALYSIS AND OPTIMIZATION (Multi-Stage Workflow)

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/audit_log.php';



function tcao_logic($baseURL)
{

    $user = $_SESSION['username'] ?? 'unknown';
    global $conn;

    // Handle delete (admin only)
    if (isset($_GET['delete'])) {
    deleteData('transport_costs', $_GET['delete']);
    log_audit_event('TCAO', 'deleted', $_GET['delete'], $user);
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

function tcao_view($baseURL)
{
    $user = $_SESSION['username'] ?? 'unknown';
    $role = $_SESSION['role'] ?? 'driver';
    $costs = fetchAll('transport_costs');
    // Fetch trips for dropdown (only those not already in transport_costs)
    $allTrips = fetchAll('driver_trips');
    $usedTripIds = array_column($costs, 'trip_id');
    $availableTrips = array_filter($allTrips, function($t) use ($usedTripIds) {
        return !in_array($t['id'], $usedTripIds);
    });
    ?>
    <div>
        <h2 class="text-2xl font-bold mb-4">Transport Cost Analysis & Optimization</h2>

        <?php if (!empty($_SESSION['tcao_error'])): ?>
            <div class="alert alert-error mb-2"><?= $_SESSION['tcao_error']; unset($_SESSION['tcao_error']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['tcao_success'])): ?>
            <div class="alert alert-success mb-2"><?= $_SESSION['tcao_success']; unset($_SESSION['tcao_success']); ?></div>
        <?php endif; ?>

        <button class="btn btn-primary mb-3" onclick="tcao_modal.showModal()">Add Cost Record</button>

        <dialog id="tcao_modal" class="modal">
            <div class="modal-box">
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <form method="POST" enctype="multipart/form-data" action="<?= htmlspecialchars($baseURL) ?>" class="mb-6">
                    <div class="form-control mb-2">
                        <label class="label">Trip</label>
                        <select name="trip_id" class="input input-bordered" required>
                            <option value="">Select a trip</option>
                            <?php foreach ($availableTrips as $trip): ?>
                                <option value="<?= $trip['id'] ?>">
                                    Trip #<?= $trip['id'] ?> | Driver: <?= htmlspecialchars($trip['driver_id']) ?> | Date: <?= htmlspecialchars($trip['trip_date']) ?> | Distance: <?= number_format($trip['distance_traveled'], 1) ?> km
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Fuel Cost</label>
                        <input type="number" step="0.01" name="fuel_cost" class="input input-bordered">
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Toll Fees</label>
                        <input type="number" step="0.01" name="toll_fees" class="input input-bordered">
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Other Expenses</label>
                        <input type="number" step="0.01" name="other_expenses" class="input input-bordered">
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Receipt (PDF/JPG/PNG)</label>
                        <input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png" class="input input-bordered">
                    </div>
                    <button class="btn btn-primary btn-outline mt-2 w-full">Submit Cost Entry</button>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>

        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Trip ID</th>
                        <th>Fuel</th>
                        <th>Toll</th>
                        <th>Other</th>
                        <th>Total</th>
                        <th>Receipt</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($costs as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['trip_id']) ?></td>
                            <td><?= number_format($c['fuel_cost'], 2) ?></td>
                            <td><?= number_format($c['toll_fees'], 2) ?></td>
                            <td><?= number_format($c['other_expenses'], 2) ?></td>
                            <td><?= number_format($c['total_cost'], 2) ?></td>
                            <td>
                                <?php if (!empty($c['receipt'])): ?>
                                    <a href="./uploads/<?= htmlspecialchars($c['receipt']) ?>" target="_blank">View</a>
                                <?php else: ?>
                                    <span class="text-gray-400">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?=
                                    $c['status'] === 'submitted' ? 'info' :
                                    ($c['status'] === 'supervisor_approved' ? 'primary' :
                                    ($c['status'] === 'finalized' ? 'success' :
                                    ($c['status'] === 'returned' ? 'error' : 'secondary')))
                                ?>">
                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $c['status']))) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($role === 'supervisor' && $c['status'] === 'submitted'): ?>
                                    <a href="<?= htmlspecialchars($baseURL . '&approve=' . $c['id'] . '&role=supervisor') ?>" class="btn btn-xs btn-success">Approve</a>
                                    <a href="<?= htmlspecialchars($baseURL . '&return=' . $c['id'] . '&role=supervisor') ?>" class="btn btn-xs btn-warning">Return</a>
                                <?php elseif ($role === 'accountant' && $c['status'] === 'supervisor_approved'): ?>
                                    <a href="<?= htmlspecialchars($baseURL . '&approve=' . $c['id'] . '&role=accountant') ?>" class="btn btn-xs btn-success">Finalize</a>
                                    <a href="<?= htmlspecialchars($baseURL . '&return=' . $c['id'] . '&role=accountant') ?>" class="btn btn-xs btn-warning">Return</a>
                                <?php elseif ($role === 'driver' && $c['status'] === 'returned' && $c['created_by'] === $user): ?>
                                    <span class="text-warning">Returned for correction</span>
                                <?php endif; ?>
                                <?php if ($role === 'admin'): ?>
                                    <a href="<?= htmlspecialchars($baseURL . '&delete=' . $c['id']) ?>" class="btn btn-xs btn-error" onclick="return confirm('Delete this cost record?')">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3 class="text-lg font-bold mt-6">Audit Log</h3>
        <div class="overflow-x-auto">
            <table class="table table-compact w-full">
                <thead>
                    <tr><th>Cost ID</th><th>Action</th><th>User</th><th>Timestamp</th></tr>
                </thead>
                <tbody>
                    <?php
                    $logs = fetchAll('tcao_audit_log');
                    foreach ($logs as $log): ?>
                        <tr>
                            <td><?= $log['cost_id'] ?></td>
                            <td><?= htmlspecialchars($log['action']) ?></td>
                            <td><?= htmlspecialchars($log['user']) ?></td>
                            <td><?= htmlspecialchars($log['timestamp']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3 class="text-lg font-bold mt-6">Financials/Disbursement (Stub)</h3>
        <div class="alert alert-info">Finalized costs will be forwarded to the Financials module for disbursement. (Integration pending)</div>
    </div>
<?php
}
