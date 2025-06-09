<?php
// admin/auth_check.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: login.php?error=2"); // error=2 means "Please login to access"
    exit;
}
?>
