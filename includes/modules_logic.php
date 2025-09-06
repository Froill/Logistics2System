<?php
function tcao_logic($baseURL)
{

    $user = $_SESSION['username'] ?? 'unknown';
    global $conn;

    // Handle delete (admin only)
    if (isset($_GET['delete'])) {
        deleteData('transport_costs', $_GET['delete']);
        log_audit_event('TCAO', 'deleted', $_GET['delete'], $user);
        header("Location: {$baseURL}");
        exit;
    }
    // Clear all cost logs
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cost_logs'])) {
        $allCosts = fetchAll('transport_costs');
        foreach ($allCosts as $cost) {
            log_audit_event('TCAO', 'deleted', $cost['id'], $user);
        }
        $conn->query("DELETE FROM transport_costs");
        $_SESSION['tcao_success'] = 'All cost logs cleared.';
        header("Location: {$baseURL}");
        exit;
    }

    // Handle status transitions (supervisor/accountant)
    if (isset($_GET['approve']) && isset($_GET['role'])) {
        $id = intval($_GET['approve']);
        $role = $_GET['role'];
        if ($role === 'supervisor') {
            $conn->query("UPDATE transport_costs SET status='supervisor_approved' WHERE id=$id");
            log_audit_event('TCAO', 'supervisor_approved', $id, $user);
        } elseif ($role === 'accountant') {
            $conn->query("UPDATE transport_costs SET status='finalized' WHERE id=$id");
            log_audit_event('TCAO', 'finalized', $id, $user);
        }
        header("Location: {$baseURL}");
        exit;
    }
    if (isset($_GET['return']) && isset($_GET['role'])) {
        $id = intval($_GET['return']);
        $role = $_GET['role'];
    $conn->query("UPDATE transport_costs SET status='returned' WHERE id=$id");
    log_audit_event('TCAO', 'returned_by_' . $role, $id, $user);
        header("Location: {$baseURL}");
        exit;
    }

    // Handle driver submission (with receipt upload)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trip_id'])) {
        $fuel  = floatval($_POST['fuel_cost'] ?: 0);
        $toll  = floatval($_POST['toll_fees'] ?: 0);
        $other = floatval($_POST['other_expenses'] ?: 0);
        $total = $fuel + $toll + $other;

        // Handle receipt upload
        $receipt_path = null;
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
            $target = __DIR__ . '/../uploads/receipts_' . uniqid() . '.' . $ext;
            if (!is_dir(__DIR__ . '/../uploads')) mkdir(__DIR__ . '/../uploads');
            move_uploaded_file($_FILES['receipt']['tmp_name'], $target);
            $receipt_path = basename($target);
        }

        // Validate: check for duplicate trip_id
        $stmt = $conn->prepare("SELECT id FROM transport_costs WHERE trip_id=?");
        $stmt->bind_param('s', $_POST['trip_id']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $_SESSION['tcao_error'] = 'Trip already has a cost entry.';
            header("Location: {$baseURL}");
            exit;
        }

        // Insert
        $stmt = $conn->prepare("INSERT INTO transport_costs (trip_id, fuel_cost, toll_fees, other_expenses, total_cost, receipt, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, 'submitted', ?, NOW())");
        $stmt->bind_param('sdddsss', $_POST['trip_id'], $fuel, $toll, $other, $total, $receipt_path, $user);
        $stmt->execute();
    $cost_id = $stmt->insert_id;
    log_audit_event('TCAO', 'submitted', $cost_id, $user);
        $_SESSION['tcao_success'] = 'Cost entry submitted.';
        header("Location: {$baseURL}");
        exit;
    }
}