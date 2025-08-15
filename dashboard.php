<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

require __DIR__ . '/includes/functions.php';

$role = $_SESSION['role'];
$username = $_SESSION['username'];

// allowed modules (matching filenames in /modules)
$allowed_modules = [
  'fvm' => 'Fleet & Vehicle Management',
  'vrds' => 'Vehicle Routing & Dispatch',
  'driver_trip' => 'Driver & Trip Performance',
  'tcao' => 'Transport Cost Analysis'
];

// which module to show (default fvm)
$module = $_GET['module'] ?? 'fvm';
if (!array_key_exists($module, $allowed_modules)) {
  $module = 'fvm';
}

// base url for forms/links
$baseURL = 'dashboard.php?module=' . $module;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Logistics 2 Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.0.0/dist/full.css" rel="stylesheet" type="text/css" />
</head>

<body class="min-h-screen">
  <?php include 'includes/navbar.php'; ?>

  <div class="flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="p-4">
      <!-- Server-side Tabs (links cause server render, keeps everything simple) -->
      <div class="tabs tabs-boxed">
        <?php foreach ($allowed_modules as $key => $label): ?>
          <a href="dashboard.php?module=<?= $key ?>"
            class="tab h-min <?= $key === $module ? 'tab-active' : '' ?>">
            <?= htmlspecialchars($label) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Module content -->
      <div class="mt-4 p-4 shadow rounded ">
        <main class="flex-1 p-6 ">
          <?php
          // Make $baseURL available to modules so forms/links point back to dashboard
          // Example modules will use $baseURL for form actions and delete links.
          include __DIR__ . "/modules/{$module}.php";
          ?>
        </main>
      </div>
    </div>

  </div>


  <script src="https://cdn.tailwindcss.com"></script>
  <!-- DaisyUI (works with Tailwind CDN) -->
  <script>
    tailwind.config = {
      plugins: [daisyui],
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.js"></script>
  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="./js/soliera.js"></script>
</body>

</html>