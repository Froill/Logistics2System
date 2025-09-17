<?php
// TRANSPORT COST ANALYSIS AND OPTIMIZATION (Multi-Stage Workflow)

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/audit_log.php';
require_once __DIR__ . '/../includes/tcao_logic.php';


function tcao_view($baseURL)
{
    // Detect AJAX request
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

    $currency = '₱'; // Could be extended to allow multiple currencies
    $user = $_SESSION['full_name'] ?? 'unknown';
    $role = $_SESSION['role'] ?? 'driver';
    $costs = fetchAll('transport_costs');
    $allTrips = fetchAll('driver_trips');
    $drivers = fetchAll('drivers');
    $vehicles = fetchAll('fleet_vehicles');
    $usedTripIds = array_column($costs, 'trip_id');
    $availableTrips = array_filter($allTrips, function ($t) use ($usedTripIds) {
        return !in_array($t['id'], $usedTripIds);
    });
    // Get filter values from GET (for server-side filtering)
    $filterDriver = isset($_GET['filter_driver']) ? $_GET['filter_driver'] : '';
    $filterVehicle = isset($_GET['filter_vehicle']) ? $_GET['filter_vehicle'] : '';
    $filterStart = isset($_GET['filter_start']) ? $_GET['filter_start'] : '';
    $filterEnd = isset($_GET['filter_end']) ? $_GET['filter_end'] : '';
    // Join trips + costs for analysis
    $joinedData = [];
    foreach ($costs as $c) {
        foreach ($allTrips as $t) {
            if ($t['id'] == $c['trip_id']) {
                // Find driver and vehicle info
                $driver = null;
                foreach ($drivers as $d) {
                    if ($d['id'] == $t['driver_id']) {
                        $driver = $d;
                        break;
                    }
                }
                $vehicle = null;
                foreach ($vehicles as $v) {
                    if ($v['id'] == $t['vehicle_id']) {
                        $vehicle = $v;
                        break;
                    }
                }
                // Filtering logic
                if ($filterDriver && $t['driver_id'] != $filterDriver) continue;
                if ($filterVehicle && $t['vehicle_id'] != $filterVehicle) continue;
                if ($filterStart && strtotime($t['trip_date']) < strtotime($filterStart)) continue;
                if ($filterEnd && strtotime($t['trip_date']) > strtotime($filterEnd)) continue;
                $joinedData[] = array_merge($t, $c, [
                    'driver_name' => $driver ? $driver['driver_name'] : '',
                    'vehicle_name' => $vehicle ? $vehicle['vehicle_name'] : '',
                ]);
            }
        }
    }
?>
    <!-- html output starts here -->
    <div>
        <h2 class="text-xl md:text-2xl text-pretty font-bold mb-4">Transport Cost Analysis & Optimization</h2>


        <?php if (!empty($_SESSION['tcao_error'])): ?>
            <div class="alert alert-error mb-2"><?= $_SESSION['tcao_error'];
                                                unset($_SESSION['tcao_error']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['tcao_success'])): ?>
            <div class="alert alert-success mb-2"><?= $_SESSION['tcao_success'];
                                                    unset($_SESSION['tcao_success']); ?></div>
        <?php endif; ?>

        <div class="flex flex-col md:flex-row gap-2 mb-3">
            <button class="btn btn-primary" onclick="tcao_modal.showModal()">
                <i data-lucide="plus-circle" class="w-4 h-4 mr-1"></i> Add Cost Record
            </button>
            <button class="btn btn-info" onclick="cost_log_modal.showModal()">
                <i data-lucide="clipboard-list" class="w-4 h-4 mr-1"></i> Cost Log
            </button>
        </div>

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
                                    Trip <?= $trip['id'] ?> | Driver: <?= htmlspecialchars($trip['driver_id']) ?> | Date: <?= htmlspecialchars($trip['trip_date']) ?> | Distance: <?= number_format($trip['distance_traveled'], 1) ?> km
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Fuel Cost</label>
                        <input type="number" step="10" name="fuel_cost" class="input input-bordered" placeholder="<?php echo $currency; ?>0.00">
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Toll Fees</label>
                        <input type="number" step="10" name="toll_fees" class="input input-bordered" placeholder="<?php echo $currency; ?>0.00">
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Other Expenses</label>
                        <input type="number" step="10" name="other_expenses" class="input input-bordered" placeholder="<?php echo $currency; ?>0.00">
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Receipt (PDF/JPG/PNG)</label>
                        <input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png" class="file-input file-input-primary">
                    </div>
                    <button class="btn btn-primary btn-outline mt-2 w-full">Submit Cost Entry</button>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>

        <dialog id="cost_log_modal" class="modal">
            <div class="modal-box w-11/12 max-w-5xl cost-log-content">
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg mb-4">Cost Log</h3>
                <?php
                // Pagination logic
                $page = isset($_GET['cost_page']) ? max(1, intval($_GET['cost_page'])) : 1;
                $perPage = 10;
                $totalCosts = count($costs);
                $totalPages = ceil($totalCosts / $perPage);
                $start = ($page - 1) * $perPage;
                $pagedCosts = array_slice($costs, $start, $perPage);
                ?>
                <form method="POST" action="<?= htmlspecialchars($baseURL) ?>">
                    <button type="submit" name="clear_cost_logs" class="btn btn-error mb-2" onclick="return confirm('Clear all cost logs?')">Clear Log</button>
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
                                <?php foreach ($pagedCosts as $c): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($c['trip_id']) ?></td>
                                        <td><?= $currency, number_format($c['fuel_cost'], 2) ?></td>
                                        <td><?= $currency, number_format($c['toll_fees'], 2) ?></td>
                                        <td><?= $currency, number_format($c['other_expenses'], 2) ?></td>
                                        <td>
                                            <?php if (!empty($c['receipt'])): ?>
                                                <a href="./uploads/<?= htmlspecialchars($c['receipt']) ?>" target="_blank">View</a>
                                            <?php else: ?>
                                                <span class="text-gray-400">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?=
                                                                        $c['status'] === 'submitted' ? 'info' : ($c['status'] === 'supervisor_approved' ? 'primary' : ($c['status'] === 'finalized' ? 'success' : ($c['status'] === 'returned' ? 'error' : 'secondary')))
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
                </form>
                <!-- Pagination Controls -->
                <div class="flex justify-center mt-4 gap-2" id="costLogPagination">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="#" onclick="openCostLogPage(<?= $p ?>); return false;" class="btn btn-xs <?= $p == $page ? 'btn-primary' : 'btn-outline' ?>">Page <?= $p ?></a>
                    <?php endfor; ?>
                </div>
            </div>
            <script src="./js/ajax-pagination.js"></script>
            <script>
                function openCostLogPage(page) {
                    ajaxPaginateModal('cost_log_modal', '<?= htmlspecialchars($baseURL) ?>', 'cost_page', page, '.cost-log-content');
                }
            </script>
            </tbody>
            </table>
    </div>
    </form>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
    </dialog>


    <h3 class="text-lg font-bold mt-6">Key Performance Indicators</h3>
    <!-- Key Performance Indicators Section to see driver list, vehicle list, and date range-->
    <?php include __DIR__ . '/../includes/tcao-kpi.php'; ?>
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6 mt-4">

        <!-- Avg Cost per km -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h4 class="card-title">Avg Cost per km</h4>
                <p class="text-2xl font-bold text-primary">
                    ₱<?= number_format($avgCostPerKm, 2) ?>
                </p>
            </div>
        </div>

        <!-- Avg Cost per Trip -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h4 class="card-title">Avg Cost per Trip</h4>
                <p class="text-2xl font-bold text-secondary">
                    ₱<?= number_format($avgCostPerTrip, 2) ?>
                </p>
            </div>
        </div>

        <!-- Load Utilization -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h4 class="card-title">Load Utilization</h4>
                <p class="text-2xl font-bold text-success">
                    <?= number_format($avgLoadUtilization, 1) ?>%
                </p>
            </div>
        </div>

        <!-- Fuel Share of Total Cost -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h4 class="card-title">Fuel % of Cost</h4>
                <p class="text-2xl font-bold text-warning">
                    <?= number_format($fuelShare, 1) ?>%
                </p>
            </div>
        </div>
    </div>

    <h3 class="text-lg font-bold mt-6">Visual Analysis</h3>

    <!-- Driver, Vehicle, Date Filters -->
    <div class="flex flex-wrap gap-4 mb-6 items-end">
        <form method="GET" class="flex flex-wrap gap-4 mb-6 items-end">
            <input type="hidden" name="module" value="tcao">
            <div>
                <label class="label"><span class="label-text">Driver</span></label>
                <select name="filter_driver" class="select select-bordered w-48">
                    <option value="">All Drivers</option>
                    <?php foreach ($drivers as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($filterDriver == $d['id'] ? 'selected' : '') ?>><?= htmlspecialchars($d['driver_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label"><span class="label-text">Vehicle</span></label>
                <select name="filter_vehicle" class="select select-bordered w-48">
                    <option value="">All Vehicles</option>
                    <?php foreach ($vehicles as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= ($filterVehicle == $v['id'] ? 'selected' : '') ?>><?= htmlspecialchars($v['vehicle_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex flex-col md:flex-row gap-3">
                <label class="label"><span class="label-text">Date Range</span></label>
                <input type="date" name="filter_start" class="input input-bordered w-40" value="<?= htmlspecialchars($filterStart) ?>">
                <span class="mx-auto md:mx-2 my-auto">to</span>
                <input type="date" name="filter_end" class="input input-bordered w-40" value="<?= htmlspecialchars($filterEnd) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Apply</button>
        </form>
        <!-- end of filters -->

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-2 w-full">

            <!-- Cost per Trip -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h4 class="card-title">Cost per Trip</h4>
                    <canvas id="costPerTripChart"></canvas>
                </div>
            </div>

            <!-- Cost Breakdown -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h4 class="card-title">Cost Breakdown (All Trips)</h4>
                    <canvas id="costBreakdownChart"></canvas>
                </div>
            </div>

            <!-- Cost per km -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h4 class="card-title">Cost per km</h4>
                    <canvas id="costPerKmChart"></canvas>
                </div>
            </div>

            <!-- Load Utilization -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h4 class="card-title">Load Utilization</h4>
                    <canvas id="loadUtilChart"></canvas>
                </div>
            </div>
        </div>



        <div class="overflow-x-auto mb-6">
            <h3 class="text-lg font-bold">Transport Cost Analysis</h3>
            <?php
            // Pagination for Transport Cost Analysis
            $tcPage    = isset($_GET['tc_page']) ? max(1, intval($_GET['tc_page'])) : 1;
            $tcPerPage = 10; // rows per page
            $tcTotal   = count($joinedData);
            $tcPages   = ceil($tcTotal / $tcPerPage);
            $tcStart   = ($tcPage - 1) * $tcPerPage;
            $pagedJoinedData = array_slice($joinedData, $tcStart, $tcPerPage);
            ?>
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Trip ID</th>
                        <th>Driver</th>
                        <th>Distance (km)</th>
                        <th>Cargo Weight (kg)</th>
                        <th>Total Cost (<?= $currency ?>)</th>
                        <th>Cost/km</th>
                        <th>Cost/ton-km</th>
                        <th>Load Utilization</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagedJoinedData as $row):
                        $distance = floatval($row['distance_traveled'] ?: 0);
                        $weight   = floatval($row['cargo_weight'] ?: 0);
                        $capacity = floatval($row['vehicle_capacity'] ?: 1);
                        $cost     = floatval($row['total_cost']);
                        $costPerKm = $distance > 0 ? $cost / $distance : 0;
                        $costPerTonKm = ($distance > 0 && $weight > 0) ? $cost / ($weight * $distance / 1000) : 0;
                        $loadUtil = $capacity > 0 ? ($weight / $capacity) * 100 : 0;
                    ?>
                        <tr>
                            <td><?= $row['trip_id'] ?></td>
                            <td><?= htmlspecialchars($row['driver_name'] ?? '') ?></td>
                            <td><?= number_format($distance, 1) ?></td>
                            <td><?= number_format($weight, 0) ?></td>
                            <td><?= $currency, number_format($cost, 2) ?></td>
                            <td><?= $currency, number_format($costPerKm, 2) ?></td>
                            <td><?= $currency, number_format($costPerTonKm, 2) ?></td>
                            <td><?= number_format($loadUtil, 1) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
            // Pagination controls
            echo '<div class="flex justify-center mt-4 space-x-2 join">';
            if ($tcPage > 1) {
                echo '<a href="dashboard.php?module=tcao&tc_page=' . ($tcPage - 1) . '" class="join-item btn btn-sm">Prev</a>';
            }
            for ($p = 1; $p <= $tcPages; $p++) {
                $active = ($p == $tcPage) ? 'btn-primary' : '';
                echo '<a href="dashboard.php?module=tcao&tc_page=' . $p . '" class="join-item btn btn-sm ' . $active . '">' . $p . '</a>';
            }
            if ($tcPage < $tcPages) {
                echo '<a href="dashboard.php?module=tcao&tc_page=' . ($tcPage + 1) . '" class="join-item btn btn-sm">Next</a>';
            }
            echo '</div>';
            ?>
        </div>
    </div>


    <!--
<h3 class="text-lg font-bold mt-6">Optimization Scenarios (What-If)</h3>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="card bg-base-100 shadow p-4">
        <h4 class="font-semibold mb-2">Scenario 1: Route Optimization</h4>
        <p>Switch to shorter or toll-free routes → Potential fuel & toll savings.</p>
    </div>
    <div class="card bg-base-100 shadow p-4">
        <h4 class="font-semibold mb-2">Scenario 2: Load Utilization</h4>
        <p>Increase load factor from current <?= number_format(array_sum(array_column($joinedData, 'cargo_weight')) / (array_sum(array_column($joinedData, 'vehicle_capacity')) ?: 1) * 100, 1) ?>% to ≥85% → Lower cost per ton-km.</p>
    </div>
    <div class="card bg-base-100 shadow p-4">
        <h4 class="font-semibold mb-2">Scenario 3: Vehicle Right-Sizing</h4>
        <p>Match vehicle type to trip load → Avoid under/over capacity costs.</p>
    </div>
    <div class="card bg-base-100 shadow p-4">
        <h4 class="font-semibold mb-2">Scenario 4: Outsourcing Mix</h4>
        <p>Compare in-house vs. 3PL rates for long-distance/high-volume routes.</p>
    </div>
</div>
            -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const tripLabels = <?= json_encode(array_column($joinedData, 'trip_id')) ?>;
        const costData = <?= json_encode(array_column($joinedData, 'total_cost')) ?>;
        const fuelData = <?= json_encode(array_column($joinedData, 'fuel_cost')) ?>;
        const tollData = <?= json_encode(array_column($joinedData, 'toll_fees')) ?>;
        const otherData = <?= json_encode(array_column($joinedData, 'other_expenses')) ?>;

        // Derived metrics
        const distanceData = <?= json_encode(array_column($joinedData, 'distance_traveled')) ?>;
        const costPerKmData = costData.map((c, i) => distanceData[i] > 0 ? (c / distanceData[i]).toFixed(2) : 0);
        const weightData = <?= json_encode(array_map(fn($r) => $r['cargo_weight'] ?? 0, $joinedData)) ?>;
        const capacityData = <?= json_encode(array_map(fn($r) => $r['vehicle_capacity'] ?? 1, $joinedData)) ?>;
        const loadUtilData = weightData.map((w, i) => capacityData[i] > 0 ? ((w / capacityData[i]) * 100).toFixed(1) : 0);

        // Chart 1: Cost per Trip
        new Chart(document.getElementById('costPerTripChart'), {
            type: 'bar',
            data: {
                labels: tripLabels,
                datasets: [{
                    label: 'Total Cost',
                    data: costData,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)' // Tailwind blue-500
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Chart 2: Cost Breakdown
        new Chart(document.getElementById('costBreakdownChart'), {
            type: 'pie',
            data: {
                labels: ['Fuel', 'Toll', 'Other'],
                datasets: [{
                    data: [
                        fuelData.reduce((a, b) => a + parseFloat(b), 0),
                        tollData.reduce((a, b) => a + parseFloat(b), 0),
                        otherData.reduce((a, b) => a + parseFloat(b), 0)
                    ],
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.7)', // green-500
                        'rgba(234, 179, 8, 0.7)', // yellow-500
                        'rgba(239, 68, 68, 0.7)' // red-500
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });

        // Chart 3: Cost per km
        new Chart(document.getElementById('costPerKmChart'), {
            type: 'line',
            data: {
                labels: tripLabels,
                datasets: [{
                    label: 'Cost per km',
                    data: costPerKmData,
                    borderColor: 'rgba(99, 102, 241, 0.9)', // indigo-500
                    backgroundColor: 'rgba(99, 102, 241, 0.4)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true
            }
        });

        // Chart 4: Load Utilization
        new Chart(document.getElementById('loadUtilChart'), {
            type: 'bar',
            data: {
                labels: tripLabels,
                datasets: [{
                    label: 'Utilization %',
                    data: loadUtilData,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)' // emerald-500
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    </script>


<?php
}
