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
// 2. SESSION TIMEOUT CONFIGURATION
// ---------------------------
// Total session lifetime in seconds
$sessionLifetime = 300; // 5 minutes

// If 'last_activity' is set and session expired, log user out
if (isset($_SESSION['last_activity'])) {
    $inactiveTime = time() - $_SESSION['last_activity']; // Calculate inactivity duration

    if ($inactiveTime > $sessionLifetime) {
        // Clear session data
        session_unset();
        session_destroy();

        // Redirect to logout page
        header("Location: logout.php");
        exit; // Stop further execution
    }
}

// Reset the last activity timestamp to current time
$_SESSION['last_activity'] = time();
