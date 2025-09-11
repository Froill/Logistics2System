<?php
session_start();
require_once 'db.php';
require_once 'mailer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $_SESSION['fp_error'] = "Email is required.";
        header("Location: ../forgot-password.php");
        exit();
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        // Set timezone to Manila
        date_default_timezone_set('Asia/Manila');

        // Expire 1 hour from now in Philippine time
        $expires = date("Y-m-d H:i:s", time() + 3600);

        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user['id'], $token, $expires);
        $stmt->execute();

        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $baseUrl .= "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2);
        $resetLink = $baseUrl . "/reset-password.php?token=$token";
        $subject = "Password Reset Request";
        $firstName = explode(' ', $user['full_name'])[0];

        $body = "Hi {$firstName},<br><br>
                We received a request to reset your password.  
                Click the button below to choose a new one:<br><br>

                <a href='$resetLink' 
                style='display:inline-block;
                        padding:12px 24px;
                        font-size:16px;
                        font-weight:bold;
                        color:#ffffff;
                        background-color:#011e56;
                        text-decoration:none;
                        border-radius:6px;'>
                Reset Password
                </a><br><br>

                If you didn’t request a password reset, you can safely ignore this email.<br><br>
                This link will expire in 1 hour.<br><br>";


        sendEmail($email, $subject, $body);

        // after sending email or showing error
        $_SESSION['success'] = "We sent you a password reset link!";
        header("Location: ../login.php");
        exit();
    } else {
        // after sending email or showing error
        $_SESSION['error'] = "No account found with that email.";
        header("Location: ../login.php");
        exit();
    }
}
