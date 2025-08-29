<?php
session_start();
require_once 'db.php'; // DB connection
require_once 'mailer.php'; // Mailer functions

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
function ip_net(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        // IPv4 /24 (zero out last octet)
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = '0';
        }
        return implode('.', $parts) . '/24';
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // IPv6 /64 (keep first 4 hextets)
        $hextets = explode(':', $ip);
        $first4 = array_slice($hextets, 0, 4);
        return implode(':', $first4) . '::/64';
    }
    return 'unknown';
}

$username = sanitize($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $_SESSION['error'] = "Both fields are required.";
    $_SESSION['username'] = $username;
    header("Location: ../login.php");
    exit();
}

// reCAPTCHA validation
$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
$secretKey = "6Lf6lrArAAAAACTLFi57Z6MeWOYCkAQ2cV9kkeyu";

$response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$recaptchaResponse}");
$responseData = json_decode($response);

if (!$responseData->success) {
    $_SESSION['error'] = "reCAPTCHA failed. Please try again.";
    header("Location: ../login.php");
    exit();
}

try {
    // Lazy cleanup of expired trusted devices
    $conn->query("DELETE FROM trusted_devices WHERE expires_at < NOW()");

    // Prepare statement with MySQLi
    $stmt = $conn->prepare("SELECT id, eid, username, password, role, email 
                        FROM users 
                        WHERE username = ? OR eid = ? 
                        LIMIT 1");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {

        /*** Hybrid trust check: cookie + fingerprint ***/
        if (!empty($_COOKIE['device_token'])) {
            $deviceToken = $_COOKIE['device_token'];
            $ua = ua_hash();
            $net = ip_net();

            // Fetch device row
            $q = $conn->prepare("
                SELECT id, expires_at, ua_hash, ip_net
                FROM trusted_devices
                WHERE user_id = ? AND device_token = ? AND expires_at > NOW()
                LIMIT 1
            ");
            $q->bind_param("is", $user['id'], $deviceToken);
            $q->execute();
            $row = $q->get_result()->fetch_assoc();
            $q->close();

            // Check fingerprint match
            if ($row && hash_equals($row['ua_hash'], $ua) && $row['ip_net'] === $net) {
                // Trusted device & fingerprint OK → login without OTP
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['eid']      = $user['eid'];
                $_SESSION['role']     = $user['role'];
                $_SESSION['username'] = $user['username'];

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
            'username' => $user['username'],
            'email' => $user['email']
        ];

        if (sendOTPEmail($user['email'], $otp)) {
            header("Location: ../verify-otp.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to send OTP email. Try again.";
            header("Location: ../login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Invalid username or password.";
        $_SESSION['username'] = $username;
        header("Location: ../login.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Login DB error: " . $e->getMessage());
    $_SESSION['error'] = "An unexpected error occurred. Please try again later.";
    header("Location: ../login.php");
    exit();
}
