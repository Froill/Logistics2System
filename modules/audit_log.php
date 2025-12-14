<?php
// Unified Audit Log Module
// Logs actions from TCAO, FVM, VRDS, DTP, and other modules
// Table: audit_log (id, module, action, record_id, user, details, timestamp)

require_once __DIR__ . '/../includes/functions.php';

function log_audit_event($module, $action, $record_id, $user, $details = null)
{
    global $conn;
    $stmt = $conn->prepare("INSERT INTO audit_log (module, action, record_id, user, details, timestamp) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('ssiss', $module, $action, $record_id, $user, $details);
    $stmt->execute();
}

function audit_log_view()
{
    // Access control
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: dashboard.php");
        exit;
    }

    // Add log to the current module that is being accessed by the user
    $moduleName = 'audit_log';

    if ($_SESSION['current_module'] !== $moduleName) {
        log_audit_event(
            'Audit Logs',
            'ACCESS',
            null,
            $_SESSION['full_name'],
            'User accessed Audit logs module'
        );
        $_SESSION['current_module'] = $moduleName;
    }


    global $conn;

    // Records per page
    $limit = 10;

    // Current page
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;

    // Offset
    $offset = ($page - 1) * $limit;

    // Count total rows
    $result = $conn->query("SELECT COUNT(*) as total FROM audit_log");
    $row = $result->fetch_assoc();
    $total_rows = $row['total'];
    $total_pages = ceil($total_rows / $limit);

    // Fetch logs with pagination
    $stmt = $conn->prepare("SELECT * FROM audit_log ORDER BY timestamp DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo '<div class="overflow-x-auto">';
    echo '<h2 class="text-2xl font-bold mb-4">Audit Logs</h2>';
    echo '<table class="table table-zebra w-full">';
    echo '<thead><tr><th>ID</th><th>Module</th><th>Action</th><th>Record ID</th><th>User</th><th>Details</th><th>Timestamp</th></tr></thead><tbody>';

    foreach ($logs as $log) {
        $dt = new DateTime($log['timestamp']);
        $formattedTime = $dt->format('Y-m-d g:i A');

        echo '<tr>';
        echo '<td>' . htmlspecialchars($log['id']) . '</td>';
        echo '<td>' . htmlspecialchars($log['module']) . '</td>';
        echo '<td>' . htmlspecialchars($log['action']) . '</td>';
        echo '<td>' . htmlspecialchars($log['record_id']) . '</td>';
        echo '<td>' . htmlspecialchars($log['user']) . '</td>';
        echo '<td>' . htmlspecialchars($log['details']) . '</td>';
        echo '<td>' . htmlspecialchars($formattedTime) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    // Pagination controls
    echo '<div class="flex flex-wrap justify-center mt-4 gap-x-3 gap-y-5 join">';
    if ($page > 1) {
        echo '<a href="dashboard.php?module=audit_log&page=' . ($page - 1) . '" class="join-item  btn btn-sm">Prev</a>';
    }
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = ($i == $page) ? 'btn-primary' : '';
        echo '<a href="dashboard.php?module=audit_log&page=' . $i . '" class=" join-item btn btn-sm ' . $active . '">' . $i . '</a>';
    }
    if ($page < $total_pages) {
        echo '<a href="dashboard.php?module=audit_log&page=' . ($page + 1) . '" class="join-item btn btn-sm">Next</a>';
    }
    echo '</div>';

    echo '</div>';
}
