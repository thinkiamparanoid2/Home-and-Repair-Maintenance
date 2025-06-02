<?php
// Include database connection
include('Connection_DB.php');

// Initialize session
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || isset($_SESSION['is_technician'])) {
    header("Location: INDEX.php");
    exit;
}

if (!isset($_POST['appointment_id']) || !isset($_POST['counter_price'])) {
    header("Location: user_dashboard.php");
    exit;
}

$appointment_id = intval($_POST['appointment_id']);
$counter_price = floatval($_POST['counter_price']);
$customer_id = $_SESSION['user_id'];

// Verify that this appointment belongs to the customer
$stmt = $conn->prepare("SELECT * FROM Appointment WHERE ID = ? AND Customer_ID = ?");
$stmt->bind_param("ii", $appointment_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: user_dashboard.php");
    exit;
}

$appointment = $result->fetch_assoc();

// Check if customer has reached the counter offer limit
if (isset($appointment['customer_counter_count']) && $appointment['customer_counter_count'] >= 2) {
    $errors = ["You have reached the maximum number of counter offers (2) for this appointment."];
    $conn->close();
    goto html_output;
}

// Update the appointment with the counter offer and increment the counter
$stmt = $conn->prepare("UPDATE Appointment SET status = 'counter_offered', customer_price = ?, customer_counter_count = customer_counter_count + 1 WHERE ID = ?");
$stmt->bind_param("di", $counter_price, $appointment_id);

if ($stmt->execute()) {
    // Insert a chat message about the counter offer
    $message = "Counter offer: $" . number_format($counter_price, 2);
    $stmt = $conn->prepare("INSERT INTO Chat (Appointment_ID, sender_type, sender_ID, message) VALUES (?, 'customer', ?, ?)");
    $stmt->bind_param("iis", $appointment_id, $customer_id, $message);
    $stmt->execute();
    
    header("Location: user_dashboard.php");
} else {
    $errors = ["Error updating appointment. Please try again."];
}

$stmt->close();
$conn->close();

html_output:
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counter Offer</title>
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
        <h1>Counter Offer</h1>
        
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