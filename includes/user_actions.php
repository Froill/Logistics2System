<?php
session_start();
require_once 'db.php';

// Only admin can access
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['um_error'] = "Unauthorized access.";
    header("Location: ../dashboard.php");
    exit();
}

$baseURL = 'dashboard.php?module=user_management';

$action   = $_POST['action'] ?? '';
$user_id  = $_POST['user_id'] ?? null;
$full_name = trim($_POST['full_name'] ?? '');
$license_number = trim($_POST['license_number'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? null;

// Prevent admin role creation
if ($role === 'admin') {
    $_SESSION['um_error'] = "You cannot assign the admin role.";
    header("Location: ../$baseURL");
    exit();
}

// --- Helper functions ---
function generateEID($role, $deptID, $userID)
{
    $year = date("y"); // last 2 digits of current year
    $roleInitial = strtoupper(substr($role, 0, 1));
    return $roleInitial . $year . str_pad($deptID, 2, "0", STR_PAD_LEFT) . str_pad($userID, 2, "0", STR_PAD_LEFT);
}

function generateDefaultPassword($role, $eid)
{
    $roleInitial = strtoupper(substr($role, 0, 1));
    $last2 = substr($eid, -2);
    return '#' . $roleInitial . $last2 . "Log02";
}

try {
    switch ($action) {
        case 'create':
            if (empty($full_name) || empty($email) || empty($role)) {
                throw new Exception("Full name, email, and role are required.");
            }

            // Insert basic user
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $full_name, $email, $role);
            $stmt->execute();
            $userID = $stmt->insert_id;
            $stmt->close();

            $deptID = 7;
            $eid = generateEID($role, $deptID, $userID);
            $plainPassword = generateDefaultPassword($role, $eid);
            $hashed = password_hash($plainPassword, PASSWORD_DEFAULT);

            // Update user with eid + password
            $stmt = $conn->prepare("UPDATE users SET eid = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $eid, $hashed, $userID);
            $stmt->execute();
            $stmt->close();

            // --- Insert driver if role = driver ---
            if ($role === 'driver') {
                $license_number = $_POST['license_number'] ?? null;
                $stmt = $conn->prepare("INSERT INTO drivers (eid, driver_name, license_number, email, status) VALUES (?, ?, ?, ?, 'Available')");
                $stmt->bind_param("ssss", $eid, $full_name, $license_number, $email);
                $stmt->execute();
                $stmt->close();
            }

            require_once 'mailer.php';

            $first_name = explode(' ', $full_name)[0];
            // Send credentials to user's email
            $body = "
                <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e5e5e5; border-radius: 8px; background: #f9f9f9;'>
                    <h2 style='color: #135efdff; margin-bottom: 15px;'>Welcome to Logistics 2</h2>
            
                    <p>Hello <strong style='color:#111;'>$first_name</strong>,</p>
            
                    <p>Your account has been created successfully.</p>
            
                    <div style='background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 6px; margin: 15px 0;'>
                        <p style='margin: 5px 0;'><strong>EID:</strong> <span style='color:#135efdff;'>$eid</span></p>
                        <p style='margin: 5px 0;'><strong>Default Password:</strong> <span style='color:#EF4444;'>$plainPassword</span></p>
                    </div>
            
                    <p style='font-size: 14px; color: #555;'>⚠️ Please change your password after logging in for security.</p>
            
                    <hr style='margin: 20px 0; border: none; border-top: 1px solid #eee;' />
            
                    <p style='font-size: 12px; color: #999;'>This is an automated message. Please do not reply.</p>
                </div>";


            sendEmail($email, 'Your credentials', $body);

            $_SESSION['um_success'] = "User account created successfully. Credentials have been sent to user's email.";
            break;


        case 'update':
            if (empty($user_id) || empty($full_name) || empty($email)) {
                throw new Exception("User ID, full name, and email are required.");
            }

            // Get old role + eid
            $res = $conn->prepare("SELECT role, eid FROM users WHERE id=?");
            $res->bind_param("i", $user_id);
            $res->execute();
            $res->bind_result($oldRole, $eid);
            $res->fetch();
            $res->close();

            // Whitelist of roles that can be assigned by admin
            $allowedRoles = ['supervisor', 'manager', 'requester', 'driver']; // add more roles if needed

            // Determine role to use
            if (isset($role) && in_array($role, $allowedRoles)) {
                $roleToUse = $role;
            } else {
                $roleToUse = $oldRole; // fallback to current role
            }

            // Prepare update statement
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, password=?, role=? WHERE id=?");
                $stmt->bind_param("ssssi", $full_name, $email, $hashed, $roleToUse, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, role=? WHERE id=?");
                $stmt->bind_param("sssi", $full_name, $email, $roleToUse, $user_id);
            }
            $stmt->execute();
            $stmt->close();

            // --- Driver sync logic ---
            if ($roleToUse === 'driver') {
                // Insert or update driver record
                $stmt = $conn->prepare("
            INSERT INTO drivers (eid, driver_name, license_number, email, status)
            VALUES (?, ?, ?, ?, 'Available')
            ON DUPLICATE KEY UPDATE 
                driver_name=VALUES(driver_name),
                license_number=VALUES(license_number),
                email=VALUES(email),
                status='Available'
        ");
                $stmt->bind_param("ssss", $eid, $full_name, $license_number, $email);
                $stmt->execute();
                $stmt->close();
            } elseif ($oldRole === 'driver' && $roleToUse !== 'driver') {
                // If user is no longer a driver, mark inactive
                $stmt = $conn->prepare("UPDATE drivers SET status='Inactive' WHERE eid=?");
                $stmt->bind_param("s", $eid);
                $stmt->execute();
                $stmt->close();
            }

            $_SESSION['um_success'] = "User account updated successfully.";
            break;



        case 'delete':
            if (empty($user_id)) {
                throw new Exception("User ID is required for deletion.");
            }
            if ($user_id == $_SESSION['user_id']) {
                throw new Exception("You cannot delete your own account.");
            }

            // Get eid before deleting
            $stmt = $conn->prepare("SELECT eid FROM users WHERE id=? LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($eid);
            $stmt->fetch();
            $stmt->close();

            // Delete from users
            $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            // Cascade delete from drivers
            if (!empty($eid)) {
                $stmt = $conn->prepare("DELETE FROM drivers WHERE eid=?");
                $stmt->bind_param("s", $eid);
                $stmt->execute();
                $stmt->close();
            }
            break;


        default:
            throw new Exception("Invalid action.");
    }
} catch (Exception $e) {
    $_SESSION['um_error'] = $e->getMessage();
}

header("Location: ../$baseURL");
exit();
