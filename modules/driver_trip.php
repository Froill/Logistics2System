<?php
// DRIVER AND TRIP PERFORMANCE MONITORING
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/driver_trip_logic.php';
require_once __DIR__ . '/../includes/db.php';

function driver_trip_view($baseURL)
{
    global $conn; // use the mysqli connection

    // Add log to the current module that is being accessed by the user
    $moduleName = 'driver_trip';

    if ($_SESSION['current_module'] !== $moduleName) {
        log_audit_event(
            'DTP',
            'ACCESS',
            null,
            $_SESSION['full_name'],
            'User accessed Driver & Trip Performance module'
        );
        $_SESSION['current_module'] = $moduleName;
    }

    // Collect filters from GET request
    $filterDriver = isset($_GET['filter_driver']) ? trim($_GET['filter_driver']) : '';
    $filterVehicle = isset($_GET['filter_vehicle']) ? trim($_GET['filter_vehicle']) : '';

    // Base query
    $query = "
        SELECT 
            t.*,
            d.driver_name,
            v.vehicle_name
        FROM driver_trips t
        JOIN drivers d ON t.driver_id = d.id
        JOIN fleet_vehicles v ON t.vehicle_id = v.id
        WHERE 1=1
    ";

    // Add filters if valid
    if ($filterDriver !== '' && ctype_digit($filterDriver)) {
        $query .= " AND t.driver_id = " . (int)$filterDriver;
    }

    if ($filterVehicle !== '' && ctype_digit($filterVehicle)) {
        $query .= " AND t.vehicle_id = " . (int)$filterVehicle;
    }

    $query .= " ORDER BY t.created_at DESC";

    // Execute query
    $trips = [];
    if ($result = $conn->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $trips[] = $row;
        }
        $result->free();
    } else {
        error_log("Query failed: " . $conn->error);
    }

    // Fetch drivers for filter dropdown
    $drivers = [];
    if ($result = $conn->query("SELECT * FROM drivers ORDER BY driver_name ASC")) {
        while ($row = $result->fetch_assoc()) {
            $drivers[] = $row;
        }
        $result->free();
    }

    // Fetch vehicles for filter dropdown
    $vehicles = [];
    if ($result = $conn->query("SELECT * FROM fleet_vehicles ORDER BY vehicle_name ASC")) {
        while ($row = $result->fetch_assoc()) {
            $vehicles[] = $row;
        }
        $result->free();
    }

    // Fetch completed dispatches with vehicle payload
    $completedDispatches = [];
    $dispatchQuery = "
        SELECT d.*, v.vehicle_name, v.weight_capacity AS vehicle_payload, dr.driver_name 
        FROM dispatches d 
        JOIN fleet_vehicles v ON d.vehicle_id = v.id 
        JOIN drivers dr ON d.driver_id = dr.id 
        WHERE d.status = 'Completed' 
        ORDER BY d.dispatch_date DESC
    ";
    if ($result = $conn->query($dispatchQuery)) {
        while ($row = $result->fetch_assoc()) {
            $completedDispatches[] = $row;
        }
        $result->free();
    }

    // Calculate overall statistics
    $totalTrips = count($trips);

    $avgScore = $totalTrips ? array_reduce($trips, function ($carry, $trip) {
        return $carry + (float)$trip['performance_score'];
    }, 0) / $totalTrips : 0;

    // Determine aggregation period for performance sorting
    $period = isset($_GET['period']) ? $_GET['period'] : 'monthly'; // default to monthly
    $periodCondition = '';
    switch ($period) {
        case 'daily':
            $periodCondition = "AND DATE(t.trip_date) = CURDATE()";
            break;
        case 'weekly':
            $periodCondition = "AND YEARWEEK(t.trip_date, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'yearly':
            $periodCondition = "AND YEAR(t.trip_date) = YEAR(CURDATE())";
            break;
        case 'monthly':
        default:
            $periodCondition = "AND YEAR(t.trip_date) = YEAR(CURDATE()) AND MONTH(t.trip_date) = MONTH(CURDATE())";
            break;
    }

    // Aggregate performance per driver for the selected period
    $performanceList = [];
    $aggQuery = "
        SELECT d.id AS driver_id, d.driver_name, IFNULL(AVG(t.performance_score),0) AS avg_score, COUNT(t.id) AS trip_count
        FROM driver_trips t
        JOIN drivers d ON t.driver_id = d.id
        WHERE 1=1
        {$periodCondition}
        GROUP BY d.id, d.driver_name
        ORDER BY avg_score DESC, trip_count DESC
    ";

    if ($result = $conn->query($aggQuery)) {
        while ($row = $result->fetch_assoc()) {
            $performanceList[] = $row;
        }
        $result->free();
    }

    // Compute trip counts for commonly requested ranges
    $tripCounts = [
        'today' => 0,
        'weekly' => 0,
        'monthly' => 0,
        'yearly' => 0,
    ];

    $countQueries = [
        'today' => "SELECT COUNT(*) AS c FROM driver_trips WHERE DATE(trip_date) = CURDATE()",
        'weekly' => "SELECT COUNT(*) AS c FROM driver_trips WHERE YEARWEEK(trip_date,1) = YEARWEEK(CURDATE(),1)",
        'monthly' => "SELECT COUNT(*) AS c FROM driver_trips WHERE YEAR(trip_date) = YEAR(CURDATE()) AND MONTH(trip_date) = MONTH(CURDATE())",
        'yearly' => "SELECT COUNT(*) AS c FROM driver_trips WHERE YEAR(trip_date) = YEAR(CURDATE())",
    ];

    foreach ($countQueries as $k => $q) {
        if ($r = $conn->query($q)) {
            $row = $r->fetch_assoc();
            $tripCounts[$k] = (int)$row['c'];
            $r->free();
        }
    }
