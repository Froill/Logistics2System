<?php
// User Dashboard Summary
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Assume $_SESSION['eid'] is set for the logged-in user
$id = $_SESSION['user_id'] ?? null;

function get_user_trip_count($id)
{
  global $conn;
  $sql = "SELECT COUNT(*) as cnt FROM driver_trips WHERE driver_id = ?";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
      return $row['cnt'];
    }
  }
  return 0;
}

function get_user_pending_requests($id)
{
  global $conn;
  $sql = "SELECT COUNT(*) as cnt FROM vehicle_requests WHERE requester_id = ? AND status = 'pending'";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
      return $row['cnt'];
    }
  }
  return 0;
}

$user_trip_count = $id ? get_user_trip_count($id) : 0;
$user_pending_requests = $id ? get_user_pending_requests($id) : 0;

?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
  <div class="card bg-base-100 shadow p-6 flex flex-col items-center">
    <i data-lucide="map" class="w-10 h-10 text-primary mb-2"></i>
    <div class="text-xl font-bold">My Trips</div>
    <div class="text-3xl mt-2"><?php echo $user_trip_count; ?></div>
  </div>
  <div class="card bg-base-100 shadow p-6 flex flex-col items-center">
    <i data-lucide="file-clock" class="w-10 h-10 text-primary mb-2"></i>
    <div class="text-xl font-bold">Pending Requests</div>
    <div class="text-3xl mt-2"><?php echo $user_pending_requests; ?></div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
  <div class="card bg-base-100 shadow p-6">
    <div class="text-xl font-bold mb-2"><i data-lucide="history" class="inline w-6 h-6 mr-2"></i>Recent Trips</div>
    <ul class="list-disc ml-6">
      <?php
      $result = $conn->prepare("SELECT id, trip_date FROM driver_trips WHERE driver_id = ? ORDER BY trip_date DESC LIMIT 5");
      if ($result) {
        $result->bind_param("s", $id);
        $result->execute();
        $res = $result->get_result();
        while ($row = $res->fetch_assoc()) {
          echo "<li>Trip #{$row['id']} <span class='text-xs text-gray-500'>({$row['trip_date']})</span></li>";
        }
        $result->close();
      } else {
        echo "Error preparing statement: " . $conn->error;
      }
      ?>

    </ul>
  </div>
  <div class="card bg-base-100 shadow p-6">
    <div class="text-xl font-bold mb-2"><i data-lucide="bell" class="inline w-6 h-6 mr-2"></i>Notifications</div>
    <ul class="list-disc ml-6">
      <?php
      // Example: show pending requests as notifications
      $result = $conn->prepare("SELECT id, request_date FROM vehicle_requests WHERE requester_id = ? AND status = 'pending' ORDER BY request_date DESC LIMIT 5");
      if ($result) {
        $result->bind_param("s", $id);
        $result->execute();
        $res = $result->get_result();
        while ($row = $res->fetch_assoc()) {
          echo "<li>Pending Request #{$row['id']} <span class='text-xs text-gray-500'>({$row['request_date']})</span></li>";
        }
      }
      ?>
    </ul>
  </div>
</div>

<div class="grid grid-cols-1 gap-6 mt-8">
  <div class="card bg-base-100 shadow p-6 flex flex-col items-center">
    <div class="text-xl font-bold mb-2"><i data-lucide="plus-circle" class="inline w-6 h-6 mr-2"></i>Quick Actions</div>
    <div class="flex gap-4">
      <a href="dashboard.php?module=vrds" class="btn btn-primary">Request Vehicle</a>
    </div>
  </div>
</div>