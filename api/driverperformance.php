<?php
header("Content-Type: application/json");

// DB connection
include_once('../includes/db.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
    exit;
}

// SQL query
$query = "
    SELECT 
        t.*,
        d.driver_name,
        v.vehicle_name
    FROM driver_trips t
    JOIN drivers d ON t.driver_id = d.id
    JOIN fleet_vehicles v ON t.vehicle_id = v.id
    WHERE 1=1
";

$result = $conn->query($query);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $conn->error
    ]);
    exit;
}

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Response
echo json_encode([
    "status" => "success",
    "count" => count($data),
    "data" => $data
]);

$conn->close();
