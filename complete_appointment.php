<?php
// Include database connection
include('Connection_DB.php');

// Initialize session
session_start();

// Check if user is logged in and is not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] === true) {
    header("Location: INDEX.php");
    exit;
}

$technician_id = $_SESSION['user_id'];

// Check if appointment ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $error = "No appointment specified.";
} else {
    $appointment_id = intval($_GET['id']);
    
    // Check if the appointment exists and belongs to this technician
    $stmt = $conn->prepare("
        SELECT a.ID, a.status
        FROM Appointment a
        WHERE a.ID = ? AND a.Technician_ID = ? AND a.status = 'confirmed'
    ");
    $stmt->bind_param("ii", $appointment_id, $technician_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = "Appointment not found or cannot be marked as completed at this stage.";
    } else {
        // Update appointment status to completed
        $stmt = $conn->prepare("
            UPDATE Appointment 
            SET status = 'completed'
            WHERE ID = ? AND Technician_ID = ?
        ");
        $stmt->bind_param("ii", $appointment_id, $technician_id);
        
        if ($stmt->execute()) {
            $success = "Appointment marked as completed successfully!";
        } else {
            $error = "Error completing appointment: " . $stmt->error;
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Appointment</title>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --purple: #3e1c61;
            --gold: #b88a00;
            --gold-hover: #a37b00;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Urbanist', sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: var(--purple);
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        
        .alert-danger {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        
        .btn {
            background: var(--gold);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        
        .btn:hover {
            background: var(--gold-hover);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Complete Appointment</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <?php echo isset($error) ? $error : "There was an error processing your request."; ?>
            </div>
        <?php endif; ?>
        
        <a href="technician_dashboard.php" class="btn">Return to Dashboard</a>
    </div>
</body>
</html> 