<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = "localhost"; // Your database host
$username = "root";  // Your database username
$password = "";      // Your database password
$dbname = "db";  // Your database name (notice: lowercase 'db' instead of 'DB')

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Uncomment for debugging
// echo "Connected to database successfully";
?>
