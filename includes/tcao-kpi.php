<?php
require 'db.php';
// Fetch all trip + cost data
$query = "
    SELECT t.id, t.distance_traveled, t.cargo_weight, 
           c.fuel_cost, c.toll_fees, c.other_expenses, c.total_cost, 
           v.weight_capacity AS vehicle_capacity
    FROM driver_trips t
    LEFT JOIN transport_costs c ON t.id = c.trip_id
    LEFT JOIN fleet_vehicles v ON t.vehicle_id = v.id
";
$result = $conn->query($query);
if (!$result) {
    die("Query failed: " . $conn->error); // shows exact SQL error
}
$totalDistance = 0;
$totalCost = 0;
$totalTrips = 0;
$totalUtilization = 0;
$totalFuel = 0;

while ($row = $result->fetch_assoc()) {
    $distance = floatval($row['distance_traveled'] ?? 0);
    $cost = floatval($row['total_cost'] ?? 0);
    $fuel = floatval($row['fuel_cost'] ?? 0);
    $weight = floatval($row['cargo_weight'] ?? 0);
    $capacity = floatval($row['vehicle_capacity'] ?? 1); // avoid /0

    $totalDistance += $distance;
    $totalCost += $cost;
    $totalTrips++;
    $totalFuel += $fuel;

    // load utilization (weight carried ÷ vehicle capacity)
    if ($capacity > 0) {
        $totalUtilization += ($weight / $capacity) * 100;
    }
}

// ✅ Prevent divide by zero
$avgCostPerKm = ($totalDistance > 0) ? $totalCost / $totalDistance : 0;
$avgCostPerTrip = ($totalTrips > 0) ? $totalCost / $totalTrips : 0;
$avgLoadUtilization = ($totalTrips > 0) ? $totalUtilization / $totalTrips : 0;
$fuelShare = ($totalCost > 0) ? ($totalFuel / $totalCost) * 100 : 0;
?>
