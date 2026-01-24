<?php
// ---------------------------
// SESSION VALIDATION
// 1. CHECK IF USER IS LOGGED IN
// ---------------------------
// If 'user_id' is not set in session, redirect to login page
if (!isset($_SESSION['user_id'])) {
    // Optional: clear any leftover session data
    session_unset();
    session_destroy();

    // Redirect user to login page
    header("Location: login.php");
    exit; // Stop script execution after redirect
}

// ---------------------------
// 2. CHECK IF USER ROLE HAS CHANGED IN DATABASE
// ---------------------------
// Verify that the current session role matches the database role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    require_once __DIR__ . '/db.php';

    $user_id = $_SESSION['user_id'];
    $session_role = $_SESSION['role'];

    // Fetch the current role from database
    $sql = "SELECT role FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // If user not found or role has changed, logout
    if (!$user || $user['role'] !== $session_role) {
        session_unset();
        session_destroy();
        header("Location: logout.php");
        exit;
    }
}
