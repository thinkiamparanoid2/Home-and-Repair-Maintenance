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

// Get technician ID from query parameters
$technician_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($technician_id <= 0) {
    echo json_encode(['error' => 'Invalid technician ID']);
    exit;
}

// Get technician information
$stmt = $conn->prepare("SELECT name FROM Technician WHERE ID = ?");
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$result = $stmt->get_result();
$technician = $result->fetch_assoc();
$stmt->close();

if (!$technician) {
    echo json_encode(['error' => 'Technician not found']);
    exit;
}

// Get reviews for the technician
$stmt = $conn->prepare("
    SELECT r.*, c.name as customer_name
    FROM Review r
    JOIN Customer c ON r.Customer_ID = c.ID
    WHERE r.Technician_ID = ?
    ORDER BY r.review_date DESC
");
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}

$stmt->close();

// Return technician name and reviews as JSON
header('Content-Type: application/json');
echo json_encode([
    'technician_name' => $technician['name'],
    'reviews' => $reviews
]);
?> 