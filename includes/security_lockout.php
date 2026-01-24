<?php

/**
 * Security Module: Account Lockout & Login Rate Limiting
 * 
 * This module handles:
 * - Failed login attempt tracking
 * - Account lockout after X failed attempts
 * - IP-based rate limiting
 * - Account unlock functionality
 */

require_once 'db.php';
require_once 'config.php';
require_once 'security_config.php';

// Security Configuration - Load from security_config.php
define('MAX_FAILED_ATTEMPTS', SECURITY_MAX_FAILED_ATTEMPTS);
define('LOCKOUT_DURATION_MINUTES', SECURITY_LOCKOUT_DURATION_MINUTES);
define('IP_RATE_LIMIT_ATTEMPTS', SECURITY_IP_RATE_LIMIT_ATTEMPTS);
define('IP_RATE_LIMIT_WINDOW_MINUTES', SECURITY_IP_RATE_LIMIT_WINDOW_MINUTES);

/**
 * Get client IP address (handles proxies and load balancers)
 * 
 * @return string The client's IP address
 */
function getClientIP()
{
    // Check for IP from shared internet
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // Check for IP passed from proxy
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Can contain multiple IPs, get the first one
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    }
    // Check for remote address
    else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // Validate IP format
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = '0.0.0.0';
    }

    return $ip;
}

/**
 * Generate user agent hash for fingerprinting
 * 
 * @return string SHA256 hash of user agent
 */
function getUserAgentHash()
{
    return hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
}

/**
 * Check if account is currently locked
 * 
 * @param int $userId User ID
 * @param string $email User email
 * @return array ['is_locked' => bool, 'locked_until' => datetime|null, 'message' => string]
 */
