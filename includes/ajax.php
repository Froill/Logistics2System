<?php
// --- AJAX endpoints must be handled BEFORE any output ---
require_once __DIR__ . '/functions.php';
if (isset($_GET['ajax_ongoing_dispatches']) && $_GET['ajax_ongoing_dispatches'] == 1) {
    $dispatches = fetchAll('dispatches');
    $ongoing = array_filter($dispatches, function ($d) {
        return $d['status'] === 'Ongoing' && isset($d['origin_lat'], $d['origin_lon'], $d['destination_lat'], $d['destination_lon']);
    });
    header('Content-Type: application/json');
    echo json_encode(array_values($ongoing));
    exit;
}
// Add custom POI (admin only)
if (isset($_GET['add_custom_poi']) && $_GET['add_custom_poi'] == 1 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $lat = isset($input['lat']) ? floatval($input['lat']) : null;
    $lon = isset($input['lon']) ? floatval($input['lon']) : null;
    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');
    $poisFile = __DIR__ . '/../js/custom_pois.json';
    if ($lat && $lon && $name) {
        // If file does not exist, create it with an empty array
        if (!file_exists($poisFile)) {
            $init = file_put_contents($poisFile, json_encode([], JSON_PRETTY_PRINT));
            if ($init === false) {
                error_log('Failed to create POI file: ' . $poisFile);
                echo json_encode(['success' => false, 'error' => 'Failed to create POI file']);
                exit;
            }
        }
        $pois = json_decode(file_get_contents($poisFile), true);
        if (!is_array($pois)) $pois = [];
        // Limit lat/lon to 6 decimals
        $lat6 = round($lat, 6);
        $lon6 = round($lon, 6);
        $pois[] = [
            'name' => $name,
            'lat' => $lat6,
            'lon' => $lon6,
            'description' => $description
        ];
        $result = file_put_contents($poisFile, json_encode($pois, JSON_PRETTY_PRINT));
        if ($result === false) {
            error_log('Failed to write POI file: ' . $poisFile);
            echo json_encode(['success' => false, 'error' => 'Failed to write file', 'file' => $poisFile]);
        } else {
            echo json_encode(['success' => true]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing or invalid data', 'lat' => $lat, 'lon' => $lon, 'name' => $name]);
    }
    exit;
}
// Add more AJAX endpoints as needed