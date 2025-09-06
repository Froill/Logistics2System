
<?php
require 'includes/config.php';
require 'includes/db.php';

// Helper to clear table
function clearTable($conn, $table) {
    $conn->query("DELETE FROM `$table`");
    echo "Cleared $table<br>";
}

// Helper to insert rows
function insertRows($conn, $table, $columns, $rows) {
    $colNames = implode(", ", $columns);
    $placeholders = implode(", ", array_fill(0, count($columns), "?"));
    $sql = "INSERT INTO `$table` ($colNames) VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    foreach ($rows as $row) {
        $types = str_repeat("s", count($columns));
        $stmt->bind_param($types, ...$row);
        $stmt->execute();
    }
    echo "Inserted into $table: " . count($rows) . " rows<br>";
}

// 1. Add 6 drivers to users table
clearTable($conn, 'users');
$driverNames = ['Juan Dela Cruz', 'Maria Santos', 'Pedro Ramirez', 'Ana Villanueva', 'Ramon Cruz', 'Josefa Dela Paz'];
$users = [];
for ($i = 0; $i < 6; $i++) {
    $eid = 'D2507' . str_pad(rand(10,99), 2, '0', STR_PAD_LEFT);
    $name = $driverNames[$i];
    $email = strtolower(str_replace(' ', '.', $name)) . '@example.com';
    $password = password_hash('password123', PASSWORD_DEFAULT);
    $role = 'driver';
    $users[] = [$eid, $name, $email, $password, $role];
}
insertRows($conn, 'users', ['eid', 'full_name', 'email', 'password', 'role'], $users);

// 2. Add 3 vehicles to fleet_vehicles
clearTable($conn, 'fleet_vehicles');
$vehicleNames = ['Thunderbolt', 'Silver Arrow', 'Blue Falcon'];
$vehicles = [];
for ($i = 0; $i < 3; $i++) {
    $name = $vehicleNames[$i];
    $plate = 'PLT-' . rand(100,999);
    $type = ['Truck','Van','Pickup','Car'][rand(0,3)];
    $status = 'Dispatched';
    $vehicles[] = [$name, $plate, $type, $status];
}
insertRows($conn, 'fleet_vehicles', ['vehicle_name', 'plate_number', 'vehicle_type', 'status'], $vehicles);

// 3. Vehicle requests for August 2025
clearTable($conn, 'vehicle_requests');
$vehicleRequests = [];
$pendingCount = 0;
for ($d = 1; $d <= 31; $d++) {
    for ($r = 0; $r < 2; $r++) {
        $date = sprintf('2025-08-%02d', $d);
        $requester_id = $i = rand(1,6); // random user id
        $vehicle_id = rand(1,3); // random vehicle id
        $purpose = 'Delivery ' . $d . '-' . ($r+1);
        $origin = 'Warehouse';
        $destination = 'Hotel';
        $requested_vehicle_type = ['Truck','Van','Pickup','Car'][rand(0,3)];
        $requested_driver_id = rand(1,6);
        $status = ($pendingCount < 10) ? 'Pending' : 'Approved';
        if ($status === 'Pending') $pendingCount++;
        $vehicleRequests[] = [
            $requester_id,
            $date . ' 08:00:00',
            $date,
            $date,
            $purpose,
            $origin,
            $destination,
            $requested_vehicle_type,
            $requested_driver_id,
            $status
        ];
    }
}
insertRows($conn, 'vehicle_requests', [
    'requester_id','request_date','reservation_date','expected_return','purpose','origin','destination','requested_vehicle_type','requested_driver_id','status'
], $vehicleRequests);

// 4. Dispatch trips for August 2025
clearTable($conn, 'dispatches');
$dispatches = [];
for ($d = 1; $d <= 31; $d++) {
    $dispatch_date = sprintf('2025-08-%02d 09:00:00', $d);
    $request_id = ($d-1)*2+1; // link to vehicle_requests
    $vehicle_id = rand(1,3);
    $driver_id = rand(1,6);
    $officer_id = 1;
    $origin = 'Warehouse';
    $destination = 'Hotel';
    $purpose = 'Dispatch Trip ' . $d;
    $status = 'Completed';
    $dispatches[] = [
        $request_id,
        $vehicle_id,
        $driver_id,
        $officer_id,
        $dispatch_date,
        $dispatch_date,
        $status,
        $origin,
        $destination,
        $purpose,
        '',
        $dispatch_date,
        $dispatch_date
    ];
}
insertRows($conn, 'dispatches', [
    'request_id','vehicle_id','driver_id','officer_id','dispatch_date','return_date','status','origin','destination','purpose','notes','created_at','updated_at'
], $dispatches);

// 5. Driver trip log for completed dispatches
clearTable($conn, 'driver_trips');
$driverTrips = [];
for ($d = 1; $d <= 31; $d++) {
    $driver_id = $dispatches[$d-1][2];
    $vehicle_id = $dispatches[$d-1][1];
    $trip_date = sprintf('2025-08-%02d', $d);
    $start_time = $dispatches[$d-1][4];
    $end_time = $dispatches[$d-1][5];
    $distance = rand(5,30);
    $fuel = rand(10,40);
    $idle = rand(10,120);
    $speed = rand(30,60);
    $score = rand(70,100);
    $driverTrips[] = [
        $driver_id,
        $vehicle_id,
        $trip_date,
        $start_time,
        $end_time,
        $distance,
        $fuel,
        $idle,
        $speed,
        $score,
        'valid',
        '',
        'approved',
        '',
    ];
}
insertRows($conn, 'driver_trips', [
    'driver_id','vehicle_id','trip_date','start_time','end_time','distance_traveled','fuel_consumed','idle_time','average_speed','performance_score','validation_status','validation_message','supervisor_review_status','supervisor_remarks'
], $driverTrips);

// 6. Transport cost analysis for completed trips
clearTable($conn, 'transport_costs');
$costs = [];
for ($d = 1; $d <= 31; $d++) {
    $trip_id = $d;
    $fuel_cost = rand(1500,3500);
    $toll_fees = rand(200,800);
    $other_expenses = rand(100,500);
    $total_cost = $fuel_cost + $toll_fees + $other_expenses;
    $status = 'submitted';
    $receipt = '';
    $created_by = 'admin';
    $created_at = sprintf('2025-08-%02d 12:00:00', $d);
    $costs[] = [
        $trip_id,
        $fuel_cost,
        $toll_fees,
        $other_expenses,
        $total_cost,
        $status,
        $receipt,
        $created_by,
        $created_at
    ];
}
insertRows($conn, 'transport_costs', [
    'trip_id','fuel_cost','toll_fees','other_expenses','total_cost','status','receipt','created_by','created_at'
], $costs);

echo "<br>Dummy data generation complete.";
?>
