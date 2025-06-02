<?php
// Include database connection
include('Connection_DB.php');

// Initialize session
session_start();

// Check if user is logged in and is not an admin or technician
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] === true || (isset($_SESSION['is_technician']) && $_SESSION['is_technician'] === true)) {
    header("Location: INDEX.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $technician_id = isset($_POST['technician_id']) ? intval($_POST['technician_id']) : 0;
    $appointment_date = isset($_POST['appointment_date']) ? $_POST['appointment_date'] : '';
    $appointment_time = isset($_POST['appointment_time']) ? $_POST['appointment_time'] : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    // Validate data
    $errors = [];
    
    if ($technician_id <= 0) {
        $errors[] = "Invalid technician selected.";
    }
    
    if (empty($appointment_date) || strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
        $errors[] = "Please select a valid future date.";
    }
    
    if (empty($appointment_time)) {
        $errors[] = "Please select an appointment time.";
    }
    
    if (empty($description)) {
        $errors[] = "Please provide a description of the work needed.";
    }
    
    // Check if the technician already has a confirmed appointment at this time
    if (empty($errors)) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM Appointment
            WHERE Technician_ID = ?
            AND date = ?
            AND time = ?
            AND status = 'confirmed'
        ");
        
        $stmt->bind_param("iss", $technician_id, $appointment_date, $appointment_time);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $errors[] = "This time slot is already booked. Please select another time.";
        }
        
        $stmt->close();
    }
    
    // If no errors, create the appointment
    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO Appointment (Customer_ID, Technician_ID, date, time, description, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        
        if ($stmt === false) {
            $errors[] = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
        } else {
            $stmt->bind_param("iisss", $customer_id, $technician_id, $appointment_date, $appointment_time, $description);
            
            if ($stmt->execute()) {
                $success = "Appointment request submitted successfully! The technician will review your request and provide a price quote.";
            } else {
                $errors[] = "Error creating appointment: " . $stmt->error;
            }
            
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
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
        <h1>Book Appointment</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php elseif (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <a href="user_dashboard.php" class="btn">Return to Dashboard</a>
    </div>
</body>
</html> 