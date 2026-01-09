<?php
// Script: notify_expiries.php
// Use: run from CLI or scheduled task to email upcoming expiries
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/config.php';

$days = $argv[1] ?? 30;
$days = intval($days) > 0 ? intval($days) : 30;
$today = new DateTime('now', new DateTimeZone('Asia/Manila'));
$threshold = (clone $today)->modify("+{$days} days")->format('Y-m-d');
$todayStr = $today->format('Y-m-d');

$db = getDb();
// Insurance expiries
$insStmt = $db->prepare("SELECT vi.*, v.vehicle_name, v.plate_number FROM vehicle_insurance vi JOIN fleet_vehicles v ON vi.vehicle_id = v.id WHERE vi.coverage_end BETWEEN ? AND ? ORDER BY vi.coverage_end ASC");
$insStmt->execute([$todayStr, $threshold]);
$ins = $insStmt->fetchAll(PDO::FETCH_ASSOC);
// Document expiries (registration etc)
$docStmt = $db->prepare("SELECT vd.*, v.vehicle_name, v.plate_number FROM vehicle_documents vd JOIN fleet_vehicles v ON vd.vehicle_id = v.id WHERE vd.expiry_date BETWEEN ? AND ? ORDER BY vd.expiry_date ASC");
$docStmt->execute([$todayStr, $threshold]);
docs:
$docs = $docStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($ins) && empty($docs)) {
    echo "No upcoming expiries within {$days} days.\n";
    exit(0);
}

$body = '<h3>Upcoming Vehicle Document/Insurance Expiries</h3>';
if (!empty($ins)) {
    $body .= '<h4>Insurance</h4><ul>';
    foreach ($ins as $i) {
        $body .= '<li>' . htmlspecialchars($i['vehicle_name']) . ' (' . htmlspecialchars($i['plate_number']) . ') - Policy: ' . htmlspecialchars($i['policy_number'] ?? '') . ' - Expires: ' . htmlspecialchars($i['coverage_end']) . '</li>';
    }
    $body .= '</ul>';
}
if (!empty($docs)) {
    $body .= '<h4>Documents</h4><ul>';
    foreach ($docs as $d) {
        $body .= '<li>' . htmlspecialchars($d['vehicle_name']) . ' (' . htmlspecialchars($d['plate_number']) . ') - ' . htmlspecialchars($d['doc_type'] ?? '') . ' - Expires: ' . htmlspecialchars($d['expiry_date']) . '</li>';
    }
    $body .= '</ul>';
}

$to = SMTP_EMAIL; // send to admin configured in config
$subject = "Upcoming vehicle document/insurance expiries in next {$days} days";
$sent = sendEmail($to, $subject, $body);
if ($sent) {
    echo "Notification sent to {$to}\n";
    exit(0);
} else {
    echo "Failed to send notification\n";
    exit(1);
}
