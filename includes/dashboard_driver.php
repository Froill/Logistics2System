<?php
// Driver Dashboard Summary
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Get driver ID from session
$driver_id = $_SESSION['user_id'] ?? null;

function get_driver_trip_count($driver_id)
{
    global $conn;
    $sql = "SELECT COUNT(*) as cnt FROM driver_trips WHERE driver_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $driver_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            return $row['cnt'];
        }
    }
    return 0;
}

function get_driver_pending_reviews($driver_id)
{
    global $conn;
    $sql = "SELECT COUNT(*) as cnt FROM driver_trips WHERE driver_id = ? AND supervisor_review_status = 'pending'";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $driver_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            return $row['cnt'];
        }
    }
    return 0;
}

function get_driver_active_trips($driver_id)
{
    global $conn;
    $sql = "SELECT COUNT(*) as cnt FROM driver_trips WHERE driver_id = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $driver_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            return $row['cnt'];
        }
    }
    return 0;
}

$trip_count = $driver_id ? get_driver_trip_count($driver_id) : 0;
$pending_reviews = $driver_id ? get_driver_pending_reviews($driver_id) : 0;
$active_trips = $driver_id ? get_driver_active_trips($driver_id) : 0;

?>
<div class="text-2xl font-bold mb-4">Driver Dashboard</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
  <div class="card bg-white shadow p-4">
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="text-xs font-semibold uppercase opacity-70">Total Trips</div>
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
        <div class="text-xs font-semibold uppercase opacity-70">Active Trips</div>
        <div class="text-3xl font-bold mt-1"><?php echo $active_trips; ?></div>
      </div>
      <span class="stat-icon-bubble">
        <i data-lucide="truck" class="w-6 h-6"></i>
      </span>
    </div>
  </div>
  <div class="card bg-white shadow p-4">
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="text-xs font-semibold uppercase opacity-70">Pending Reviews</div>
        <div class="text-3xl font-bold mt-1"><?php echo $pending_reviews; ?></div>
      </div>
      <span class="stat-icon-bubble">
        <i data-lucide="clock" class="w-6 h-6"></i>
      </span>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
  <div class="card bg-base-100 shadow p-6">
    <div class="text-xl font-bold mb-2"><i data-lucide="history" class="inline w-6 h-6 mr-2"></i>Recent Trips</div>
    <ul class="list-disc ml-6">
      <?php
      $result = $conn->prepare("SELECT id, trip_date, origin, destination, status FROM driver_trips WHERE driver_id = ? ORDER BY trip_date DESC LIMIT 5");
      if ($result) {
          $result->bind_param("i", $driver_id);
          $result->execute();
          $res = $result->get_result();
          while ($row = $res->fetch_assoc()) {
              $status_badge = $row['status'] === 'active' ? 'badge-success' : 'badge-secondary';
              echo "<li>Trip #{$row['id']} <span class='text-xs text-gray-500'>({$row['trip_date']})</span> 
                    <span class='badge {$status_badge} badge-sm ml-2'>{$row['status']}</span>
                    <br><span class='text-xs text-gray-600'>{$row['origin']} → {$row['destination']}</span></li>";
          }
          $result->close();
      } else {
          echo "<li class='text-gray-400'>Error loading trips</li>";
      }
      ?>
    </ul>
  </div>
  
  <div class="card bg-base-100 shadow p-6">
    <div class="text-xl font-bold mb-2"><i data-lucide="alert-circle" class="inline w-6 h-6 mr-2"></i>Actions Needed</div>
    <ul class="space-y-3">
      <?php
      // Get trips needing driver attention
      $urgent_result = $conn->prepare("
          SELECT id, trip_date, origin, destination, supervisor_review_status 
          FROM driver_trips 
          WHERE driver_id = ? AND supervisor_review_status = 'pending' 
          ORDER BY trip_date ASC 
          LIMIT 3
      ");
      if ($urgent_result) {
          $urgent_result->bind_param("i", $driver_id);
          $urgent_result->execute();
          $urgent_res = $urgent_result->get_result();
          if ($urgent_res->num_rows > 0) {
              while ($row = $urgent_res->fetch_assoc()) {
                  echo "<li class='bg-yellow-50 border border-yellow-200 rounded-lg p-3'>
                        <div class='flex items-center justify-between'>
                            <div>
                                <span class='font-semibold text-sm'>Trip #{$row['id']}</span>
                                <br><span class='text-xs text-gray-600'>{$row['origin']} → {$row['destination']}</span>
                            </div>
                            <span class='badge badge-warning badge-xs'>Review Needed</span>
                        </div>
                        <div class='text-xs text-gray-500 mt-1'>{$row['trip_date']}</div>
                      </li>";
              }
          } else {
              echo "<li class='text-green-600 text-sm'>✓ All trips reviewed</li>";
          }
          $urgent_result->close();
      }
      ?>
    </ul>
  </div>
</div>

<div class="grid grid-cols-1 gap-6 mt-8">
  <div class="card bg-base-100 shadow p-6 flex flex-col items-center">
    <div class="text-xl font-bold mb-2"><i data-lucide="plus-circle" class="inline w-6 h-6 mr-2"></i>Quick Actions</div>
    <div class="flex flex-col md:flex-row gap-4">
      <a href="dashboard.php?module=driver_trip" class="btn btn-primary">View All Trips</a>
      <a href="dashboard.php?module=driver_trip&action=new" class="btn btn-secondary">Log New Trip</a>
      <a href="dashboard.php?module=vrds" class="btn btn-accent">VRDS</a>
    </div>
  </div>
</div>

<!-- Mobile Dock Navigation -->
<div class="fixed bottom-0 left-0 right-0 bg-base-100 border-t border-base-300 md:hidden z-50">
  <div class="dock-container">
    <div class="grid grid-cols-4 gap-1 p-2">
      <a href="dashboard.php?module=dashboard" class="dock-item active">
        <div class="flex flex-col items-center py-2">
          <i data-lucide="home" class="w-5 h-5 mb-1"></i>
          <span class="text-xs">Home</span>
        </div>
      </a>
      <a href="dashboard.php?module=vrds" class="dock-item">
        <div class="flex flex-col items-center py-2">
          <i data-lucide="calendar-clock" class="w-5 h-5 mb-1"></i>
          <span class="text-xs">VRDS</span>
        </div>
      </a>
      <a href="dashboard.php?module=inbox" class="dock-item">
        <div class="flex flex-col items-center py-2 relative">
          <i data-lucide="mail" class="w-5 h-5 mb-1"></i>
          <span class="text-xs">Inbox</span>
          <!-- Notification badge if needed -->
          <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center" id="inbox-badge" style="display: none;">0</span>
        </div>
      </a>
      <a href="dashboard.php?module=profile" class="dock-item">
        <div class="flex flex-col items-center py-2">
          <i data-lucide="user" class="w-5 h-5 mb-1"></i>
          <span class="text-xs">Profile</span>
        </div>
      </a>
    </div>
  </div>
</div>

<!-- Add some padding to prevent content from being hidden behind dock on mobile -->
<div class="h-16 md:hidden"></div>

<style>
.dock-item {
  @apply text-base-content/70 hover:text-base-content transition-colors rounded-lg;
}

.dock-item.active {
  @apply text-primary bg-primary/10;
}

.dock-container {
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}
</style>