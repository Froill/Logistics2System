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

        <section class="card bg-base-100 shadow-xl p-4 lg:p-6 mb-8">
    <h3 class="text-lg font-bold mb-6 text-center lg:text-left">Key Metrics</h3>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-5">
        <div class="stat bg-base-200 p-6 rounded-box text-primary">
            <div class="stat-figure">
                <i data-lucide="car" class="inline-block w-10 h-10 stroke-current"></i>
            </div>
            <div class="stat-title">Total Vehicles</div>
            <div class="stat-value"><?= $totalVehicles ?></div>
            <div class="stat-desc">Fleet size across all operations</div>
        </div>

        <div class="stat bg-base-200 p-6 rounded-box text-success">
            <div class="stat-figure">
                <i data-lucide="circle-check" class="inline-block w-10 h-10 stroke-current"></i>
            </div>
            <div class="stat-title">Active</div>
            <div class="stat-value"><?= $activeCount ?></div>
            <div class="stat-desc">Currently available for dispatch</div>
        </div>

        <div class="stat bg-base-200 p-6 rounded-box text-error">
            <div class="stat-figure">
                <i data-lucide="pause-circle" class="inline-block w-10 h-10 stroke-current"></i>
            </div>
            <div class="stat-title">Inactive</div>
            <div class="stat-value"><?= $inactiveCount ?></div>
            <div class="stat-desc">Idle or temporarily unused</div>
        </div>
        
        <div class="stat bg-base-200 p-6 rounded-box text-info">
            <div class="stat-figure">
                <i data-lucide="navigation" class="inline-block w-10 h-10 stroke-current"></i>
            </div>
            <div class="stat-title">Dispatched</div>
            <div class="stat-value"><?= $dispatchedCount ?></div>
            <div class="stat-desc">On an active trip or delivery</div>
        </div>

        <div class="stat bg-base-200 p-6 rounded-box text-secondary">
            <div class="stat-figure">
                <i data-lucide="wrench" class="inline-block w-10 h-10 stroke-current"></i>
            </div>
            <div class="stat-title">Under Maintenance</div>
            <div class="stat-value"><?= $maintenanceCount ?></div>
            <div class="stat-desc">Scheduled or ongoing repairs</div>
        </div>
    </div>
