<?php
// admin/process_login.php

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
} else {
    session_set_cookie_params($cookie_params);
}

session_start();
require_once '../config.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["username"])) || empty(trim($_POST["password"]))) {
        header("Location: login.php?error=1"); // Or a more specific error
        exit;
    }

    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    try {
        $sql = "SELECT id, username, password_hash, is_superadmin FROM admins WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":username", $username, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $admin = $stmt->fetch();
            if (password_verify($password, $admin['password_hash'])) {
                // Password is correct, start new session
                $_SESSION['admin_loggedin'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_is_superadmin'] = !empty($admin['is_superadmin']); // Converte para boolean

                // ADD SESSION REGENERATION HERE
                session_regenerate_id(true); // Destroy old session ID and create a new one

                // Redirect to admin dashboard
                header("Location: index.php");
                exit;
            } else {
                // Password not valid
                header("Location: login.php?error=1"); // Invalid credentials
                exit;
            }
        } else {
            // Username not found
            header("Location: login.php?error=1"); // Invalid credentials
            exit;
        }
    } catch (PDOException $e) {
        // error_log("Login PDOException: " . $e->getMessage());
        // For security, don't reveal too much, generic error is fine for login
        header("Location: login.php?error=1&dberr"); // Generic error
        exit;
    }
} else {
    // Not a POST request, redirect to login page
    header("Location: login.php");
    exit;
}
?>
