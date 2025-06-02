<?php
// Include database connection
include('Connection_DB.php');

// Initialize session
session_start();

// Check if user is logged in as a technician
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_technician']) || $_SESSION['is_technician'] !== true) {
    header("Location: INDEX.php");
    exit;
}

// Get technician information
$technician_id = $_SESSION['user_id'];
$technician_name = $_SESSION['user_name'];

// Get technician details
$stmt = $conn->prepare("SELECT * FROM Technician WHERE ID = ?");
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$technician_details = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get technician's reviews
$stmt = $conn->prepare("SELECT r.*, c.name as customer_name 
                       FROM Review r 
                       JOIN Customer c ON r.Customer_ID = c.ID 
                       WHERE r.Technician_ID = ? 
                       ORDER BY r.review_date DESC");
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$reviews = $stmt->get_result();
$stmt->close();

// Calculate average rating and completed appointments
$stmt = $conn->prepare("SELECT 
                           AVG(rating) as avg_rating,
                           (SELECT COUNT(*) FROM Appointment WHERE Technician_ID = ? AND status = 'completed') as completed_appointments
                       FROM Review 
                       WHERE Technician_ID = ?");
$stmt->bind_param("ii", $technician_id, $technician_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get pending appointment requests
$stmt = $conn->prepare("SELECT a.*, c.name as customer_name, c.phone, c.address 
                       FROM Appointment a 
                       JOIN Customer c ON a.Customer_ID = c.ID 
                       WHERE a.Technician_ID = ? AND a.status = 'pending'
                       ORDER BY a.date, a.time");
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$pending_appointments = $stmt->get_result();
$stmt->close();

// Get appointments with a price quote (waiting for customer confirmation)
$stmt = $conn->prepare("SELECT a.*, c.name as customer_name, c.phone, c.address 
                       FROM Appointment a 
                       JOIN Customer c ON a.Customer_ID = c.ID 
                       WHERE a.Technician_ID = ? AND a.status = 'price_quoted'
                       ORDER BY a.date, a.time");
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$quoted_appointments = $stmt->get_result();
$stmt->close();

// Get counter-offered appointments
$stmt = $conn->prepare("SELECT a.*, c.name as customer_name, c.phone, c.address 
                       FROM Appointment a 
                       JOIN Customer c ON a.Customer_ID = c.ID 
                       WHERE a.Technician_ID = ? AND a.status = 'counter_offered'
                       ORDER BY a.date, a.time");
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$counter_offered_appointments = $stmt->get_result();
$stmt->close();

// Get confirmed appointments
$stmt = $conn->prepare("SELECT a.*, c.name as customer_name, c.phone, c.address 
                       FROM Appointment a 
                       JOIN Customer c ON a.Customer_ID = c.ID 
                       WHERE a.Technician_ID = ? AND a.status = 'confirmed'
                       ORDER BY a.date, a.time");
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$confirmed_appointments = $stmt->get_result();
$stmt->close();

// Get completed appointments
$stmt = $conn->prepare("SELECT a.*, c.name as customer_name, c.phone, c.address 
                       FROM Appointment a 
                       JOIN Customer c ON a.Customer_ID = c.ID 
                       WHERE a.Technician_ID = ? AND a.status = 'completed'
                       ORDER BY a.date, a.time DESC");
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$completed_appointments = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard</title>
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
        
        header {
            background-color: var(--purple);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .btn {
            background: var(--gold);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: var(--gold-hover);
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
            border-bottom-color: var(--gold);
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .appointment-card {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: transform 0.3s;
        }
        
        .appointment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        
        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .appointment-date {
            font-weight: bold;
            color: var(--purple);
        }
        
        .appointment-customer {
            margin-bottom: 15px;
        }
        
        .appointment-description {
            margin-bottom: 15px;
            padding: 10px;
            background: #f9f9f9;
            border-left: 3px solid var(--gold);
        }
        
        .appointment-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 100;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }
        
        .close {
            float: right;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        input, textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .section-title {
            margin: 30px 0 20px 0;
            color: var(--purple);
            border-bottom: 2px solid var(--gold);
            padding-bottom: 10px;
            display: inline-block;
        }

        .count-badge {
            background: var(--gold);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 5px;
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: #777;
        }

        .profile-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: var(--purple);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
        }

        .profile-info h2 {
            color: var(--purple);
            margin-bottom: 5px;
        }

        .profile-stats {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--gold);
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }

        .reviews-section {
            margin-top: 20px;
        }

        .review-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .review-rating {
            color: var(--gold);
        }

        .review-date {
            color: #666;
            font-size: 0.9rem;
        }

        .review-content {
            margin-top: 10px;
        }

        .rating-stars {
            font-size: 1.2rem;
            color: var(--gold);
        }

        .profile-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .detail-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .detail-label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .detail-value {
            color: var(--purple);
            font-weight: bold;
        }

        .time-slot-group {
            margin-bottom: 30px;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 20px;
            background: #f9f9f9;
        }

        .time-slot-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--purple);
        }

        .multiple-requests-badge {
            background: var(--gold);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            margin-left: 10px;
        }

        .warning-text {
            color: #856404;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Welcome, <?php echo htmlspecialchars($technician_name); ?></h1>
        <a href="logout.php" class="btn">Logout</a>
    </header>
    
    <div class="container">
        <!-- Profile Section -->
        <div class="profile-section">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($technician_name, 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($technician_name); ?></h2>
                    <p><?php echo ucfirst(htmlspecialchars($technician_details['technician_type'])); ?></p>
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo number_format($stats['avg_rating'], 1); ?></div>
                            <div class="stat-label">Average Rating</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $stats['completed_appointments']; ?></div>
                            <div class="stat-label">Completed Jobs</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profile-details">
                <div class="detail-item">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><?php echo htmlspecialchars($technician_details['email']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Phone</div>
                    <div class="detail-value"><?php echo htmlspecialchars($technician_details['phone']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Address</div>
                    <div class="detail-value"><?php echo htmlspecialchars($technician_details['address']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Service Type</div>
                    <div class="detail-value"><?php echo ucfirst(htmlspecialchars($technician_details['technician_type'])); ?></div>
                </div>
            </div>

            <div class="reviews-section">
                <h3>Recent Reviews</h3>
                <?php if ($reviews->num_rows > 0): ?>
                    <?php while ($review = $reviews->fetch_assoc()): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div>
                                    <strong><?php echo htmlspecialchars($review['customer_name']); ?></strong>
                                    <div class="rating-stars">
                                        <?php 
                                            echo str_repeat('★', $review['rating']) . 
                                                 str_repeat('☆', 5 - $review['rating']); 
                                        ?>
                                    </div>
                                </div>
                                <div class="review-date">
                                    <?php echo date('F j, Y', strtotime($review['review_date'])); ?>
                                </div>
                            </div>
                            <div class="review-content">
                                <?php echo htmlspecialchars($review['comment']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No reviews yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="tabs">
            <div class="tab active" data-tab="pending">
                Appointment Requests
                <?php if ($pending_appointments->num_rows > 0): ?>
                <span class="count-badge"><?php echo $pending_appointments->num_rows; ?></span>
                <?php endif; ?>
            </div>
            <div class="tab" data-tab="quoted">
                Price Quotes
                <?php if ($quoted_appointments->num_rows > 0): ?>
                <span class="count-badge"><?php echo $quoted_appointments->num_rows; ?></span>
                <?php endif; ?>
            </div>
            <div class="tab" data-tab="counter_offered">
                Counter-Offered Quotes
                <?php if ($counter_offered_appointments->num_rows > 0): ?>
                <span class="count-badge"><?php echo $counter_offered_appointments->num_rows; ?></span>
                <?php endif; ?>
            </div>
            <div class="tab" data-tab="confirmed">
                Confirmed Appointments
                <?php if ($confirmed_appointments->num_rows > 0): ?>
                <span class="count-badge"><?php echo $confirmed_appointments->num_rows; ?></span>
                <?php endif; ?>
            </div>
            <div class="tab" data-tab="completed">History</div>
        </div>
        
        <!-- Pending Appointments Tab -->
        <div class="tab-content active" id="pending">
            <h2 class="section-title">Appointment Requests</h2>
            
            <?php if ($pending_appointments->num_rows > 0): ?>
                <?php 
                $time_slots = array();
                while ($appointment = $pending_appointments->fetch_assoc()) {
                    $slot_key = $appointment['date'] . '_' . $appointment['time'];
                    if (!isset($time_slots[$slot_key])) {
                        $time_slots[$slot_key] = array();
                    }
                    $time_slots[$slot_key][] = $appointment;
                }
                
                foreach ($time_slots as $slot_key => $appointments):
                    list($date, $time) = explode('_', $slot_key);
                ?>
                    <div class="time-slot-group">
                        <div class="time-slot-header">
                            <h3>
                                <?php 
                                    echo date('F j, Y', strtotime($date)) . ' at ';
                                    echo date('g:i A', strtotime($time));
                                ?>
                                <?php if (count($appointments) > 1): ?>
                                    <span class="multiple-requests-badge"><?php echo count($appointments); ?> requests</span>
                                <?php endif; ?>
                            </h3>
                        </div>
                        
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="appointment-card">
                                <div class="appointment-header">
                                    <span>Request ID: #<?php echo $appointment['ID']; ?></span>
                                </div>
                                
                                <div class="appointment-customer">
                                    <h3><?php echo htmlspecialchars($appointment['customer_name']); ?></h3>
                                    <p>Phone: <?php echo htmlspecialchars($appointment['phone']); ?></p>
                                    <p>Address: <?php echo htmlspecialchars($appointment['address']); ?></p>
                                </div>
                                
                                <div class="appointment-description">
                                    <h4>Service Description:</h4>
                                    <p><?php echo htmlspecialchars($appointment['description']); ?></p>
                                </div>
                                
                                <div class="appointment-actions">
                                    <?php if (count($appointments) > 1): ?>
                                        <p class="warning-text">Note: There are multiple requests for this time slot. Once you confirm one, others will need to be rescheduled.</p>
                                    <?php endif; ?>
                                    <button class="btn quote-btn" data-id="<?php echo $appointment['ID']; ?>">Set Price</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No pending appointment requests.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quoted Appointments Tab -->
        <div class="tab-content" id="quoted">
            <h2 class="section-title">Price Quotes</h2>
            
            <?php if ($quoted_appointments->num_rows > 0): ?>
                <?php while ($appointment = $quoted_appointments->fetch_assoc()): ?>
                    <div class="appointment-card">
                        <div class="appointment-header">
                            <div class="appointment-date">
                                <?php 
                                    echo date('F j, Y', strtotime($appointment['date'])) . ' at ';
                                    echo date('g:i A', strtotime($appointment['time']));
                                ?>
                            </div>
                            <span>Request ID: #<?php echo $appointment['ID']; ?></span>
                        </div>
                        
                        <div class="appointment-customer">
                            <h3><?php echo htmlspecialchars($appointment['customer_name']); ?></h3>
                            <p>Phone: <?php echo htmlspecialchars($appointment['phone']); ?></p>
                            <p>Address: <?php echo htmlspecialchars($appointment['address']); ?></p>
                        </div>
                        
                        <div class="appointment-description">
                            <h4>Service Description:</h4>
                            <p><?php echo htmlspecialchars($appointment['description']); ?></p>
                        </div>
                        
                        <div class="appointment-actions">
                            <p><strong>Price Quoted: $<?php echo number_format($appointment['technician_price'], 2); ?></strong></p>
                            <p>Waiting for customer confirmation...</p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No appointments with price quotes pending customer confirmation.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Counter-Offered Appointments Tab -->
        <div class="tab-content" id="counter_offered">
            <h2 class="section-title">Counter-Offered Quotes</h2>
            
            <?php if ($counter_offered_appointments->num_rows > 0): ?>
                <?php while ($appointment = $counter_offered_appointments->fetch_assoc()): ?>
                    <div class="appointment-card">
                        <div class="appointment-header">
                            <div class="appointment-date">
                                <?php 
                                    echo date('F j, Y', strtotime($appointment['date'])) . ' at ';
                                    echo date('g:i A', strtotime($appointment['time']));
                                ?>
                            </div>
                            <span>Request ID: #<?php echo $appointment['ID']; ?></span>
                        </div>
                        
                        <div class="appointment-customer">
                            <h3><?php echo htmlspecialchars($appointment['customer_name']); ?></h3>
                            <p>Phone: <?php echo htmlspecialchars($appointment['phone']); ?></p>
                            <p>Address: <?php echo htmlspecialchars($appointment['address']); ?></p>
                        </div>
                        
                        <div class="appointment-description">
                            <h4>Service Description:</h4>
                            <p><?php echo htmlspecialchars($appointment['description']); ?></p>
                        </div>
                        
                        <div class="appointment-actions">
                            <p><strong>Your Original Price: $<?php echo number_format($appointment['technician_price'], 2); ?></strong></p>
                            <p><strong>Customer Counter Offer: $<?php echo number_format($appointment['customer_price'], 2); ?></strong></p>
                            <div style="display: flex; gap: 10px; margin-top: 10px;">
                                <a href="accept_counter_offer.php?id=<?php echo $appointment['ID']; ?>" class="btn">Accept Counter Offer</a>
                                <a href="reject_counter_offer.php?id=<?php echo $appointment['ID']; ?>" class="btn" style="background-color: #d9534f;">Reject Counter Offer</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No counter-offered quotes pending customer confirmation.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Confirmed Appointments Tab -->
        <div class="tab-content" id="confirmed">
            <h2 class="section-title">Confirmed Appointments</h2>
            
            <?php if ($confirmed_appointments->num_rows > 0): ?>
                <?php while ($appointment = $confirmed_appointments->fetch_assoc()): ?>
                    <div class="appointment-card">
                        <div class="appointment-header">
                            <div class="appointment-date">
                                <?php 
                                    echo date('F j, Y', strtotime($appointment['date'])) . ' at ';
                                    echo date('g:i A', strtotime($appointment['time']));
                                ?>
                            </div>
                            <span>Appointment ID: #<?php echo $appointment['ID']; ?></span>
                        </div>
                        
                        <div class="appointment-customer">
                            <h3><?php echo htmlspecialchars($appointment['customer_name']); ?></h3>
                            <p>Phone: <?php echo htmlspecialchars($appointment['phone']); ?></p>
                            <p>Address: <?php echo htmlspecialchars($appointment['address']); ?></p>
                        </div>
                        
                        <div class="appointment-description">
                            <h4>Service Description:</h4>
                            <p><?php echo htmlspecialchars($appointment['description']); ?></p>
                        </div>
                        
                        <div class="appointment-actions">
                            <p><strong>Agreed Price: $<?php echo number_format($appointment['final_price'], 2); ?></strong></p>
                            <a href="complete_appointment.php?id=<?php echo $appointment['ID']; ?>" class="btn">Mark as Completed</a>
                        </div>
                    </div>
                    <?php 
                        $appointment_id = $appointment['ID'];
                        include('chat_interface.php'); 
                    ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No confirmed appointments.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Completed Appointments Tab -->
        <div class="tab-content" id="completed">
            <h2 class="section-title">Appointment History</h2>
            
            <?php if ($completed_appointments->num_rows > 0): ?>
                <?php while ($appointment = $completed_appointments->fetch_assoc()): ?>
                    <div class="appointment-card">
                        <div class="appointment-header">
                            <div class="appointment-date">
                                <?php 
                                    echo date('F j, Y', strtotime($appointment['date'])) . ' at ';
                                    echo date('g:i A', strtotime($appointment['time']));
                                ?>
                            </div>
                            <span>Appointment ID: #<?php echo $appointment['ID']; ?></span>
                        </div>
                        
                        <div class="appointment-customer">
                            <h3><?php echo htmlspecialchars($appointment['customer_name']); ?></h3>
                            <p>Phone: <?php echo htmlspecialchars($appointment['phone']); ?></p>
                            <p>Address: <?php echo htmlspecialchars($appointment['address']); ?></p>
                        </div>
                        
                        <div class="appointment-description">
                            <h4>Service Description:</h4>
                            <p><?php echo htmlspecialchars($appointment['description']); ?></p>
                        </div>
                        
                        <div class="appointment-actions">
                            <p><strong>Price: $<?php echo number_format($appointment['final_price'], 2); ?></strong></p>
                            <p>Completed</p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No completed appointments yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quote Modal -->
    <div id="quoteModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Set Price for Service</h2>
            <form id="quoteForm" method="POST" action="set_price.php">
                <input type="hidden" id="appointmentId" name="appointment_id">
                
                <label for="price">Price ($):</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required>
                
                <button type="submit" class="btn">Submit Price Quote</button>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab navigation
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs and content
                    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    document.getElementById(this.dataset.tab).classList.add('active');
                });
            });
            
            // Quote buttons
            const quoteBtns = document.querySelectorAll('.quote-btn');
            quoteBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const appointmentId = this.dataset.id;
                    document.getElementById('appointmentId').value = appointmentId;
                    document.getElementById('quoteModal').style.display = 'flex';
                });
            });
            
            // Close modal when clicking X
            document.querySelectorAll('.close').forEach(closeBtn => {
                closeBtn.addEventListener('click', function() {
                    this.closest('.modal').style.display = 'none';
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 