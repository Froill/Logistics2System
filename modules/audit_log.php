<?php
// Unified Audit Log Module
// Logs actions from TCAO, FVM, VRDS, DTP, and other modules
// Table: audit_log (id, module, action, record_id, user, details, timestamp)

require_once __DIR__ . '/../includes/functions.php';

function log_audit_event($module, $action, $record_id, $user, $details = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO audit_log (module, action, record_id, user, details, timestamp) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('ssiss', $module, $action, $record_id, $user, $details);
    $stmt->execute();
}

function audit_log_view() {
    $logs = fetchAll('audit_log');
    echo '<div class="overflow-x-auto">';
    echo '<h2 class="text-2xl font-bold mb-4">Audit Logs</h2>';
    echo '<table class="table table-zebra w-full">';
    echo '<thead><tr><th>ID</th><th>Module</th><th>Action</th><th>Record ID</th><th>User</th><th>Details</th><th>Timestamp</th></tr></thead><tbody>';
    foreach ($logs as $log) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($log['id']) . '</td>';
        echo '<td>' . htmlspecialchars($log['module']) . '</td>';
        echo '<td>' . htmlspecialchars($log['action']) . '</td>';
        echo '<td>' . htmlspecialchars($log['record_id']) . '</td>';
        echo '<td>' . htmlspecialchars($log['user']) . '</td>';
        echo '<td>' . htmlspecialchars($log['details']) . '</td>';
        echo '<td>' . htmlspecialchars($log['timestamp']) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
