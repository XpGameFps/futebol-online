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
    $sql = "SELECT COUNT(*) as online_count
            FROM active_sessions
            WHERE last_activity >= DATE_SUB(NOW(), INTERVAL " . USER_ACTIVE_INTERVAL_MINUTES . " MINUTE)";

    $stmt = $pdo->query($sql); // Using query() as it's a simple SELECT COUNT(*)
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result !== false && isset($result['online_count'])) {
        $response['online_count'] = (int)$result['online_count'];
        $response['status'] = 'success';
        $response['message'] = 'Online user count retrieved successfully.';
    } else {
        $response['message'] = 'Could not retrieve online user count.';
        error_log("get_online_users.php: Failed to fetch or parse online_count from query result.");
    }

} catch (PDOException $e) {
    $response['message'] = 'Database query failed.';
    error_log("PDOException in get_online_users.php: " . $e->getMessage());
    // For production, you might not want to expose $e->getMessage() directly
    // $response['detail'] = $e->getMessage(); // Optional for debugging
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
