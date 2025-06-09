<?php
require_once 'auth_check.php'; // Ensures admin is logged in
require_once '../config.php'; // Database connection

$stream_id_to_delete = null;
$match_id_for_redirect = null;
$status_message_type = 'stream_delete_error'; // Default to error
$status_reason = 'unknown_error';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['stream_id']) && filter_var($_POST['stream_id'], FILTER_VALIDATE_INT)) {
        $stream_id_to_delete = (int)$_POST['stream_id'];
    } else {
        $status_reason = 'invalid_stream_id';
    }

    if (isset($_POST['match_id']) && filter_var($_POST['match_id'], FILTER_VALIDATE_INT)) {
        $match_id_for_redirect = (int)$_POST['match_id'];
    }
    // match_id for redirect is helpful but not strictly critical for the delete operation itself.

    if ($stream_id_to_delete !== null) {
        try {
            // Check if stream exists (optional, but good for accurate feedback)
            $stmt_check = $pdo->prepare("SELECT id FROM streams WHERE id = :id");
            $stmt_check->bindParam(':id', $stream_id_to_delete, PDO::PARAM_INT);
            $stmt_check->execute();

            if ($stmt_check->rowCount() > 0) {
                $sql_delete = "DELETE FROM streams WHERE id = :id";
                $stmt_delete = $pdo->prepare($sql_delete);
                $stmt_delete->bindParam(':id', $stream_id_to_delete, PDO::PARAM_INT);

                if ($stmt_delete->execute()) {
                    if ($stmt_delete->rowCount() > 0) {
                        $status_message_type = 'stream_deleted';
                        $status_reason = 'success';
                    } else {
                        // Record existed at check but couldn't be deleted or was deleted by another process
                        $status_reason = 'not_deleted_or_already_gone';
                    }
                } else {
                    $status_reason = 'execute_failed';
                }
            } else {
                $status_reason = 'stream_not_found';
            }
        } catch (PDOException $e) {
            // error_log("PDOException in delete_stream.php: " . $e->getMessage());
            $status_reason = 'pdo_exception_' . $e->getCode();
        }
    } else {
        if ($status_reason === 'unknown_error') { // If $stream_id_to_delete was null from the start
             $status_reason = 'missing_stream_id';
        }
    }
} else {
    // Not a POST request
    $status_reason = 'invalid_request_method';
}

// Construct redirect URL
$redirect_url = "index.php?status=" . $status_message_type . "&reason=" . $status_reason;
if ($match_id_for_redirect !== null) {
    $redirect_url .= "#match-" . $match_id_for_redirect;
}

header("Location: " . $redirect_url);
exit;
?>
