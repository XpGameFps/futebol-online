<?php

$cookie_params = [
    'lifetime' => 0,     'path' => '/',     'domain' => '',     'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',     'httponly' => true,     'samesite' => 'Lax' ];
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

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: login.php?error=2");     exit;
}

require_once 'csrf_utils.php';
?>

