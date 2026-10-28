<?php
/**
 * MySQLi Database Connection
 * Used exclusively for Practical 9 to demonstrate MySQLi prepared statements.
 */

$host = "localhost";
$username = "root";
$password = "";
$dbname = "studenthub";

// Create connection object
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for proper character encoding
$conn->set_charset("utf8mb4");

// Optional: Make MySQLi throw exceptions like PDO for better error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>
