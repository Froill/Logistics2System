<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

require __DIR__ . '/includes/functions.php';

$id = $_SESSION['user_id'];
$eid = $_SESSION['eid'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// allowed modules (matching filenames in /modules)

$allowed_modules = [
  'dashboard' => 'Dashboard',
  'fvm' => 'Fleet & Vehicle Management',
  'vrds' => 'Vehicle Routing & Dispatch',
  'driver_trip' => 'Driver & Trip Performance',
  'tcao' => 'Transport Cost Analysis',
  'user_management' => 'User Management',
  'audit_log' => 'Audit Log',
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
  if (in_array($role, ['admin', 'manager', 'supervisor', 'fleet_manager'])) {
    $dashboard_include = __DIR__ . '/includes/dashboard_admin.php';
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
<html lang="en" data-theme="light">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Logistics 2 Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.0.0/dist/full.css" rel="stylesheet" type="text/css" />
  <link rel="stylesheet" href="./css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="min-h-screen flex flex-row">
  <?php include 'includes/sidebar.php'; ?>

  <div class="flex flex-col flex-grow">
    <?php include 'includes/navbar.php'; ?>
    <div class="p-4">


      <!-- Module content -->
      <div class="card mt-4 p-4 shadow rounded">
        <main class="card-body flex-1 p-6">
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

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.js"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="./js/soliera.js"></script>
  <script>
    lucide.createIcons();
  </script>
</body>

</html>