<?php

/**
 * Account Lock/Unlock Handler
 * 
 * Processes account unlock requests from the admin interface.
 */

session_start();

// Access control - only admins can unlock accounts
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['um_error'] = 'Unauthorized access.';
    header("Location: ../dashboard.php");
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security_lockout.php';
require_once __DIR__ . '/../modules/lock_management.php';

$action = $_POST['action'] ?? '';
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

if ($action === 'unlock' && $user_id > 0) {
    // Call the admin unlock function
    $result = adminUnlockAccount($user_id, 'Admin unlock', $_SESSION['user_id'] ?? 0);

    if (isset($result['success']) && $result['success']) {
        $_SESSION['um_success'] = $result['message'] ?? 'Account successfully unlocked.';

        // Log the unlock action
        require_once __DIR__ . '/functions.php';
        log_audit_event(
            'User Mgmt',
            'ACCOUNT_UNLOCKED',
            $user_id,
            $_SESSION['full_name'] ?? 'Admin',
            'Admin unlocked user account'
        );
    } else {
        $_SESSION['um_error'] = $result['message'] ?? 'Failed to unlock account. Please try again.';
    }
} else {
    $_SESSION['um_error'] = 'Invalid request.';
}

// Redirect back to user management
header("Location: ../dashboard.php?module=user_management");
exit;
