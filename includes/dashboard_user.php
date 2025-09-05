<?php
// User Dashboard Summary
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Assume $_SESSION['eid'] is set for the logged-in user
$eid = $_SESSION['eid'] ?? null;

function get_user_trip_count($eid) {
    global $conn;
    $sql = "SELECT COUNT(*) as cnt FROM driver_trips WHERE driver_eid = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $eid);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            return $row['cnt'];
        }
    }
    return 0;
}

function get_user_pending_requests($eid) {
    global $conn;
    $sql = "SELECT COUNT(*) as cnt FROM vehicle_requests WHERE requester_eid = ? AND status = 'pending'";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $eid);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            return $row['cnt'];
        }
    }
    return 0;
}

$user_trip_count = $eid ? get_user_trip_count($eid) : 0;
$user_pending_requests = $eid ? get_user_pending_requests($eid) : 0;

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
      $result = $conn->prepare("SELECT trip_id, route, date FROM driver_trips WHERE driver_eid = ? ORDER BY date DESC LIMIT 5");
      if ($result) {
        $result->bind_param("s", $eid);
        $result->execute();
        $res = $result->get_result();
        while ($row = $res && $row->fetch_assoc()) {
          echo "<li>Trip #{$row['trip_id']} - {$row['route']} <span class='text-xs text-gray-500'>({$row['date']})</span></li>";
        }
      }
      ?>
    </ul>
  </div>
  <div class="card bg-base-100 shadow p-6">
    <div class="text-xl font-bold mb-2"><i data-lucide="bell" class="inline w-6 h-6 mr-2"></i>Notifications</div>
    <ul class="list-disc ml-6">
      <?php
      // Example: show pending requests as notifications
      $result = $conn->prepare("SELECT id, requested_at FROM vehicle_requests WHERE requester_eid = ? AND status = 'pending' ORDER BY requested_at DESC LIMIT 5");
      if ($result) {
        $result->bind_param("s", $eid);
        $result->execute();
        $res = $result->get_result();
        while ($row = $res && $row->fetch_assoc()) {
          echo "<li>Pending Request #{$row['id']} <span class='text-xs text-gray-500'>({$row['requested_at']})</span></li>";
        }
      }
      ?>
    </ul>
  </div>
</div>

<div class="grid grid-cols-1 gap-6 mt-8">
  <div class="card bg-base-100 shadow p-6 flex flex-col items-center">
    <div class="text-xl font-bold mb-2"><i data-lucide="user" class="inline w-6 h-6 mr-2"></i>Profile Info</div>
    <div class="mb-2">Name: <b><?php echo htmlspecialchars($full_name ?? ''); ?></b></div>
    <div class="mb-2">EID: <b><?php echo htmlspecialchars($eid ?? ''); ?></b></div>
    <div class="mb-2">Role: <b><?php echo htmlspecialchars($role ?? ''); ?></b></div>
  </div>
  <div class="card bg-base-100 shadow p-6 flex flex-col items-center">
    <div class="text-xl font-bold mb-2"><i data-lucide="plus-circle" class="inline w-6 h-6 mr-2"></i>Quick Actions</div>
    <div class="flex gap-4">
      <a href="dashboard.php?module=driver_trip" class="btn btn-primary">View My Trips</a>
      <a href="dashboard.php?module=fvm" class="btn btn-primary">Request Vehicle</a>
    </div>
  </div>
</div>
