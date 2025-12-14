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
