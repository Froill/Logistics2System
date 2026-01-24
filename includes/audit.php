<?php
function log_audit_event($module, $action, $record_id, $user_id, $details = null)
{
    global $conn;
    $stmt = $conn->prepare("INSERT INTO audit_log (module, action, record_id, user, details, timestamp) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('ssiss', $module, $action, $record_id, $user_id, $details);
    $stmt->execute();
}