?>
    <div class="space-y-6">
        <div class="flex flex-col gap-4">
            <h2 class="text-2xl font-bold">Driver & Trip Performance</h2>
            <div class='flex flex-col md:flex-row gap-3'>
                <button class="btn btn-primary" onclick="submit_trip_modal.showModal()">
                    <i data-lucide="plus-circle" class="w-4 h-4 mr-1"></i> Submit Trip Data
                </button>
                <button class="btn btn-info" onclick="trip_log_modal.showModal()">
                    <i data-lucide="clipboard-list" class="w-4 h-4 mr-1"></i> Review Trip Submissions
                </button>
                <button class="btn btn-success" onclick="export_trip_modal.showModal()">
                    <i data-lucide="download" class="w-4 h-4 mr-1"></i> Export Trip Data
                </button>
                <!-- Export Trip Data Modal -->
                <dialog id="export_trip_modal" class="modal">
                    <div class="modal-box max-w-xl">
                        <form method="dialog">
                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                        </form>
                        <h3 class="font-bold text-lg mb-4">Export Trip Data</h3>
                        <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="space-y-4">
                            <div class="form-control">
                                <label class="label">Export Type</label>
                                <select name="export_type" class="select select-bordered w-full" required onchange="toggleExportDriver(this.value)">
                                    <option value="all">All Drivers & Vehicles</option>
                                    <option value="driver">Individual Driver Performance</option>
                                </select>
                            </div>
                            <div class="form-control" id="exportDriverSelect" style="display:none;">
                                <label class="label">Select Driver</label>
                                <select name="driver_id" class="select select-bordered w-full">
                                    <option value="">Select Driver</option>
                                    <?php foreach ($drivers as $d): ?>
                                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['driver_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="export_trip_data" class="btn btn-success w-full mt-4">Export</button>
                        </form>
                        <script>
                            function toggleExportDriver(val) {
                                document.getElementById('exportDriverSelect').style.display = (val === 'driver') ? '' : 'none';
                            }
                        </script>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button>close</button>
                    </form>
                </dialog>
            </div>
        </div>

        <!-- Performance Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Stats Cards -->
            <div class="stats shadow bg-base-200">
                <div class="stat">
                    <div class="stat-figure text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                        </svg>
                    </div>
                    <div class="stat-title">Total Trips</div>
                    <div class="stat-value text-primary"><?= $totalTrips ?></div>
                    <div class="stat-desc">Trip Records</div>
                </div>
            </div>

            <div class="stats shadow bg-base-200">
                <div class="stat">
                    <div class="stat-figure text-secondary">
                        <div class="radial-progress text-secondary" style="--value:<?= $avgScore; ?>; --size:3rem"><?= number_format($avgScore, 0) ?></div>
                    </div>
                    <div class="stat-title">Average Performance</div>
                    <div class="stat-value text-secondary"><?= number_format($avgScore, 1) ?>%</div>
                    <div class="stat-desc">Overall Driver Score</div>
                </div>
            </div>

            <div class="stats shadow bg-base-200">
                <div class="stat">
                    <div class="stat-figure text-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div class="stat-title">Pending Reviews</div>
                    <div class="stat-value text-warning"><?= count(array_filter($trips, fn($t) => $t['supervisor_review_status'] === 'pending')) ?></div>
                    <div class="stat-desc">Needs Attention</div>
                </div>
            </div>
        </div>

        <!-- Driver and Vehicle Search Filter Form -->
        <!-- Period selector and aggregated performance -->
        <div class="mt-6 mb-6">
            <form method="GET" class="flex items-center gap-4 mb-4">
                <input type="hidden" name="module" value="driver_trip">
                <label class="label">Show Performance For</label>
                <select name="period" class="select select-bordered" onchange="this.form.submit()">
                    <option value="daily" <?= ($period === 'daily' ? 'selected' : '') ?>>Today</option>
                    <option value="weekly" <?= ($period === 'weekly' ? 'selected' : '') ?>>This Week</option>
                    <option value="monthly" <?= ($period === 'monthly' ? 'selected' : '') ?>>This Month</option>
                    <option value="yearly" <?= ($period === 'yearly' ? 'selected' : '') ?>>This Year</option>
                </select>
            </form>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div class="stats shadow bg-base-200">
                    <div class="stat">
                        <div class="stat-title">Trips Today</div>
                        <div class="stat-value text-primary"><?= $tripCounts['today'] ?></div>
                    </div>
                </div>
                <div class="stats shadow bg-base-200">
                    <div class="stat">
                        <div class="stat-title">Trips This Week</div>
                        <div class="stat-value text-secondary"><?= $tripCounts['weekly'] ?></div>
                    </div>
                </div>
                <div class="stats shadow bg-base-200">
                    <div class="stat">
                        <div class="stat-title">Trips This Month</div>
                        <div class="stat-value text-accent"><?= $tripCounts['monthly'] ?></div>
                    </div>
                </div>
                <div class="stats shadow bg-base-200">
                    <div class="stat">
                        <div class="stat-title">Trips This Year</div>
                        <div class="stat-value text-warning"><?= $tripCounts['yearly'] ?></div>
                    </div>
                </div>
            </div>

            <div class="card bg-base-200 shadow mb-6">
                <div class="card-body">
                    <h3 class="card-title">Top Drivers (<?= htmlspecialchars(ucfirst($period)) ?>)</h3>
                    <?php if (empty($performanceList)): ?>
                        <div class="text-sm">No performance data for selected period.</div>
                    <?php else: ?>
                        <table class="table table-compact w-full">
                            <thead>
                                <tr>
                                    <th>Driver</th>
                                    <th>Avg Score</th>
                                    <th>Trips</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($performanceList as $p): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['driver_name']) ?></td>
                                        <td><?= number_format($p['avg_score'],1) ?>%</td>
                                        <td><?= (int)$p['trip_count'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
         <h2 class = "font-bold text-lg mb 4"> Driver/Vehicle Search </h2>
        <div class="flex flex-wrap gap-4 mb-6 items-end">
            <form method="GET" class="flex flex-wrap gap-4 mb-6 items-end">
                <input type="hidden" name="module" value="driver_trip">

                <!-- Driver Filter -->
                <div>
                    <label class="label">Driver</label>
                    <select name="filter_driver" class="select select-bordered w-48">
                        <option value="">All Drivers</option>
                        <?php foreach ($drivers as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($filterDriver == $d['id'] ? 'selected' : '') ?>>
                                <?= htmlspecialchars($d['driver_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Vehicle Filter -->
                <div>
                    <label class="label">Vehicle</label>
                    <select name="filter_vehicle" class="select select-bordered w-48">
                        <option value="">All Vehicles</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?= $v['id'] ?>" <?= ($filterVehicle == $v['id'] ? 'selected' : '') ?>>
                                <?= htmlspecialchars($v['vehicle_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Apply</button>
            </form>
        </div>


        <!-- Trips Table / No Results -->
        <?php if (empty($trips)): ?>
            <div class="alert alert-warning shadow-lg my-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="info" class="h-5 w-5 stroke-current"></i>
                    <span>
                        No trips found
                        <?php if ($filterDriver || $filterVehicle): ?>
                            for the selected filters.
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        <?php else: ?>
            <h3 class="card-title mb-2">Driver Performance</h3>
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Driver</th>
                        <th>Vehicle</th>
                        <th>Performance Score</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trips as $trip): ?>
                        <tr>
                            <td><?= htmlspecialchars($trip['driver_name']) ?></td>
                            <td><?= htmlspecialchars($trip['vehicle_name']) ?></td>
                            <td><?= htmlspecialchars($trip['performance_score']) ?></td>
                            <td><?= htmlspecialchars($trip['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <!-- Trip Performance Chart -->
            <div class="card bg-base-200 shadow-xl">
                <div class="card-body">
                    <h3 class="card-title">Performance Trends</h3>
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>

            <!-- Trip Status Distribution -->
            <div class="card bg-base-200 shadow-xl">
                <div class="card-body">
                    <h3 class="card-title">Trip Status Distribution</h3>
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Initialize Charts -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Prepare performance data
            const performanceData = {
                labels: <?= json_encode(array_map(fn($t) => date('M d', strtotime($t['trip_date'])), array_slice($trips, 0, 7))) ?>,
                datasets: [{
                    label: 'Performance Score',
                    data: <?= json_encode(array_map(fn($t) => $t['performance_score'], array_slice($trips, 0, 7))) ?>,
                    borderColor: 'rgb(var(--p))',
                    tension: 0.1
                }]
            };

            // Create performance line chart
            new Chart(document.getElementById('performanceChart'), {
                type: 'line',
                data: performanceData,
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

            // Prepare status distribution data
            const statusCounts = {
                pending: <?= count(array_filter($trips, fn($t) => $t['supervisor_review_status'] === 'pending')) ?>,
                approved: <?= count(array_filter($trips, fn($t) => $t['supervisor_review_status'] === 'approved')) ?>,
                rejected: <?= count(array_filter($trips, fn($t) => $t['supervisor_review_status'] === 'rejected')) ?>
            };

            // Create status bar chart
            new Chart(document.getElementById('statusChart'), {
                type: 'bar',
                data: {
                    labels: ['Pending', 'Approved', 'Rejected'],
                    datasets: [{
                        label: 'Trip Count',
                        data: [statusCounts.pending, statusCounts.approved, statusCounts.rejected],
                        backgroundColor: [
                            'rgba(234,179,8,0.6)', // yellow
                            'rgba(34,197,94,0.6)', // green
                            'rgba(239,68,68,0.6)' // red
                        ],
                        borderColor: [
                            'rgba(234,179,8,1)',
                            'rgba(34,197,94,1)',
                            'rgba(239,68,68,1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>

        <!-- Submit Trip Modal -->
        <dialog id="submit_trip_modal" class="modal">
            <div class="modal-box max-w-2xl">
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg mb-4">Submit Trip Data</h3>
                <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="space-y-4" onsubmit="return validateForm(this);">
                    <input type="hidden" name="submit_trip" value="1">
                    <div class="form-control mb-2">
                        <label class="label">Completed Dispatch</label>
                        <select name="dispatch_id" class="select select-bordered w-full" onchange="fillDispatchFields(this)" required>
                            <option value="">Select Completed Dispatch</option>
                            <?php foreach ($completedDispatches as $cd): ?>
                                <option value="<?= $cd['id'] ?>"
                                    data-driver_id="<?= $cd['driver_id'] ?>"
                                    data-vehicle_id="<?= $cd['vehicle_id'] ?>"
                                    data-trip_date="<?= substr($cd['dispatch_date'], 0, 10) ?>"
                                    data-vehicle_payload="<?= htmlspecialchars($cd['vehicle_payload']) ?>"> <?= htmlspecialchars($cd['dispatch_date']) ?> | <?= htmlspecialchars($cd['vehicle_name']) ?> | <?= htmlspecialchars($cd['driver_name']) ?> | <?= htmlspecialchars($cd['purpose']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label">Driver</label>
                            <select id="driver_id_field" name="driver_id" class="select select-bordered" required>
                                <option value="">Select Driver</option>
                                <?php foreach ($drivers as $driver): ?>
                                    <option value="<?= $driver['id'] ?>"><?= htmlspecialchars($driver['driver_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="driver_id_hidden" name="driver_id" disabled>
                        </div>
                        <div class="form-control">
                            <label class="label">Vehicle</label>
                            <select id="vehicle_id_field" name="vehicle_id" class="select select-bordered" required>
                                <option value="">Select Vehicle</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?= $vehicle['id'] ?>"><?= htmlspecialchars($vehicle['vehicle_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="vehicle_id_hidden" name="vehicle_id" disabled>
                        </div>
                        <div class="form-control">
                            <label class="label">Trip Date</label>
                            <input id="trip_date_field" type="date" name="trip_date" class="input input-bordered" required>
                        </div>
                        <div class="form-control">
                            <label class="label">Cargo Weight (kg)</label>
                            <input type="number" name="cargo_weight" step="0.01" min="0" class="input input-bordered" required>
                        </div>
                        <div class="form-control">
                            <label class="label">Vehicle Capacity (kg)</label>
                            <input id="vehicle_capacity_field" type="number" name="vehicle_capacity" step="0.01" min="0" class="input input-bordered" required>
                        </div>
                        <div class="form-control">
                            <label class="label">Start Time</label>
                            <input type="time" name="start_time" class="input input-bordered" required>

                        </div>
                        <div class="form-control">
                            <label class="label">End Time</label>
                            <input type="time" name="end_time" class="input input-bordered">
                        </div>
                        <div class="form-control">
                            <label class="label">Distance (km)</label>
                            <input type="number" name="distance_traveled" step="0.01" min="0" class="input input-bordered" required>
                        </div>
                        <div class="form-control">
                            <label class="label">Fuel Consumed (L)</label>
                            <input type="number" name="fuel_consumed" step="0.01" min="0" class="input input-bordered" required>
                        </div>
                        <div class="form-control">
                            <label class="label">Idle Time (minutes)</label>
                            <input type="number" name="idle_time" min="0" class="input input-bordered">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-full mt-6">Submit Trip Data</button>
                </form>
                <script>
                    function setAutofilledLocked(isLocked) {
                        var driverSel = document.getElementById('driver_id_field');
                        var vehicleSel = document.getElementById('vehicle_id_field');
                        var tripDate = document.getElementById('trip_date_field');
                        var capacity = document.getElementById('vehicle_capacity_field');

                        var driverHidden = document.getElementById('driver_id_hidden');
                        var vehicleHidden = document.getElementById('vehicle_id_hidden');

                        if (driverSel && driverHidden) {
                            driverSel.disabled = !!isLocked;
                            driverHidden.disabled = !isLocked;
                            driverHidden.value = driverSel.value || '';
                        }
                        if (vehicleSel && vehicleHidden) {
                            vehicleSel.disabled = !!isLocked;
                            vehicleHidden.disabled = !isLocked;
                            vehicleHidden.value = vehicleSel.value || '';
                        }

                        if (tripDate) {
                            tripDate.readOnly = !!isLocked;
                            tripDate.style.pointerEvents = isLocked ? 'none' : '';
                            tripDate.tabIndex = isLocked ? -1 : 0;
                        }
                        if (capacity) {
                            capacity.readOnly = !!isLocked;
                            capacity.style.pointerEvents = isLocked ? 'none' : '';
                            capacity.tabIndex = isLocked ? -1 : 0;
                        }
                    }

                    function fillDispatchFields(sel) {
                        var opt = sel.options[sel.selectedIndex];
                        if (!opt || !opt.dataset) return;

                        if (!sel.value) {
                            setAutofilledLocked(false);
                            return;
                        }

                        document.getElementById('driver_id_field').value = opt.dataset.driver_id || '';
                        document.getElementById('vehicle_id_field').value = opt.dataset.vehicle_id || '';
                        document.getElementById('trip_date_field').value = opt.dataset.trip_date || '';
                        document.getElementById('vehicle_capacity_field').value = opt.dataset.vehicle_payload || '';

                        setAutofilledLocked(true);
                    }
                </script>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>
        <!--end of add trip modal-->

        <!-- Trip Log Modal -->
        <dialog id="trip_log_modal" class="modal">
            <div class="modal-box w-11/12 max-w-5xl">
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg mb-4">Trip Records</h3>
                <?php
                // Pagination logic
                $page = isset($_GET['trip_page']) ? max(1, intval($_GET['trip_page'])) : 1;
                $perPage = 10;
                $totalTrips = count($trips);
                $totalPages = ceil($totalTrips / $perPage);
                $start = ($page - 1) * $perPage;
                $pagedTrips = array_slice($trips, $start, $perPage);
                ?>
                <form id="clearTripLogsForm" method="POST" action="<?= htmlspecialchars($baseURL) ?>">
                    <input type="hidden" name="clear_trip_logs" value="1">
                    <button type="button" class="btn btn-error mb-2" onclick="clearTripLogs()">Clear Log</button>
                </form>
                <script>
                    function clearTripLogs() {
                        if (!confirm('Clear all trip logs?')) return;
                        // If in AJAX modal, submit via AJAX
                        if (window.location.search.includes('ajax_trip_log=1')) {
                            var form = document.getElementById('clearTripLogsForm');
                            var xhr = new XMLHttpRequest();
                            xhr.open('POST', form.action, true);
                            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                            xhr.onload = function() {
                                if (xhr.status === 200) {
                                    // Reload modal content after clearing
                                    openTripLogPage(1);
                                }
                            };
                            var formData = new FormData(form);
                            xhr.send(formData);
                        } else {
                            document.getElementById('clearTripLogsForm').submit();
                        }
                    }
                </script>
                <!-- Trip Log Table -->
                <div class="overflow-x-auto">
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
                                                <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="space-y-4">
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
                                                <a href="<?= htmlspecialchars($baseURL . '&delete=' . $t['id']) ?>"
                                                    class="btn btn-sm btn-error"
                                                    onclick="return confirm('Are you sure you want to delete this trip record?')">
                                                    <i class="fas fa-trash-alt mr-2"></i> Delete
                                                </a>
                                            <?php endif; ?>
                                        </div>
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
                    <script>
                        function openTripLogPage(page) {
                            // AJAX fetch modal content for the selected page
                            var modal = document.getElementById('trip_log_modal');
                            var xhr = new XMLHttpRequest();
                            xhr.open('GET', '<?= htmlspecialchars($baseURL) ?>&trip_page=' + page + '&ajax_trip_log=1', true);
                            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                            xhr.onload = function() {
                                if (xhr.status === 200) {
                                    // Replace modal content
                                    var temp = document.createElement('div');
                                    temp.innerHTML = xhr.responseText;
                                    var newBox = temp.querySelector('.modal-box');
                                    if (newBox) {
                                        modal.querySelector('.modal-box').innerHTML = newBox.innerHTML;
                                    }
                                }
                            };
                            xhr.send();
                        }
                    </script>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>
    </div>
<?php
}
