<?php
session_start();
require_once 'Connection_DB.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['appointment_id']) || !isset($_POST['message'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit;
}

$appointment_id = intval($_POST['appointment_id']);
$message = trim($_POST['message']);
$sender_id = $_SESSION['user_id'];
$sender_type = isset($_SESSION['is_technician']) ? 'technician' : 'customer';

// Verify that the user has access to this appointment
$query = "SELECT * FROM Appointment WHERE ID = ? AND (Customer_ID = ? OR Technician_ID = ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $appointment_id, $sender_id, $sender_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access to this appointment']);
    exit;
}

// Insert the message
$query = "INSERT INTO Chat (Appointment_ID, sender_type, sender_ID, message) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("isss", $appointment_id, $sender_type, $sender_id, $message);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Message sent successfully',
        'data' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'sender_type' => $sender_type,
            'message' => $message
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
}

$stmt->close();
$conn->close();
?> 