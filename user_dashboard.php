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

// Get user information
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get user's appointments
$stmt = $conn->prepare("SELECT a.*, t.name as technician_name, t.technician_type, t.phone 
                       FROM Appointment a 
                       JOIN Technician t ON a.Technician_ID = t.ID 
                       WHERE a.Customer_ID = ? 
                       ORDER BY a.date, a.time");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
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
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .sidebar ul {
            list-style: none;
        }
        
        .sidebar li {
            margin-bottom: 15px;
        }
        
        .sidebar a {
            color: var(--purple);
            text-decoration: none;
            font-weight: bold;
            display: block;
            padding: 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background: #f0f0f0;
        }
        
        .appointment-card {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 15px;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        
        .appointment-date {
            font-weight: bold;
            color: var(--purple);
        }
        
        .appointment-actions {
            display: flex;
            gap: 10px;
        }
        
        .search-container {
            margin: 20px 0;
        }
        
        .search-container input, .search-container select {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin-right: 10px;
        }
        
        .technician-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .technician-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            transition: transform 0.3s;
        }
        
        .technician-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .technician-info {
            margin-bottom: 15px;
        }
        
        .technician-info h3 {
            color: var(--purple);
        }
        
        .rating {
            color: var(--gold);
            margin: 10px 0;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .appointment-card {
                grid-template-columns: 1fr;
            }
        }

        .hidden {
            display: none;
        }

        #mainContent > div {
            display: none;
        }

        #mainContent > div.active {
            display: block;
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
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close {
            float: right;
            font-size: 1.5rem;
            cursor: pointer;
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
            color: purple;
        }
    </style>
