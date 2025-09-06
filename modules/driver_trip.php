<?php
// DRIVER AND TRIP PERFORMANCE MONITORING
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/audit_log.php';
function validateTripData($data) {
    $errors = [];
    
    // Check for required fields
    $required_fields = ['driver_id', 'vehicle_id', 'trip_date', 'start_time', 'distance_traveled', 'fuel_consumed'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[] = "The {$field} field is required.";
        }
    }

    // Validate numeric values
    if (isset($data['distance_traveled']) && $data['distance_traveled'] <= 0) {
        $errors[] = "Distance traveled must be greater than 0.";
    }
    if (isset($data['fuel_consumed']) && $data['fuel_consumed'] <= 0) {
        $errors[] = "Fuel consumed must be greater than 0.";
    }
    if (isset($data['idle_time']) && $data['idle_time'] < 0) {
        $errors[] = "Idle time cannot be negative.";
    }

    // Validate dates
    if (isset($data['trip_date']) && strtotime($data['trip_date']) > time()) {
        $errors[] = "Trip date cannot be in the future.";
    }
    if (isset($data['start_time']) && strtotime($data['start_time']) > time()) {
        $errors[] = "Start time cannot be in the future.";
    }
    if (isset($data['end_time'])) {
        if (strtotime($data['end_time']) > time()) {
            $errors[] = "End time cannot be in the future.";
        }
        if (strtotime($data['end_time']) < strtotime($data['start_time'])) {
            $errors[] = "End time cannot be before start time.";
        }
    }

    return $errors;
}

function calculatePerformanceScore($tripData) {
    // Calculate based on various metrics
    $score = 100; // Start with perfect score
    
    // Fuel efficiency (km/l)
    $fuelEfficiency = $tripData['distance_traveled'] / $tripData['fuel_consumed'];
    $expectedEfficiency = 10; // Example: 10 km/l is the baseline
    $score -= max(0, ($expectedEfficiency - $fuelEfficiency) * 5);

    // Idle time penalty
    if ($tripData['idle_time'] > 30) { // More than 30 minutes idle
        $score -= min(20, ($tripData['idle_time'] - 30) / 10);
    }

    // Speed compliance
    if ($tripData['average_speed'] > 80) { // Example: Speed limit is 80 km/h
        $score -= min(20, ($tripData['average_speed'] - 80) * 2);
    }

    return max(0, min(100, $score));
}

function driver_trip_logic($baseURL)
{
    if (isset($_GET['delete'])) {
        deleteData('driver_trips', $_GET['delete']);
        log_audit_event('DTP', 'delete_trip', $_GET['delete'], $_SESSION['username'] ?? 'unknown');
        header("Location: {$baseURL}");
        exit;
    }

    // Step 1 & 2: Driver Submits Data & System Validation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_trip'])) {
        try {
            // Debug information
            error_log('Received POST data: ' . print_r($_POST, true));

            $tripData = [
                'driver_id' => intval($_POST['driver_id']),
                'vehicle_id' => intval($_POST['vehicle_id']),
                'trip_date' => $_POST['trip_date'],
                'start_time' => $_POST['start_time'],
                'end_time' => !empty($_POST['end_time']) ? $_POST['end_time'] : null,
                'distance_traveled' => floatval($_POST['distance_traveled']),
                'fuel_consumed' => floatval($_POST['fuel_consumed']),
                'idle_time' => !empty($_POST['idle_time']) ? intval($_POST['idle_time']) : 0
            ];

            // Calculate average speed only if we have both times
            if (!empty($_POST['end_time'])) {
                $duration = abs(strtotime($_POST['end_time']) - strtotime($_POST['start_time']));
                if ($duration > 0) {
                    $tripData['average_speed'] = ($tripData['distance_traveled'] / $duration) * 3600;
                }
            }

            // Debug information
            error_log('Processed trip data: ' . print_r($tripData, true));

            // Validate the data
            $validationErrors = validateTripData($tripData);
            
            if (empty($validationErrors)) {
                // Step 4: Data Storage and Processing
                $tripData['validation_status'] = 'valid';
                $tripData['performance_score'] = calculatePerformanceScore($tripData);
                
                $result = insertData('driver_trips', $tripData);
                if ($result) {
                    global $conn;
                    $id = $conn->insert_id;
                    log_audit_event('DTP', 'add_trip', $id, $_SESSION['username'] ?? 'unknown');
                    $_SESSION['success_message'] = "Trip data submitted successfully.";
                    error_log('Trip data inserted successfully');
                } else {
                    $_SESSION['error_message'] = "Failed to save trip data. Please try again.";
                    error_log('Failed to insert trip data');
                }
            } else {
                // Step 3: Invalid Data Handling
                $tripData['validation_status'] = 'invalid';
                $tripData['validation_message'] = implode(", ", $validationErrors);
                $_SESSION['error_message'] = "Please correct the following errors: " . implode(", ", $validationErrors);
                error_log('Validation errors: ' . implode(", ", $validationErrors));
            }
        } catch (Exception $e) {
            error_log('Error processing trip data: ' . $e->getMessage());
            $_SESSION['error_message'] = "An error occurred while processing your request. Please try again.";
        }
        
        header("Location: {$baseURL}");
        exit;
    }

    // Step 5 & 6: Management Review
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_trip'])) {
        $tripId = $_POST['trip_id'];
        $reviewStatus = $_POST['review_status'];
        $remarks = $_POST['supervisor_remarks'];

        updateData('driver_trips', $tripId, [
            'supervisor_review_status' => $reviewStatus,
            'supervisor_remarks' => $remarks
        ]);

        $_SESSION['success_message'] = "Trip review updated successfully.";
        header("Location: {$baseURL}");
        exit;
    }
}