function isAccountLocked($userId, $email)
{
    global $conn;

    // Check users table first (primary location)
    $stmt = $conn->prepare("
        SELECT account_locked, locked_until 
        FROM users 
        WHERE id = ? AND email = ?
        LIMIT 1
    ");
    $stmt->bind_param("is", $userId, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return [
            'is_locked' => false,
            'locked_until' => null,
            'message' => ''
        ];
    }

    // Check if account is locked
    if ($user['account_locked'] && $user['locked_until']) {
        $lockedUntil = new DateTime($user['locked_until']);
        $now = new DateTime();

        if ($now < $lockedUntil) {
            // Account is still locked
            $minutesRemaining = ceil(($lockedUntil->getTimestamp() - $now->getTimestamp()) / 60);
            return [
                'is_locked' => true,
                'locked_until' => $user['locked_until'],
                'message' => "Account is temporarily locked due to too many failed login attempts. Please try again in " . $minutesRemaining . " minutes."
            ];
        } else {
            // Lockout period has expired, unlock account
            unlockAccount($userId, 'Automatic unlock - lockout period expired');
            return [
                'is_locked' => false,
                'locked_until' => null,
                'message' => ''
            ];
        }
    }

    return [
        'is_locked' => false,
        'locked_until' => null,
        'message' => ''
    ];
}

/**
 * Check if IP is rate limited
 * 
 * @param string $ipAddress IP address to check
 * @return array ['is_limited' => bool, 'blocked_until' => datetime|null, 'message' => string]
 */
function isIPRateLimited($ipAddress)
{
    global $conn;

    // Get IP limit record
    $stmt = $conn->prepare("
        SELECT id, is_blocked, blocked_until, request_count, first_request_time
        FROM ip_rate_limits
        WHERE ip_address = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $ipAddress);
    $stmt->execute();
    $result = $stmt->get_result();
    $ipRecord = $result->fetch_assoc();
    $stmt->close();

    if (!$ipRecord) {
        // No record yet, create one
        createIPRateLimit($ipAddress);
        return [
            'is_limited' => false,
            'blocked_until' => null,
            'message' => ''
        ];
    }

    // Check if IP is currently blocked
    if ($ipRecord['is_blocked'] && $ipRecord['blocked_until']) {
        $blockedUntil = new DateTime($ipRecord['blocked_until']);
        $now = new DateTime();

        if ($now < $blockedUntil) {
            // IP is still blocked
            $minutesRemaining = ceil(($blockedUntil->getTimestamp() - $now->getTimestamp()) / 60);
            return [
                'is_limited' => true,
                'blocked_until' => $ipRecord['blocked_until'],
                'message' => "Too many login attempts from your IP address. Please try again in " . $minutesRemaining . " minutes."
            ];
        } else {
            // Block period has expired, unblock IP
            resetIPRateLimit($ipAddress);
            return [
                'is_limited' => false,
                'blocked_until' => null,
                'message' => ''
            ];
        }
    }

    return [
        'is_limited' => false,
        'blocked_until' => null,
        'message' => ''
    ];
}

/**
 * Record a failed login attempt
 * 
 * @param string $email User email
 * @param string $ipAddress IP address
 * @param string|null $reason Reason for failure
 * @return bool Success status
 */
function recordFailedLoginAttempt($email, $ipAddress, $reason = null)
{
    global $conn;

    try {
        // Record in failed_login_attempts table
        $stmt = $conn->prepare("
            INSERT INTO failed_login_attempts (email, ip_address, user_agent_hash, failure_reason)
            VALUES (?, ?, ?, ?)
        ");
        $userAgentHash = getUserAgentHash();
        $stmt->bind_param("ssss", $email, $ipAddress, $userAgentHash, $reason);
        $stmt->execute();
        $stmt->close();

        // Get user by email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            $userId = $user['id'];

            // Count failed attempts in last window
            $windowStart = date('Y-m-d H:i:s', time() - (60 * LOCKOUT_DURATION_MINUTES));
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count FROM failed_login_attempts
                WHERE email = ? AND attempt_time > ?
            ");
            $stmt->bind_param("ss", $email, $windowStart);
            $stmt->execute();
            $countResult = $stmt->get_result();
            $countRow = $countResult->fetch_assoc();
            $stmt->close();
            $failedCount = $countRow['count'] + 1;

            // Update user record
            $stmt = $conn->prepare("
                UPDATE users 
                SET failed_login_count = ?, last_failed_login = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $failedCount, $userId);
            $stmt->execute();
            $stmt->close();

            // Lock account if threshold reached
            if ($failedCount >= MAX_FAILED_ATTEMPTS) {
                lockAccount($userId, $email, 'failed_attempts', $failedCount);
                return false;
            }
        }

        // Update IP rate limit
        incrementIPAttempt($ipAddress);

        return true;
    } catch (Exception $e) {
        error_log("Failed to record login attempt: " . $e->getMessage());
        return false;
    }
}

/**
 * Lock an account
 * 
 * @param int $userId User ID
 * @param string $email User email
 * @param string $reason Lock reason
 * @param int $failedCount Failed attempts count
 * @return bool Success status
 * 
 */
date_default_timezone_set('Asia/Manila');

function lockAccount($userId, $email, $reason = 'failed_attempts', $failedCount = 0)
{
    global $conn;

    try {
        $lockedUntil = date('Y-m-d H:i:s', time() + (LOCKOUT_DURATION_MINUTES * 60));

        // Update users table
        $stmt = $conn->prepare("
            UPDATE users
            SET account_locked = 1, locked_until = ?
            WHERE id = ?
        ");
        $stmt->bind_param("si", $lockedUntil, $userId);
        $stmt->execute();
        $stmt->close();

        // Record in account_lockouts table
        $stmt = $conn->prepare("
            INSERT INTO account_lockouts (user_id, email, lockout_reason, locked_until, failed_attempts_count)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssi", $userId, $email, $reason, $lockedUntil, $failedCount);
        $stmt->execute();
        $stmt->close();

        // Log audit event
        require_once 'audit.php';
        log_audit_event(
            'Authentication',
            'Account Locked',
            $userId,
            $email,
            "Account locked due to $reason. Failed attempts: $failedCount"
        );

        return true;
    } catch (Exception $e) {
        error_log("Failed to lock account: " . $e->getMessage());
        return false;
    }
}

/**
 * Unlock an account
 * 
 * @param int $userId User ID
 * @param string $unlockReason Reason for unlock
 * @param int|null $unlockedBy Admin user ID who unlocked
 * @return bool Success status
 */
function unlockAccount($userId, $unlockReason = 'Manual unlock', $unlockedBy = null)
{
    global $conn;

    try {
        // Get user email
        $stmt = $conn->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            return false;
        }

        $email = $user['email'];

        // Update users table
        $stmt = $conn->prepare("
            UPDATE users
            SET account_locked = 0, locked_until = NULL, failed_login_count = 0, last_failed_login = NULL
            WHERE id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

        // Update account_lockouts table
        $stmt = $conn->prepare("
            UPDATE account_lockouts
            SET unlocked_at = NOW(), unlocked_by = ?, unlock_reason = ?
            WHERE user_id = ? AND unlocked_at IS NULL
        ");
        $stmt->bind_param("isi", $unlockedBy, $unlockReason, $userId);
        $stmt->execute();
        $stmt->close();

        // Log audit event
        if (file_exists(__DIR__ . '/audit.php')) {
            require_once 'audit.php';
            $unlockedByText = $unlockedBy ? "by admin user $unlockedBy" : "automatically";
            log_audit_event(
                'Authentication',
                'Account Unlocked',
                $userId,
                $email,
                "Account unlocked $unlockedByText. Reason: $unlockReason"
            );
        }

        return true;
    } catch (Exception $e) {
        error_log("Failed to unlock account: " . $e->getMessage());
        return false;
    }
}

/**
 * Create IP rate limit record
 * 
 * @param string $ipAddress IP address
 * @return bool Success status
 */
function createIPRateLimit($ipAddress)
{
    global $conn;

    try {
        $stmt = $conn->prepare("
            INSERT INTO ip_rate_limits (ip_address, request_count)
            VALUES (?, 1)
        ");
        $stmt->bind_param("s", $ipAddress);
        $stmt->execute();
        $stmt->close();
        return true;
    } catch (Exception $e) {
        error_log("Failed to create IP rate limit: " . $e->getMessage());
        return false;
    }
}

/**
 * Increment IP attempt count and check if should block
 * 
 * @param string $ipAddress IP address
 * @return bool True if IP is now blocked
 */
function incrementIPAttempt($ipAddress)
{
    global $conn;

    try {
        // Update attempt count
        $stmt = $conn->prepare("
            UPDATE ip_rate_limits
            SET request_count = request_count + 1, last_request_time = NOW()
            WHERE ip_address = ?
        ");
        $stmt->bind_param("s", $ipAddress);
        $stmt->execute();
        $stmt->close();

        // Check if we need to block
        $stmt = $conn->prepare("
            SELECT request_count, first_request_time FROM ip_rate_limits
            WHERE ip_address = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $ipAddress);
        $stmt->execute();
        $result = $stmt->get_result();
        $record = $result->fetch_assoc();
        $stmt->close();

        if ($record) {
            // Check if attempts exceed limit within the time window
            $firstRequestTime = new DateTime($record['first_request_time']);
            $now = new DateTime();
            $minutesPassed = ($now->getTimestamp() - $firstRequestTime->getTimestamp()) / 60;

            // If more than X minutes have passed, reset the count
            if ($minutesPassed > IP_RATE_LIMIT_WINDOW_MINUTES) {
                resetIPRateLimit($ipAddress);
                return false;
            }

            // Block if exceeds attempt limit
            if ($record['request_count'] >= IP_RATE_LIMIT_ATTEMPTS) {
                $blockedUntil = date('Y-m-d H:i:s', time() + (LOCKOUT_DURATION_MINUTES * 60));
                $stmt = $conn->prepare("
                    UPDATE ip_rate_limits
                    SET is_blocked = 1, blocked_until = ?, block_reason = ?
                    WHERE ip_address = ?
                ");
                $reason = "Exceeded " . IP_RATE_LIMIT_ATTEMPTS . " login attempts";
                $stmt->bind_param("sss", $blockedUntil, $reason, $ipAddress);
                $stmt->execute();
                $stmt->close();
                return true;
            }
        }

        return false;
    } catch (Exception $e) {
        error_log("Failed to increment IP attempt: " . $e->getMessage());
        return false;
    }
}

/**
 * Reset IP rate limit
 * 
 * @param string $ipAddress IP address
 * @return bool Success status
 */
function resetIPRateLimit($ipAddress)
{
    global $conn;

    try {
        $stmt = $conn->prepare("
            UPDATE ip_rate_limits
            SET request_count = 0, is_blocked = 0, blocked_until = NULL, first_request_time = NOW()
            WHERE ip_address = ?
        ");
        $stmt->bind_param("s", $ipAddress);
        $stmt->execute();
        $stmt->close();
        return true;
    } catch (Exception $e) {
        error_log("Failed to reset IP rate limit: " . $e->getMessage());
        return false;
    }
}

/**
 * Get login attempt statistics for an email
 * 
 * @param string $email User email
 * @return array Statistics about failed attempts
 */
function getLoginAttemptStats($email)
{
    global $conn;

    try {
        // Get recent failed attempts (last 24 hours)
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_attempts,
                COUNT(DISTINCT ip_address) as unique_ips,
                MAX(attempt_time) as last_attempt
            FROM failed_login_attempts
            WHERE email = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();

        // Get lockout history
        $stmt = $conn->prepare("
            SELECT COUNT(*) as lockout_count FROM account_lockouts
            WHERE email = ? AND locked_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $lockoutData = $result->fetch_assoc();
        $stmt->close();

        return [
            'total_attempts' => $stats['total_attempts'] ?? 0,
            'unique_ips' => $stats['unique_ips'] ?? 0,
            'last_attempt' => $stats['last_attempt'] ?? null,
            'lockout_count_30days' => $lockoutData['lockout_count'] ?? 0
        ];
    } catch (Exception $e) {
        error_log("Failed to get login stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Get locked accounts (for admin dashboard)
 * 
 * @param int $limit Number of records to return
 * @return array List of locked accounts
 */
function getLockedAccounts($limit = 50)
{
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT 
                al.id,
                al.user_id,
                al.email,
                u.full_name,
                al.lockout_reason,
                al.locked_at,
                al.locked_until,
                al.failed_attempts_count,
                CASE 
                    WHEN al.locked_until > NOW() THEN 'active'
                    ELSE 'expired'
                END as status
            FROM account_lockouts al
            JOIN users u ON al.user_id = u.id
            WHERE al.unlocked_at IS NULL
            ORDER BY al.locked_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
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
 * Clear failed login attempts for a user
 * Used after successful login
 * 
 * @param string $email User email
 * @return bool Success status
 */
function clearFailedLoginAttempts($email)
{
    global $conn;

    try {
        // Get user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            return false;
        }

        // Reset failed count
        $stmt = $conn->prepare("
            UPDATE users
            SET failed_login_count = 0, last_failed_login = NULL
            WHERE id = ?
        ");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $stmt->close();

        return true;
    } catch (Exception $e) {
        error_log("Failed to clear login attempts: " . $e->getMessage());
        return false;
    }
}
