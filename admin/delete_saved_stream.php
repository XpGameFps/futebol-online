<?php
require_once 'auth_check.php';
require_once '../config.php';

$status_message_type = 'saved_stream_delete_error';
$status_reason = 'unknown_error';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!function_exists('validate_csrf_token')) {
        require_once 'csrf_utils.php';
    }
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $status_message_type = 'saved_stream_delete_error';
        $status_reason = 'csrf_failure';
        header("Location: manage_saved_streams.php?status=" . $status_message_type . "&reason=" . $status_reason);
        exit;
    }

    if (isset($_POST['saved_stream_id']) && filter_var($_POST['saved_stream_id'], FILTER_VALIDATE_INT)) {
        $saved_stream_id_to_delete = (int)$_POST['saved_stream_id'];
        try {
            $stmt_delete = $pdo->prepare("DELETE FROM saved_stream_urls WHERE id = :id");
        $stmt_delete->bindParam(':id', $saved_stream_id_to_delete, PDO::PARAM_INT);
        if ($stmt_delete->execute()) {
            if ($stmt_delete->rowCount() > 0) {
                $status_message_type = 'saved_stream_deleted';
                $status_reason = 'success';
            } else { $status_reason = 'not_found'; }
        } else { $status_reason = 'execute_failed'; }
        } catch (PDOException $e) { $status_reason = 'pdo_exception_' . $e->getCode(); }
    } else {
        $status_reason = 'invalid_id_provided'; // ID not set or invalid after CSRF check
    }
} else {
    $status_reason = 'invalid_request_method'; // Not a POST request
}

header("Location: manage_saved_streams.php?status=" . $status_message_type . "&reason=" . $status_reason);
exit;
?>
