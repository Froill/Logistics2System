<?php
session_start();

require_once 'db.php';
require_once 'audit.php';
require_once dirname(__DIR__) . '/modules/audit_log.php';

// Verify user is authenticated
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Session expired. Please log in again.';
    header('Location: ../login.php');
    exit();
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php');
    exit();
}

// Verify acceptance checkbox
if (!isset($_POST['accept_tnc']) || $_POST['accept_tnc'] !== '1') {
    $_SESSION['error'] = 'You must accept the terms and conditions to continue.';
    header('Location: ../terms-and-conditions.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

try {
    // Try to update user record to mark T&C as accepted
    // Use ALTER IGNORE or check if columns exist first
    $stmt = $conn->prepare("
        UPDATE users 
        SET t_and_c_accepted = 1, t_and_c_accepted_at = NOW() 
        WHERE id = ?
    ");

    if ($stmt) {
        $stmt->bind_param('i', $user_id);

        if (!$stmt->execute()) {
            // If columns don't exist, skip database update (backward compatibility)
            // User still continues to dashboard after T&C acceptance
            if (strpos($stmt->error, 'Unknown column') === false) {
                throw new Exception('Failed to update user record: ' . $stmt->error);
            }
        }

        $stmt->close();
    }

    // Update session to reflect T&C acceptance
    $_SESSION['t_and_c_accepted'] = 1;

    // Log this action
    log_audit_event(
        'Authentication',
        'T&C Acceptance',
        $user_id,
        $full_name,
        'User accepted Terms and Conditions'
    );

    // Redirect to dashboard
    header('Location: ../dashboard.php');
    exit();
} catch (Exception $e) {
    error_log('T&C Acceptance Error: ' . $e->getMessage());
    $_SESSION['error'] = 'An error occurred while processing your request. Please try again.';
    header('Location: ../terms-and-conditions.php');
    exit();
}
