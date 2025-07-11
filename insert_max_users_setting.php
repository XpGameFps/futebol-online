<?php
require_once __DIR__ . '/../FutOnline_config/config.php';

$sql = "INSERT INTO site_settings (setting_key, setting_value) VALUES (:setting_key, :setting_value)";

try {
        $stmt = $pdo->prepare($sql);

        $setting_key = 'max_concurrent_users';
    $setting_value = '0';
    $stmt->bindParam(':setting_key', $setting_key);
    $stmt->bindParam(':setting_value', $setting_value);

        if ($stmt->execute()) {
        echo "New setting 'max_concurrent_users' added successfully.\n";
    } else {
        echo "Error: Could not execute the insert statement.\n";
    }
} catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {         echo "Warning: Setting 'max_concurrent_users' already exists.\n";
    } else {
        die("Error executing statement: " . $e->getMessage() . "\n");
    }
}

?>
