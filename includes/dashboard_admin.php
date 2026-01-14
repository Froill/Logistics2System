<?php
// Admin Dashboard Summary
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$moduleName = 'dashboard';

if ($_SESSION['current_module'] !== $moduleName) {
  log_audit_event(
    'Dashboard',
    'ACCESS',
    null,
    $_SESSION['full_name'],
    'User accessed dashboard'
  );
  $_SESSION['current_module'] = $moduleName;
}

function get_count($table)
{
  global $conn;
  $sql = "SELECT COUNT(*) as cnt FROM $table";
  $result = $conn->query($sql);
  if ($result && $row = $result->fetch_assoc()) {
    return $row['cnt'];
  }
  return 0;
}

$vehicle_count = get_count('fleet_vehicles');
$user_count = get_count('users');
$trip_count = get_count('driver_trips');
$pending_requests = get_count('vehicle_requests');
$audit_count = get_count('audit_log');

?>
<div class="text-2xl font-bold mb-4">Overview</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
  <div class="card bg-white shadow p-4">
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="text-xs font-semibold uppercase opacity-70">Total Vehicles</div>
        <div class="text-3xl font-bold mt-1"><?php echo $vehicle_count; ?></div>
      </div>
      <span class="stat-icon-bubble">
        <i data-lucide="truck" class="w-6 h-6"></i>
      </span>
    </div>
  </div>
  <div class="card bg-white shadow p-4">
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="text-xs font-semibold uppercase opacity-70">Total Users</div>
        <div class="text-3xl font-bold mt-1"><?php echo $user_count; ?></div>
      </div>
      <span class="stat-icon-bubble">
        <i data-lucide="users" class="w-6 h-6"></i>
      </span>
    </div>
  </div>
  <div class="card bg-white shadow p-4">
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="text-xs font-semibold uppercase opacity-70">Driver Trips</div>
        <div class="text-3xl font-bold mt-1"><?php echo $trip_count; ?></div>
      </div>
      <span class="stat-icon-bubble">
        <i data-lucide="map" class="w-6 h-6"></i>
      </span>
    </div>
  </div>
  <div class="card bg-white shadow p-4">
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="text-xs font-semibold uppercase opacity-70">Vehicle Requests</div>
        <div class="text-3xl font-bold mt-1"><?php echo $pending_requests; ?></div>
      </div>
      <span class="stat-icon-bubble">
        <i data-lucide="file-clock" class="w-6 h-6"></i>
      </span>
    </div>
  </div>
  <div class="card bg-white shadow p-4">
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="text-xs font-semibold uppercase opacity-70">Audit Log Entries</div>
        <div class="text-3xl font-bold mt-1"><?php echo $audit_count; ?></div>
      </div>
      <span class="stat-icon-bubble">
        <i data-lucide="clipboard-list" class="w-6 h-6"></i>
      </span>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
  <div class="card bg-base-100 shadow p-6">
    <div class="text-xl font-bold mb-2"><i data-lucide="activity" class="inline w-6 h-6 mr-2"></i>Recent Activity</div>
    <ul class="grid gap-4 sm:grid-cols-1">
      <?php
      $result = $conn->query("SELECT action, id, timestamp FROM audit_log ORDER BY timestamp DESC LIMIT 5");
      if ($result) {
        while ($row = $result->fetch_assoc()) {
          // Calculate time ago
          $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
          $tsObj = DateTime::createFromFormat('Y-m-d H:i:s', $row['timestamp'], new DateTimeZone('Asia/Manila'));
          if ($tsObj === false) {
            $ago = 'just now';
          } else {
            $diff = $now->getTimestamp() - $tsObj->getTimestamp();
            if ($diff < 0) {
              $ago = 'just now';
            } elseif ($diff < 60) {
              $ago = $diff . 's ago';
            } elseif ($diff < 3600) {
              $ago = floor($diff / 60) . 'm ago';
            } elseif ($diff < 86400) {
              $ago = floor($diff / 3600) . 'h ago';
            } else {
              $ago = $tsObj->format('M d, Y H:i');
            }
          }
          echo "
          <li class='card bg-base-100 shadow-md hover:shadow-lg transition rounded-lg p-4'>
            <div class='flex flex-col gap-2 md:flex-row md:gap-0  items-center justify-between'>
              <h2 class='card-title text-lg font-bold badge badge-outline'># {$row['id']}</h2>
              <span class='text-xs text-center md:text-left opacity-75'>($ago)</span>
            </div>
            <p class='text-md mt-2 text-center md:text-left'>{$row['action']}</p>
          </li>
          ";
        }
      }
      ?>
    </ul>
  </div>
  <div class="card bg-base-100 shadow p-6">
    <div class="text-xl font-bold mb-2"><i data-lucide="clock" class="inline w-6 h-6 mr-2"></i>Pending Approvals</div>
    <ul class="grid gap-4 grid-cols-1">
      <?php
      // Always show both pending vehicle requests and trip log reviews
      $pendingVehicleRequests = [];
      $result = $conn->query("SELECT id, requester_id, request_date, status FROM vehicle_requests WHERE status = 'Pending' ORDER BY request_date DESC LIMIT 10");
      if ($result) {
        while ($row = $result->fetch_assoc()) {
          $pendingVehicleRequests[] = $row;
        }
      }
      if (count($pendingVehicleRequests) > 0) {
        foreach ($pendingVehicleRequests as $row) {
          // Fetch requester name
          $requesterName = '';
          $userQ = $conn->query("SELECT full_name FROM users WHERE id = " . intval($row['requester_id']));
          if ($userQ && $u = $userQ->fetch_assoc()) {
            $requesterName = $u['full_name'];
          }
          $vrdsLink = "dashboard.php?module=vrds&highlight_request=" . $row['id'];
          // Calculate time ago (robust)
          $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
          $tsObj = DateTime::createFromFormat('Y-m-d H:i:s', $row['request_date'], new DateTimeZone('Asia/Manila'));
          if ($tsObj === false) {
            $ago = 'just now';
          } else {
            $diff = $now->getTimestamp() - $tsObj->getTimestamp();
            if ($diff < 0) {
              $ago = 'just now';
            } elseif ($diff < 60) {
              $ago = $diff . 's ago';
            } elseif ($diff < 3600) {
              $ago = floor($diff / 60) . 'm ago';
            } elseif ($diff < 86400) {
              $ago = floor($diff / 3600) . 'h ago';
            } else {
              $ago = $tsObj->format('M d, Y H:i');
            }
          }
          echo "
          <li class='card bg-base-100 shadow-md hover:shadow-lg transition rounded-lg p-4'>
            <div class='flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2'>
              
              <!-- Left section: Link + requester -->
              <a href='$vrdsLink' class='text-info font-semibold hover:underline text-sm sm:text-base'>
                Vehicle Request #{$row['id']} by <b>{$requesterName}</b>
              </a>
              
              <!-- Right section: status + time -->
              <div class='text-sm opacity-75 flex flex-col md:flex-row items-center gap-2 font-medium'>
                <span>Status: <span class='badge badge-outline badge-secondary badge-sm p-2'>{$row['status']}</span></span>
                <span>($ago)</span>
              </div>
            </div>
          </li>
          ";
        }
      } else {
        echo "<li class='text-gray-400'>No pending vehicle requests.</li>";
      }

      $pendingTripLogs = [];
      $tripResult = $conn->query("SELECT id, driver_id, trip_date FROM driver_trips WHERE supervisor_review_status = 'pending' ORDER BY trip_date DESC LIMIT 3");
      if ($tripResult) {
        while ($row = $tripResult->fetch_assoc()) {
          // Fetch driver name
          $driverName = '';
          $driverQ = $conn->query("SELECT driver_name FROM drivers WHERE id = " . intval($row['driver_id']));
          if ($driverQ && $d = $driverQ->fetch_assoc()) {
            $driverName = $d['driver_name'];
          }
          $row['driver_name'] = $driverName;
          $pendingTripLogs[] = $row;
        }
      }
      if (count($pendingTripLogs) > 0) {
        foreach ($pendingTripLogs as $row) {
          $tripLink = "dashboard.php?module=driver_trip&highlight_trip=" . $row['id'];
          // Calculate time ago
          $now = time();
          $ts = strtotime($row['trip_date']);
          $diff = $now - $ts;
          if ($diff < 60) {
            $ago = $diff . 's ago';
          } elseif ($diff < 3600) {
            $ago = floor($diff / 60) . 'm ago';
          } elseif ($diff < 86400) {
            $ago = floor($diff / 3600) . 'h ago';
          } else {
            $ago = date('M d, Y H:i', $ts);
          }
          echo "
          <li class='card bg-base-100 shadow-md hover:shadow-lg transition rounded-lg p-4'>
            <div class='flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2'>
              
              <!-- Left section: link + driver -->
              <a href='$tripLink' class='text-info font-semibold hover:underline text-sm sm:text-base'>
              Trip Log #{$row['id']} by <b>{$row['driver_name']}</b>
              </a>
              
              <!-- Right section: timestamp -->
              <span class='text-sm opacity-75'>($ago)</span>
            </div>
          </li>
          ";
        }
      } else {
        echo "<li class='text-gray-400'>No pending trip log reviews.</li>";
      }
      ?>
    </ul>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
  <div class="card bg-base-100 shadow p-6">
    <div class="text-xl font-bold mb-2"><i data-lucide="settings" class="inline w-6 h-6 mr-2"></i>Fleet Status Overview</div>
    <?php
    $active = get_count_where('fleet_vehicles', "status = 'active'");
    $inactive = get_count_where('fleet_vehicles', "status = 'inactive'");
    $maintenance = get_count_where('fleet_vehicles', "status = 'under maintenance'");
    $dispatch = get_count_where('fleet_vehicles', "status = 'dispatched'");
    ?>
    <ul class="ml-6">
      <li>Active: <b><?php echo $active; ?></b></li>
      <li>Inactive: <b><?php echo $inactive; ?></b></li>
      <li>Maintenance: <b><?php echo $maintenance; ?></b></li>
      <li>Dispatched: <b><?php echo $dispatch; ?></b></li>
    </ul>
  </div>
  <div class="card bg-base-100 shadow p-6">
    <div class="text-xl font-bold mb-2"><i data-lucide="credit-card" class="inline w-6 h-6 mr-2"></i>Financial Summary</div>
    <?php
    $cost_result = $conn->query("SELECT SUM(total_cost) as total_cost FROM transport_costs");
    $total_cost = 0;
    if ($cost_result && ($row = $cost_result->fetch_assoc())) {
      $total_cost = $row['total_cost'] ? $row['total_cost'] : 0;
      echo '<div>Total Transport Cost: <b>₱' . number_format($total_cost, 2) . '</b></div>';
    } else {
      echo '<div class="text-gray-400">No cost data available.</div>';
    }
    ?>
  </div>
</div>

<div class="grid grid-cols-1 gap-6 mt-8">
  <div class="card bg-base-100 shadow p-6 flex flex-col items-center">
    <div class="text-xl font-bold mb-2"><i data-lucide="plus-circle" class="inline w-6 h-6 mr-2"></i>Quick Actions</div>
    <div class="flex flex-col md:flex-row gap-4">
      <a href="dashboard.php?module=fvm" class="btn btn-primary">Add Vehicle</a>
      <a href="dashboard.php?module=driver_trip" class="btn btn-primary">Assign Trip</a>
      <a href="dashboard.php?module=tcao" class="btn btn-primary">View Cost Analysis</a>
    </div>
  </div>
</div>

<?php
// Helper for fleet status widget
function get_count_where($table, $where)
{
  global $conn;
  $sql = "SELECT COUNT(*) as cnt FROM $table WHERE $where";
  $result = $conn->query($sql);
  if ($result && $row = $result->fetch_assoc()) {
    return $row['cnt'];
  }
  return 0;
}
?>