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

$customer_id = $_SESSION['user_id'];

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $technician_id = isset($_POST['technician_id']) ? intval($_POST['technician_id']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = isset($_POST['comment']) ? $_POST['comment'] : '';
    
    // Validate data
    $errors = [];
    
    if ($technician_id <= 0) {
        $errors[] = "Invalid technician selected.";
    }
    
    if ($rating < 1 || $rating > 5) {
        $errors[] = "Please select a rating between 1 and 5.";
    }
    
    if (empty($comment)) {
        $errors[] = "Please provide a comment.";
    }
    
    // Check if technician exists
    if ($technician_id > 0) {
        $stmt = $conn->prepare("SELECT ID FROM Technician WHERE ID = ?");
        $stmt->bind_param("i", $technician_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $errors[] = "Technician not found.";
        }
        $stmt->close();
    }
    
    // Check if there is a completed appointment with this technician
    if ($technician_id > 0) {
        $stmt = $conn->prepare("
            SELECT ID 
            FROM Appointment 
            WHERE Customer_ID = ? AND Technician_ID = ? AND status = 'completed'
        ");
        $stmt->bind_param("ii", $customer_id, $technician_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $errors[] = "You can only review technicians after completing an appointment with them.";
        }
        $stmt->close();
    }
    
    // Check if a review already exists for this appointment
    if ($technician_id > 0) {
        $stmt = $conn->prepare("
            SELECT r.Customer_ID 
            FROM Review r
            JOIN Appointment a ON r.Customer_ID = a.Customer_ID AND r.Technician_ID = a.Technician_ID
            WHERE r.Customer_ID = ? AND r.Technician_ID = ? AND a.status = 'completed'
        ");
        $stmt->bind_param("ii", $customer_id, $technician_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "You have already reviewed this technician for a completed appointment.";
        } else {
            // Insert new review
            $stmt = $conn->prepare("
                INSERT INTO Review (Customer_ID, Technician_ID, rating, comment)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iiis", $customer_id, $technician_id, $rating, $comment);
            
            if ($stmt->execute()) {
                $success = "Thank you for your review!";
            } else {
                $errors[] = "Error submitting review: " . $stmt->error;
            }
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
    <title>Submit Review</title>
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
        <h1>Submit Review</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php elseif (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <p>There was an error processing your request.</p>
            </div>
        <?php endif; ?>
        
        <a href="user_dashboard.php" class="btn">Return to Dashboard</a>
    </div>
</body>
</html> 