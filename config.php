<?php
// Database configuration
// These should be updated by the user in their cPanel environment.
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');

// Attempt to connect to MySQL database
try {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Turn on errors in the form of exceptions
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Make the default fetch be an associative array
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'     // Set charset to utf8mb4
    ];
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, $options);
} catch(PDOException $e){
    // Important: In a production environment, you might want to log this error
    // and show a more user-friendly message instead of die().
    die("ERRO: Não foi possível conectar ao banco de dados. " . $e->getMessage());
}

// The $pdo object can now be used by other scripts that include this file.
?>
