
<?php
//VEHICLE ROUTING AND DISPATCH SYSTEM
require_once __DIR__ . '/../includes/functions.php';

if (isset($_GET['delete'])) {
    deleteData('vehicle_routes', $_GET['delete']);
    header("Location: {$baseURL}");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['route_name'])) {
    // allow null vehicle_id
    $vehicle_id = !empty($_POST['vehicle_id']) ? $_POST['vehicle_id'] : null;
    insertData('vehicle_routes', [
        'route_name' => $_POST['route_name'],
        'vehicle_id' => $vehicle_id,
        'dispatch_date' => $_POST['dispatch_date'],
        'status' => $_POST['status']
    ]);
    header("Location: {$baseURL}");
    exit;
}

$routes = fetchAll('vehicle_routes');
$vehicles = fetchAll('fleet_vehicles');
?>

<div>
    <h2 class="text-2xl font-bold mb-4">Vehicle Routing & Dispatch</h2>

    <button class="btn btn-primary mb-3" onclick="vrds_modal.showModal()">Add Route</button>

    <dialog id="vrds_modal" class="modal">
        <div class="modal-box">

            <form method="dialog">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
            </form>

            <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="mb-6">
                <div class="form-control mb-2">
                    <label class="label">Route Name</label>
                    <input type="text" name="route_name" class="input input-bordered" required>
                </div>
                <div class="form-control mb-2">
                    <label class="label">Vehicle</label>
                    <select name="vehicle_id" class="select select-bordered">
                        <option value="">-- None --</option>
                        <?php foreach ($vehicles as $veh): ?>
                            <option value="<?= $veh['id'] ?>"><?= htmlspecialchars($veh['vehicle_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-control mb-2">
                    <label class="label">Dispatch Date</label>
                    <input type="date" name="dispatch_date" class="input input-bordered">
                </div>
                <div class="form-control mb-2">
                    <label class="label">Status</label>
                    <select name="status" class="select select-bordered">
                        <option>Planned</option>
                        <option>Dispatched</option>
                        <option>Completed</option>
                    </select>
                </div>
                <button class="btn btn-primary btn-outline mt-2 w-full">Add Route</button>
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
                    <th>Route Name</th>
                    <th>Vehicle</th>
                    <th>Dispatch Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($routes as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['route_name']) ?></td>
                        <td>
                            <?php
                            $vehName = '';
                            foreach ($vehicles as $veh) {
                                if ($veh['id'] == $r['vehicle_id']) {
                                    $vehName = $veh['vehicle_name'];
                                    break;
                                }
                            }
                            echo htmlspecialchars($vehName);
                            ?>
                        </td>
                        <td><?= htmlspecialchars($r['dispatch_date']) ?></td>
                        <td><?= htmlspecialchars($r['status']) ?></td>
                        <td>
                            <a href="<?= htmlspecialchars($baseURL . '&delete=' . $r['id']) ?>"
                                class="btn btn-sm btn-error"
                                onclick="return confirm('Delete this route?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>