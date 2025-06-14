<?php
// Include the database configuration
require_once 'config.php';

// SQL statement to insert the new setting
$sql = "INSERT INTO site_settings (setting_key, setting_value) VALUES (:setting_key, :setting_value)";

try {
    // Prepare the statement
    $stmt = $pdo->prepare($sql);

    // Bind the parameters
    $setting_key = 'max_concurrent_users';
    $setting_value = '0';
    $stmt->bindParam(':setting_key', $setting_key);
    $stmt->bindParam(':setting_value', $setting_value);

    // Execute the statement
    if ($stmt->execute()) {
        echo "New setting 'max_concurrent_users' added successfully.\n";
    } else {
        echo "Error: Could not execute the insert statement.\n";
    }
} catch (PDOException $e) {
    // Check if the error is due to a duplicate key
    if ($e->errorInfo[1] == 1062) { // 1062 is the MySQL error code for duplicate entry
        echo "Warning: Setting 'max_concurrent_users' already exists.\n";
    } else {
        die("Error executing statement: " . $e->getMessage() . "\n");
    }
}

// Close the connection (optional, as PDO closes it automatically at the end of the script)
// $pdo = null;
?>
