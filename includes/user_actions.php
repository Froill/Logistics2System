<?php
session_start();
require_once 'db.php';

// Only admin can access
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['um_error'] = "Unauthorized access.";
    header("Location: ../dashboard.php");
    exit();
}

$action = $_POST['action'] ?? '';
$user_id = $_POST['user_id'] ?? null;
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'user'; // Now role comes from form

// Prevent admin role creation
if ($role === 'admin') {
    $_SESSION['um_error'] = "You cannot assign the admin role.";
    header("Location: ../dashboard.php?module=um");
    exit();
}

try {
    switch ($action) {
        case 'create':
            if (empty($username) || empty($email) || empty($password)) {
                throw new Exception("All fields are required.");
            }
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed, $role);
            $stmt->execute();

            $_SESSION['um_success'] = "User account created successfully.";
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

header("Location: ../dashboard.php?module=um");
exit();
