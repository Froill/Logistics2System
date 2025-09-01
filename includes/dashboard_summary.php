<?php
// includes/dashboard_summary.php
// Dashboard summary: show key stats from each module
require_once __DIR__ . '/db.php';

function get_count($table) {
    global $conn;
    $sql = "SELECT COUNT(*) as cnt FROM $table";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return $row['cnt'];
    }
    return 0;
}

// Example: adjust table names as needed
$vehicle_count = get_count('fleet_vehicles');
$user_count = get_count('users');
$trip_count = get_count('driver_trips');
$pending_requests = get_count('vehicle_requests');
$audit_count = get_count('audit_log');

?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
  <div class="card bg-base-100 shadow p-6 flex flex-col items-center">
    <i data-lucide="truck" class="w-10 h-10 text-primary mb-2"></i>
    <div class="text-xl font-bold">Vehicles</div>
    <div class="text-3xl mt-2"><?php echo $vehicle_count; ?></div>
  </div>
  <div class="card bg-base-100 shadow p-6 flex flex-col items-center">
    <i data-lucide="users" class="w-10 h-10 text-primary mb-2"></i>
    <div class="text-xl font-bold">Users</div>
    <div class="text-3xl mt-2"><?php echo $user_count; ?></div>
  </div>
  <div class="card bg-base-100 shadow p-6 flex flex-col items-center">
    <i data-lucide="map" class="w-10 h-10 text-primary mb-2"></i>
    <div class="text-xl font-bold">Trips</div>
    <div class="text-3xl mt-2"><?php echo $trip_count; ?></div>
  </div>
  <div class="card bg-base-100 shadow p-6 flex flex-col items-center">
    <i data-lucide="file-clock" class="w-10 h-10 text-primary mb-2"></i>
    <div class="text-xl font-bold">Pending Requests</div>
    <div class="text-3xl mt-2"><?php echo $pending_requests; ?></div>
  </div>
  <div class="card bg-base-100 shadow p-6 flex flex-col items-center">
    <i data-lucide="clipboard-list" class="w-10 h-10 text-primary mb-2"></i>
    <div class="text-xl font-bold">Audit Log Entries</div>
    <div class="text-3xl mt-2"><?php echo $audit_count; ?></div>
  </div>
</div>
