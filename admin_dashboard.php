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

// Get admin information
$admin_name = $_SESSION['user_name'];

// Get all appointments
$appointments_query = "SELECT a.*, c.name as customer_name, t.name as technician_name, t.technician_type 
                      FROM Appointment a 
                      JOIN Customer c ON a.Customer_ID = c.ID 
                      JOIN Technician t ON a.Technician_ID = t.ID 
                      ORDER BY a.date DESC, a.time DESC";
$appointments = $conn->query($appointments_query);

// Get all technicians
$technicians_query = "SELECT t.*, 
                      (SELECT AVG(rating) FROM Review WHERE Technician_ID = t.ID) as avg_rating,
                      (SELECT COUNT(*) FROM Review WHERE Technician_ID = t.ID) as review_count,
                      (SELECT COUNT(*) FROM Appointment WHERE Technician_ID = t.ID AND status = 'completed') as completed_jobs
                      FROM Technician t
                      ORDER BY t.name";
$technicians = $conn->query($technicians_query);

// Get all customers
$customers_query = "SELECT c.*, 
                   (SELECT COUNT(*) FROM Appointment WHERE Customer_ID = c.ID) as appointment_count 
                   FROM Customer c
                   ORDER BY c.name";
$customers = $conn->query($customers_query);

// Get all reviews
$reviews_query = "SELECT r.*, c.name as customer_name, t.name as technician_name, t.technician_type 
                 FROM Review r 
                 JOIN Customer c ON r.Customer_ID = c.ID 
                 JOIN Technician t ON r.Technician_ID = t.ID 
                 ORDER BY r.review_date DESC";
