<?php
// TRANSPORT COST ANALYSIS AND OPTIMIZATION
require_once __DIR__ . '/../includes/functions.php';

function tcao_logic($baseURL)
{
    if (isset($_GET['delete'])) {
        deleteData('transport_costs', $_GET['delete']);
        header("Location: {$baseURL}");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trip_id'])) {
        $fuel  = floatval($_POST['fuel_cost'] ?: 0);
        $toll  = floatval($_POST['toll_fees'] ?: 0);
        $other = floatval($_POST['other_expenses'] ?: 0);
        $total = $fuel + $toll + $other;

        insertData('transport_costs', [
            'trip_id'        => $_POST['trip_id'],
            'fuel_cost'      => $fuel,
            'toll_fees'      => $toll,
            'other_expenses' => $other,
            'total_cost'     => $total
        ]);
        header("Location: {$baseURL}");
        exit;
    }
}

function tcao_view($baseURL)
{
    $costs = fetchAll('transport_costs');
?>
    <div>
        <h2 class="text-2xl font-bold mb-4">Transport Cost Analysis & Optimization</h2>

        <button class="btn btn-primary mb-3" onclick="tcao_modal.showModal()">Add Cost Record</button>

        <dialog id="tcao_modal" class="modal">
            <div class="modal-box">

                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>

                <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="mb-6">
                    <div class="form-control mb-2">
                        <label class="label">Trip ID</label>
                        <input type="text" name="trip_id" class="input input-bordered" required>
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
                    <button class="btn btn-primary btn-outline mt-2 w-full">Add Cost Record</button>
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
                        <th>Fuel Cost</th>
                        <th>Toll Fees</th>
                        <th>Other Expenses</th>
                        <th>Total Cost</th>
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
                                <a href="<?= htmlspecialchars($baseURL . '&delete=' . $c['id']) ?>"
                                    class="btn btn-sm btn-error"
                                    onclick="return confirm('Delete this cost record?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
}
