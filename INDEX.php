<?php
// Include database connection
include('Connection_DB.php');

// Initialize session
session_start();

// Store login error in a variable and clear from session
$login_error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : null;
if (isset($_SESSION['login_error'])) {
    unset($_SESSION['login_error']);
}

// Initialize variables to store form data in case of error
$form_data = array(
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'user_type' => '',
    'technician_type' => ''
);

// Initialize error and success messages
$register_error = '';
$register_success = '';

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    // Check if password is set in POST
    if (isset($_POST['password'])) {
        // Get form data
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Check if the login is for the admin
        if ($email === 'admin@brac.bd' && $password === '370') {
            // Admin login logic
            $_SESSION['user_id'] = 'admin';
            $_SESSION['user_name'] = 'Admin';
            $_SESSION['is_admin'] = true;
            header("Location: admin_dashboard.php");
            exit;
        } else {
            // First check if it's a customer
            $stmt = $conn->prepare("SELECT * FROM Customer WHERE email=?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if (password_verify($password, $row['password'])) {
                    $_SESSION['user_id'] = $row['ID'];
                    $_SESSION['user_name'] = $row['name'];
                    $_SESSION['is_admin'] = $row['isadmin'];
                    header("Location: user_dashboard.php");
                    exit;
                } else {
                    $_SESSION['login_error'] = "Invalid credentials. Please try again.";
                    header("Location: INDEX.php");
                    exit;
                }
            } else {
                // Then check if it's a technician
                $stmt = $conn->prepare("SELECT * FROM Technician WHERE email=?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    if (password_verify($password, $row['password'])) {
                        $_SESSION['user_id'] = $row['ID'];
                        $_SESSION['user_name'] = $row['name'];
                        $_SESSION['is_admin'] = false; // Technicians are not admins
                        $_SESSION['is_technician'] = true; // Mark as technician
                        header("Location: technician_dashboard.php");
                        exit;
                    } else {
                        $_SESSION['login_error'] = "Invalid credentials. Please try again.";
                        header("Location: INDEX.php");
                        exit;
                    }
                } else {
                    $_SESSION['login_error'] = "Invalid credentials. Please try again.";
                    header("Location: INDEX.php");
                    exit;
                }
            }
        }
    } else {
        $_SESSION['login_error'] = "Password is missing.";
        header("Location: INDEX.php");
        exit;
    }
}

