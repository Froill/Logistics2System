<?php
session_start();
require_once 'db.php';

$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($password !== $confirm) {
        $_SESSION['reset_error'] = "Passwords do not match.";
        header("Location: ../reset-password.php?token=" . urlencode($token));
        exit();
    }

    // Force MySQL to use Asia/Manila timezone
    $conn->query("SET time_zone = '+08:00'");

    $stmt = $conn->prepare("
    SELECT * FROM password_resets
    WHERE token = ?
      AND used = 0
      AND expires_at > NOW()
    LIMIT 1
");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();

    if ($reset) {
        // Hash password and update
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $reset['user_id']);
        $stmt->execute();

        // Mark token used
        $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
        $stmt->bind_param("i", $reset['id']);
        $stmt->execute();

        $_SESSION['success'] = "Password reset successful. Please login.";
        header("Location: ../login.php");
        exit();
    } else {
        $_SESSION['error'] = "Invalid or expired reset link.";
        header("Location: ../reset-password.php?token=" . urlencode($token));
        exit();
    }
}
