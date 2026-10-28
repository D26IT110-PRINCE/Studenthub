<?php
/**
 * Admin User Creation Helper
 * Run this file ONCE in your browser to create a guaranteed working admin account.
 */

require_once 'db_connect_mysqli.php';

$name = "Admin User";
$email = "admin@gmail.com";
$password = "admin123"; // You can change this password here
$role = "admin";

// Generate the hash using the server's own PHP engine
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Use a Prepared Statement to insert the user
$sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

if ($stmt->execute()) {
    echo "<h2>Success!</h2>";
    echo "Admin user created successfully.<br>";
    echo "<strong>Email:</strong> $email<br>";
    echo "<strong>Password:</strong> $password<br>";
    echo "<p>You can now delete this file (create_admin.php) for security.</p>";
} else {
    echo "<h2>Error</h2>";
    echo "Could not create admin user. If the email already exists, please delete that row from phpMyAdmin first.";
}

$stmt->close();
$conn->close();
?>
