<?php

$config_path = __DIR__ . '/../../../FutOnline_config/config.php';

if (!file_exists($config_path)) {
    $config_path = dirname(dirname(dirname(__DIR__))) . '/FutOnline_config/config.php';
    if (!file_exists($config_path)) {
        echo "Error: config.php not found. Please check the path.\n";
        error_log("clear_inactive_sessions.php: config.php not found at " . $config_path);
        exit(1);
    }
}
require_once $config_path;

define('SESSION_INACTIVITY_INTERVAL_MINUTES', 10);

$deleted_sessions_count = 0;

if (!isset($pdo)) {
    echo "Error: \$pdo database connection is not available. Check config.php.\n";
    error_log("clear_inactive_sessions.php: \$pdo is not available.");
    exit(1);
}

try {
    $sql = "DELETE FROM active_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL " . SESSION_INACTIVITY_INTERVAL_MINUTES . " MINUTE)";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute()) {
        $deleted_sessions_count = $stmt->rowCount();
        $log_message = "Successfully cleared inactive sessions. Sessions deleted: " . $deleted_sessions_count;
        echo $log_message . "\n";
    } else {
        $error_info = $stmt->errorInfo();
        $log_message = "Error executing clear_inactive_sessions query: " . ($error_info[2] ?? 'Unknown error');
        echo $log_message . "\n";
        error_log($log_message);
    }
} catch (PDOException $e) {
    $log_message = "PDOException in clear_inactive_sessions.php: " . $e->getMessage();
    echo $log_message . "\n";
    error_log($log_message);
    exit(1);
}

exit(0);
?>
