<?php
session_start();

// Check if user is in pending OTP stage
if (!isset($_SESSION['otp']) || !isset($_SESSION['pending_user'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputOtp = trim($_POST['otp'] ?? '');

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
        $_SESSION['otp_error'] = 'Too many incorrect attempts. Please login again.';
        session_destroy();
        header('Location: ../login.php');
        exit();
    }

    // Validate OTP
    if ($inputOtp === $_SESSION['otp']) {
        // OTP correct: finalize login
        $_SESSION['user_id'] = $_SESSION['pending_user']['id'];
        $_SESSION['role'] = $_SESSION['pending_user']['role'];
        $_SESSION['username'] = $_SESSION['pending_user']['username'];

        // Clear temporary OTP sessions
        unset($_SESSION['otp']);
        unset($_SESSION['otp_expires']);
        unset($_SESSION['otp_attempts']);
        unset($_SESSION['pending_user']);

        header('Location: ../dashboard.php'); // Success page
        exit();
    } else {
        // Wrong OTP
        $_SESSION['otp_attempts']++;
        $_SESSION['otp_error'] = "Incorrect OTP. Attempts left: " . (3 - $_SESSION['otp_attempts']);
        header('Location: ../verify-otp.php');
        exit();
    }
}
