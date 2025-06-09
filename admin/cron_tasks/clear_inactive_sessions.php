<?php
// admin/cron_tasks/clear_inactive_sessions.php
// This script is intended to be run periodically (e.g., via a cron job)
// to clear out old session records from the active_sessions table.

// Adjust path to config.php based on this script's location
// If this script is in admin/cron_tasks/, then config.php is ../../config.php
$config_path = __DIR__ . '/../../config.php';

if (!file_exists($config_path)) {
    // Attempt an alternative path if the first one fails (e.g. if script moved)
    $config_path = dirname(dirname(__DIR__)) . '/config.php'; // Assumes project root is parent of admin
    if(!file_exists($config_path)){
        echo "Error: config.php not found. Please check the path.
";
        error_log("clear_inactive_sessions.php: config.php not found at " . $config_path);
        exit(1); // Exit with an error code
    }
}
require_once $config_path;

// Define the inactivity interval (e.g., 10 minutes)
// Users whose last_activity is older than this will be removed.
define('SESSION_INACTIVITY_INTERVAL_MINUTES', 10);

$deleted_sessions_count = 0;

if (!isset($pdo)) {
    echo "Error: \$pdo database connection is not available. Check config.php.
";
    error_log("clear_inactive_sessions.php: \$pdo is not available.");
    exit(1);
}

try {
    $sql = "DELETE FROM active_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL " . SESSION_INACTIVITY_INTERVAL_MINUTES . " MINUTE)";

    $stmt = $pdo->prepare($sql); // Prepare is good practice, though no user input here

    if ($stmt->execute()) {
        $deleted_sessions_count = $stmt->rowCount();
        $log_message = "Successfully cleared inactive sessions. Sessions deleted: " . $deleted_sessions_count;
        echo $log_message . "
";
        // error_log($log_message); // Optionally log to PHP error log as well
    } else {
        $error_info = $stmt->errorInfo();
        $log_message = "Error executing clear_inactive_sessions query: " . ($error_info[2] ?? 'Unknown error');
        echo $log_message . "
";
        error_log($log_message);
    }
} catch (PDOException $e) {
    $log_message = "PDOException in clear_inactive_sessions.php: " . $e->getMessage();
    echo $log_message . "
";
    error_log($log_message);
    exit(1); // Exit with an error code on PDO exception
}

// Optional: Further actions, like logging to a custom file, etc.
// For a cron job, simple echo output might be captured by the cron daemon.
exit(0); // Exit successfully
?>
