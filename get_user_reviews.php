<?php
// Include database connection
include('Connection_DB.php');

// Initialize session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get reviews written by the user
$stmt = $conn->prepare("
    SELECT r.*, t.name as technician_name, t.technician_type
    FROM Review r
    JOIN Technician t ON r.Technician_ID = t.ID
    WHERE r.Customer_ID = ?
    ORDER BY r.review_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}

$stmt->close();

// Return reviews as JSON
header('Content-Type: application/json');
echo json_encode($reviews);
?> 