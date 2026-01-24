<?php
session_start();
require_once __DIR__ . '/modules/audit_log.php';

// Store user info before destroying the session
$userId = $_SESSION['user_id'] ?? null;
$eid    = $_SESSION['eid'] ?? null;

// Record audit log only if a user was logged in
if ($userId && $eid) {
    log_audit_event(
        'Authentication',
        'Logout',
        $userId,
        $_SESSION['full_name'] ?? 'unknown',
        'User logged out successfully'
    );
}

// Destroy session after logging
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
