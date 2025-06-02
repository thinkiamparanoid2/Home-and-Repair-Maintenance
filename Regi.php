<?php
// Include database connection
include('Connection_DB.php');

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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    // Debugging: Check if form data is being received correctly
    var_dump($_POST);
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $address = $_POST['address']; // Capture the address
    $passwordHash = password_hash($password, PASSWORD_DEFAULT); // Hash password for security
    $isadmin = ($_POST['user_type'] == 'technician') ? 0 : 1; // Assuming technician is not admin
    $technician_type = isset($_POST['technician_type']) ? $_POST['technician_type'] : null; // Capture technician type

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
        // Check for existing email or phone in Technician table
        $check_stmt = $conn->prepare("SELECT * FROM Technician WHERE email = ? OR phone = ?");
        $check_stmt->bind_param("ss", $email, $phone);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $register_error = "A user with this email or phone number already exists.";
        } else {
            // Insert into Technician table
            $sql_technician = "INSERT INTO Technician (name, email, phone, password, address, technician_type) 
                             VALUES ('$name', '$email', '$phone', '$passwordHash', '$address', '$technician_type')";

            if ($conn->query($sql_technician) === TRUE) {
                $register_success = "Technician registration successful!";
                
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
                $register_error = "Technician Error: " . $conn->error;
            }
        }
        $check_stmt->close();
    } else {
        // Check for existing email or phone in Customer table
        $check_stmt = $conn->prepare("SELECT * FROM Customer WHERE email = ? OR phone = ?");
        $check_stmt->bind_param("ss", $email, $phone);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $register_error = "A user with this email or phone number already exists.";
        } else {
            // Insert into Customer table
            $sql_customer = "INSERT INTO Customer (name, email, phone, password, isadmin, address) 
                           VALUES ('$name', '$email', '$phone', '$passwordHash', '$isadmin', '$address')";
            
            if ($conn->query($sql_customer) === TRUE) {
                $register_success = "Customer registration successful!";
                
                // Start session and log in the user
                session_start();
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['is_admin'] = $isadmin;
                
                // Redirect to user dashboard
                header("Location: user_dashboard.php");
                exit;
            } else {
                $register_error = "Customer Error: " . $conn->error;
            }
        }
        $check_stmt->close();
    }
}
?>

<!-- HTML form for registration -->
<form method="POST" action="">
    <input type="text" name="name" placeholder="Full Name" required value="<?php echo htmlspecialchars($form_data['name']); ?>">
    <input type="tel" name="phone" placeholder="Phone Number" required value="<?php echo htmlspecialchars($form_data['phone']); ?>">
    <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($form_data['email']); ?>">
    <input id="password" type="password" minlength="5" maxlength="14" placeholder="Password (5–14 chars)" required oninput="validatePassword(this.value)">
    <div id="passwordStrength"></div>

    <!-- Address field -->
    <input type="text" name="address" placeholder="Address" required value="<?php echo htmlspecialchars($form_data['address']); ?>">

    <!-- User type selection -->
    <select id="userType" name="user_type" onchange="toggleTechnicianType()" required>
        <option value="">Select User Type</option>
        <option value="customer" <?php echo ($form_data['user_type'] == 'customer') ? 'selected' : ''; ?>>Customer</option>
        <option value="technician" <?php echo ($form_data['user_type'] == 'technician') ? 'selected' : ''; ?>>Technician</option>
    </select>

    <!-- Technician Type selection (hidden initially) -->
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

<script>
function toggleTechnicianType() {
    const userType = document.getElementById('userType').value;
    const techContainer = document.getElementById('technicianTypeContainer');
    const technicianSelect = document.getElementById('technicianType');
    
    if (userType === 'technician') {
        techContainer.style.display = 'block';
        technicianSelect.setAttribute('required', 'required');
    } else {
        techContainer.style.display = 'none';
        technicianSelect.removeAttribute('required');
    }
}
</script>
