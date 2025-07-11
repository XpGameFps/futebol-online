<?php
$cookie_params = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
];
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
require_once __DIR__ . '/../../FutOnline_config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["username"])) || empty(trim($_POST["password"]))) {
        header("Location: login.php?error=1");
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
                $_SESSION['admin_loggedin'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_is_superadmin'] = !empty($admin['is_superadmin']);
                header("Location: index.php");
                exit;
            } else {
                header("Location: login.php?error=1");
                exit;
            }
        } else {
            header("Location: login.php?error=1");
            exit;
        }
    } catch (PDOException $e) {
        header("Location: login.php?error=1&dberr");
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
?>

