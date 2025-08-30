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

$action = $_POST['action'] ?? '';
$user_id = $_POST['user_id'] ?? null;
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'user';

// Prevent admin role creation
if ($role === 'admin') {
    $_SESSION['um_error'] = "You cannot assign the admin role.";
    header("Location: ../$baseURL");
    exit();
}

try {
    switch ($action) {
        case 'create':
        case 'create':
            if (empty($username) || empty($email) || empty($role)) {
                throw new Exception("Username, email, and role are required.");
            }

            // --- Generate EID ---
            function generateEID($role, $deptID, $userID)
            {
                $year = date("y"); // last 2 digits of current year
                $roleInitial = strtoupper(substr($role, 0, 1));
                return $roleInitial . $year . str_pad($deptID, 2, "0", STR_PAD_LEFT) . str_pad($userID, 2, "0", STR_PAD_LEFT);
            }

            // --- Generate Default Password ---
            function generateDefaultPassword($role, $eid)
            {
                $roleInitial = strtoupper(substr($role, 0, 1));
                $last2 = substr($eid, -2);
                return '#' . $roleInitial . $last2 . "Log02";
            }

            // First, insert a temporary record (without eid/password) so we get the user_id
            $stmt = $conn->prepare("INSERT INTO users (username, email, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $role);
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                throw new Exception("Failed to create user: $error");
            }

            $userID = $stmt->insert_id;  // get auto-incremented ID
            $stmt->close();

            // Logistic 2 department ID = 7
            $deptID = 7;

            // Generate EID
            $eid = generateEID($role, $deptID, $userID);

            // Generate password from algorithm and hash it
            $plainPassword = generateDefaultPassword($role, $eid);
            $hashed = password_hash($plainPassword, PASSWORD_DEFAULT);

            // Update record with eid and password
            $stmt = $conn->prepare("UPDATE users SET eid = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $eid, $hashed, $userID);
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                throw new Exception("Failed to update user credentials: $error");
            }
            $stmt->close();

            require_once 'mailer.php';

            // Send credentials to user's email
            $body = "
                <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e5e5e5; border-radius: 8px; background: #f9f9f9;'>
                    <h2 style='color: #135efdff; margin-bottom: 15px;'>Welcome to Logistics 2</h2>
            
                    <p>Hello <strong style='color:#111;'>$username</strong>,</p>
            
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
            if (empty($user_id) || empty($username) || empty($email)) {
                throw new Exception("User ID, username, and email are required.");
            }

            if ($role === 'admin') {
                throw new Exception("You cannot assign the admin role.");
            }

            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username=?, email=?, password=?, role=? WHERE id=?");
                $stmt->bind_param("ssssi", $username, $email, $hashed, $role, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
                $stmt->bind_param("sssi", $username, $email, $role, $user_id);
            }
            $stmt->execute();

            $_SESSION['um_success'] = "User account updated successfully.";
            break;

        case 'delete':
            if (empty($user_id)) {
                throw new Exception("User ID is required for deletion.");
            }

            // Prevent admin from deleting themselves
            if ($user_id == $_SESSION['user_id']) {
                throw new Exception("You cannot delete your own account.");
            }

            $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $_SESSION['um_success'] = "User account deleted successfully.";
            break;

        default:
            throw new Exception("Invalid action.");
    }
} catch (Exception $e) {
    $_SESSION['um_error'] = $e->getMessage();
}

header("Location: ../$baseURL");
exit();