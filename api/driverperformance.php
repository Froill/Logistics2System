<?php
header("Content-Type: application/json");

$configPath = __DIR__ . '/../includes/config.php';
if (file_exists($configPath)) {
    include_once $configPath;
}

$expectedApiKey = null;
if (defined('DRIVERPERFORMANCE_API_KEY') && DRIVERPERFORMANCE_API_KEY !== '') {
    $expectedApiKey = DRIVERPERFORMANCE_API_KEY;
} else {
    $envKey = getenv('DRIVERPERFORMANCE_API_KEY');
    if ($envKey !== false && $envKey !== '') {
        $expectedApiKey = $envKey;
    }
}

if ($expectedApiKey === null) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'API key is not configured'
    ]);
    exit;
}

$providedApiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($providedApiKey === '' || !hash_equals($expectedApiKey, $providedApiKey)) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized'
    ]);
    exit;
}

// DB connection
include_once('../includes/db.php');

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
    echo \n;
}

// Response
echo json_encode([
    "status" => "success",
    "count" => count($data),
    "data" => $data
]);

$conn->close();
