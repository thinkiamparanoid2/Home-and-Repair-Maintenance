<?php
// Initialize session
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to homepage
header("Location: INDEX.php");
exit;
?> 