// Handle registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    // Debug log
    $log_file = fopen("register_debug.log", "a");
    fwrite($log_file, "=== Registration attempt " . date('Y-m-d H:i:s') . " ===\n");
    fwrite($log_file, "POST data: " . print_r($_POST, true) . "\n");
    
    // Get form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $address = $_POST['address'];
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $isadmin = ($_POST['user_type'] == 'technician') ? 0 : 1;
    $technician_type = isset($_POST['technician_type']) ? $_POST['technician_type'] : null;

    // Store form data in case we need to redisplay the form
    $form_data = array(
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'user_type' => $_POST['user_type'],
        'technician_type' => $technician_type
    );

    // Check if user is technician or customer
    if ($_POST['user_type'] == 'technician') {
        fwrite($log_file, "Processing as technician\n");
        
        try {
            // Check for existing email or phone in Technician table
            $check_stmt = $conn->prepare("SELECT * FROM Technician WHERE email = ? OR phone = ?");
            $check_stmt->bind_param("ss", $email, $phone);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $register_error = "A user with this email or phone number already exists.";
                fwrite($log_file, "Registration failed: User already exists\n");
            } else {
                // Insert into Technician table using prepared statement
                $stmt = $conn->prepare("INSERT INTO Technician (name, email, phone, password, address, technician_type) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt === false) {
                    fwrite($log_file, "Prepare failed: (" . $conn->errno . ") " . $conn->error . "\n");
                    $register_error = "Database error: " . $conn->error;
                } else {
                    $stmt->bind_param("ssssss", $name, $email, $phone, $passwordHash, $address, $technician_type);
                    
                    if ($stmt->execute()) {
                        $register_success = "Technician registration successful!";
                        fwrite($log_file, "Technician registration successful! ID: " . $conn->insert_id . "\n");
                        
                        // Start session and log in the user
                        session_start();
                        $_SESSION['user_id'] = $conn->insert_id;
                        $_SESSION['user_name'] = $name;
                        $_SESSION['is_admin'] = false;
                        $_SESSION['is_technician'] = true;
                        
                        // Redirect to technician dashboard
                        header("Location: technician_dashboard.php");
                        exit;
                    } else {
                        $register_error = "Error: " . $stmt->error;
                        fwrite($log_file, "Technician registration failed: " . $stmt->error . "\n");
                    }
                    $stmt->close();
                }
            }
            $check_stmt->close();
        } catch (Exception $e) {
            fwrite($log_file, "Exception: " . $e->getMessage() . "\n");
            $register_error = "Exception: " . $e->getMessage();
        }
    } else {
        fwrite($log_file, "Processing as customer\n");
        
        try {
            // Check for existing email or phone in Customer table
            $check_stmt = $conn->prepare("SELECT * FROM Customer WHERE email = ? OR phone = ?");
            $check_stmt->bind_param("ss", $email, $phone);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $register_error = "A user with this email or phone number already exists.";
                fwrite($log_file, "Registration failed: User already exists\n");
            } else {
                // Insert into Customer table using prepared statement
                $stmt = $conn->prepare("INSERT INTO Customer (name, email, phone, password, isadmin, address) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt === false) {
                    fwrite($log_file, "Prepare failed: (" . $conn->errno . ") " . $conn->error . "\n");
                    $register_error = "Database error: " . $conn->error;
                } else {
                    $stmt->bind_param("ssssis", $name, $email, $phone, $passwordHash, $isadmin, $address);
                    
                    if ($stmt->execute()) {
                        $register_success = "Customer registration successful!";
                        fwrite($log_file, "Customer registration successful! ID: " . $conn->insert_id . "\n");
                        
                        // Start session and log in the user
                        session_start();
                        $_SESSION['user_id'] = $conn->insert_id;
                        $_SESSION['user_name'] = $name;
                        $_SESSION['is_admin'] = $isadmin;
                        
                        // Redirect to user dashboard
                        header("Location: user_dashboard.php");
                        exit;
                    } else {
                        $register_error = "Error: " . $stmt->error;
                        fwrite($log_file, "Customer registration failed: " . $stmt->error . "\n");
                    }
                    $stmt->close();
                }
            }
            $check_stmt->close();
        } catch (Exception $e) {
            fwrite($log_file, "Exception: " . $e->getMessage() . "\n");
            $register_error = "Exception: " . $e->getMessage();
        }
    }
    
    fwrite($log_file, "Registration process completed\n\n");
    fclose($log_file);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Home & Repair Maintenance</title>

  <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;700&display=swap" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

  <style>
    :root {
      --primary: #2c5282;
      --accent: #f59e0b;
      --accent-hover: #d97706;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Urbanist', sans-serif;
      background: url('https://images.unsplash.com/photo-1600585152220-90363fe7e115?auto=format&fit=crop&w=1600&q=80') no-repeat center center fixed;
      background-size: cover;
      color: white;
    }

    header {
      height: 100vh;
      background: rgba(0, 0, 0, 0.7);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 2rem;
    }

    header h1 {
      font-size: 3.5rem;
      color: var(--accent);
      margin-bottom: 1rem;
      text-shadow: 2px 2px 15px #000;
    }

    header p {
      font-size: 1.4rem;
      max-width: 700px;
      margin-bottom: 2rem;
    }

    .btn {
      background: var(--accent);
      color: white;
      padding: 14px 30px;
      border: none;
      border-radius: 10px;
      font-size: 1.1rem;
      margin: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .btn:hover {
      background: var(--accent-hover);
      transform: translateY(-3px);
    }

    section.services {
      background-color: rgba(0, 0, 0, 0.85);
      padding: 70px 20px;
      text-align: center;
    }

    .services h2 {
      font-size: 2.8rem;
      color: var(--accent);
      margin-bottom: 50px;
    }

    .service-list {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 40px;
    }

    .service-item {
      background-color: rgba(44, 82, 130, 0.2);
      border: 1px solid var(--accent);
      padding: 25px;
      border-radius: 16px;
      width: 240px;
      transition: transform 0.4s ease, background 0.4s ease;
      backdrop-filter: blur(10px);
      font-size: 1.1rem;
    }

    .service-item:hover {
      transform: scale(1.05);
      background-color: rgba(44, 82, 130, 0.4);
    }

    .backdrop {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.6);
      z-index: 5;
      display: none;
    }

    .backdrop.active {
      display: block;
    }

    .modal {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) scale(0.6);
      width: 370px;
      background: #fff;
      color: #333;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 0 20px rgba(0,0,0,0.5);
      opacity: 0;
      visibility: hidden;
      transition: all 0.4s ease;
      z-index: 10;
    }

    .modal.active {
      opacity: 1;
      visibility: visible;
      transform: translate(-50%, -50%) scale(1);
    }

    .modal h2 {
      color: var(--primary);
      margin-bottom: 15px;
    }

    .modal input, .modal select {
      width: 100%;
      padding: 12px;
      margin: 12px 0;
      border: 1px solid #ccc;
      border-radius: 8px;
    }

    .modal .btn {
      width: 100%;
      margin-top: 10px;
    }

    .close {
      background: crimson;
      color: white;
      padding: 5px 12px;
      float: right;
      border-radius: 8px;
      cursor: pointer;
    }

    #passwordStrength {
      font-size: 0.9rem;
      margin-top: -8px;
      margin-bottom: 12px;
    }

    .weak { color: red; }
    .medium { color: orange; }
    .strong { color: green; }

    .password-container {
      position: relative;
      width: 100%;
    }

    .password-container input {
      width: 100%;
    }

    .password-toggle {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      font-size: 1.2rem;
    }

    .error-message {
      color: #d9534f;
      margin-top: 10px;
      animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
    }

    .success-message {
      color: #5cb85c;
      margin-top: 10px;
      animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes shake {
      10%, 90% {
        transform: translate3d(-1px, 0, 0);
      }
      
      20%, 80% {
        transform: translate3d(2px, 0, 0);
      }

      30%, 50%, 70% {
        transform: translate3d(-4px, 0, 0);
      }

      40%, 60% {
        transform: translate3d(4px, 0, 0);
      }
    }

    @media (max-width: 600px) {
      .service-list {
        flex-direction: column;
        align-items: center;
      }

      header h1 {
        font-size: 2.2rem;
      }

      .modal {
        width: 90%;
      }
    }
  </style>
</head>

<body>

  <header>
    <h1>Welcome to Home & Repair Maintenance</h1>
    <p>Your trusted platform to hire expert plumbers, electricians, carpenters and more — fast and reliable service for your home.</p>
    <button class="btn" onclick="showModal('login')">Login</button>
    <button class="btn" onclick="showModal('register')">Register</button>
  </header>

  <section class="services">
    <h2>Our Services</h2>
    <div class="service-list">
      <div class="service-item">🔧 Plumbing</div>
      <div class="service-item">💡 Electrical</div>
      <div class="service-item">🔨 Carpentry</div>
      <div class="service-item">🧹 Cleaning</div>
    </div>
  </section>

  <div id="backdrop" class="backdrop" onclick="closeModals()"></div>

  <!-- Login Modal -->
 <div id="loginModal" class="modal">
    <span class="close" onclick="closeModals()">X</span>
    <h2>Login</h2>
    <form method="POST" action="INDEX.php">
        <input type="email" name="email" placeholder="Email" required>
        <div class="password-container">
            <input type="password" name="password" id="loginPassword" placeholder="Password" required>
            <span class="password-toggle" onclick="toggleLoginPasswordVisibility()">👁</span>
        </div>
        <button class="btn" name="login">Login</button>
        <?php if (isset($login_error)): ?>
            <p class="error-message"><?php echo $login_error; ?></p>
        <?php endif; ?>
    </form>
</div>

<!-- Register Modal -->
<div id="registerModal" class="modal">
    <span class="close" onclick="closeModals()">X</span>
    <h2>Register</h2>
    <form method="POST" action="INDEX.php">
        <input type="text" name="name" placeholder="Full Name" required value="<?php echo htmlspecialchars($form_data['name']); ?>">
        <input type="tel" name="phone" placeholder="Phone Number" required value="<?php echo htmlspecialchars($form_data['phone']); ?>">
        <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($form_data['email']); ?>">
        <div class="password-container">
            <input id="password" type="password" name="password" minlength="5" maxlength="14" placeholder="Password (5–14 chars)" required oninput="validatePassword(this.value)">
            <span class="password-toggle" onclick="togglePasswordVisibility()">👁</span>
        </div>
        <div id="passwordStrength"></div>

        <input type="text" name="address" placeholder="Address" required value="<?php echo htmlspecialchars($form_data['address']); ?>">

        <select id="userType" name="user_type" onchange="toggleTechnicianType()" required>
            <option value="">Select User Type</option>
            <option value="customer" <?php echo ($form_data['user_type'] == 'customer') ? 'selected' : ''; ?>>Customer</option>
            <option value="technician" <?php echo ($form_data['user_type'] == 'technician') ? 'selected' : ''; ?>>Technician</option>
        </select>

<div id="technicianTypeContainer" style="display: <?php echo ($form_data['user_type'] == 'technician') ? 'block' : 'none'; ?>;">
    <select id="technicianType" name="technician_type" <?php echo ($form_data['user_type'] == 'technician') ? 'required' : ''; ?>>
        <option value="">Select Technician Type</option>
        <option value="plumber" <?php echo ($form_data['technician_type'] == 'plumber') ? 'selected' : ''; ?>>Plumber</option>
        <option value="electrician" <?php echo ($form_data['technician_type'] == 'electrician') ? 'selected' : ''; ?>>Electrician</option>
        <option value="carpenter" <?php echo ($form_data['technician_type'] == 'carpenter') ? 'selected' : ''; ?>>Carpenter</option>
        <option value="cleaner" <?php echo ($form_data['technician_type'] == 'cleaner') ? 'selected' : ''; ?>>Cleaner</option>
    </select>
</div>


        <button class="btn" name="register">Register</button>
        <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])): ?>
            <?php if ($register_success): ?>
                <p class="success-message"><?php echo $register_success; ?></p>
            <?php endif; ?>
            <?php if ($register_error): ?>
                <p class="error-message"><?php echo $register_error; ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </form>
