<?php
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
