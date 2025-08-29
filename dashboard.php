<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

require __DIR__ . '/includes/functions.php';

$id = $_SESSION['user_id'];
$eid = $_SESSION['eid'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// allowed modules (matching filenames in /modules)
$allowed_modules = [
  'fvm' => 'Fleet & Vehicle Management',
  'vrds' => 'Vehicle Routing & Dispatch',
  'driver_trip' => 'Driver & Trip Performance',
  'tcao' => 'Transport Cost Analysis',
  'user_management' => 'User Management'
];

// which module to show (default fvm)
$module = $_GET['module'] ?? 'fvm';
if (!array_key_exists($module, $allowed_modules)) {
  $module = 'fvm';
}

// base url for forms/links
$baseURL = 'dashboard.php?module=' . $module;

/* ------------------------------------------------------------------
   Handle module logic BEFORE outputting HTML
   ------------------------------------------------------------------ */
$moduleFile = __DIR__ . "/modules/{$module}.php";
if (file_exists($moduleFile)) {
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
</head>

<body class="min-h-screen flex flex-row">
  <?php include 'includes/sidebar.php'; ?>

  <div class="flex flex-col flex-grow">
    <?php include 'includes/navbar.php'; ?>
    <div class="p-4">


      <!-- Module content -->
      <div class="card mt-4 p-4 shadow rounded">
        <main class="card-body flex-1 p-6">
          <?php
          if (function_exists("{$module}_view")) {
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
</body>

</html>