</head>
<body>
    <header>
        <h1>Welcome, <?php echo htmlspecialchars($user_name); ?></h1>
        <div>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </header>
    
    <div class="container">
        <div class="dashboard-grid">
            <div class="sidebar">
                <ul>
                    <li><a href="#" class="nav-link active" data-target="servicesSection">Find Services</a></li>
                    <li><a href="#" class="nav-link" data-target="appointmentsSection">My Appointments</a></li>
                    <li><a href="#" class="nav-link" data-target="reviewsSection">My Reviews</a></li>
                </ul>
            </div>
            
            <div id="mainContent">
                <!-- Services Section -->
                <div id="servicesSection" class="active">
                    <h2>Find Services</h2>
                    <div class="search-container">
                        <select id="serviceType">
                            <option value="">Select Service Type</option>
                            <option value="plumber">Plumber</option>
                            <option value="electrician">Electrician</option>
                            <option value="carpenter">Carpenter</option>
                            <option value="cleaner">Cleaner</option>
                        </select>
                        <button class="btn" id="searchBtn">Search</button>
                    </div>
                    
                    <div id="techniciansContainer" class="technician-grid">
                        <!-- Technicians will be loaded here dynamically -->
                    </div>
                </div>
                
                <!-- Appointments Section -->
                <div id="appointmentsSection">
                    <h2>My Appointments</h2>
                    <div class="card">
                        <?php if ($appointments->num_rows > 0): ?>
                            <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                <div class="appointment-card">
                                    <div class="appointment-date">
                                        <?php 
                                            echo date('F j, Y', strtotime($appointment['date'])) . '<br>';
                                            echo date('g:i A', strtotime($appointment['time']));
                                        ?>
                                        <p class="status-<?php echo $appointment['status']; ?>">
                                            Status: <?php echo ucfirst(str_replace('_', ' ', $appointment['status'])); ?>
                                        </p>
                                    </div>
                                    <div class="appointment-info">
                                        <h3><?php echo htmlspecialchars($appointment['technician_name']); ?></h3>
                                        <p>Service: <?php echo htmlspecialchars($appointment['technician_type']); ?></p>
                                        <p>Phone: <?php echo htmlspecialchars($appointment['phone']); ?></p>
                                        <p>Description: <?php echo htmlspecialchars($appointment['description']); ?></p>
                                        <?php if ($appointment['technician_price']): ?>
                                            <p>Price Quoted: $<?php echo number_format($appointment['technician_price'], 2); ?></p>
                                        <?php endif; ?>
                                        <?php if ($appointment['customer_price']): ?>
                                            <p>Your Counter Offer: $<?php echo number_format($appointment['customer_price'], 2); ?></p>
                                        <?php endif; ?>
                                        <?php if ($appointment['final_price']): ?>
                                            <p>Final Price: $<?php echo number_format($appointment['final_price'], 2); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="appointment-actions">
                                        <?php if ($appointment['status'] == 'price_quoted'): ?>
                                            <a href="confirm_appointment.php?id=<?php echo $appointment['ID']; ?>" class="btn">Confirm</a>
                                            <a href="#" class="btn counter-offer-btn" data-id="<?php echo $appointment['ID']; ?>" style="background-color: #f0ad4e;">Counter Offer</a>
                                            <a href="cancel_appointment.php?id=<?php echo $appointment['ID']; ?>" class="btn" style="background-color: #d9534f;">Cancel</a>
                                        <?php elseif ($appointment['status'] == 'counter_offered'): ?>
                                            <p>Waiting for technician's response to your counter offer of $<?php echo number_format($appointment['customer_price'], 2); ?></p>
                                            <a href="cancel_appointment.php?id=<?php echo $appointment['ID']; ?>" class="btn" style="background-color: #d9534f;">Cancel</a>
                                        <?php elseif ($appointment['status'] == 'completed'): ?>
                                            <a href="#" class="btn review-btn" data-tech-id="<?php echo $appointment['Technician_ID']; ?>">Review</a>
                                        <?php elseif ($appointment['status'] == 'pending'): ?>
                                            <a href="cancel_appointment.php?id=<?php echo $appointment['ID']; ?>" class="btn" style="background-color: #d9534f;">Cancel</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($appointment['status'] == 'confirmed'): ?>
                                    <?php 
                                        $appointment_id = $appointment['ID'];
                                        include('chat_interface.php'); 
                                    ?>
                                <?php endif; ?>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>You don't have any appointments yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Reviews Section -->
                <div id="reviewsSection">
                    <h2>My Reviews</h2>
                    <div id="reviewsContainer">
                        <!-- Reviews will be loaded here dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Book Appointment</h2>
            <form id="bookingForm" method="POST" action="book_appointment.php">
                <input type="hidden" id="technicianId" name="technician_id">
                
                <div>
                    <label for="appointmentDate">Date:</label>
                    <input type="date" id="appointmentDate" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div>
                    <label for="appointmentTime">Time:</label>
                    <select id="appointmentTime" name="appointment_time" required>
                        <option value="">Select Time</option>
                        <?php 
                            // Generate time slots from 10 AM to 9 PM
                            for ($hour = 10; $hour <= 21; $hour++) {
                                $timeSlot = sprintf("%02d:00:00", $hour);
                                $displayTime = date("g:i A", strtotime($timeSlot));
                                echo "<option value=\"$timeSlot\">$displayTime</option>";
                            }
                        ?>
                    </select>
                </div>
                
                <div>
                    <label for="description">Description of Work:</label>
                    <textarea id="description" name="description" rows="5" required></textarea>
                </div>
                
                <button type="submit" class="btn">Request Appointment</button>
            </form>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Write a Review</h2>
            <form id="reviewForm" method="POST" action="submit_review.php">
                <input type="hidden" id="reviewTechnicianId" name="technician_id">
                
                <div>
                    <label>Rating:</label>
                    <div class="rating-selector">
                        <input type="radio" id="star5" name="rating" value="5" required>
                        <label for="star5">★★★★★</label>
                        <input type="radio" id="star4" name="rating" value="4">
                        <label for="star4">★★★★</label>
                        <input type="radio" id="star3" name="rating" value="3">
                        <label for="star3">★★★</label>
                        <input type="radio" id="star2" name="rating" value="2">
                        <label for="star2">★★</label>
                        <input type="radio" id="star1" name="rating" value="1">
                        <label for="star1">★</label>
                    </div>
                </div>
                
                <div>
                    <label for="reviewComment">Comment:</label>
                    <textarea id="reviewComment" name="comment" rows="4" required></textarea>
                </div>
                
                <button type="submit" class="btn">Submit Review</button>
            </form>
        </div>
    </div>

    <!-- Counter Offer Modal -->
    <div id="counterOfferModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Make a Counter Offer</h2>
            <form id="counterOfferForm" method="POST" action="counter_offer.php">
                <input type="hidden" id="appointmentId" name="appointment_id">
                
                <div>
                    <label for="counterPrice">Your Counter Price ($):</label>
                    <input type="number" id="counterPrice" name="counter_price" step="0.01" min="0" required>
                </div>
                
                <button type="submit" class="btn">Submit Counter Offer</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navigation
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all links and sections
                    navLinks.forEach(l => l.classList.remove('active'));
                    document.querySelectorAll('#mainContent > div').forEach(div => div.classList.remove('active'));
                    
                    // Add active class to clicked link and corresponding section
                    this.classList.add('active');
                    document.getElementById(this.dataset.target).classList.add('active');
                });
            });
            
            // Search for technicians
            document.getElementById('searchBtn').addEventListener('click', function() {
                const serviceType = document.getElementById('serviceType').value;
                if (!serviceType) {
                    alert('Please select a service type');
                    return;
                }
                
                fetch('get_technicians.php?type=' + serviceType)
                    .then(response => response.json())
                    .then(data => {
                        const container = document.getElementById('techniciansContainer');
                        container.innerHTML = '';
                        
                        if (data.length === 0) {
                            container.innerHTML = '<p>No technicians found for this service.</p>';
                            return;
                        }
                        
                        data.forEach(tech => {
                            const techCard = document.createElement('div');
                            techCard.className = 'technician-card';
                            
                            const stars = '★'.repeat(tech.avg_rating || 0) + '☆'.repeat(5 - (tech.avg_rating || 0));
                            
                            techCard.innerHTML = `
                                <div class="technician-info">
                                    <h3>${tech.name}</h3>
                                    <p>Type: ${tech.technician_type}</p>
                                    <p>Phone: ${tech.phone}</p>
                                    <div class="rating">${stars} (${tech.review_count || 0} reviews)</div>
                                </div>
                                <div class="technician-actions">
                                    <button class="btn book-btn" data-id="${tech.ID}">Book</button>
                                    <button class="btn view-reviews-btn" data-id="${tech.ID}">View Reviews</button>
                                </div>
                            `;
                            
                            container.appendChild(techCard);
                        });
                        
                        // Add event listeners to new buttons
                        document.querySelectorAll('.book-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const techId = this.dataset.id;
                                document.getElementById('technicianId').value = techId;
                                document.getElementById('bookingModal').style.display = 'flex';
                            });
                        });
                        
                        document.querySelectorAll('.view-reviews-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const techId = this.dataset.id;
                                loadTechnicianReviews(techId);
                            });
                        });
                    })
                    .catch(error => console.error('Error:', error));
            });
            
            // Close modals when clicking on X or outside of modal
            document.querySelectorAll('.close').forEach(closeBtn => {
                closeBtn.addEventListener('click', function() {
                    this.closest('.modal').style.display = 'none';
                });
            });
            
            window.addEventListener('click', function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            });
            
            // Review button click handler
            document.querySelectorAll('.review-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const techId = this.dataset.techId;
                    document.getElementById('reviewTechnicianId').value = techId;
                    document.getElementById('reviewModal').style.display = 'flex';
                });
            });
            
            // Load user reviews
            function loadUserReviews() {
                fetch('get_user_reviews.php')
                    .then(response => response.json())
                    .then(data => {
                        const container = document.getElementById('reviewsContainer');
                        container.innerHTML = '';
                        
                        if (data.length === 0) {
                            container.innerHTML = '<p>You have not written any reviews yet.</p>';
                            return;
                        }
                        
                        data.forEach(review => {
                            const reviewCard = document.createElement('div');
                            reviewCard.className = 'card';
                            
                            const stars = '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
                            
                            reviewCard.innerHTML = `
                                <h3>${review.technician_name}</h3>
                                <div class="rating">${stars}</div>
                                <p>${review.comment}</p>
                                <small>Posted on ${new Date(review.review_date).toLocaleDateString()}</small>
                            `;
                            
                            container.appendChild(reviewCard);
                        });
                    })
                    .catch(error => console.error('Error:', error));
            }
            
            // Load technician reviews
            function loadTechnicianReviews(techId) {
                fetch('get_technician_reviews.php?id=' + techId)
                    .then(response => response.json())
                    .then(data => {
                        // Navigate to reviews section
                        navLinks.forEach(l => l.classList.remove('active'));
                        document.querySelectorAll('#mainContent > div').forEach(div => div.classList.remove('active'));
                        document.querySelector('[data-target="reviewsSection"]').classList.add('active');
                        document.getElementById('reviewsSection').classList.add('active');
                        
                        const container = document.getElementById('reviewsContainer');
                        container.innerHTML = `<h3>Reviews for ${data.technician_name || 'Technician'}</h3>`;
                        
                        if (data.reviews.length === 0) {
                            container.innerHTML += '<p>No reviews yet for this technician.</p>';
                            return;
                        }
                        
                        data.reviews.forEach(review => {
                            const reviewCard = document.createElement('div');
                            reviewCard.className = 'card';
                            
                            const stars = '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
                            
                            reviewCard.innerHTML = `
                                <div class="rating">${stars}</div>
                                <p>${review.comment}</p>
                                <small>Posted by ${review.customer_name} on ${new Date(review.review_date).toLocaleDateString()}</small>
                            `;
                            
                            container.appendChild(reviewCard);
                        });
                    })
                    .catch(error => console.error('Error:', error));
            }
            
            // Load user reviews when reviews section is opened
            document.querySelector('[data-target="reviewsSection"]').addEventListener('click', loadUserReviews);
            
            // Counter Offer button click handler
            document.querySelectorAll('.counter-offer-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const appointmentId = this.dataset.id;
                    document.getElementById('appointmentId').value = appointmentId;
                    document.getElementById('counterOfferModal').style.display = 'flex';
                });
            });
        });
    </script>
</body>
</html> 