<?php
session_start();
require_once 'db.php';
require_once dirname(__DIR__) . '/modules/audit_log.php'; // Audit log functions

/** Fingerprint helpers **/
function ua_hash(): string
{
    return hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
}
function ip_net(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = '0';
        }
        return implode('.', $parts) . '/24';
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $hextets = explode(':', $ip);
        $first4 = array_slice($hextets, 0, 4);
        return implode(':', $first4) . '::/64';
    }
    return 'unknown';
}

if (!isset($_SESSION['otp']) || !isset($_SESSION['pending_user'])) {
    header('Location: ../login.php');
    exit();
}

// block if previously locked
if (!empty($_SESSION['otp_locked'])) {
    $_SESSION['otp_error'] = 'Too many incorrect attempts. Please login again.';
    session_destroy();
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $inputArray = [
        trim($_POST['otp1']),
        trim($_POST['otp2']),
        trim($_POST['otp3']),
        trim($_POST['otp4']),
        trim($_POST['otp5']),
        trim($_POST['otp6'])
    ];

    $inputOtp = implode('', $inputArray);

    $user = $_SESSION['pending_user'];

    if (empty($inputOtp)) {
        $_SESSION['otp_error'] = 'OTP is required.';
        header('Location: ../verify-otp.php');
        exit();
    }

    // Check OTP expiration
    if (time() > ($_SESSION['otp_expires'] ?? 0)) {
        $_SESSION['otp_error'] = 'OTP has expired. Please login again.';
        session_destroy();
        header('Location: ../login.php');
        exit();
    }

    // Initialize OTP attempts if not yet set
    if (!isset($_SESSION['otp_attempts'])) {
        $_SESSION['otp_attempts'] = 0;
    }

    // Check if maximum attempts reached
    if ($_SESSION['otp_attempts'] >= 3) {
        $_SESSION['otp_locked'] = true; // mark locked
        $_SESSION['otp_error'] = 'Too many incorrect attempts. Please login again.';
        log_audit_event('Authentication', 'OTP Failure', $user['id'], $user['eid'], 'Exceeded maximum OTP attempts');
        session_destroy();
        header('Location: ../login.php');
        exit();
    }

    // Validate OTP
    if ($inputOtp === $_SESSION['otp']) {

        // Complete login
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['eid']       = $user['eid'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];

        /*** Persist trusted device: cookie + fingerprint ***/
        $deviceToken = bin2hex(random_bytes(32)); // random 64-char token
        $ua = ua_hash();
        $net = ip_net();
        $expiresAt = date('Y-m-d H:i:s', time() + 7 * 24 * 60 * 60);

        $stmt = $conn->prepare("
            INSERT INTO trusted_devices (user_id, device_token, ua_hash, ip_net, expires_at, last_seen)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE ua_hash = VALUES(ua_hash), ip_net = VALUES(ip_net),
                                    expires_at = VALUES(expires_at), last_seen = NOW()
        ");
        $stmt->bind_param("issss", $user['id'], $deviceToken, $ua, $net, $expiresAt);
        $stmt->execute();
        $stmt->close();

        // Set cookie (7 days)
        setcookie(
            'device_token',
            $deviceToken,
            [
                'expires'  => time() + 7 * 24 * 60 * 60,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        // Cleanup temp OTP data
        unset($_SESSION['otp'], $_SESSION['otp_expires'], $_SESSION['otp_attempts'], $_SESSION['pending_user'], $_SESSION['otp_locked']);
        log_audit_event(
            'Authentication',
            'Successful Login',
            $user['id'],
            $user['eid'],
            'User successfully logged in after OTP verification'
        );
        header('Location: ../dashboard.php');
        exit();
    } else {
        // Wrong OTP
        $_SESSION['otp_attempts']++;
        if ($_SESSION['otp_attempts'] >= 2) {
            $_SESSION['otp_locked'] = true; // mark locked immediately
        }
        $_SESSION['otp_error'] = "Incorrect OTP. Attempts left: " . max(0, (3 - $_SESSION['otp_attempts']));
        header('Location: ../verify-otp.php');
        exit();
    }
}