</section>

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
            <!-- Documents Button -->
            <button class="btn btn-outline btn-info" onclick="documentsModal.showModal()">
                <i data-lucide="file-text" class="w-4 h-4 mr-1"></i> Documents
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
            <!-- Documents Modal -->
            <dialog id="documentsModal" class="modal">
                <div class="modal-box w-11/12 max-w-4xl">
                    <form method="dialog">
                        <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                    </form>
                    <h3 class="font-bold text-lg mb-4">Vehicle Documents & Insurance</h3>
                    <?php
                        $regDocs = fetchAllQuery("SELECT vd.*, v.vehicle_name, v.plate_number FROM vehicle_documents vd JOIN fleet_vehicles v ON vd.vehicle_id = v.id WHERE vd.doc_type = 'Registration' ORDER BY vd.uploaded_at DESC");
                        $insDocs = fetchAllQuery("SELECT vi.*, v.vehicle_name, v.plate_number FROM vehicle_insurance vi JOIN fleet_vehicles v ON vi.vehicle_id = v.id ORDER BY vi.coverage_end DESC");
                    ?>
                    <h4 class="font-semibold mt-2 mb-2">Registration (OR/CR)</h4>
                    <div class="overflow-x-auto mb-4">
                        <table class="table table-compact w-full">
                            <thead>
                                <tr>
                                    <th>Vehicle</th>
                                    <th>Plate</th>
                                    <th>Document</th>
                                    <th>Expiry</th>
                                    <th>Uploaded</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($regDocs)): ?>
                                    <tr><td colspan="6" class="text-center opacity-50">No registration documents found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($regDocs as $doc): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($doc['vehicle_name']) ?></td>
                                            <td><?= htmlspecialchars($doc['plate_number']) ?></td>
                                            <td><?= htmlspecialchars($doc['doc_name'] ?? 'Registration') ?></td>
                                            <td><?= !empty($doc['expiry_date']) ? htmlspecialchars(date('M d, Y', strtotime($doc['expiry_date']))) : '<span class="opacity-50">None</span>' ?></td>
                                            <td><?= !empty($doc['uploaded_at']) ? htmlspecialchars(date('M d, Y', strtotime($doc['uploaded_at']))) : '<span class="opacity-50">Unknown</span>' ?></td>
                                            <td>
                                                <?php if (!empty($doc['file_path'])): ?>
                                                    <a class="btn btn-xs btn-outline" href="<?= htmlspecialchars('includes/ajax.php?download_vehicle_file=1&doc_id=' . $doc['id'] . '&table=document') ?>">Download</a>
                                                <?php else: ?>
                                                    <span class="opacity-50">No file</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <h4 class="font-semibold mt-2 mb-2">Insurance Records</h4>
                    <div class="overflow-x-auto mb-2">
                        <table class="table table-compact w-full">
                            <thead>
                                <tr>
                                    <th>Vehicle</th>
                                    <th>Plate</th>
                                    <th>Insurer</th>
                                    <th>Policy #</th>
                                    <th>Coverage End</th>
                                    <th>Premium</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($insDocs)): ?>
                                    <tr><td colspan="7" class="text-center opacity-50">No insurance records found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($insDocs as $ins): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ins['vehicle_name']) ?></td>
                                            <td><?= htmlspecialchars($ins['plate_number']) ?></td>
                                            <td><?= htmlspecialchars($ins['insurer'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($ins['policy_number'] ?? '-') ?></td>
                                            <td><?= !empty($ins['coverage_end']) ? htmlspecialchars(date('M d, Y', strtotime($ins['coverage_end']))) : '<span class="opacity-50">None</span>' ?></td>
                                            <td><?= !empty($ins['premium']) ? htmlspecialchars(number_format($ins['premium'],2)) : '<span class="opacity-50">-</span>' ?></td>
                                            <td>
                                                <?php if (!empty($ins['document_path'])): ?>
                                                    <a class="btn btn-xs btn-outline" href="<?= htmlspecialchars('includes/ajax.php?download_vehicle_file=1&doc_id=' . $ins['id'] . '&table=insurance') ?>">Download</a>
                                                <?php else: ?>
                                                    <span class="opacity-50">No file</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
        <div class="overflow-x-auto bg-base-100 rounded-xl border border-base-300 shadow-lg">
            <h3 class="text-lg font-bold mb-2 px-4 pt-4 tracking-tight">Fleet Vehicles</h3>
            <table class="table table-zebra table-sm md:table-md w-full" id="vehicleTable">
                <thead class="bg-base-200 text-xs uppercase text-base-content/70 sticky top-0 z-10">
                    <tr>
                        <th>ID</th>
                        <th>Vehicle Name & Type</th>
                        <th>License</th>
                        <th>Payload (kg)</th>
                        <th>Fuel Capacity (L)</th>
                        <th>Insurance Expiry</th>
                        <th>Registration Expiry</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="vehicleBody">
                    <?php foreach ($vehicles as $v): ?>
                        <tr>
                            <td><div class="font-mono text-xs"><?= htmlspecialchars($v['id']) ?></div></td>
                            <td>
                                <div class="font-medium"><?= htmlspecialchars($v['vehicle_name']) ?></div>
                                <div class="text-xs opacity-70"><?= htmlspecialchars('(' . $v['vehicle_type'] . ')' ?? '-') ?></div>
                            </td>
                            <td><?= htmlspecialchars($v['plate_number']) ?></td>
                            <td class="whitespace-nowrap"><?= htmlspecialchars($v['weight_capacity'] ?? '-') ?>kg</td>
                            <td class="whitespace-nowrap"><?= htmlspecialchars($v['fuel_capacity'] ?? '-') ?>L</td>
                            <?php
                                $ins = fetchOneQuery("SELECT coverage_end FROM vehicle_insurance WHERE vehicle_id = ? ORDER BY coverage_end DESC LIMIT 1", [$v['id']]);
                                $reg = fetchOneQuery("SELECT expiry_date FROM vehicle_documents WHERE vehicle_id = ? AND doc_type = 'Registration' ORDER BY expiry_date DESC LIMIT 1", [$v['id']]);
                            ?>
                            <?php
                                $today = new DateTime('now', new DateTimeZone('Asia/Manila'));
                                $today->setTime(0,0,0);
                            ?>
                            <td>
                                <?php if ($ins && !empty($ins['coverage_end'])):
                                    $insDate = DateTime::createFromFormat('Y-m-d', date('Y-m-d', strtotime($ins['coverage_end'])));
                                    $insDate->setTime(0,0,0);
                                    $insValid = $insDate >= $today;
                                    $insClass = $insValid ? 'badge badge-sm badge-success' : 'badge badge-sm badge-error';
                                ?>
                                    <span class="<?= $insClass ?>"><?= htmlspecialchars(date('M d, Y', strtotime($ins['coverage_end']))) ?></span>
                                <?php else: ?>
                                    <span class="opacity-50">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($reg && !empty($reg['expiry_date'])):
                                    $regDate = DateTime::createFromFormat('Y-m-d', date('Y-m-d', strtotime($reg['expiry_date'])));
                                    $regDate->setTime(0,0,0);
                                    $regValid = $regDate >= $today;
                                    $regClass = $regValid ? 'badge badge-sm badge-success' : 'badge badge-sm badge-error';
                                ?>
                                    <span class="<?= $regClass ?>"><?= htmlspecialchars(date('M d, Y', strtotime($reg['expiry_date']))) ?></span>
                                <?php else: ?>
                                    <span class="opacity-50">None</span>
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
                                    $badgeClass .= ' badge-info';
                                }
                                ?>
                                <span class="<?= $badgeClass ?>">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex flex-col gap-3 ">
                                    <button class="btn btn-sm btn-info btn-circle" onclick="document.getElementById('view_modal_<?= $v['id'] ?>').showModal()" title="View">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
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
                                        <?php
                                            $docs = fetchAllQuery("SELECT * FROM vehicle_documents WHERE vehicle_id = ? ORDER BY uploaded_at DESC", [$v['id']]);
                                            $insData = fetchAllQuery("SELECT * FROM vehicle_insurance WHERE vehicle_id = ? ORDER BY coverage_end DESC", [$v['id']]);
                                        ?>
                                        <?php if (!empty($insData)): ?>
                                            <div class="mb-2"><strong>Insurance:</strong>
                                                <?php foreach ($insData as $ins): ?>
                                                    <div class="mb-2 p-2 border rounded">
                                                        <div class="font-semibold"><?= htmlspecialchars($ins['insurer'] ?? 'Unknown') ?> <?php if (!empty($ins['policy_number'])): ?>(<?= htmlspecialchars($ins['policy_number']) ?>)<?php endif; ?></div>
                                                        <div class="text-sm opacity-70"><?php if (!empty($ins['coverage_end'])): ?>Expires <?= htmlspecialchars(date('M d, Y', strtotime($ins['coverage_end']))) ?><?php else: ?>No expiry<?php endif; ?></div>
                                                        <?php if (!empty($ins['document_path'])):
                                                            $insPath = $ins['document_path'];
                                                            $abs = __DIR__ . '/../' . $insPath;
                                                            $proxyUrl = '';
                                                            if (file_exists($abs)) {
                                                                // Use direct path if file exists on disk
                                                                $proxyUrl = $insPath;
                                                                $insExt = strtolower(pathinfo($insPath, PATHINFO_EXTENSION));
                                                            } else {
                                                                // Try common fallback filenames in the vehicle uploads folder
                                                                $fallbacks = ['insurance1.jpg','insurance1.png','insurance.jpg','insurance.png','insurance_policy_1.jpg','insurance_policy_1.pdf','insurance_policy_1.png','orcr1.png','orcr1.jpg','orcr.png'];
                                                                foreach ($fallbacks as $f) {
                                                                    $p = 'uploads/vehicles/' . $v['id'] . '/' . $f;
                                                                    if (file_exists(__DIR__ . '/../' . $p)) {
                                                                        $proxyUrl = $p;
                                                                        $insExt = strtolower(pathinfo($p, PATHINFO_EXTENSION));
                                                                        break;
                                                                    }
                                                                }
                                                                // fallback to proxy by db id
                                                                if (empty($proxyUrl)) {
                                                                    $proxyUrl = 'includes/ajax.php?download_vehicle_file=1&doc_id=' . $ins['id'] . '&table=insurance&inline=1';
                                                                    $insExt = strtolower(pathinfo($insPath, PATHINFO_EXTENSION));
                                                                }
                                                            }
                                                            if (!empty($insExt) && in_array($insExt, ['jpg','jpeg','png','gif'])): ?>
                                                                <div class="mt-2"><img class="doc-thumb cursor-pointer" data-src="<?= htmlspecialchars($proxyUrl) ?>" src="<?= htmlspecialchars($proxyUrl) ?>" alt="Insurance Image" style="max-width:220px; max-height:160px; border-radius:6px; border:1px solid #ddd;"></div>
                                                            <?php elseif (!empty($insExt) && $insExt === 'pdf'): ?>
                                                                <div class="mt-2"><object data="<?= htmlspecialchars($proxyUrl) ?>" type="application/pdf" width="100%" height="220">PDF preview not available. <a href="<?= htmlspecialchars('includes/ajax.php?download_vehicle_file=1&doc_id=' . $ins['id'] . '&table=insurance') ?>">Download</a></object></div>
                                                            <?php else: ?>
                                                                <div class="mt-2"><a class="btn btn-xs btn-outline" href="<?= htmlspecialchars('includes/ajax.php?download_vehicle_file=1&doc_id=' . $ins['id'] . '&table=insurance') ?>">Download</a></div>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <div class="mt-2 opacity-50">No file</div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($docs)): ?>
                                            <div class="mb-2"><strong>Documents:</strong>
                                                <?php foreach ($docs as $doc): ?>
                                                    <div class="mb-2 p-2 border rounded">
                                                        <div class="font-semibold"><?= htmlspecialchars($doc['doc_type'] ?? 'Document') ?></div>
                                                        <div class="text-sm opacity-70"><?php if (!empty($doc['expiry_date'])): ?>Expires <?= htmlspecialchars(date('M d, Y', strtotime($doc['expiry_date']))) ?><?php else: ?>No expiry<?php endif; ?></div>
                                                        <?php if (!empty($doc['file_path'])):
                                                            $dPath = $doc['file_path'];
                                                            $absd = __DIR__ . '/../' . $dPath;
                                                            $dProxy = '';
                                                            if (file_exists($absd)) {
                                                                $dProxy = $dPath;
                                                                $dExt = strtolower(pathinfo($dPath, PATHINFO_EXTENSION));
                                                            } else {
                                                                $fallbacks = ['orcr1.png','orcr1.jpg','orcr.png','orcr.jpg','registration1.pdf','registration1.png'];
                                                                foreach ($fallbacks as $f) {
                                                                    $p = 'uploads/vehicles/' . $v['id'] . '/' . $f;
                                                                    if (file_exists(__DIR__ . '/../' . $p)) {
                                                                        $dProxy = $p;
                                                                        $dExt = strtolower(pathinfo($p, PATHINFO_EXTENSION));
                                                                        break;
                                                                    }
                                                                }
                                                                if (empty($dProxy)) {
                                                                    $dProxy = 'includes/ajax.php?download_vehicle_file=1&doc_id=' . $doc['id'] . '&table=document&inline=1';
                                                                    $dExt = strtolower(pathinfo($dPath, PATHINFO_EXTENSION));
                                                                }
                                                            }
                                                            if (!empty($dExt) && in_array($dExt, ['jpg','jpeg','png','gif'])): ?>
                                                                <div class="mt-2"><img class="doc-thumb cursor-pointer" data-src="<?= htmlspecialchars($dProxy) ?>" src="<?= htmlspecialchars($dProxy) ?>" alt="Document Image" style="max-width:220px; max-height:160px; border-radius:6px; border:1px solid #ddd;"></div>
                                                            <?php elseif (!empty($dExt) && $dExt === 'pdf'): ?>
                                                                <div class="mt-2"><object data="<?= htmlspecialchars($dProxy) ?>" type="application/pdf" width="100%" height="220">PDF preview not available. <a href="<?= htmlspecialchars('includes/ajax.php?download_vehicle_file=1&doc_id=' . $doc['id'] . '&table=document') ?>">Download</a></object></div>
                                                            <?php else: ?>
                                                                <div class="mt-2"><a class="btn btn-xs btn-outline" href="<?= htmlspecialchars('includes/ajax.php?download_vehicle_file=1&doc_id=' . $doc['id'] . '&table=document') ?>">Download</a></div>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <div class="mt-2 opacity-50">No file</div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <form method="dialog" class="modal-backdrop">
                                        <button>close</button>
                                    </form>
                                    <dialog id="docViewerModal" class="modal">
                                        <div class="modal-box w-full h-screen max-w-full p-0" style="position:relative;">
                                            <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4">✕</button></form>
                                            <div id="docViewerToolbar" style="position:absolute; top:1rem; left:1rem; z-index:40; display:flex; gap:0.5rem;">
                                                <button id="zoomOutBtn" class="btn btn-sm">−</button>
                                                <button id="resetZoomBtn" class="btn btn-sm">Reset</button>
                                                <button id="zoomInBtn" class="btn btn-sm">+</button>
                                                <div id="zoomLevel" style="display:flex; align-items:center; padding-left:.5rem; color:#fff; opacity:.9;">100%</div>
                                            </div>
                                            <div id="docViewerContent" style="width:100%; height:calc(100vh - 3rem); display:flex; align-items:center; justify-content:center; overflow:auto; padding:1rem; box-sizing:border-box; background:#000;"></div>
                                        </div>
                                        <form method="dialog" class="modal-backdrop"><button>close</button></form>
                                    </dialog>
                                    <script>
                                        (function(){
                                            let currentImage = null;
                                            let currentScale = 1;
                                            let isPanning = false;
                                            let panStart = {x:0,y:0,scrollLeft:0,scrollTop:0};

                                            function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }

                                            function setScale(newScale){
                                                newScale = clamp(newScale, 0.2, 5);
                                                if (!currentImage) { currentScale = newScale; updateZoomLabel(); return; }
                                                const container = document.getElementById('docImageContainer');
                                                const prev = currentScale;
                                                // center-based adjust
                                                const cx = container.clientWidth / 2;
                                                const cy = container.clientHeight / 2;
                                                currentScale = newScale;
                                                currentImage.style.transform = 'scale(' + currentScale + ')';
                                                // adjust scroll to keep center in view
                                                const ratio = currentScale / prev;
                                                container.scrollLeft = (container.scrollLeft + cx) * ratio - cx;
                                                container.scrollTop = (container.scrollTop + cy) * ratio - cy;
                                                updateZoomLabel();
                                            }

                                            function updateZoomLabel(){
                                                const el = document.getElementById('zoomLevel');
                                                if (el) el.textContent = Math.round(currentScale * 100) + '%';
                                                const out = document.getElementById('zoomOutBtn');
                                                const inc = document.getElementById('zoomInBtn');
                                                if (out) out.disabled = currentScale <= 0.2;
                                                if (inc) inc.disabled = currentScale >= 5;
                                            }

                                            function openViewer(src, ext) {
                                                const modal = document.getElementById('docViewerModal');
                                                const content = document.getElementById('docViewerContent');
                                                const toolbar = document.getElementById('docViewerToolbar');
                                                if (!modal || !content) return;
                                                content.innerHTML = '';
                                                currentImage = null;
                                                currentScale = 1;
                                                updateZoomLabel();
                                                const cleanExt = (ext || '').toLowerCase();
                                                if (['jpg','jpeg','png','gif'].includes(cleanExt)) {
                                                    // create a scrollable container so we can pan when zoomed
                                                    const wrapper = document.createElement('div');
                                                    wrapper.id = 'docImageContainer';
                                                    wrapper.style.width = '100%';
                                                    wrapper.style.height = '100%';
                                                    wrapper.style.overflow = 'auto';
                                                    wrapper.style.display = 'flex';
                                                    wrapper.style.alignItems = 'center';
                                                    wrapper.style.justifyContent = 'center';

                                                    const img = document.createElement('img');
                                                    img.src = src;
                                                    img.style.maxWidth = '100%';
                                                    img.style.maxHeight = 'calc(100vh - 6rem)';
                                                    img.style.transform = 'scale(1)';
                                                    img.style.transition = 'transform .12s ease';
                                                    img.style.borderRadius = '6px';
                                                    img.style.boxShadow = '0 6px 24px rgba(0,0,0,0.6)';
                                                    img.style.cursor = 'grab';
                                                    img.draggable = false;

                                                    wrapper.appendChild(img);
                                                    content.appendChild(wrapper);
                                                    currentImage = img;

                                                    // pointer-based panning
                                                    wrapper.addEventListener('pointerdown', function(e){
                                                        if (currentScale <= 1) return;
                                                        isPanning = true;
                                                        panStart.x = e.clientX;
                                                        panStart.y = e.clientY;
                                                        panStart.scrollLeft = wrapper.scrollLeft;
                                                        panStart.scrollTop = wrapper.scrollTop;
                                                        img.style.cursor = 'grabbing';
                                                        wrapper.setPointerCapture(e.pointerId);
                                                    });
                                                    wrapper.addEventListener('pointermove', function(e){
                                                        if (!isPanning) return;
                                                        const dx = e.clientX - panStart.x;
                                                        const dy = e.clientY - panStart.y;
                                                        wrapper.scrollLeft = panStart.scrollLeft - dx;
                                                        wrapper.scrollTop = panStart.scrollTop - dy;
                                                    });
                                                    wrapper.addEventListener('pointerup', function(e){
                                                        isPanning = false;
                                                        img.style.cursor = 'grab';
                                                        try { wrapper.releasePointerCapture(e.pointerId); } catch(e){}
                                                    });
                                                    wrapper.addEventListener('pointercancel', function(e){
                                                        isPanning = false;
                                                        img.style.cursor = 'grab';
                                                    });

                                                    // wheel-to-zoom when holding Ctrl (prevents accidental zoom)
                                                    wrapper.addEventListener('wheel', function(e){
                                                        if (!e.ctrlKey) return; // require ctrl to zoom with wheel
                                                        e.preventDefault();
                                                        const delta = -e.deltaY;
                                                        const factor = delta > 0 ? 1.12 : 0.88;
                                                        setScale(currentScale * factor);
                                                    }, { passive: false });

                                                    // toolbar visible for images
                                                    if (toolbar) toolbar.style.display = 'flex';
                                                } else if (cleanExt === 'pdf') {
                                                    const iframe = document.createElement('iframe');
                                                    iframe.src = src;
                                                    iframe.style.width = '100%';
                                                    iframe.style.height = 'calc(100vh - 6rem)';
                                                    iframe.style.border = 'none';
                                                    content.appendChild(iframe);
                                                    if (toolbar) toolbar.style.display = 'none';
                                                } else {
                                                    const a = document.createElement('a');
                                                    a.href = src;
                                                    a.textContent = 'Open file in new tab';
                                                    a.target = '_blank';
                                                    a.className = 'btn btn-primary';
                                                    content.appendChild(a);
                                                    if (toolbar) toolbar.style.display = 'none';
                                                }
                                                if (typeof modal.showModal === 'function') modal.showModal(); else modal.style.display = 'block';
                                            }

                                            // wire toolbar buttons
                                            document.getElementById('zoomInBtn').addEventListener('click', function(){ setScale(currentScale * 1.25); });
                                            document.getElementById('zoomOutBtn').addEventListener('click', function(){ setScale(currentScale * 0.8); });
                                            document.getElementById('resetZoomBtn').addEventListener('click', function(){ setScale(1); const wrapper = document.getElementById('docImageContainer'); if (wrapper) { wrapper.scrollLeft = 0; wrapper.scrollTop = 0; } });

                                            document.addEventListener('click', function(e){
                                                const t = e.target;
                                                if (t && t.classList && t.classList.contains('doc-thumb')) {
                                                    e.preventDefault();
                                                    const src = t.getAttribute('data-src') || t.src;
                                                    const ext = (src.split('.').pop() || '').toLowerCase();
                                                    openViewer(src, ext);
                                                }
                                            });

                                            // Close on ESC
                                            document.addEventListener('keydown', function(e){
                                                if (e.key === 'Escape') {
                                                    const modal = document.getElementById('docViewerModal');
                                                    if (modal && typeof modal.close === 'function') modal.close();
                                                }
                                            });

                                            // cleanup when modal closes
                                            const modalEl = document.getElementById('docViewerModal');
                                            if (modalEl) {
                                                modalEl.addEventListener('close', function(){
                                                    const content = document.getElementById('docViewerContent');
                                                    if (content) content.innerHTML = '';
                                                    currentImage = null;
                                                    currentScale = 1;
                                                    updateZoomLabel();
                                                });
                                            }
                                        })();
                                    </script>
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
        <div class="overflow-x-auto mt-8 bg-base-100 rounded-xl border border-base-300 shadow-lg">
            <h3 class="text-lg font-bold mb-2 px-4 pt-4 tracking-tight">Drivers</h3>
            <table class="table table-zebra table-sm md:table-md w-full">
                <thead class="bg-base-200 text-xs uppercase text-base-content/70 sticky top-0 z-10">
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
                            <td class="font-medium"><?= htmlspecialchars($d['driver_name']) ?></td>
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
                            </td>
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