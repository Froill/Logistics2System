<?php
// Unified Audit Log Module
// Logs actions from TCAO, FVM, VRDS, DTP, and other modules
// Table: audit_log (id, module, action, record_id, user, details, timestamp)

require_once __DIR__ . '/../includes/functions.php';

function log_audit_event($module, $action, $record_id, $user_id, $details = null)
{
    global $conn;
    $stmt = $conn->prepare("INSERT INTO audit_log (module, action, record_id, user, details, timestamp) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('ssiss', $module, $action, $record_id, $user_id, $details);
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

    // Sorting
    $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'timestamp';
    $sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

    // Validate sort column
    $valid_columns = ['id', 'module', 'action', 'record_id', 'user', 'details', 'timestamp'];
    if (!in_array($sortBy, $valid_columns)) {
        $sortBy = 'timestamp';
    }

    // Validate sort order
    if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') {
        $sortOrder = 'DESC';
    }

    // Toggle sort order
    $toggleSortOrder = ($sortOrder === 'ASC') ? 'DESC' : 'ASC';

    // Build WHERE clause for filters
    $where_conditions = [];
    $where_types = '';
    $where_params = [];

    // Filter by module
    if (!empty($_GET['filter_module'])) {
        $module_filter = '%' . $_GET['filter_module'] . '%';
        $where_conditions[] = "module LIKE ?";
        $where_types .= 's';
        $where_params[] = $module_filter;
    }

    // Filter by action
    if (!empty($_GET['filter_action'])) {
        $action_filter = '%' . $_GET['filter_action'] . '%';
        $where_conditions[] = "action LIKE ?";
        $where_types .= 's';
        $where_params[] = $action_filter;
    }

    // Filter by user
    if (!empty($_GET['filter_user'])) {
        $user_filter = '%' . $_GET['filter_user'] . '%';
        $where_conditions[] = "user LIKE ?";
        $where_types .= 's';
        $where_params[] = $user_filter;
    }

    // Filter by date range (from)
    if (!empty($_GET['filter_date_from'])) {
        $where_conditions[] = "DATE(timestamp) >= ?";
        $where_types .= 's';
        $where_params[] = $_GET['filter_date_from'];
    }

    // Filter by date range (to)
    if (!empty($_GET['filter_date_to'])) {
        $where_conditions[] = "DATE(timestamp) <= ?";
        $where_types .= 's';
        $where_params[] = $_GET['filter_date_to'];
    }

    // Build WHERE clause
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }

    // Offset
    $offset = ($page - 1) * $limit;

    // Count total rows with filters
    $count_query = "SELECT COUNT(*) as total FROM audit_log $where_clause";
    $count_stmt = $conn->prepare($count_query);
    if (!empty($where_conditions)) {
        $count_stmt->bind_param($where_types, ...$where_params);
    }
    $count_stmt->execute();
    $row = $count_stmt->get_result()->fetch_assoc();
    $total_rows = $row['total'];
    $total_pages = ceil($total_rows / $limit);
    $count_stmt->close();

    // Fetch logs with pagination, sorting and filters
    $fetch_query = "SELECT * FROM audit_log $where_clause ORDER BY $sortBy $sortOrder LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($fetch_query);

    $limit_copy = $limit;
    $offset_copy = $offset;

    if (!empty($where_conditions)) {
        $where_params[] = $limit_copy;
        $where_params[] = $offset_copy;
        $stmt->bind_param($where_types . 'ii', ...$where_params);
    } else {
        $stmt->bind_param('ii', $limit_copy, $offset_copy);
    }

    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Build query string for pagination/sorting links (filters only, no sort params)
    $query_params = [];
    if (!empty($_GET['filter_module'])) $query_params[] = 'filter_module=' . urlencode($_GET['filter_module']);
    if (!empty($_GET['filter_action'])) $query_params[] = 'filter_action=' . urlencode($_GET['filter_action']);
    if (!empty($_GET['filter_user'])) $query_params[] = 'filter_user=' . urlencode($_GET['filter_user']);
    if (!empty($_GET['filter_date_from'])) $query_params[] = 'filter_date_from=' . urlencode($_GET['filter_date_from']);
    if (!empty($_GET['filter_date_to'])) $query_params[] = 'filter_date_to=' . urlencode($_GET['filter_date_to']);
    $filter_string = !empty($query_params) ? '&' . implode('&', $query_params) : '';

    echo '<div class="p-4">';
    echo '<h2 class="text-2xl font-bold mb-4">Audit Logs</h2>';

    // Filter Form
    echo '<div class="bg-base-200 p-4 rounded-lg mb-6">';
    echo '<h3 class="text-lg font-semibold mb-3">Filters</h3>';
    echo '<form id="filter_form" method="GET" action="dashboard.php" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3">';
    echo '<input type="hidden" name="module" value="audit_log">';

    echo '<div><label class="text-sm font-medium">Module</label>';
    echo '<select name="filter_module" class="select select-bordered select-sm w-full">';
    echo '<option value="">All Modules</option>';
    $modules = ['Audit Logs', 'Authentication', 'Dashboard', 'DTP', 'FVM', 'TCAO', 'User Mgmt', 'VRDS'];
    foreach ($modules as $mod) {
        $selected = (!empty($_GET['filter_module']) && $_GET['filter_module'] === $mod) ? 'selected' : '';
        echo '<option value="' . htmlspecialchars($mod) . '" ' . $selected . '>' . htmlspecialchars($mod) . '</option>';
    }
    echo '</select></div>';

    echo '<div><label class="text-sm font-medium">Action</label>';
    echo '<input type="text" name="filter_action" placeholder="Search action..." value="' . htmlspecialchars($_GET['filter_action'] ?? '') . '" class="input input-bordered input-sm w-full"></div>';

    echo '<div><label class="text-sm font-medium">User</label>';
    echo '<input type="text" name="filter_user" placeholder="Search user..." value="' . htmlspecialchars($_GET['filter_user'] ?? '') . '" class="input input-bordered input-sm w-full"></div>';

    echo '<div><label class="text-sm font-medium">Date From</label>';
    echo '<input type="date" name="filter_date_from" value="' . htmlspecialchars($_GET['filter_date_from'] ?? '') . '" class="input input-bordered input-sm w-full"></div>';

    echo '<div><label class="text-sm font-medium">Date To</label>';
    echo '<input type="date" name="filter_date_to" value="' . htmlspecialchars($_GET['filter_date_to'] ?? '') . '" class="input input-bordered input-sm w-full"></div>';

    echo '<div><button type="submit" class="btn btn-primary btn-sm">Apply Filters</button></div>';
    echo '<div><a href="dashboard.php?module=audit_log" class="btn btn-error btn-sm">Clear Filters</a></div>';
    echo '</form>';
    echo '</div>';

    // Table
    echo '<div class="overflow-x-auto">';
    echo '<table class="table table-zebra w-full">';
    echo '<thead><tr>';

    $columns = ['ID', 'Module', 'Action', 'Record ID', 'User', 'Details', 'Timestamp'];
    $column_names = ['id', 'module', 'action', 'record_id', 'user', 'details', 'timestamp'];

    foreach ($columns as $index => $col) {
        $col_name = $column_names[$index];
        $current_sort_order = ($sortBy === $col_name) ? $toggleSortOrder : 'ASC';
        $sort_icon = '';

        if ($sortBy === $col_name) {
            $sort_icon = ($sortOrder === 'ASC') ? ' ▲' : ' ▼';
        }

        $sort_link = "dashboard.php?module=audit_log&sort_by=$col_name&sort_order=$current_sort_order$filter_string";
        echo "<th class='cursor-pointer hover:bg-base-300'><a href='$sort_link'>$col$sort_icon</a></th>";
    }

    echo '</tr></thead><tbody>';

    if (empty($logs)) {
        echo '<tr><td colspan="7" class="text-center text-gray-500">No logs found</td></tr>';
    } else {
        foreach ($logs as $log) {
            $dt = new DateTime($log['timestamp']);
            $formattedTime = $dt->format('Y-m-d g:i A');

            echo '<tr>';
            echo '<td>' . htmlspecialchars($log['id']) . '</td>';
            echo '<td><span class="badge badge-primary">' . htmlspecialchars($log['module']) . '</span></td>';
            echo '<td><span class="badge badge-secondary">' . htmlspecialchars($log['action']) . '</span></td>';
            echo '<td>' . htmlspecialchars($log['record_id'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($log['user']) . '</td>';
            echo '<td class="max-w-xs truncate" title="' . htmlspecialchars($log['details'] ?? '') . '">' . htmlspecialchars(substr($log['details'] ?? '', 0, 50)) . '</td>';
            echo '<td>' . htmlspecialchars($formattedTime) . '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table>';
    echo '</div>';

    // Pagination controls
    $sort_params = 'sort_by=' . urlencode($sortBy) . '&sort_order=' . urlencode($sortOrder);
    echo '<div class="flex flex-wrap justify-center mt-4 gap-x-3 gap-y-5 join">';
    if ($page > 1) {
        echo '<a href="dashboard.php?module=audit_log&page=' . ($page - 1) . '&' . $sort_params . $filter_string . '" class="join-item btn btn-sm">Prev</a>';
    }
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = ($i == $page) ? 'btn-primary' : '';
        echo '<a href="dashboard.php?module=audit_log&page=' . $i . '&' . $sort_params . $filter_string . '" class="join-item btn btn-sm ' . $active . '">' . $i . '</a>';
    }
    if ($page < $total_pages) {
        echo '<a href="dashboard.php?module=audit_log&page=' . ($page + 1) . '&' . $sort_params . $filter_string . '" class="join-item btn btn-sm">Next</a>';
    }
    echo '</div>';

    echo '</div>';
}
