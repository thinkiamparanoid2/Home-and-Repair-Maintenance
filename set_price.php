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

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    
    // Validate data
    $errors = [];
    
    if ($appointment_id <= 0) {
        $errors[] = "Invalid appointment selected.";
    }
    
    if ($price <= 0) {
        $errors[] = "Please enter a valid price.";
    }
    
    // Check if the appointment exists and belongs to this technician
    if ($appointment_id > 0) {
        $stmt = $conn->prepare("
            SELECT a.ID, a.status, a.technician_counter_count
            FROM Appointment a
            WHERE a.ID = ? AND a.Technician_ID = ? AND (a.status = 'pending' OR a.status = 'counter_offered')
        ");
        
        if ($stmt === false) {
            $errors[] = "Error preparing statement: " . $conn->error;
        } else {
            $stmt->bind_param("ii", $appointment_id, $technician_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $errors[] = "Appointment not found or already processed.";
            } else {
                $appointment = $result->fetch_assoc();
                // Only check/increment counter if status is 'counter_offered'
                if ($appointment['status'] === 'counter_offered') {
                    if (isset($appointment['technician_counter_count']) && $appointment['technician_counter_count'] >= 2) {
                        $errors[] = "You have reached the maximum number of counter offers (2) for this appointment.";
                    }
                }
            }
            $stmt->close();
        }
    }
    
    // If no errors, update appointment
    if (empty($errors)) {
        if (isset($appointment) && $appointment['status'] === 'counter_offered') {
            // Increment technician_counter_count and set status to 'price_quoted'
            $stmt = $conn->prepare("
                UPDATE Appointment 
                SET status = 'price_quoted', technician_price = ?, technician_counter_count = technician_counter_count + 1
                WHERE ID = ? AND Technician_ID = ?
            ");
        } else {
            // Normal price quote (first time or after pending)
            $stmt = $conn->prepare("
                UPDATE Appointment 
                SET status = 'price_quoted', technician_price = ?
                WHERE ID = ? AND Technician_ID = ?
            ");
        }
        
        if ($stmt === false) {
            $errors[] = "Error preparing statement: " . $conn->error;
        } else {
            $stmt->bind_param("dii", $price, $appointment_id, $technician_id);
            
            if ($stmt->execute()) {
                $success = "Price quote submitted successfully!";
            } else {
                $errors[] = "Error setting price: " . $stmt->error;
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
    <title>Set Price</title>
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
        <h1>Set Price</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
            <a href="technician_dashboard.php" class="btn">Return to Dashboard</a>
        <?php elseif (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <a href="javascript:history.back()" class="btn">Go Back</a>
        <?php else: ?>
            <div class="alert alert-danger">
                <p>There was an error processing your request.</p>
            </div>
            <a href="technician_dashboard.php" class="btn">Return to Dashboard</a>
        <?php endif; ?>
    </div>
</body>
</html> 