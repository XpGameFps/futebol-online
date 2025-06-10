<?php
// admin/auth_check.php

// Set secure session cookie parameters
// Ensure this is called BEFORE session_start()
$cookie_params = [
    'lifetime' => 0, // Expires when browser closes
    'path' => '/admin/', // Restrict cookie to /admin/ path
    'domain' => $_SERVER['HTTP_HOST'], // Current domain
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // Only send over HTTPS
    'httponly' => true, // Prevent JavaScript access to the session cookie
    'samesite' => 'Lax' // CSRF protection measure
];
// For PHP versions < 7.3, session_set_cookie_params must be called differently
if (PHP_VERSION_ID < 70300) {
    session_set_cookie_params(
        $cookie_params['lifetime'],
        $cookie_params['path'],
        $cookie_params['domain'],
        $cookie_params['secure'],
        $cookie_params['httponly']
    );
    // Note: SameSite is not available via this function in PHP < 7.3
    // It would need to be set via header() manually if critical and PHP < 7.3
    // header("Set-Cookie: SameSite=Lax", false); // Example, but complex due to other cookie attributes
} else {
    session_set_cookie_params($cookie_params);
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: login.php?error=2"); // error=2 means "Please login to access"
    exit;
}

require_once 'csrf_utils.php';
?>
