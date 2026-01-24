<?php
session_start();
require_once 'db.php'; // DB connection
require_once 'mailer.php'; // Mailer functions
require_once 'security_lockout.php'; // Account lockout & rate limiting
require_once dirname(__DIR__) . '/modules/audit_log.php'; // Audit log functions

// Helper: sanitize input
function sanitize($data)
{
    return htmlspecialchars(trim($data));
}

/** Fingerprint helpers **/
function ua_hash(): string
{
    return hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
}

// Get client IP address
$clientIP = getClientIP();

// ============================================
// 1. CHECK IP RATE LIMITING
// ============================================
$ipLimitCheck = isIPRateLimited($clientIP);
if ($ipLimitCheck['is_limited']) {
    $_SESSION['error'] = $ipLimitCheck['message'];
    $_SESSION['eid'] = sanitize($_POST['eid'] ?? '');
    header("Location: ../login.php");
    exit();
}

$eid = sanitize($_POST['eid'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($eid) || empty($password)) {
    $_SESSION['error'] = "Both fields are required.";
    $_SESSION['eid'] = $eid;
    header("Location: ../login.php");
    exit();
}

// reCAPTCHA validation
$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
$secretKey = "6Lf6lrArAAAAACTLFi57Z6MeWOYCkAQ2cV9kkeyu";

// Prepare POST data
$postData = http_build_query([
    'secret'   => $secretKey,
    'response' => $recaptchaResponse,
]);

// Init cURL
$ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

// Execute
$response = curl_exec($ch);
curl_close($ch);

$responseData = json_decode($response, true);

if (empty($responseData['success'])) {
    $errorMessage = "reCAPTCHA failed.";
    if (!empty($responseData['error-codes'])) {
        $errorMessage .= " Error codes: " . implode(", ", $responseData['error-codes']);
    }
    if (!empty($responseData['hostname'])) {
        $errorMessage .= " Hostname: " . $responseData['hostname'];
    }
    error_log($errorMessage);

    $_SESSION['error'] = "reCAPTCHA failed. Please try again.";
    header("Location: ../login.php");
    exit();
}

try {
    // Lazy cleanup of expired trusted devices
    $conn->query("DELETE FROM trusted_devices WHERE expires_at < NOW()");

    // Prepare statement with MySQLi
    $stmt = $conn->prepare("SELECT id, eid, full_name, password, role, email 
                        FROM users 
                        WHERE email = ? 
                        LIMIT 1");
    $stmt->bind_param("s", $eid);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // ============================================
    // 2. CHECK ACCOUNT LOCKOUT STATUS
    // ============================================
    if ($user) {
        $lockoutCheck = isAccountLocked($user['id'], $user['email']);
        if ($lockoutCheck['is_locked']) {
            $_SESSION['error'] = $lockoutCheck['message'];
            $_SESSION['eid'] = $eid;
            // Still count this as a failed attempt for rate limiting
            recordFailedLoginAttempt($user['email'], $clientIP, 'Account locked');
            header("Location: ../login.php");
            exit();
        }
    }

    // ============================================
    // 3. VERIFY CREDENTIALS
    // ============================================
    if ($user && password_verify($password, $user['password'])) {
        // Clear failed login attempts on successful authentication
        clearFailedLoginAttempts($user['email']);

        /*** Hybrid trust check: cookie + fingerprint ***/
        if (!empty($_COOKIE['device_token'])) {
            $deviceToken = $_COOKIE['device_token'];
            $ua = ua_hash();

            // Fetch device row
            $q = $conn->prepare("
                SELECT id, expires_at, ua_hash
                FROM trusted_devices
                WHERE user_id = ? AND device_token = ? AND expires_at > NOW()
                LIMIT 1
            ");
            $q->bind_param("is", $user['id'], $deviceToken);
            $q->execute();
            $row = $q->get_result()->fetch_assoc();
            $q->close();

            // Check fingerprint match
            if ($row && hash_equals($row['ua_hash'], $ua)) {
                // Trusted device & fingerprint OK → login without OTP
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['eid']      = $user['eid'];
                $_SESSION['role']     = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['current_module'] = 'dashboard';

                log_audit_event('Authentication', 'Login', $user['id'], $eid, 'User logged in via trusted device');
                header("Location: ../dashboard.php");
                exit();
            }
        }
        /*** Not trusted or fingerprint mismatch → fall back to OTP flow ***/

        //OTP generation
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otp_expiration = time() + (5 * 60); // 5 minutes

        $_SESSION['otp'] = $otp;
        $_SESSION['otp_expires'] = $otp_expiration;
        $_SESSION['pending_user'] = [
            'id' => $user['id'],
            'eid' => $user['eid'],
            'role' => $user['role'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'current_module' => 'dashboard'

        ];

        if (sendOTPEmail($user['email'], $otp)) {
            log_audit_event('Authentication', 'OTP Sent', $user['id'], $user['eid'], 'OTP sent for login');
            header("Location: ../verify-otp.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to send OTP email. Try again.";
            header("Location: ../login.php");
            exit();
        }
    } else {
        // ============================================
        // 4. RECORD FAILED LOGIN ATTEMPT
        // ============================================
        if ($user) {
            recordFailedLoginAttempt($user['email'], $clientIP, 'Invalid password');
            $_SESSION['error'] = "Invalid email or password.";
            log_audit_event('User Management', 'Failed Attempt', $user['id'], $eid, 'Invalid password entered');
        } else {
            // User not found - still record attempt and check IP rate limit
            recordFailedLoginAttempt($eid, $clientIP, 'User not found');
            $_SESSION['error'] = "Invalid email or password.";
            // Don't log which email doesn't exist (security best practice)
        }
        $_SESSION['eid'] = $eid;
        header("Location: ../login.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Login DB error: " . $e->getMessage());
    $_SESSION['error'] = "An unexpected error occurred. Please try again later.";
    header("Location: ../login.php");
    exit();
}
