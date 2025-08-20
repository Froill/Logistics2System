<?php
session_start();
require_once 'db.php'; // DB connection
require_once 'mailer.php'; // Mailer functions

// Helper: sanitize input
function sanitize($data)
{
    return htmlspecialchars(trim($data));
}

// Input validation
$username = sanitize($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = "Both fields are required.";
    $_SESSION['username'] = $username;
    header("Location: ../login.php");
    exit();
}

try {
    // Prepare statement with MySQLi
    $stmt = $conn->prepare("SELECT id, username, password, role, email FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if (password_verify($password, $user['password'])) {
            // Credentials are valid, now generate and send OTP

            // Generate 6-digit OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp_expiration = time() + (5 * 60); // OTP valid for 5 minutes

            // Save OTP and expiration in session
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_expires'] = $otp_expiration;

            // Also save user temporarily
            $_SESSION['pending_user'] = [
                'id' => $user['id'],
                'role' => $user['role'],
                'username' => $user['username'],
                'email' => $user['email']
            ];

            // Send OTP to user's Gmail
            if (sendOTPEmail($user['email'], $otp)) {
                header("Location: ../verify-otp.php");
                exit();
            } else {
                $_SESSION['login_error'] = "Failed to send OTP email. Try again.";
                header("Location: ../login.php");
                exit();
            }
        } else {
            $_SESSION['login_error'] = "Invalid username or password.";
            $_SESSION['username'] = $username;
            header("Location: ../login.php");
            exit();
        }
    } else {
        $_SESSION['login_error'] = "Invalid username or password.";
        $_SESSION['username'] = $username;
        header("Location: ../login.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Login DB error: " . $e->getMessage());
    $_SESSION['login_error'] = "An unexpected error occurred. Please try again later.";
    header("Location: ../login.php");
    exit();
}
