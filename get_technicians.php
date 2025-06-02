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

// Get technician type from query parameters
$type = isset($_GET['type']) ? $_GET['type'] : '';

if (empty($type)) {
    echo json_encode(['error' => 'No technician type specified']);
    exit;
}

// Query technicians by type
$stmt = $conn->prepare("
    SELECT t.*, 
           IFNULL(AVG(r.rating), 0) as avg_rating,
           COUNT(r.rating) as review_count
    FROM Technician t
    LEFT JOIN Review r ON t.ID = r.Technician_ID
    WHERE t.technician_type = ?
    GROUP BY t.ID
    ORDER BY avg_rating DESC
");
$stmt->bind_param("s", $type);
$stmt->execute();
$result = $stmt->get_result();

$technicians = [];
while ($row = $result->fetch_assoc()) {
    // Don't include password in response
    unset($row['password']);
    $technicians[] = $row;
}

$stmt->close();

// Return technicians as JSON
header('Content-Type: application/json');
echo json_encode($technicians);
?> 