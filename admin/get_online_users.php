<?php
// admin/get_online_users.php
require_once 'auth_check.php'; // Ensures admin is logged in (session already started)
require_once '../config.php'; // Database connection

// Define the activity window for "online" users (e.g., 5 minutes)
define('USER_ACTIVE_INTERVAL_MINUTES', 5);

$response = ['online_count' => 0, 'status' => 'error', 'message' => 'Unknown error'];

if (!isset($pdo)) {
    $response['message'] = 'Database connection not available.';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

try {
    // Fetch current online users
    $sql_online = "SELECT COUNT(*) as online_count
            FROM active_sessions
            WHERE last_activity >= DATE_SUB(NOW(), INTERVAL " . USER_ACTIVE_INTERVAL_MINUTES . " MINUTE)";

    $stmt_online = $pdo->query($sql_online);
    $result_online = $stmt_online->fetch(PDO::FETCH_ASSOC);

    if ($result_online !== false && isset($result_online['online_count'])) {
        $response['online_count'] = (int)$result_online['online_count'];
        $response['status'] = 'success'; // Tentatively set
        $response['message'] = 'Online user count retrieved.';
    } else {
        $response['message'] = 'Could not retrieve online user count.';
        error_log("get_online_users.php: Failed to fetch or parse online_count.");
        // Output immediately if this critical part fails
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Fetch max concurrent users from site_settings
    $max_users_val = 0; // Default value
    try {
        $sql_max_users = "SELECT setting_value FROM site_settings WHERE setting_key = 'max_concurrent_users'";
        $stmt_max_users = $pdo->query($sql_max_users);
        $result_max_users = $stmt_max_users->fetch(PDO::FETCH_ASSOC);

        if ($result_max_users && isset($result_max_users['setting_value'])) {
            $max_users_val = (int)$result_max_users['setting_value'];
        }
        // If key not found or value is null, $max_users_val remains 0.
    } catch (PDOException $e_max) {
        error_log("PDOException in get_online_users.php fetching max_concurrent_users: " . $e_max->getMessage());
        // $max_users_val remains 0.
        // Optionally, set a specific error message for this part in the response:
        // $response['max_users_error_message'] = 'Could not retrieve max users setting.';
    }
    $response['max_users_count'] = $max_users_val;

    // Refine overall message if both were successful
    if ($response['status'] === 'success') {
         $response['message'] = 'Online and max user counts retrieved successfully.';
    }

} catch (PDOException $e) { // Catches exceptions from the first (online_count) query primarily
    $response['message'] = 'Database query failed for online users.';
    error_log("PDOException in get_online_users.php (online_count part): " . $e->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
