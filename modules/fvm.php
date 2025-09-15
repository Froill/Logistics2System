<?php
//FLEET & VEHICLE MANAGEMENT MODULE
// Manages fleet vehicles, their statuses, and logs (maintenance, fuel, etc.)
require_once __DIR__ . '/audit_log.php';
require_once __DIR__ . '/../includes/modules_logic.php';

function fvm_view($baseURL)
{
    $vehicles = fetchAll('fleet_vehicles');
    $drivers = fetchAll('drivers');
    // Fetch all logs for each vehicle
    $vehicle_logs = fetchAllQuery("SELECT l.*, v.vehicle_name FROM fleet_vehicle_logs l JOIN fleet_vehicles v ON l.vehicle_id = v.id ORDER BY l.created_at DESC");

    $totalVehicles = count($vehicles);
    $activeCount = count(array_filter($vehicles, fn($v) => $v['status'] === 'Active'));
    $inactiveCount = count(array_filter($vehicles, fn($v) => $v['status'] === 'Inactive'));
    $maintenanceCount = count(array_filter($vehicles, fn($v) => $v['status'] === 'Under Maintenance'));
    $dispatchedCount = count(array_filter($vehicles, fn($v) => $v['status'] === 'Dispatched')); // if applicable
?>
    <div>
        <h2 class="text-2xl font-bold mb-4">Fleet & Vehicle Management</h2>
        <!-- <?php // Show debug message outside modals, at the top of the main content
                if (!empty($_SESSION['fvm_debug'])): ?>
    <div class="alert alert-info mb-2"><?php echo $_SESSION['fvm_debug'];
                                        unset($_SESSION['fvm_debug']); ?></div>
<?php endif; ?> -->
        <!-- Vehicle Logs Modal (Paginated) -->
        <dialog id="vehicle_logs_modal" class="modal">
            <div class="modal-box w-11/12 max-w-5xl">
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg mb-4">Vehicle Logs</h3>
                <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="mb-4">
                    <input type="hidden" name="clear_maintenance_logs" value="1">
                    <button type="submit" class="btn btn-error" onclick="return confirm('Clear all maintenance logs?')">Clear Maintenance Logs</button>
                </form>
                <?php
                // Pagination logic
                $page = isset($_GET['log_page']) ? max(1, intval($_GET['log_page'])) : 1;
                $perPage = 10;
                $totalLogs = count($vehicle_logs);
                $totalPages = ceil($totalLogs / $perPage);
                $start = ($page - 1) * $perPage;
                $pagedLogs = array_slice($vehicle_logs, $start, $perPage);
                ?>
                <?php
                // Separate upcoming and past maintenance logs
                $upcoming = [];
                $past = [];
                $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
                foreach ($pagedLogs as $log) {
                    if ($log['log_type'] === 'maintenance') {
                        $logDate = new DateTime($log['created_at'], new DateTimeZone('Asia/Manila'));
                        if ($logDate > $now) {
                            $upcoming[] = $log;
                        } else {
                            $past[] = $log;
                        }
                    } else {
                        $past[] = $log;
                    }
                }
                ?>
                <div class="overflow-x-auto mb-6">
                    <h4 class="font-semibold text-md mb-2">Upcoming Maintenance Schedule</h4>
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
                            <?php if (count($upcoming) === 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center opacity-50">No upcoming maintenance scheduled.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($upcoming as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['vehicle_name']) ?></td>
                                        <td>
                                            <span class="badge badge-warning">
                                                <?= ucfirst(htmlspecialchars($log['log_type'])) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($log['details']) ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="overflow-x-auto">
                    <h4 class="font-semibold text-md mb-2">Recent & Past Maintenance Records</h4>
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
                            <?php if (count($past) === 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center opacity-50">No past maintenance records.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($past as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['vehicle_name']) ?></td>
                                        <td>
                                            <span class="badge font-bold <?= $log['log_type'] === 'maintenance' ? 'badge-warning' : 'badge-info' ?>">
                                                <?= ucfirst(htmlspecialchars($log['log_type'])) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($log['details']) ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination Controls -->
                <div class="flex justify-center mt-4 gap-2">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="<?= htmlspecialchars($baseURL . '&log_page=' . $p) ?>" class="btn btn-xs <?= $p == $page ? 'btn-primary' : 'btn-outline' ?>">Page <?= $p ?></a>
                    <?php endfor; ?>
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
                <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="mb-6 flex flex-col" enctype="multipart/form-data">
                    <div class="form-control mb-2">
                        <label class="label">Vehicle Name</label>
                        <input type="text" name="vehicle_name" class="input input-bordered" required>
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Plate Number</label>
                        <input type="text" name="plate_number" class="input input-bordered" required>
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Vehicle Type</label>
                        <select name="vehicle_type" class="select select-bordered" required>
                            <option value="">Select type</option>
                            <option value="Car">Car</option>
                            <option value="Van">Van</option>
                            <option value="Truck">Truck</option>
                            <option value="Pickup">Pickup</option>
                        </select>
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Payload (kg)</label>
                        <input type="number" name="weight_capacity" class="input input-bordered" min="0" step="any" required>
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Fuel Capacity (L)</label>
                        <input type="number" name="fuel_capacity" class="input input-bordered" min="0" step="any" required>
                    </div>
                    <div class="form-control mb-2">
                        <label class="label">Upload Image</label>
                        <input type="file" name="vehicle_image" accept="image/*" class="file-input file-input-bordered">
                    </div>
                    <button class="btn btn-primary mt-2 btn-outline w-full">Add Vehicle</button>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Vehicle Status Pie Chart -->
            <div class="card shadow-lg p-4">
                <h3 class="text-lg font-bold mb-2">Vehicle Status Distribution</h3>
                <canvas id="vehicleStatusChart"></canvas>
            </div>

            <!-- Vehicle Metrics -->
            <div class="card shadow-lg p-4">
                <h3 class="text-lg font-bold mb-2">Key Metrics</h3>
                <ul class="space-y-2">
                    <li>Total Vehicles: <span class="font-semibold"><?= $totalVehicles ?></span></li>
                    <li>Active: <span class="text-green-600 font-semibold"><?= $activeCount ?></span></li>
                    <li>Inactive: <span class="text-red-600 font-semibold"><?= $inactiveCount ?></span></li>
                    <li>Dispatched: <span class="text-blue-600 font-semibold"><?= $dispatchedCount ?></span></li>
                    <li>Under Maintenance: <span class="text-yellow-600 font-semibold"><?= $maintenanceCount ?></span></li>
                </ul>
            </div>
        </div>

        <?php if (!empty($_SESSION['fvm_success'])): ?>
            <div class="alert alert-success mb-3">
                <?= htmlspecialchars($_SESSION['fvm_success']) ?>
            </div>
            <?php unset($_SESSION['fvm_success']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['fvm_error'])): ?>
            <div class="alert alert-error mb-3">
                <?= htmlspecialchars($_SESSION['fvm_error']) ?>
            </div>
            <?php unset($_SESSION['fvm_error']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['fvm_success'])): ?>
            <div class="alert alert-success mb-3">
                <?= htmlspecialchars($_SESSION['fvm_success']) ?>
            </div>
            <?php unset($_SESSION['fvm_success']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['fvm_error'])): ?>
            <div class="alert alert-error mb-3">
                <?= htmlspecialchars($_SESSION['fvm_error']) ?>
            </div>
            <?php unset($_SESSION['fvm_error']); ?>
        <?php endif; ?>
        <div class="flex flex-col md:flex-row gap-2 mb-3">
            <!-- Add Vehicle Button -->
            <button class="btn btn-soft btn-primary" onclick="fvm_modal.showModal()">
                <i data-lucide="plus" class="w-4 h-4 mr-1"></i> Add Vehicle
            </button>
            <!-- Schedule Maintenance Button -->
            <button class="btn btn-secondary" onclick="schedule_maintenance_modal.showModal()">
                <i data-lucide="calendar" class="w-4 h-4 mr-1"></i> Schedule Maintenance
            </button>
            <!-- View Vehicle Logs Button -->
            <button class="btn btn-soft btn-info" onclick="vehicle_logs_modal.showModal()">
                <i data-lucide="clipboard-list" class="w-4 h-4 mr-1"></i> View Maintenance Logs
            </button>
        </div>

        <!-- Schedule Maintenance Modal -->
        <dialog id="schedule_maintenance_modal" class="modal">
            <div class="modal-box w-11/12 max-w-3xl">
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg mb-4">Scheduled Maintenance Calendar</h3>
                <?php
                // Gather all upcoming maintenance dates for all vehicles
                $maintenanceDates = [];
                foreach ($vehicles as $v) {
                    $lastMaint = null;
                    foreach ($vehicle_logs as $log) {
                        if ($log['vehicle_id'] == $v['id'] && $log['log_type'] === 'maintenance') {
                            $lastMaint = $log;
                            break;
                        }
                    }
                    $nextMaint = null;
                    if ($lastMaint) {
                        $lastDate = new DateTime($lastMaint['created_at'], new DateTimeZone('Asia/Manila'));
                        $nextMaint = $lastDate->modify('+1 month');
                        $maintenanceDates[$nextMaint->format('Y-m-d')][] = [
                            'vehicle_name' => $v['vehicle_name'],
                            'plate_number' => $v['plate_number'],
                            'vehicle_type' => $v['vehicle_type'],
                        ];
                    }
                }
                ?>
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <button id="calPrevBtn" class="btn btn-xs btn-outline">&lt; Prev</button>
                        <span id="calMonthLabel" class="font-semibold text-lg"></span>
                        <button id="calNextBtn" class="btn btn-xs btn-outline">Next &gt;</button>
                    </div>
                    <div id="calendarContainer"></div>
                </div>
                <script>
                    // Calendar JS logic
                    const maintenanceDates = <?php echo json_encode($maintenanceDates); ?>;
                    const today = new Date();
                    let calMonth = today.getMonth(); // 0-based
                    let calYear = today.getFullYear();

                    function escapeHtml(text) {
                        return text.replace(/[&<>"']/g, function(m) {
                            return ({
                                '&': '&amp;',
                                '<': '&lt;',
                                '>': '&gt;',
                                '"': '&quot;',
                                '\'': '&#39;'
                            } [m]);
                        });
                    }

                    function renderCalendar(month, year) {
                        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                        document.getElementById('calMonthLabel').textContent = monthNames[month] + ' ' + year;
                        const firstDay = new Date(year, month, 1);
                        const startDayOfWeek = firstDay.getDay();
                        const daysInMonth = new Date(year, month + 1, 0).getDate();
                        let html = '<table class="table table-compact w-full border"><thead><tr>';
                        ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].forEach(d => html += '<th class="text-center">' + d + '</th>');
                        html += '</tr></thead><tbody>';
                        let day = 1;
                        for (let row = 0; day <= daysInMonth; row++) {
                            html += '<tr>';
                            for (let col = 0; col < 7; col++) {
                                if (row === 0 && col < startDayOfWeek) {
                                    html += '<td></td>';
                                } else if (day > daysInMonth) {
                                    html += '<td></td>';
                                } else {
                                    const dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                                    const highlight = maintenanceDates[dateStr];
                                    const isToday = (day === today.getDate() && month === today.getMonth() && year === today.getFullYear());
                                    let tdStyle = 'vertical-align:top;';
                                    if (highlight) tdStyle += 'background:#fef08a;';
                                    if (isToday) tdStyle += 'background:#bbf7d0;'; // green shade for today
                                    html += '<td class="text-center align-top" style="' + tdStyle + '">';
                                    html += '<div class="font-bold">' + day + '</div>';
                                    if (highlight) {
                                        html += '<button class="btn btn-xs btn-info mt-1 maint-show-btn" style="font-size:10px;" data-maint="' + escapeHtml(JSON.stringify(highlight)) + '">Show</button>';
                                    }
                                    html += '</td>';
                                    day++;
                                }
                            }
                            html += '</tr>';
                        }
                        html += '</tbody></table>';
                        document.getElementById('calendarContainer').innerHTML = html;

                        // Attach event listeners for show buttons
                        document.querySelectorAll('.maint-show-btn').forEach(btn => {
                            btn.onclick = function(ev) {
                                showMaintPopup(ev, btn.getAttribute('data-maint'));
                            };
                        });
                    }

                    // Popup logic
                    function showMaintPopup(e, dataStr) {
                        e.stopPropagation();
                        let data;
                        try {
                            data = JSON.parse(dataStr);
                        } catch {
                            return;
                        }
                        let html = '<div style="padding:10px 16px;">';
                        html += '<div class="font-bold mb-1">Scheduled Maintenance:</div>';
                        data.forEach(info => {
                            html += '<div class="mb-1">' +
                                '<span class="font-semibold">' + escapeHtml(info.vehicle_name) + '</span>' +
                                ' <span class="text-xs opacity-50">(' + escapeHtml(info.plate_number) + ')</span>' +
                                '</div>';
                        });
                        html += '</div>';
                        let popup = document.getElementById('maintPopup');
                        if (!popup) {
                            popup = document.createElement('div');
                            popup.id = 'maintPopup';
                            popup.style.position = 'fixed';
                            popup.style.zIndex = 99999; // Higher than modal
                            popup.style.background = '#fff';
                            popup.style.border = '1px solid #888';
                            popup.style.borderRadius = '8px';
                            popup.style.boxShadow = '0 2px 12px rgba(0,0,0,0.15)';
                            popup.onclick = function(ev) {
                                ev.stopPropagation();
                            };
                            document.body.appendChild(popup);
                        }
                        popup.innerHTML = html + '<div class="text-center mt-2"><button class="btn btn-xs btn-outline" onclick="closeMaintPopup()">Close</button></div>';
                        popup.style.display = 'block';
                        // Position popup near mouse
                        popup.style.left = (e.clientX + 10) + 'px';
                        popup.style.top = (e.clientY + 10) + 'px';
                        // Hide on outside click
                        document.body.onclick = function() {
                            closeMaintPopup();
                        };
                    }

                    function closeMaintPopup() {
                        let popup = document.getElementById('maintPopup');
                        if (popup) popup.style.display = 'none';
                        document.body.onclick = null;
                    }

                    document.getElementById('calPrevBtn').onclick = function(e) {
                        e.preventDefault();
                        calMonth--;
                        if (calMonth < 0) {
                            calMonth = 11;
                            calYear--;
                        }
                        renderCalendar(calMonth, calYear);
                    };
                    document.getElementById('calNextBtn').onclick = function(e) {
                        e.preventDefault();
                        calMonth++;
                        if (calMonth > 11) {
                            calMonth = 0;
                            calYear++;
                        }
                        renderCalendar(calMonth, calYear);
                    };
                    renderCalendar(calMonth, calYear);
                </script>
                <!-- Maintenance Adjustment Table -->
                <h3 class="font-bold text-lg mb-4">Adjust Maintenance Dates</h3>
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>Vehicle Name</th>
                                <th>Plate Number</th>
                                <th>Car Type</th>
                                <th>Next Maintenance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Ensure $today is defined for this scope
                            $today = new DateTime('now', new DateTimeZone('Asia/Manila'));
                            foreach ($vehicles as $v):
                                $lastMaint = null;
                                foreach ($vehicle_logs as $log) {
                                    if ($log['vehicle_id'] == $v['id'] && $log['log_type'] === 'maintenance') {
                                        $lastMaint = $log;
                                        break;
                                    }
                                }
                                $nextMaint = null;
                                if ($lastMaint) {
                                    $lastDate = new DateTime($lastMaint['created_at'], new DateTimeZone('Asia/Manila'));
                                    $nextMaint = $lastDate->modify('+1 month');
                                }
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($v['vehicle_name']) ?></td>
                                    <td><?= htmlspecialchars($v['plate_number']) ?></td>
                                    <td><?= htmlspecialchars($v['vehicle_type'] ?? '-') ?></td>
                                    <td><?= $nextMaint ? $nextMaint->format('M d, Y') : '<span class="opacity-50">No record</span>' ?></td>
                                    <td>
                                        <?php
                                        if ($nextMaint && $today >= $nextMaint) {
                                            echo '<span title="Needs Maintenance"><i data-lucide="alert-triangle" class="text-red-600" style="width:28px;height:28px;vertical-align:middle;"></i></span>';
                                        } else {
                                            echo '<span title="OK"><i data-lucide="check-circle" class="text-green-600" style="width:28px;height:28px;vertical-align:middle;"></i></span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-xs btn-warning" title="Adjust Maintenance Date" onclick="document.getElementById('adjust_maint_modal_<?= $v['id'] ?>').showModal()">
                                            <i data-lucide="calendar-clock"></i>
                                        </button>
                                        <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" style="display:inline">
                                            <input type="hidden" name="check_status_vehicle_id" value="<?= $v['id'] ?>">
                                            <button type="submit" class="btn btn-xs btn-success ml-1" title="Check Status">
                                                <i data-lucide="file-check-2"></i>
                                            </button>
                                        </form>
                                        <!-- Adjust Maintenance Modal -->
                                        <dialog id="adjust_maint_modal_<?= $v['id'] ?>" class="modal">
                                            <div class="modal-box">
                                                <form method="dialog">
                                                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                                                </form>
                                                <h3 class="font-bold text-lg mb-4">Adjust Maintenance Date</h3>
                                                <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="flex flex-col gap-4" enctype="multipart/form-data">
                                                    <input type="hidden" name="adjust_maintenance_vehicle_id" value="<?= $v['id'] ?>">
                                                    <div class="form-control">
                                                        <label class="label">Set Next Maintenance Date</label>
                                                        <input type="date" name="next_maintenance_date" class="input input-bordered" required>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary">Save</button>
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
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>


        <!-- Vehicle Table -->
        <div class="overflow-x-auto">
            <h3 class="text-lg font-bold mb-2">Fleet Vehicles</h3>
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Vehicle Name</th>
                        <th>License & Type</th>
                        <th>Payload (kg)</th>
                        <th>Fuel Capacity (L)</th>
                        <th>Fuel Consumption (Last Dispatch)</th>
                        <th>Current Fuel Tank</th>
                        <th>Status</th>
                        <th>Actions</th>
                        <th>Logs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $v): ?>
                        <tr>
                            <td><?= htmlspecialchars($v['vehicle_name']) ?></td>
                            <td>
                                <div><?= htmlspecialchars($v['plate_number']) ?></div>
                                <div><?= htmlspecialchars($v['vehicle_type'] ?? '-') ?></div>
                            </td>
                            <td><?= htmlspecialchars($v['weight_capacity'] ?? '-') ?>kg</td>
                            <td><?= htmlspecialchars($v['fuel_capacity'] ?? '-') ?>L</td>
                            <td class="text-center">
                                <?php
                                $trip = fetchOneQuery(
                                    "SELECT fuel_consumed FROM driver_trips WHERE vehicle_id = ? ORDER BY id DESC LIMIT 1",
                                    [$v['id']]
                                );
                                $fuelCapacity = isset($v['fuel_capacity']) && is_numeric($v['fuel_capacity']) ? floatval($v['fuel_capacity']) : 0;

                                if ($trip && isset($trip['fuel_consumed']) && $fuelCapacity > 0):
                                    $fuelConsumed = floatval($trip['fuel_consumed']);
                                    $percent = min(100, max(0, round(($fuelConsumed / $fuelCapacity) * 100)));
                                ?>
                                    <div class="flex flex-col gap-2 items-center font-bold text-info">
                                        <div class=""><?= htmlspecialchars($fuelConsumed) ?> L / <?= htmlspecialchars($fuelCapacity) ?> L</div>
                                        <div class="radial-progress bg-base-100" style="--value:<?= $percent ?>; --size:4rem; --thickness:6px;">
                                            <?= $percent ?>%
                                        </div>
                                    </div>
                                <?php elseif ($trip && isset($trip['fuel_consumed'])): ?>
                                    <div class="flex flex-col gap-2 items-center font-bold text-info">
                                        <div class=""><?= htmlspecialchars($trip['fuel_consumed']) ?> L / N/A</div>
                                        <div class="radial-progress" style="--value:0; --size:4rem; --thickness:6px;">0%</div>
                                    </div>
                                <?php else: ?>
                                    <div class="flex flex-col gap-2 items-center font-bold text-info">
                                        <div class="">No Records Yet</div>
                                        <div class="radial-progress" style="--value:0; --size:4rem; --thickness:6px;">0%</div>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td class="text-center">
                                <?php
                                if ($trip && isset($trip['fuel_consumed']) && $fuelCapacity > 0):
                                    $fuelConsumed = floatval($trip['fuel_consumed']);
                                    $fuelLeft = max(0, $fuelCapacity - $fuelConsumed);
                                    $percentLeft = min(100, max(0, round(($fuelLeft / $fuelCapacity) * 100)));
                                ?>
                                    <div class="flex flex-col gap-2 items-center font-bold text-success">
                                        <div><?= htmlspecialchars($fuelLeft) ?> L / <?= htmlspecialchars($fuelCapacity) ?> L</div>
                                        <div class="radial-progress" style="--value:<?= $percentLeft ?>; --size:4rem; --thickness:6px;">
                                            <?= $percentLeft ?>%
                                        </div>
                                    </div>
                                <?php elseif ($fuelCapacity > 0): ?>
                                    <div class="flex flex-col gap-2 items-center font-bold text-success">
                                        <div><?= htmlspecialchars($fuelCapacity) ?> L / <?= htmlspecialchars($fuelCapacity) ?> L</div>
                                        <div class="radial-progress text-success" style="--value:100; --size:4rem; --thickness:6px;">100%</div>
                                    </div>
                                <?php else: ?>
                                    <div class="flex flex-col gap-2 items-center font-bold text-success">

                                        <div class="">N/A</div>
                                        <div class="radial-progress text-success" style="--value:0; --size:4rem; --thickness:6px;">0%</div>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php
                                $status = $v['status'];
                                $badgeClass = 'badge p-3 font-bold text-nowrap';
                                if ($status === 'Active') {
                                    $badgeClass .= ' badge-success';
                                } elseif ($status === 'Inactive') {
                                    $badgeClass .= ' badge-error ';
                                } elseif ($status === 'Under Maintenance') {
                                    $badgeClass .= ' badge-warning';
                                } else {
                                    $badgeClass .= ' badge-secondary';
                                }
                                ?>
                                <span class="<?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
                            </td>
                            <td>
                                <div class="flex flex-col gap-3 ">
                                    <button class="btn btn-sm btn-info" onclick="document.getElementById('view_modal_<?= $v['id'] ?>').showModal()" title="View">
                                        <i data-lucide="eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-primary" onclick="document.getElementById('manage_modal_<?= $v['id'] ?>').showModal()" title="Edit">
                                        <i data-lucide="pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-error"
                                        title="Delete"
                                        onclick="if (confirm('Delete this vehicle?')) { window.location.href='<?= htmlspecialchars($baseURL . '&delete=' . $v['id']) ?>'; }">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </div>


                                <!-- View Vehicle Modal -->
                                <dialog id="view_modal_<?= $v['id'] ?>" class="modal">
                                    <div class="modal-box">
                                        <form method="dialog">
                                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                                        </form>
                                        <h3 class="font-bold text-lg mb-4">Vehicle Information</h3>
                                        <div class="mb-2"><strong>Name:</strong> <?= htmlspecialchars($v['vehicle_name']) ?></div>
                                        <div class="mb-2"><strong>Plate Number:</strong> <?= htmlspecialchars($v['plate_number']) ?></div>
                                        <div class="mb-2"><strong>Status:</strong> <?= htmlspecialchars($v['status']) ?></div>
                                        <?php if (!empty($v['vehicle_image'])): ?>
                                            <div class="mb-2"><strong>Image:</strong><br>
                                                <img src="<?= htmlspecialchars($v['vehicle_image']) ?>" alt="Vehicle Image" style="max-width: 220px; max-height: 160px; border-radius: 8px; border: 1px solid #ccc;" />
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <form method="dialog" class="modal-backdrop">
                                        <button>close</button>
                                    </form>
                                </dialog>

                                <!-- Manage Vehicle Modal -->
                                <dialog id="manage_modal_<?= $v['id'] ?>" class="modal">
                                    <div class="modal-box">
                                        <form method="dialog">
                                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                                        </form>
                                        <h3 class="font-bold text-lg mb-4">Vehicle Details</h3>
                                        <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="flex flex-col gap-4" enctype="multipart/form-data">
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
                                                <label class="label">Car Type</label>
                                                <select name="vehicle_type" class="select select-bordered" required>
                                                    <option value="Car" <?= ($v['vehicle_type'] === 'Car') ? 'selected' : '' ?>>Car</option>
                                                    <option value="Van" <?= ($v['vehicle_type'] === 'Van') ? 'selected' : '' ?>>Van</option>
                                                    <option value="Truck" <?= ($v['vehicle_type'] === 'Truck') ? 'selected' : '' ?>>Truck</option>
                                                    <option value="Pickup" <?= ($v['vehicle_type'] === 'Pickup') ? 'selected' : '' ?>>Pickup</option>
                                                </select>
                                            </div>
                                            <div class="form-control">
                                                <label class="label">Weight Capacity (kg)</label>
                                                <input type="number" name="weight_capacity" class="input input-bordered"
                                                    value="<?= htmlspecialchars($v['weight_capacity']) ?>" min="0" step="any" required>
                                            </div>
                                            <div class="form-control">
                                                <label class="label">Fuel Capacity (L)</label>
                                                <input type="number" name="fuel_capacity" class="input input-bordered"
                                                    value="<?= htmlspecialchars($v['fuel_capacity']) ?>" min="0" step="any" required>
                                            </div>
                                            <div class="form-control">
                                                <label class="label">Update Image</label>
                                                <input type="file" name="vehicle_image" accept="image/*" class="file-input file-input-bordered w-full" />
                                                <?php if (!empty($v['vehicle_image'])): ?>
                                                    <div class="mt-2">
                                                        <img src="<?= htmlspecialchars($v['vehicle_image']) ?>" alt="Vehicle Image" style="max-width: 120px; max-height: 80px; border-radius: 6px; border: 1px solid #ccc;" />
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="form-control">
                                                <label class="label">Status</label>
                                                <select name="status" class="select select-bordered" required>
                                                    <option value="Active" <?= $v['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                                    <option value="Inactive" <?= $v['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                                    <option value="Under Maintenance" <?= $v['status'] === 'Under Maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                                                </select>
                                            </div>


                                            <div class="flex gap-2 mt-4">
                                                <button type="submit" class="btn btn-primary flex-1">Update Vehicle</button>
                                                <a href="<?= htmlspecialchars($baseURL . '&delete=' . $v['id']) ?>"
                                                    class="btn btn-error"
                                                    onclick="return confirm('Are you sure you want to delete this vehicle? This action cannot be undone.')">Delete</a>
                                            </div>
                                            <?php // Show debug message outside modals, at the top of the main content
                                            if (!empty($_SESSION['fvm_debug'])): ?>
                                                <div class="alert alert-info mb-2"><?php echo $_SESSION['fvm_debug'];
                                                                                    unset($_SESSION['fvm_debug']); ?></div>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                    <form method="dialog" class="modal-backdrop">
                                        <button>close</button>
                                    </form>
                                </dialog>
                            </td>
                            <td>
                                <!-- Add Log Button -->
                                <button class="btn btn-sm btn-success" onclick="document.getElementById('log_modal_<?= $v['id'] ?>').showModal()" title="Add Log">
                                    <i data-lucide="plus"></i>
                                </button>
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

        <!-- Drivers Table -->
        <div class="overflow-x-auto mt-8">
            <h3 class="text-lg font-bold mb-2">Drivers</h3>
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Assigned Vehicle</th>
                        <th>Contact </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($drivers as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['driver_name']) ?></td>
                            <td>
                                <?php
                                $status = $d['status'] ?? 'Unknown';
                                $badgeClass = 'badge p-2 font-bold text-nowrap';
                                if ($status === 'Available') {
                                    $badgeClass .= ' badge-success';
                                } elseif ($status === 'Dispatched') {
                                    $badgeClass .= ' badge-info';
                                } elseif ($status === 'Inactive') {
                                    $badgeClass .= ' badge-error';
                                } else {
                                    $badgeClass .= ' badge-secondary';
                                }
                                ?>
                                <span class="<?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
                            </td>
                            <td>
                                <?php
                                // Find assigned vehicle if dispatched
                                $assigned = '';
                                if ($d['status'] === 'Dispatched') {
                                    $dispatch = fetchOneQuery("SELECT v.vehicle_name FROM dispatches ds JOIN fleet_vehicles v ON ds.vehicle_id = v.id WHERE ds.driver_id = ? AND ds.status = 'Ongoing' ORDER BY ds.dispatch_date DESC LIMIT 1", [$d['id']]);
                                    if ($dispatch && isset($dispatch['vehicle_name'])) {
                                        $assigned = $dispatch['vehicle_name'];
                                    }
                                }
                                echo $assigned ? htmlspecialchars($assigned) : '<span class="opacity-50">None</span>';
                                ?>
                            </td>
                            <td>
                                <div>Mobile No. : <?= htmlspecialchars($d['phone'] ?? 'N/A') ?></div>
                                <div>Email : <?= htmlspecialchars($d['email'] ?? 'N/A') ?></div>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('vehicleStatusChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Inactive', 'Dispatched', 'Under Maintenance'],
                datasets: [{
                    label: 'Vehicles',
                    data: [
                        <?= $activeCount ?>,
                        <?= $inactiveCount ?>,
                        <?= $dispatchedCount ?>,
                        <?= $maintenanceCount ?>
                    ],
                    backgroundColor: [
                        'rgba(34,197,94,0.6)', // green
                        'rgba(239,68,68,0.6)', // red
                        'rgba(59,130,246,0.6)', // blue
                        'rgba(234,179,8,0.6)' // yellow
                    ],
                    borderColor: [
                        'rgba(34,197,94,1)',
                        'rgba(239,68,68,1)',
                        'rgba(59,130,246,1)',
                        'rgba(234,179,8,1)'
                    ],
                    borderWidth: 2,

                }]
            },
            options: {
                responsive: true,

                plugins: {
                    legend: {
                        position: 'bottom',

                    }
                }
            }
        });
    </script>

<?php } ?>