$reviews = $conn->query($reviews_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --purple: #3e1c61;
            --gold: #b88a00;
            --gold-hover: #a37b00;
            --red: #d9534f;
            --red-hover: #c9302c;
            --green: #5cb85c;
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
        
        .btn-danger {
            background: var(--red);
        }
        
        .btn-danger:hover {
            background: var(--red-hover);
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
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table, th, td {
            border: 1px solid #ddd;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
        }
        
        th {
            background-color: var(--purple);
            color: white;
        }
        
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .status-pending {
            color: orange;
        }
        
        .status-price_quoted {
            color: blue;
        }
        
        .status-counter_offered {
            color: purple;
        }
        
        .status-confirmed {
            color: green;
        }
        
        .status-cancelled {
            color: red;
        }
        
        .status-completed {
            color: var(--purple);
        }
        
        .search-box {
            padding: 10px;
            margin-bottom: 20px;
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .rating {
            color: var(--gold);
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
        
        .section-title {
            color: var(--purple);
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            display: inline-block;
            width: calc(25% - 20px);
            margin-right: 20px;
            text-align: center;
        }
        
        .stat-card h3 {
            color: var(--purple);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--gold);
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
        <a href="logout.php" class="btn">Logout</a>
    </header>
    
    <div class="container">
        <!-- Stats Overview -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total Appointments</h3>
                <div class="stat-number"><?php echo $appointments->num_rows; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Technicians</h3>
                <div class="stat-number"><?php echo $technicians->num_rows; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Customers</h3>
                <div class="stat-number"><?php echo $customers->num_rows; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Reviews</h3>
                <div class="stat-number"><?php echo $reviews->num_rows; ?></div>
            </div>
        </div>
        
        <div class="tabs">
            <div class="tab active" data-tab="appointments">All Appointments</div>
            <div class="tab" data-tab="technicians">Manage Technicians</div>
            <div class="tab" data-tab="customers">Manage Customers</div>
            <div class="tab" data-tab="reviews">All Reviews</div>
        </div>
        
        <!-- All Appointments Tab -->
        <div class="tab-content active" id="appointments">
            <h2 class="section-title">Appointment Management</h2>
            <input type="text" id="appointmentSearch" class="search-box" placeholder="Search appointments...">
            
            <div class="card">
                <table id="appointmentTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Technician</th>
                            <th>Service Type</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($appointments->num_rows > 0): ?>
                            <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $appointment['ID']; ?></td>
                                    <td><?php echo htmlspecialchars($appointment['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['technician_name']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['technician_type']); ?></td>
                                    <td>
                                        <?php 
                                            echo date('F j, Y', strtotime($appointment['date'])) . ' at ';
                                            echo date('g:i A', strtotime($appointment['time']));
                                        ?>
                                    </td>
                                    <td class="status-<?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $appointment['status'])); ?>
                                    </td>
                                    <td>
                                        <?php 
                                            if ($appointment['final_price']) {
                                                echo '$' . number_format($appointment['final_price'], 2); 
                                            } elseif ($appointment['technician_price']) {
                                                echo '$' . number_format($appointment['technician_price'], 2) . ' (quoted)'; 
                                            } elseif ($appointment['customer_price']) {
                                                echo '$' . number_format($appointment['customer_price'], 2) . ' (counter)'; 
                                            } else {
                                                echo 'Not set';
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">No appointments found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Manage Technicians Tab -->
        <div class="tab-content" id="technicians">
            <h2 class="section-title">Technician Management</h2>
            <input type="text" id="technicianSearch" class="search-box" placeholder="Search technicians...">
            
            <div class="card">
                <table id="technicianTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Rating</th>
                            <th>Jobs</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($technicians->num_rows > 0): ?>
                            <?php while ($technician = $technicians->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $technician['ID']; ?></td>
                                    <td><?php echo htmlspecialchars($technician['name']); ?></td>
                                    <td><?php echo htmlspecialchars($technician['email']); ?></td>
                                    <td><?php echo htmlspecialchars($technician['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($technician['technician_type']); ?></td>
                                    <td class="rating">
                                        <?php 
                                            $rating = round($technician['avg_rating'] ?? 0, 1);
                                            echo $rating . ' ★ (' . $technician['review_count'] . ' reviews)';
                                        ?>
                                    </td>
                                    <td><?php echo $technician['completed_jobs']; ?> completed</td>
                                    <td>
                                        <a href="remove_technician.php?id=<?php echo $technician['ID']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to remove this technician?')">Remove</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">No technicians found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Manage Customers Tab -->
        <div class="tab-content" id="customers">
            <h2 class="section-title">Customer Management</h2>
            <input type="text" id="customerSearch" class="search-box" placeholder="Search customers...">
            
            <div class="card">
                <table id="customerTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Appointments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($customers->num_rows > 0): ?>
                            <?php while ($customer = $customers->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $customer['ID']; ?></td>
                                    <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['address']); ?></td>
                                    <td><?php echo $customer['appointment_count']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No customers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- All Reviews Tab -->
        <div class="tab-content" id="reviews">
            <h2 class="section-title">Review Management</h2>
            <input type="text" id="reviewSearch" class="search-box" placeholder="Search reviews...">
            
            <div class="card">
                <table id="reviewTable">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Technician</th>
                            <th>Service Type</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($reviews->num_rows > 0): ?>
                            <?php while ($review = $reviews->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($review['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($review['technician_name']); ?></td>
                                    <td><?php echo htmlspecialchars($review['technician_type']); ?></td>
                                    <td class="rating">
                                        <?php 
                                            $stars = str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']);
                                            echo $stars;
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($review['comment']); ?></td>
                                    <td><?php echo date('F j, Y', strtotime($review['review_date'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No reviews found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
            
            // Table search functionality
            function setupTableSearch(inputId, tableId) {
                const input = document.getElementById(inputId);
                const table = document.getElementById(tableId);
                
                if (input && table) {
                    input.addEventListener('keyup', function() {
                        const filter = this.value.toLowerCase();
                        const rows = table.getElementsByTagName('tr');
                        
                        for (let i = 1; i < rows.length; i++) {
                            let found = false;
                            const cells = rows[i].getElementsByTagName('td');
                            
                            for (let j = 0; j < cells.length; j++) {
                                const cellText = cells[j].textContent || cells[j].innerText;
                                
                                if (cellText.toLowerCase().indexOf(filter) > -1) {
                                    found = true;
                                    break;
                                }
                            }
                            
                            rows[i].style.display = found ? '' : 'none';
                        }
                    });
                }
            }
            
            // Setup search for each table
            setupTableSearch('appointmentSearch', 'appointmentTable');
            setupTableSearch('technicianSearch', 'technicianTable');
            setupTableSearch('customerSearch', 'customerTable');
            setupTableSearch('reviewSearch', 'reviewTable');
        });
    </script>
</body>
</html> 