</div>



  <script>
    // Function to show the modals
function showModal(type) {
    // Hide the backdrop and modal if they are already active
    document.getElementById('backdrop').classList.add('active');
    document.getElementById(type + 'Modal').classList.add('active');
}

// Function to close the modals
function closeModals() {
    document.getElementById('backdrop').classList.remove('active');
    document.getElementById('loginModal').classList.remove('active');
    document.getElementById('registerModal').classList.remove('active');
}

// Password validation for registration form
function validatePassword(password) {
    const strengthEl = document.getElementById("passwordStrength");
    const upper = /[A-Z]/.test(password);
    const num = /[0-9]/.test(password);
    const special = /[!@#$%^&*]/.test(password);

    const lengthValid = password.length >= 5 && password.length <= 14;
    let strength = 0;
    if (upper) strength++;
    if (num) strength++;
    if (special) strength++;

    if (!lengthValid) {
        strengthEl.textContent = "Password must be 5 to 14 characters long.";
        strengthEl.className = "weak";
        return;
    }

    if (strength === 3) {
        strengthEl.textContent = "Password strength: Strong";
        strengthEl.className = "strong";
    } else if (strength === 2) {
        strengthEl.textContent = "Password strength: Medium";
        strengthEl.className = "medium";
    } else {
        strengthEl.textContent = "Password strength: Weak";
        strengthEl.className = "weak";
    }
}
// Function to toggle technician type selection
function toggleTechnicianType() {
    const userType = document.getElementById('userType').value;
    const techContainer = document.getElementById('technicianTypeContainer');
    const technicianSelect = document.getElementById('technicianType');
    
    // Only display the technician type field if user selects "Technician"
    if (userType === 'technician') {
        techContainer.style.display = 'block'; // Show the technician type dropdown
        technicianSelect.setAttribute('required', 'required'); // Make it required when shown
    } else {
        techContainer.style.display = 'none'; // Hide it when not required
        technicianSelect.removeAttribute('required'); // No need to validate if not shown
    }
}

// Function to toggle password visibility
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
}

// Function to toggle login password visibility
function toggleLoginPasswordVisibility() {
    const loginPasswordInput = document.getElementById('loginPassword');
    const type = loginPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    loginPasswordInput.setAttribute('type', type);
}

// GSAP Animation for services section
gsap.registerPlugin(ScrollTrigger);
gsap.from('.service-item', {
    scrollTrigger: '.service-list',
    opacity: 0,
    y: 60,
    duration: 1,
    stagger: 0.2,
    ease: 'power3.out'
});

// Check if there's a login error and show the modal
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($login_error)): ?>
    showModal('login');
    <?php endif; ?>
    
    <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])): ?>
    showModal('register');
    <?php endif; ?>
});
</script>
</body>
</html>
