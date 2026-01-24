<?php
session_start();
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/ajax.php';
require_once __DIR__ . '/modules/audit_log.php';

// Check if user has accepted T&C
// If t_and_c_accepted is not set in session, redirect to accept first
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// For new sessions, default t_and_c_accepted to 1 if not set (backward compatibility)
// Users will be redirected to T&C page if database says they haven't accepted
if (!isset($_SESSION['t_and_c_accepted'])) {
  $_SESSION['t_and_c_accepted'] = 0;
}

// If user hasn't accepted T&C, redirect them
if (!$_SESSION['t_and_c_accepted']) {
  header("Location: terms-and-conditions.php");
  exit();
}

$id = $_SESSION['user_id'];
$eid = $_SESSION['eid'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// allowed modules (matching filenames in /modules)

$allowed_modules = [
  'dashboard' => 'Dashboard',
  'fvm' => 'Fleet & Vehicle Management',
  'vrds' => 'Vehicle Reservation & Dispatch',
  'driver_trip' => 'Driver & Trip Performance',
  'tcao' => 'Transport Cost Analysis',
  'user_management' => 'User Management',
  'audit_log' => 'Audit Log',
  'profile' => 'User Profile',
];

// which module to show (default dashboard)
$module = $_GET['module'] ?? 'dashboard';
if (!array_key_exists($module, $allowed_modules)) {
  $module = 'dashboard';
}

// base url for forms/links
$baseURL = 'dashboard.php?module=' . $module;

/* ------------------------------------------------------------------
   Handle module logic BEFORE outputting HTML
   ------------------------------------------------------------------ */
$moduleFile = __DIR__ . "/modules/{$module}.php";
// Route dashboard view based on role
if ($module === 'dashboard') {
  if (in_array($role, ['admin', 'manager', 'supervisor', 'manager'])) {
    $dashboard_include = __DIR__ . '/includes/dashboard_admin.php';
  } elseif ($role === 'driver') {
    $dashboard_include = __DIR__ . '/includes/dashboard_driver.php';
  } else {
    $dashboard_include = __DIR__ . '/includes/dashboard_user.php';
  }
} else if (file_exists($moduleFile)) {
  // each module file can define a function for its logic
  require_once $moduleFile;
  if (function_exists("{$module}_logic")) {
    call_user_func("{$module}_logic", $baseURL);
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Meta -->
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/x-icon" href="images/logo/sonly-2.png">
  <title>Logistics 2 Dashboard</title>

  <!-- Theme loader -->
  <script>
    (() => {
      const savedTheme = localStorage.getItem("theme");
      const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
      const theme = savedTheme || (prefersDark ? "dark" : "light");
      document.documentElement.setAttribute("data-theme", theme);
    })();
  </script>

  <!-- Styles -->
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.0.0/dist/full.css" rel="stylesheet" type="text/css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="./css/style.css" />

  <!-- Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="min-h-screen flex w-full">
  <?php include 'includes/sidebar.php'; ?>

  <div class="flex flex-col w-full">
    <?php include 'includes/navbar.php'; ?>
    <div class="">


      <!-- Module content -->
      <div class="card mt-4 shadow rounded">
        <main class="card-body flex-1 p-5">
          <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-error" style="color: #fff; background: #e3342f; padding: 10px; margin-bottom: 10px; border-radius: 4px;">
              <?= htmlspecialchars($_SESSION['error_message']) ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
          <?php endif; ?>
          <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success" style="color: #155724; background: #d4edda; padding: 10px; margin-bottom: 10px; border-radius: 4px;">
              <?= htmlspecialchars($_SESSION['success_message']) ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
          <?php endif; ?>
          <?php
          if ($module === 'dashboard') {
            // Show admin or user dashboard summary
            include $dashboard_include;
          } else if ($module === 'audit_log' && function_exists('audit_log_view')) {
            audit_log_view();
          } else if (function_exists("{$module}_view")) {
            call_user_func("{$module}_view", $baseURL);
          }
          ?>
        </main>
      </div>
    </div>
  </div>

  <!-- Logout Modal -->
  <dialog id="logoutModal" class="modal">
    <form method="POST" action="logout.php" class="modal-box">

      <div class="flex items-center gap-2 mb-3">
        <i data-lucide="log-out" class="size-6"></i>
        <h3 class="font-bold text-lg">Confirm Logout</h3>
      </div>

      <p class="mb-4">Are you sure you want to log out?</p>

      <div class="modal-action">
        <button type="submit" class="btn btn-error">Logout</button>
        <button type="button" class="btn" onclick="logoutModal.close()">Cancel</button>
      </div>
    </form>
  </dialog>

  <!-- Timeout Warning Modal -->
  <dialog id="timeoutModal" class="modal">
    <form method="dialog" class="modal-box text-center">
      <div class="flex items-center gap-2 justify-center mb-3">
        <i data-lucide="clock-fading" class="h-6 w-auto"></i>
        <h3 class="font-bold text-lg">Session Expiring!</h3>
      </div>

      <p class="py-4">
        You will be logged out in
        <span id="countdown" class="font-mono text-error font-bold">30</span> seconds
        due to inactivity.
      </p>
      <div class="modal-action justify-center">
        <!-- "Stay Logged In" button resets activity -->
        <button id="stayLoggedIn" class="btn btn-primary">Stay Logged In</button>
      </div>
    </form>
  </dialog>

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.js"></script>
  <script src="./js/soliera.js"></script>
  <script src="./js/session-timeout.js"></script>

</body>

</html>