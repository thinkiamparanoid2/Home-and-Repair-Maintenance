<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include('Connection_DB.php');

echo "<h1>Database Structure Check</h1>";

// Check Appointment table structure
$result = $conn->query("DESCRIBE Appointment");
if ($result) {
    echo "<h2>Appointment Table Structure:</h2>";
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        echo "Field: " . $row['Field'] . ", Type: " . $row['Type'] . ", Null: " . $row['Null'] . ", Default: " . $row['Default'] . "<br>";
    }
    echo "</pre>";
} else {
    echo "Error checking Appointment table structure: " . $conn->error;
}

// Check if counter_price field exists
$result = $conn->query("SHOW COLUMNS FROM Appointment LIKE 'counter_price'");
if ($result) {
    if ($result->num_rows > 0) {
        echo "<p>counter_price field exists.</p>";
    } else {
        echo "<p>counter_price field does NOT exist!</p>";
    }
} else {
    echo "Error checking for counter_price field: " . $conn->error;
}

// Close connection
$conn->close();
?> 