<?php
// Include database connection
include('Connection_DB.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = $_POST['email'];
    $password = $_POST['password'];




    // Check if the login is for the admin
    if ($email === 'rafsanrashid7@gmail.com' && $password === 'Rafsan1234') {
        // Start session for admin login
        session_start();
        $_SESSION['user_id'] = 'admin';  // Set user_id to 'admin' for admin login
        $_SESSION['user_name'] = 'Admin';
        $_SESSION['is_admin'] = true;

        echo "Admin Login successful";
    } else {
        // Check if the user exists in the database
        $sql = "SELECT * FROM Customer WHERE email='$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Verify password for regular users
            if (password_verify($password, $row['password'])) {
                // Start session and store user data for regular users
                session_start();
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['is_admin'] = $row['isadmin'];

                echo "Login successful";
            } else {
                echo "Invalid password";
            }
        } else {
            echo "User not found";
        }
    }
}
?>

<!-- HTML form for login -->
<form method="POST" action="login.php">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>
