<?php
require 'includes/config.php';

// Test account details
$username = "admin1";
$password_plain = "password"; // Change if you want
$role = "admin";

// Hash the password
$password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

// Prepare SQL
$sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $username, $password_hashed, $role);

if ($stmt->execute()) {
    echo "✅ Test admin user created successfully.<br>";
    echo "Username: {$username}<br>";
    echo "Password: {$password_plain}<br>";
} else {
    echo "❌ Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
