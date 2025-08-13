<?php
require_once __DIR__ . '/../includes/functions.php';

if (isset($_GET['delete'])) {
    deleteData('driver_trips', $_GET['delete']);
    header("Location: {$baseURL}");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['driver_name'])) {
    insertData('driver_trips', [
        'driver_name' => $_POST['driver_name'],
        'trip_date' => $_POST['trip_date'],
        'performance_score' => $_POST['performance_score'],
        'remarks' => $_POST['remarks']
    ]);
    header("Location: {$baseURL}");
    exit;
}

$trips = fetchAll('driver_trips');
?>

<div>
    <h2 class="text-2xl font-bold mb-4">Driver & Trip Performance</h2>

    <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="mb-6 bg-base-200 p-4 rounded">
        <div class="form-control mb-2">
            <label class="label">Driver Name</label>
            <input type="text" name="driver_name" class="input input-bordered" required>
        </div>
        <div class="form-control mb-2">
            <label class="label">Trip Date</label>
            <input type="date" name="trip_date" class="input input-bordered" required>
        </div>
        <div class="form-control mb-2">
            <label class="label">Performance Score</label>
            <input type="number" name="performance_score" min="0" max="100" class="input input-bordered">
        </div>
        <div class="form-control mb-2">
            <label class="label">Remarks</label>
            <textarea name="remarks" class="textarea textarea-bordered"></textarea>
        </div>
        <button class="btn btn-primary mt-2">Add Trip Record</button>
    </form>

    <div class="overflow-x-auto">
        <table class="table table-zebra w-full">
            <thead>
                <tr>
                    <th>Driver Name</th>
                    <th>Trip Date</th>
                    <th>Performance Score</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trips as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['driver_name']) ?></td>
                    <td><?= htmlspecialchars($t['trip_date']) ?></td>
                    <td><?= htmlspecialchars($t['performance_score']) ?></td>
                    <td><?= htmlspecialchars($t['remarks']) ?></td>
                    <td>
                        <a href="<?= htmlspecialchars($baseURL . '&delete=' . $t['id']) ?>"
                           class="btn btn-sm btn-error"
                           onclick="return confirm('Delete this record?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
