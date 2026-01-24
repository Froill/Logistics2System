<?php

/**
 * Admin Lock Management Module
 * 
 * This module provides functionality for administrators to:
 * - View locked accounts
 * - Unlock accounts manually
 * - View login attempt history
 * - Generate security reports
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security_lockout.php';
require_once __DIR__ . '/audit_log.php';

// Check if user is admin (only start session if not already active)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdmin()
{
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['superadmin', 'admin']);
}

/**
 * Get all currently locked accounts (unique users only)
 */
function getAllLockedAccounts()
{
    global $conn;

    try {
        // Get locked accounts from users table to avoid duplicates
        $stmt = $conn->prepare("
            SELECT 
                u.id as user_id,
                u.full_name,
                u.email,
                u.failed_login_count,
                u.locked_until,
                u.account_locked
            FROM users u
            WHERE u.account_locked = 1 
            AND u.locked_until > NOW()
            ORDER BY u.locked_until DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $accounts = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $accounts;
    } catch (Exception $e) {
        error_log("Failed to get locked accounts: " . $e->getMessage());
        return [];
    }
}

/**
 * Get login attempt history for a user
 */
function getUserLoginHistory($email, $days = 30)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT 
            id, email, ip_address, attempt_time, failure_reason
        FROM failed_login_attempts
        WHERE email = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY attempt_time DESC
        LIMIT 100
    ");
    $stmt->bind_param("si", $email, $days);
    $stmt->execute();
    $result = $stmt->get_result();
    $history = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $history;
}

/**
 * Unlock a user account (Admin action)
 */
function adminUnlockAccount($userId, $reason = 'Admin unlock', $adminId = null)
{
    if (!isAdmin()) {
        return ['success' => false, 'message' => 'Unauthorized'];
    }

    $success = unlockAccount($userId, $reason, $adminId);

    return [
        'success' => $success,
        'message' => $success ? 'Account unlocked successfully' : 'Failed to unlock account'
    ];
}

/**
 * Get security dashboard statistics
 */
function getSecurityStats()
{
    global $conn;

    $stats = [];

    // Currently locked accounts
    $result = $conn->query("
        SELECT COUNT(*) as count FROM users 
        WHERE account_locked = 1 AND locked_until > NOW()
    ");
    $row = $result->fetch_assoc();
    $stats['total_locked_accounts'] = $row['count'] ?? 0;

    // Total failed attempts (sum across all users)
    $result = $conn->query("
        SELECT SUM(failed_login_count) as count FROM users
        WHERE failed_login_count > 0
    ");
    $row = $result->fetch_assoc();
    $stats['total_failed_attempts'] = $row['count'] ?? 0;

    // Blocked/rate-limited IP addresses
    $result = $conn->query("
        SELECT COUNT(DISTINCT ip_address) as count FROM ip_rate_limits 
        WHERE is_blocked = 1 AND blocked_until > NOW()
    ");
    $row = $result->fetch_assoc();
    $stats['locked_ips'] = $row['count'] ?? 0;

    // Total users in system
    $result = $conn->query("
        SELECT COUNT(*) as count FROM users
    ");
    $row = $result->fetch_assoc();
    $stats['total_users'] = $row['count'] ?? 0;

    return $stats;
}

/**
 * Get list of most targeted accounts (attempted logins)
 */
function getMostTargetedAccounts($limit = 20)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT 
            email,
            COUNT(*) as attempt_count,
            COUNT(DISTINCT ip_address) as unique_ips,
            MAX(attempt_time) as last_attempt
        FROM failed_login_attempts
        WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY email
        ORDER BY attempt_count DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $accounts = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $accounts;
}

/**
 * Reset all locks for testing/administrative purposes
 * DANGEROUS - Only for trusted admins
 */
function resetAllLocks($adminId = null)
{
    global $conn;

    try {
        $conn->begin_transaction();

        // Unlock all accounts
        $stmt = $conn->prepare("
            UPDATE users SET account_locked = 0, locked_until = NULL, failed_login_count = 0
            WHERE account_locked = 1
        ");
        $stmt->execute();
        $stmt->close();

        // Reset IP rate limits
        $stmt = $conn->prepare("
            UPDATE ip_rate_limits SET is_blocked = 0, blocked_until = NULL, request_count = 0
        ");
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        // Log this action
        log_audit_event('Security', 'Reset All Locks', $adminId ?? 0, 'System', 'All account lockouts and IP rate limits have been reset');

        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Failed to reset all locks: " . $e->getMessage());
        return false;
    }
}
