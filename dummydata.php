<?php
require 'includes/config.php';

function insertDummyData($conn, $table, $rows) {
    $colsResult = $conn->query("SHOW COLUMNS FROM `$table`");
    $columns = [];
    while ($col = $colsResult->fetch_assoc()) {
        if (stripos($col['Extra'], 'auto_increment') === false) {
            $columns[] = $col['Field'];
        }
    }

    $expectedCount = count($columns);
    echo "Table: $table | Columns: " . implode(", ", $columns) . " | Expected values per row: $expectedCount<br>";

    foreach ($rows as $row) {
        if (count($row) !== $expectedCount) {
            echo "❌ Skipped row in $table — wrong number of values (" . count($row) . " given, $expectedCount expected)<br>";
            continue;
        }

        $colNames = implode(", ", $columns);
        $placeholders = implode(", ", array_fill(0, $expectedCount, "?"));
        $sql = "INSERT INTO `$table` ($colNames) VALUES ($placeholders)";
        $stmt = $conn->prepare($sql);
        $types = str_repeat("s", $expectedCount);
        $stmt->bind_param($types, ...$row);
        $stmt->execute();
    }

    echo "✅ Inserted rows into $table<br><br>";
}

// ✅ Fixed dummy data
$dummyData = [
    'fleet_vehicles' => [
        ['Toyota Hilux', 'ABC-123', 'Available'],
        ['Mitsubishi L300', 'XYZ-456', 'In Use'],
        ['Isuzu D-Max', 'LMN-789', 'Under Maintenance'],
        ['Hyundai H100', 'JKL-321', 'Available'],
        ['Ford Ranger', 'PQR-654', 'In Use'],
    ],
    'driver_trips' => [
        ['John Doe', '2025-08-01', '85', 'Good performance'],
        ['Jane Smith', '2025-08-02', '90', 'Excellent'],
        ['Michael Cruz', '2025-08-03', '70', 'Needs improvement'],
        ['Anna Reyes', '2025-08-04', '95', 'Outstanding'],
        ['Chris Lee', '2025-08-05', '80', 'Satisfactory'],
    ],
    'transport_costs' => [
        ['1', '3500', '500', '200', '4200'],
        ['2', '3000', '450', '150', '3600'],
        ['3', '4000', '550', '300', '4850'],
        ['4', '2500', '300', '100', '2900'],
        ['5', '3700', '600', '250', '4550'],
    ],
    'vehicle_routes' => [
        ['Route A', '1', '2025-08-06', 'Active'],
        ['Route B', '2', '2025-08-06', 'Completed'],
        ['Route C', '3', '2025-08-06', 'Active'],
        ['Route D', '4', '2025-08-06', 'Pending'],
        ['Route E', '5', '2025-08-06', 'Active'],
    ],
];

// Run inserts
foreach ($dummyData as $table => $rows) {
    if ($conn->query("SHOW TABLES LIKE '$table'")->num_rows > 0) {
        insertDummyData($conn, $table, $rows);
    } else {
        echo "⚠️ Table $table does not exist, skipped.<br>";
    }
}
?>
