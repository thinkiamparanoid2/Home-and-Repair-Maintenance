<?php
// Include database connection
include('Connection_DB.php');

// Initialize session
session_start();

// Check if user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] !== true) {
    header("Location: INDEX.php");
    exit;
}

$error = null;
$success = null;

// Check if technician ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $error = "No technician specified.";
} else {
    $technician_id = intval($_GET['id']);
    
    // First check if technician has any pending, price_quoted, counter_offered, or confirmed appointments
    $stmt = $conn->prepare("
        SELECT COUNT(*) as active_count 
        FROM Appointment 
        WHERE Technician_ID = ? AND status IN ('pending', 'price_quoted', 'counter_offered', 'confirmed')
    ");
    $stmt->bind_param("i", $technician_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $active_count = $row['active_count'];
    $stmt->close();
    
    if ($active_count > 0) {
        $error = "Cannot remove technician with active appointments. The technician has $active_count active appointments that need to be completed or cancelled first.";
    } else {
        // Begin transaction to ensure all operations succeed or fail together
        $conn->begin_transaction();
        
        try {
            // Delete reviews associated with this technician
            $stmt = $conn->prepare("DELETE FROM Review WHERE Technician_ID = ?");
            $stmt->bind_param("i", $technician_id);
            $stmt->execute();
            $stmt->close();
            
            // Delete completed or cancelled appointments
            $stmt = $conn->prepare("DELETE FROM Appointment WHERE Technician_ID = ? AND status IN ('completed', 'cancelled')");
            $stmt->bind_param("i", $technician_id);
            $stmt->execute();
            $stmt->close();
            
            // Now delete the technician
            $stmt = $conn->prepare("DELETE FROM Technician WHERE ID = ?");
            $stmt->bind_param("i", $technician_id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $success = "Technician removed successfully!";
                $conn->commit();
            } else {
                $error = "Technician not found or already removed.";
                $conn->rollback();
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error removing technician: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remove Technician</title>
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
        <h1>Remove Technician</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <p>There was an error processing your request.</p>
            </div>
        <?php endif; ?>
        
        <a href="admin_dashboard.php" class="btn">Return to Dashboard</a>
    </div>
</body>
</html> 