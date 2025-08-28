<?php
function fvm_logic($baseURL)
{
    // Handle delete
    if (isset($_GET['delete'])) {
        deleteData('fleet_vehicles', $_GET['delete']);
        header("Location: {$baseURL}");
        exit;
    }

    // Handle insert
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vehicle_name'])) {
        insertData('fleet_vehicles', [
            'vehicle_name' => $_POST['vehicle_name'],
            'plate_number' => $_POST['plate_number'],
            'status'       => $_POST['status']
        ]);
        header("Location: {$baseURL}");
        exit;
    }
}

function fvm_view($baseURL)
{
    $vehicles = fetchAll('fleet_vehicles');
?>
    <div>

        <h2 class="text-2xl font-bold mb-4">Fleet & Vehicle Management</h2>

        <!-- Add Vehicle Button -->
        <button class="btn btn-soft btn-primary mb-3" onclick="fvm_modal.showModal()">Add Vehicle</button>

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
                    <div class="form-control mb-2">
                        <label class="label">Status</label>
                        <select name="status" class="select select-bordered">
                            <option>Active</option>
                            <option>Under Maintenance</option>
                            <option>Inactive</option>
                        </select>
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
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $v): ?>
                        <tr>
                            <td><?= htmlspecialchars($v['vehicle_name']) ?></td>
                            <td><?= htmlspecialchars($v['plate_number']) ?></td>
                            <td><?= htmlspecialchars($v['status']) ?></td>
                            <td>
                                <a href="<?= htmlspecialchars($baseURL . '&delete=' . $v['id']) ?>"
                                    class="btn btn-sm btn-error"
                                    onclick="return confirm('Delete this vehicle?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
}
