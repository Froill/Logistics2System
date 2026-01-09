<?php
//FLEET & VEHICLE MANAGEMENT MODULE
// Manages fleet vehicles, their statuses, and logs (maintenance, fuel, etc.)
require_once __DIR__ . '/../includes/fvm_logic.php';

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

    // Add log to the current module that is being accessed by the user
    $moduleName = 'fvm';

    if ($_SESSION['current_module'] !== $moduleName) {
        log_audit_event(
            'FVM',
            'ACCESS',
            null,
            $_SESSION['full_name'],
            'User accessed Fleet & Vehicle Management module'
        );
        $_SESSION['current_module'] = $moduleName;
    }
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
                    <h4 class="font-semibold text-md mb-2">Current & Past Maintenance Records</h4>
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

        <div class="grid grid-cols-1 lg:grid-cols-1 gap-6 mb-6">
            <!-- Vehicle Status Pie Chart -->
            <div class="card shadow-lg p-4">
                <h3 class="text-lg font-bold mb-2">Vehicle Status Distribution</h3>
                <div class="w-full max-w-sm mx-auto">
                    <canvas id="vehicleStatusChart" class="w-full h-64"></canvas>
                </div>
            </div>

            <!-- Vehicle Metrics -->
            <section class="card shadow-lg p-4 ">
                <h3 class="text-lg text-center md:text-left font-bold mb-2">Key Metrics</h3>
                <div class="stats stats-vertical md:stats-horizontal shadow">
                    <div class="stat text-primary">
                        <div class="stat-figure">
                            <i data-lucide="car" class="inline-block h-10 w-auto stroke-current"></i>
                        </div>
                        <div class="stat-title">Total Vehicles</div>
                        <div class="stat-value"><?= $totalVehicles ?></div>
                        <div class="stat-desc">Fleet size across all operations</div>
                    </div>

                    <div class="stat text-success">
                        <div class="stat-figure">
                            <i data-lucide="circle-check" class="inline-block h-10 w-auto stroke-current"></i>
                        </div>
                        <div class="stat-title">Active</div>
                        <div class="stat-value"><?= $activeCount ?></div>
                        <div class="stat-desc">Currently available for dispatch</div>
                    </div>

                    <div class="stat text-error">
                        <div class="stat-figure">
                            <i data-lucide="pause-circle" class="inline-block h-10 w-auto stroke-current"></i>
                        </div>
                        <div class="stat-title">Inactive</div>
                        <div class="stat-value"><?= $inactiveCount ?></div>
                        <div class="stat-desc">Idle or temporarily unused</div>
                    </div>

                    <div class="stat text-info">
                        <div class="stat-figure">
                            <i data-lucide="navigation" class="inline-block h-10 w-auto stroke-current"></i>
                        </div>
                        <div class="stat-title">Dispatched</div>
                        <div class="stat-value"><?= $dispatchedCount ?></div>
                        <div class="stat-desc">On an active trip or delivery</div>
                    </div>

                    <div class="stat text-secondary">
                        <div class="stat-figure">
                            <i data-lucide="wrench" class="inline-block h-10 w-auto stroke-current"></i>
                        </div>
                        <div class="stat-title">Under Maintenance</div>
                        <div class="stat-value"><?= $maintenanceCount ?></div>
                        <div class="stat-desc">Scheduled or ongoing repairs</div>
                    </div>
                </div>
            </section>
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
            <!-- Maintenance Modal Button -->
            <button class="btn btn-secondary" onclick="schedule_maintenance_modal.showModal()">
                <i data-lucide="wrench" class="w-4 h-4 mr-1"></i> Maintenance
            </button>
            <!-- View Vehicle Logs Button -->
            <button class="btn btn-soft btn-info" onclick="vehicle_logs_modal.showModal()">
                <i data-lucide="clipboard-list" class="w-4 h-4 mr-1"></i> View Maintenance Logs
            </button>
            <?php
            $role = strtolower($_SESSION['role'] ?? $_SESSION['user_type'] ?? '');
            if (in_array($role, ['admin', 'manager'])): ?>
                <!-- Export Monthly Report Button -->
                <button class="btn btn-outline btn-success" onclick="exportReportModal.showModal()">
                    <i data-lucide="download" class="w-4 h-4 mr-1"></i> Export Monthly Report
                </button>
            <?php endif; ?>
            <!-- Export Report Modal -->
            <dialog id="exportReportModal" class="modal">
                <div class="modal-box">
                    <form method="dialog">
                        <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                    </form>
                    <h3 class="font-bold text-lg mb-4">Export Monthly Fleet KPI Report</h3>
                    <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="flex flex-col gap-4">
                        <div class="form-control">
                            <label class="label">Select Month</label>
                            <input type="month" name="export_month" class="input input-bordered" required>
                        </div>
                        <button type="submit" name="export_fleet_report" value="1" class="btn btn-success">Export as CSV</button>
                    </form>
                </div>
                <form method="dialog" class="modal-backdrop">
                    <button>close</button>
                </form>
            </dialog>
        </div>

        <!-- Maintenance Modal -->
        <dialog id="schedule_maintenance_modal" class="modal">
            <div class="modal-box w-11/12 max-w-3xl" style="z-index:1000;">
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
                        $nextMaint = $lastDate;
                        $maintenanceDates[$nextMaint->format('Y-m-d')][] = [
                            'vehicle_name' => $v['vehicle_name'],
                            'plate_number' => $v['plate_number'],
                            'vehicle_type' => $v['vehicle_type'],
                            'details' => $lastMaint['details'] ?? '',
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
                                        html += '<button type="button" class="maint-modal-btn" style="margin-top:4px;cursor:pointer;" data-maint="' + escapeHtml(JSON.stringify(highlight)) + '"><span class="badge badge-info">Scheduled</span></button>';
                                    }
                                    html += '</td>';
                                    day++;
                                }
                            }
                            html += '</tr>';
                        }
                        html += '</tbody></table>';
                        document.getElementById('calendarContainer').innerHTML = html;

                        // Attach click event listeners for modal
                        document.querySelectorAll('.maint-modal-btn').forEach(btn => {
                            btn.addEventListener('click', function(ev) {
                                showMaintModal(btn.getAttribute('data-maint'));
                            });
                        });
                    }

                    // Scheduled Calendar Modal logic
                    function showMaintModal(dataStr) {
                        let data;
                        try {
                            data = JSON.parse(dataStr);
                        } catch {
                            return;
                        }
                        let html = '<h3 class="font-bold text-lg mb-2">Vehicles Scheduled for Maintenance on this Date</h3>';
                        data.forEach(info => {
                            html += '<div class="mb-1">' +
                                '<span class="font-semibold">' + escapeHtml(info.vehicle_name) + '</span>' +
                                ' <span class="text-xs opacity-50">(' + escapeHtml(info.plate_number) + ')</span>';
                            if (info.details) {
                                html += '<div class="text-sm mt-1"><b>Details:</b> ' + escapeHtml(info.details) + '</div>';
                            }
                            html += '</div>';
                        });
                        let modal = document.getElementById('maintDetailsModal');
                        if (!modal) {
                            modal = document.createElement('dialog');
                            modal.id = 'maintDetailsModal';
                            modal.className = 'modal';
                            modal.innerHTML = '<div class="modal-box"><form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form><div id="maintDetailsContent"></div></div>';
                            document.body.appendChild(modal);
                        }
                        modal.querySelector('#maintDetailsContent').innerHTML = html;
                        if (typeof modal.showModal === 'function') {
                            modal.showModal();
                        } else {
                            modal.style.display = 'block';
                        }
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
                <!-- Maintenance Check Table -->
                <h3 class="font-bold text-lg mb-4">Maintenance Check</h3>
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>Vehicle & Type</th>
                                <th>Plate Number</th>
                                <th>Next Maintenance</th>
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
                                    <td>
                                        <div><?= htmlspecialchars($v['vehicle_name']) ?></div>
                                        <div><?= htmlspecialchars('(' . $v['vehicle_type'] . ')' ?? '-') ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($v['plate_number']) ?></td>
                                    <td><?= $nextMaint ? $nextMaint->format('M d, Y') : '<span class="opacity-50">No record</span>' ?></td>
                                    <td>
                                        <button class="btn btn-xs btn-warning" title="Adjust Maintenance Date" onclick="document.getElementById('set_maint_modal_<?= $v['id'] ?>').showModal()">
                                            <i data-lucide="calendar-clock"></i>
                                        </button>
                                        <button type="button" class="btn btn-xs btn-info ml-1 vehicle-inspect-btn" data-vehid="<?= $v['id'] ?>" data-vehname="<?= htmlspecialchars($v['vehicle_name']) ?>" title="Vehicle Inspection">
                                            <i data-lucide="search-check"></i>
                                        </button>
                                        <script>
                                            // Vehicle Inspection Modal logic
                                            function showVehicleInspectionModal(vehicleId, vehicleName) {
                                                let modal = document.getElementById('vehicleInspectionModal');
                                                if (!modal) {
                                                    modal = document.createElement('dialog');
                                                    modal.id = 'vehicleInspectionModal';
                                                    modal.className = 'modal';
                                                    modal.innerHTML = '<div class="modal-box"><form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form><div id="vehicleInspectionContent"></div></div>';
                                                    document.body.appendChild(modal);
                                                }
                                                // Editable dropdowns for each item
                                                const details = [{
                                                        label: 'Battery Level',
                                                        key: 'battery',
                                                        type: 'level'
                                                    },
                                                    {
                                                        label: 'Left Headlight',
                                                        key: 'left_headlight',
                                                        type: 'status'
                                                    },
                                                    {
                                                        label: 'Right Headlight',
                                                        key: 'right_headlight',
                                                        type: 'status'
                                                    },
                                                    {
                                                        label: 'Left Taillight',
                                                        key: 'left_taillight',
                                                        type: 'status'
                                                    },
                                                    {
                                                        label: 'Right Taillight',
                                                        key: 'right_taillight',
                                                        type: 'status'
                                                    },
                                                    {
                                                        label: 'Turn Signals',
                                                        key: 'turn_signals',
                                                        type: 'status'
                                                    },
                                                    {
                                                        label: 'Oil Level',
                                                        key: 'oil',
                                                        type: 'level'
                                                    },
                                                    {
                                                        label: 'Water Coolant Level',
                                                        key: 'coolant',
                                                        type: 'level'
                                                    },
                                                    {
                                                        label: 'Brakes Condition',
                                                        key: 'brakes',
                                                        type: 'status'
                                                    },
                                                    {
                                                        label: 'Air Pressure',
                                                        key: 'air_pressure',
                                                        type: 'status'
                                                    },
                                                    {
                                                        label: 'Gas/Fuel Tank Condition',
                                                        key: 'fuel_tank',
                                                        type: 'status'
                                                    },
                                                    {
                                                        label: 'Engine Condition',
                                                        key: 'engine',
                                                        type: 'status'
                                                    },
                                                    {
                                                        label: 'Tire Condition',
                                                        key: 'tire',
                                                        type: 'status'
                                                    }
                                                ];
                                                const levelOptions = ['Empty', 'Low', 'Normal', 'Full'];
                                                const statusOptions = ['Needs Maintenance', 'Needs Replacement', 'Operational'];
                                                let html = `<h3 class="font-bold text-lg mb-2">Vehicle Inspection: <span class="text-primary">${vehicleName}</span></h3>`;
                                                html += '<form id="vehicleInspectionForm"><table class="table table-compact w-full"><tbody>';
                                                details.forEach(item => {
                                                    html += `<tr><td class="font-semibold">${item.label}</td><td>`;
                                                    if (item.type === 'level') {
                                                        html += `<select name="${item.key}" class="select select-bordered select-sm">`;
                                                        levelOptions.forEach(opt => {
                                                            html += `<option value="${opt}">${opt}</option>`;
                                                        });
                                                        html += `</select>`;
                                                    } else {
                                                        html += `<select name="${item.key}" class="select select-bordered select-sm">`;
                                                        statusOptions.forEach(opt => {
                                                            html += `<option value="${opt}">${opt}</option>`;
                                                        });
                                                        html += `</select>`;
                                                    }
                                                    html += `</td></tr>`;
                                                });
                                                html += '</tbody></table>';
                                                html += '<div class="mt-4 text-right"><button type="submit" class="btn btn-primary btn-sm">Save</button></div></form>';
                                                modal.querySelector('#vehicleInspectionContent').innerHTML = html;
                                                if (typeof modal.showModal === 'function') {
                                                    modal.showModal();
                                                } else {
                                                    modal.style.display = 'block';
                                                }
                                                // Optionally handle form submit (for now just close modal)
                                                modal.querySelector('#vehicleInspectionForm').onsubmit = function(e) {
                                                    e.preventDefault();
                                                    modal.close();
                                                };
                                            }
                                            document.querySelectorAll('.vehicle-inspect-btn').forEach(btn => {
                                                btn.addEventListener('click', function() {
                                                    const vehId = btn.getAttribute('data-vehid');
                                                    const vehName = btn.getAttribute('data-vehname') || 'Vehicle';
                                                    showVehicleInspectionModal(vehId, vehName);
                                                });
                                            });
                                        </script>

                                        <!-- Set Maintenance Modal -->
                                        <dialog id="set_maint_modal_<?= $v['id'] ?>" class="modal">
                                            <div class="modal-box">
                                                <form method="dialog">
                                                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                                                </form>
                                                <h3 class="font-bold text-lg mb-4">Set Maintenance Date</h3>
                                                <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="flex flex-col gap-4" enctype="multipart/form-data">
                                                    <input type="hidden" name="adjust_maintenance_vehicle_id" value="<?= $v['id'] ?>">
                                                    <div class="form-control">
                                                        <label class="label">Set Maintenance Date</label>
                                                        <?php $minDate = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d'); ?>
                                                        <input type="date" id="next_maintenance_date_<?= $v['id'] ?>" name="next_maintenance_date" class="input input-bordered" min="<?= $minDate ?>" value="<?= $minDate ?>" required>
                                                    </div>
                                                    <div class="form-control">
                                                        <label class="label">Details</label>
                                                        <select name="maintenance_part" class="select select-bordered" required>
                                                            <option value="">Select part</option>
                                                            <option value="Battery">Battery</option>
                                                            <option value="Left Headlight">Left Headlight</option>
                                                            <option value="Right Headlight">Right Headlight</option>
                                                            <option value="Left Taillight">Left Taillight</option>
                                                            <option value="Right Taillight">Right Taillight</option>
                                                            <option value="Turn Signals">Turn Signals</option>
                                                            <option value="Oil Level">Oil Level</option>
                                                            <option value="Water Coolant Level">Water Coolant Level</option>
                                                            <option value="Brakes Condition">Brakes Condition</option>
                                                            <option value="Air Pressure">Air Pressure</option>
                                                            <option value="Gas/Fuel Tank Condition">Gas/Fuel Tank Condition</option>
                                                            <option value="Engine Condition">Engine Condition</option>
                                                            <option value="Tire Condition">Tire Condition</option>
                                                        </select>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary">Save</button>
                                                </form>
                                            </div>
                                            <form method="dialog" class="modal-backdrop">
                                                <button>close</button>
                                            </form>
                                            <script>
                                                // Ensure client cannot pick past dates (extra safety)
                                                (function() {
                                                    try {
                                                        var dateInput = document.getElementById('next_maintenance_date_<?= $v['id'] ?>');
                                                        if (dateInput) {
                                                            dateInput.min = '<?= $minDate ?>';
                                                        }
                                                    } catch (e) {}
                                                })();
                                            </script>
                                            <?php
                                            // Handle set maintenance form submission (server-side validation)
                                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_maintenance_vehicle_id'], $_POST['next_maintenance_date'], $_POST['maintenance_part'])) {
                                                $vehicleId = intval($_POST['adjust_maintenance_vehicle_id']);
                                                $date = $_POST['next_maintenance_date'];
                                                $part = trim($_POST['maintenance_part']);
                                                // Basic validation
                                                if (!($vehicleId && $date && $part)) {
                                                    $_SESSION['fvm_error'] = 'Please fill out all maintenance details.';
                                                } else {
                                                    $today = new DateTime('now', new DateTimeZone('Asia/Manila'));
                                                    $inputDate = DateTime::createFromFormat('Y-m-d', $date);
                                                    if (!$inputDate) {
                                                        $_SESSION['fvm_error'] = 'Invalid maintenance date.';
                                                    } else {
                                                        // compare dates only (ignore time)
                                                        $today->setTime(0,0,0);
                                                        $inputDate->setTime(0,0,0);
                                                        if ($inputDate < $today) {
                                                            $_SESSION['fvm_error'] = 'Maintenance date cannot be in the past.';
                                                        } else {
                                                            // Save to fleet_vehicle_logs as a maintenance log
                                                            $db = getDb();
                                                            $stmt = $db->prepare("INSERT INTO fleet_vehicle_logs (vehicle_id, log_type, details, created_at) VALUES (?, 'maintenance', ?, ?)");
                                                            $desc = $part . ' scheduled for maintenance';
                                                            $stmt->execute([$vehicleId, $desc, $date]);
                                                            $_SESSION['fvm_success'] = 'Maintenance scheduled for ' . htmlspecialchars($part) . '.';
                                                            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
                                                            exit;
                                                        }
                                                    }
                                                }
                                            }
                                            ?>
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
            <table class="table table-zebra w-full" id="vehicleTable">
                <thead>
                    <tr>
                        <th>Vehicle Name & Type</th>
                        <th>License</th>
                        <th>Payload (kg)</th>
                        <th>Fuel Capacity (L)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="vehicleBody">
                    <?php foreach ($vehicles as $v): ?>
                        <tr>
                            <td>
                                <div><?= htmlspecialchars($v['vehicle_name']) ?></div>
                                <div><?= htmlspecialchars('(' . $v['vehicle_type'] . ')' ?? '-') ?></div>
                            </td>
                            <td><?= htmlspecialchars($v['plate_number']) ?></td>
                            <td><?= htmlspecialchars($v['weight_capacity'] ?? '-') ?>kg</td>
                            <td><?= htmlspecialchars($v['fuel_capacity'] ?? '-') ?>L</td>

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
                                    $badgeClass .= ' badge-info';
                                }
                                ?>
                                <span class="<?= $badgeClass ?>">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex flex-col gap-3 ">
                                    <button class="btn btn-sm btn-info" onclick="document.getElementById('view_modal_<?= $v['id'] ?>').showModal()" title="View">
                                        <i data-lucide="eye"></i>
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

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- Pagination Controls -->
            <div class="flex justify-center mt-4 gap-2" id="paginationControls"></div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const rowsPerPage = 5; // adjust per page
                    const tableBody = document.getElementById("vehicleBody");
                    const rows = tableBody.querySelectorAll("tr");
                    const paginationControls = document.getElementById("paginationControls");
                    const totalPages = Math.ceil(rows.length / rowsPerPage);
                    let currentPage = 1;

                    function showPage(page) {
                        currentPage = page;
                        let start = (page - 1) * rowsPerPage;
                        let end = start + rowsPerPage;

                        rows.forEach((row, i) => {
                            row.style.display = (i >= start && i < end) ? "" : "none";
                        });

                        renderPagination();
                    }

                    function renderPagination() {
                        paginationControls.innerHTML = "";

                        for (let i = 1; i <= totalPages; i++) {
                            const btn = document.createElement("button");
                            btn.textContent = i;
                            btn.className = "btn btn-sm " + (i === currentPage ? "btn-primary" : "btn-outline");
                            btn.onclick = () => showPage(i);
                            paginationControls.appendChild(btn);
                        }
                    }

                    showPage(1);
                });
            </script>
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