function driver_trip_view($baseURL)
{
    // Fetch required data
    $trips = fetchAllQuery("
        SELECT 
            t.*,
            d.driver_name,
            v.vehicle_name
        FROM driver_trips t
        JOIN drivers d ON t.driver_id = d.id
        JOIN fleet_vehicles v ON t.vehicle_id = v.id
        ORDER BY t.created_at DESC
    ");
    $drivers = fetchAll('drivers');
    $vehicles = fetchAll('fleet_vehicles');
    // Fetch completed dispatches for trip submission
    $completedDispatches = fetchAllQuery("SELECT d.*, v.vehicle_name, dr.driver_name FROM dispatches d JOIN fleet_vehicles v ON d.vehicle_id = v.id JOIN drivers dr ON d.driver_id = dr.id WHERE d.status = 'Completed' ORDER BY d.dispatch_date DESC");

    // Calculate overall statistics
    $totalTrips = count($trips);
    $avgScore = array_reduce($trips, function($carry, $trip) {
        return $carry + $trip['performance_score'];
    }, 0) / max(1, $totalTrips);
?>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold">Driver & Trip Performance</h2>
            <button class="btn btn-primary" onclick="submit_trip_modal.showModal()">Submit Trip Data</button>
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

            // Create status doughnut chart
            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Approved', 'Rejected'],
                    datasets: [{
                        data: Object.values(statusCounts),
                        backgroundColor: [
                            'rgb(var(--wa))',
                            'rgb(var(--su))',
                            'rgb(var(--er))'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
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
                                    data-trip_date="<?= substr($cd['dispatch_date'],0,10) ?>"
                                >
                                    <?= htmlspecialchars($cd['dispatch_date']) ?> | <?= htmlspecialchars($cd['vehicle_name']) ?> | <?= htmlspecialchars($cd['driver_name']) ?> | <?= htmlspecialchars($cd['purpose']) ?>
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
                        </div>
                        <div class="form-control">
                            <label class="label">Vehicle</label>
                            <select id="vehicle_id_field" name="vehicle_id" class="select select-bordered" required>
                                <option value="">Select Vehicle</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?= $vehicle['id'] ?>"><?= htmlspecialchars($vehicle['vehicle_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label">Trip Date</label>
                            <input id="trip_date_field" type="date" name="trip_date" class="input input-bordered" required>
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
                function fillDispatchFields(sel) {
                    var opt = sel.options[sel.selectedIndex];
                    if (!opt || !opt.dataset) return;
                    document.getElementById('driver_id_field').value = opt.dataset.driver_id || '';
                    document.getElementById('vehicle_id_field').value = opt.dataset.vehicle_id || '';
                    document.getElementById('trip_date_field').value = opt.dataset.trip_date || '';
                }
                </script>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>

        <!-- Trip Records -->
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
                    <?php foreach ($trips as $t): ?>
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
                                    <button class="btn btn-sm btn-warning" 
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
                                    <button class="btn btn-sm btn-info" 
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
    </div>
<?php
}
