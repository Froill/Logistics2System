<?php
// --- AJAX endpoints must be handled BEFORE any output ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';
if (isset($_GET['ajax_ongoing_dispatches']) && $_GET['ajax_ongoing_dispatches'] == 1) {
    $role = $_SESSION['role'] ?? '';
    $driverRecordId = null;
    if ($role === 'driver') {
        $currentUserEid = $_SESSION['eid'] ?? null;
        if ($currentUserEid && isset($GLOBALS['conn']) && ($stmt = $GLOBALS['conn']->prepare('SELECT id FROM drivers WHERE eid = ? LIMIT 1'))) {
            $stmt->bind_param('s', $currentUserEid);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($r = $res->fetch_assoc()) {
                $driverRecordId = (int)$r['id'];
            }
            $stmt->close();
        }
    }

    if (empty($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof mysqli)) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([]);
        exit;
    }

    if ($role === 'driver' && !$driverRecordId) {
        header('Content-Type: application/json');
        echo json_encode([]);
        exit;
    }

    $conn = $GLOBALS['conn'];
    $sql = "SELECT id, request_id, vehicle_id, driver_id, officer_id, dispatch_date, status, origin, destination, purpose, origin_lat, origin_lon, destination_lat, destination_lon\n            FROM dispatches\n            WHERE status = 'Ongoing'\n              AND origin_lat IS NOT NULL AND origin_lon IS NOT NULL\n              AND destination_lat IS NOT NULL AND destination_lon IS NOT NULL";
    if ($role === 'driver') {
        $sql .= " AND driver_id = ?";
    }
    $sql .= " ORDER BY dispatch_date DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([]);
        exit;
    }

    if ($role === 'driver') {
        $stmt->bind_param('i', $driverRecordId);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    header('Content-Type: application/json');
    echo json_encode($rows);
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
if (isset($_GET['delete_custom_poi'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $idx = isset($input['idx']) ? intval($input['idx']) : -1;

    $poiFile = __DIR__ . '/../js/custom_pois.json';
    if (!file_exists($poiFile)) {
        echo json_encode(['success' => false, 'error' => 'POI file not found']);
        exit;
    }
    $pois = json_decode(file_get_contents($poiFile), true);
    if (!is_array($pois) || $idx < 0 || $idx >= count($pois)) {
        echo json_encode(['success' => false, 'error' => 'Invalid POI index']);
        exit;
    }
    array_splice($pois, $idx, 1);
    $ok = file_put_contents($poiFile, json_encode($pois, JSON_PRETTY_PRINT));
    if ($ok === false) {
        echo json_encode(['success' => false, 'error' => 'Failed to save POI file']);
        exit;
    }
    echo json_encode(['success' => true]);
    exit;
}
if (isset($_GET['edit_custom_poi'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $idx = isset($input['idx']) ? intval($input['idx']) : -1;
    $poi = isset($input['poi']) ? $input['poi'] : null;

    $poiFile = __DIR__ . '/../js/custom_pois.json';
    if (!file_exists($poiFile)) {
        echo json_encode(['success' => false, 'error' => 'POI file not found']);
        exit;
    }
    $pois = json_decode(file_get_contents($poiFile), true);
    if (!is_array($pois) || $idx < 0 || $idx >= count($pois) || !$poi) {
        echo json_encode(['success' => false, 'error' => 'Invalid POI index or data']);
        exit;
    }
    $pois[$idx] = [
        'name' => $poi['name'],
        'lat' => $poi['lat'],
        'lon' => $poi['lon'],
        'description' => $poi['description']
    ];
    $ok = file_put_contents($poiFile, json_encode($pois, JSON_PRETTY_PRINT));
    if ($ok === false) {
        echo json_encode(['success' => false, 'error' => 'Failed to save POI file']);
        exit;
    }
    echo json_encode(['success' => true]);
    exit;
}
// Add more AJAX endpoints as needed
// Secure download proxy for vehicle documents/insurance
if (isset($_GET['download_vehicle_file']) && $_GET['download_vehicle_file'] == 1) {
    // require login
    session_start();
    if (empty($_SESSION['user_id'])) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
    // Restrict downloads to admin/manager by default
    $role = strtolower($_SESSION['user_role'] ?? $_SESSION['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'])) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
    $docId = isset($_GET['doc_id']) ? intval($_GET['doc_id']) : 0;
    $isInsurance = isset($_GET['table']) && $_GET['table'] === 'insurance';
    $table = $isInsurance ? 'vehicle_insurance' : 'vehicle_documents';
    if (!$docId) {
        http_response_code(400);
        echo 'Invalid request';
        exit;
    }
    require_once __DIR__ . '/functions.php';

    if (empty($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof mysqli)) {
        http_response_code(500);
        echo 'Server error';
        exit;
    }
    $conn = $GLOBALS['conn'];

    $sql = $isInsurance
        ? "SELECT document_path AS file_path FROM vehicle_insurance WHERE id = ? LIMIT 1"
        : "SELECT file_path FROM vehicle_documents WHERE id = ? LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo 'Server error';
        exit;
    }
    $stmt->bind_param('i', $docId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row || empty($row['file_path'])) {
        http_response_code(404);
        echo 'File not found';
        exit;
    }
    $path = __DIR__ . '/../' . $row['file_path'];
    if (!file_exists($path)) {
        http_response_code(404);
        echo 'File not found';
        exit;
    }

    // Serve file securely (allow inline display for images/PDFs)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $path) ?: 'application/octet-stream';
    finfo_close($finfo);
    $inline = isset($_GET['inline']) && ($_GET['inline'] == '1' || $_GET['inline'] === 1);
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    // If inline requested and file is displayable, send inline disposition
    if ($inline && (strpos($mime, 'image/') === 0 || $mime === 'application/pdf')) {
        header('Content-Disposition: inline; filename="' . basename($row['file_path']) . '"');
    } else {
        header('Content-Disposition: attachment; filename="' . basename($row['file_path']) . '"');
    }
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}