<?php
session_start();
require_once 'Connection_DB.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['appointment_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit;
}

$appointment_id = intval($_GET['appointment_id']);
$user_id = $_SESSION['user_id'];

// Verify that the user has access to this appointment
$query = "SELECT * FROM Appointment WHERE ID = ? AND (Customer_ID = ? OR Technician_ID = ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $appointment_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access to this appointment']);
    exit;
}

// Get messages
$query = "SELECT c.*, 
          CASE 
            WHEN c.sender_type = 'customer' THEN cu.name
            ELSE t.name
          END as sender_name
          FROM Chat c
          LEFT JOIN Customer cu ON c.sender_ID = cu.ID AND c.sender_type = 'customer'
          LEFT JOIN Technician t ON c.sender_ID = t.ID AND c.sender_type = 'technician'
          WHERE c.Appointment_ID = ?
          ORDER BY c.timestamp ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'id' => $row['ID'],
        'sender_type' => $row['sender_type'],
        'sender_name' => $row['sender_name'],
        'message' => $row['message'],
        'timestamp' => $row['timestamp'],
        'is_read' => $row['is_read']
    ];
}

// Mark messages as read
$update_query = "UPDATE Chat SET is_read = TRUE 
                WHERE Appointment_ID = ? 
                AND sender_ID != ? 
                AND is_read = FALSE";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();

echo json_encode([
    'status' => 'success',
    'data' => $messages
]);

$stmt->close();
$conn->close();
?> 