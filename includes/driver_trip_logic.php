<?php
////////////////////////////////START OF DRIVER TRIP LOGIC
// Driver/vehicle filter logic
// Fetch driver and vehicle lists for filters
$drivers = fetchAll('drivers');
$vehicles = fetchAll('fleet_vehicles');
$filterDriver = isset($_GET['filter_driver']) ? $_GET['filter_driver'] : '';
$filterVehicle = isset($_GET['filter_vehicle']) ? $_GET['filter_vehicle'] : '';
$where = [];
$params = [];
if ($filterDriver) {
    $where[] = 't.driver_id = ?';
    $params[] = $filterDriver;
}
if ($filterVehicle) {
    $where[] = 't.vehicle_id = ?';
    $params[] = $filterVehicle;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$sql = "SELECT t.*, d.driver_name, v.vehicle_name FROM driver_trips t JOIN drivers d ON t.driver_id = d.id JOIN fleet_vehicles v ON t.vehicle_id = v.id $whereSql ORDER BY t.created_at DESC";
$trips = fetchAllQuery($sql, $params);

// AJAX trip log modal rendering (must be before any HTML output)
if (isset($_GET['ajax_trip_log']) && isset($_GET['trip_page']) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    $page = max(1, intval($_GET['trip_page']));
    $perPage = 10;
    $totalTrips = count($trips);
    $totalPages = ceil($totalTrips / $perPage);
    $start = ($page - 1) * $perPage;
    $pagedTrips = array_slice($trips, $start, $perPage);
?>
    <div class="modal-box max-w-xl">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
        </form>
        <h3 class="font-bold text-lg mb-4">Trip Log</h3>
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <button type="button" class="btn btn-error mb-2" onclick="if(confirm('Clear all trip logs?')) { document.getElementById('clearTripLogsForm').submit(); }">Clear Log</button>
            <div class="overflow-x-auto">
                <form id="clearTripLogsForm" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                    <input type="hidden" name="clear_trip_logs" value="1">
                </form>
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
                                            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="space-y-4">
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
                                            <a href="<?= htmlspecialchars($_SERVER['PHP_SELF'] . '&delete=' . $t['id']) ?>"
                                                class="btn btn-sm btn-error"
                                                onclick="return confirm('Are you sure you want to delete this trip record?')">
                                                <i class="fas fa-trash-alt mr-2"></i> Delete
                                            </a>
                                        <?php endif; ?>
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
                                    </div>
                                </td>
            </div>
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
    </div>
    </div>
<?php
    exit;
} {
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

function calculatePerformanceScore($tripData)
{
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
{ {
        if (isset($_GET['delete'])) {
            deleteData('driver_trips', $_GET['delete']);
            log_audit_event('DTP', 'delete_trip', $_GET['delete'], $_SESSION['full_name'] ?? 'unknown');
            header("Location: {$baseURL}");
            exit;
        }

        // Clear all trip logs
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_trip_logs'])) {
            global $conn;
            $conn->query("DELETE FROM driver_trips");
            log_audit_event('DTP', 'clear_trip_logs', null, $_SESSION['full_name'] ?? 'unknown');
            $_SESSION['success_message'] = 'All trip logs cleared.';
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
                    'idle_time' => !empty($_POST['idle_time']) ? intval($_POST['idle_time']) : 0,
                    'cargo_weight' => isset($_POST['cargo_weight']) ? floatval($_POST['cargo_weight']) : 0,
                    'vehicle_capacity' => isset($_POST['vehicle_capacity']) ? floatval($_POST['vehicle_capacity']) : 0
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
                        log_audit_event('DTP', 'add_trip', $id, $_SESSION['full_name'] ?? 'unknown');
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
}

// Handle Export Trip Data
if (isset($_POST['export_trip_data'])) {
    global $conn;
    $exportType = $_POST['export_type'] ?? 'all';
    $driverId = isset($_POST['driver_id']) ? intval($_POST['driver_id']) : null;
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="trip_data_export.csv"');
    $output = fopen('php://output', 'w');
    // CSV header
    fputcsv($output, ['Driver', 'Vehicle', 'Trip Date', 'Performance Score', 'Distance (km)', 'Fuel Consumed (L)', 'Idle Time (min)', 'Validation', 'Review Status']);
    $where = '';
    if ($exportType === 'driver' && $driverId) {
        $where = ' WHERE t.driver_id = ' . $driverId;
    }
    $sql = "SELECT t.*, d.driver_name, v.vehicle_name FROM driver_trips t JOIN drivers d ON t.driver_id = d.id JOIN fleet_vehicles v ON t.vehicle_id = v.id" . $where . " ORDER BY t.created_at DESC";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['driver_name'],
            $row['vehicle_name'],
            $row['trip_date'],
            $row['performance_score'],
            $row['distance_traveled'],
            $row['fuel_consumed'],
            $row['idle_time'],
            $row['validation_status'],
            $row['supervisor_review_status']
        ]);
    }
    fclose($output);
    exit;
}
////////////////////////////////END OF DRIVER TRIP LOGIC
?>