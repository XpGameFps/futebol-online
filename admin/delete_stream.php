<?php
require_once 'auth_check.php'; require_once __DIR__ . '/../../FutOnline_config/config.php'; 
$stream_id_to_delete = null;
$match_id_for_redirect = null;
$status_message_type = 'stream_delete_error'; $status_reason = 'unknown_error';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!function_exists('validate_csrf_token')) {         require_once 'csrf_utils.php';
    }
        if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $status_message_type = 'stream_delete_error';
        $status_reason = 'csrf_failure';
        $match_id_for_redirect_csrf_err = null;
        if (isset($_POST['match_id']) && filter_var($_POST['match_id'], FILTER_VALIDATE_INT)) {
            $match_id_for_redirect_csrf_err = (int)$_POST['match_id'];
        }
        $redirect_url_csrf_err = "index.php?status=" . $status_message_type . "&reason=" . $status_reason;
        if ($match_id_for_redirect_csrf_err !== null) {
            $redirect_url_csrf_err .= "#match-" . $match_id_for_redirect_csrf_err;
        }
        header("Location: " . $redirect_url_csrf_err);
        exit;
    }
    
    if (isset($_POST['stream_id']) && filter_var($_POST['stream_id'], FILTER_VALIDATE_INT)) {
        $stream_id_to_delete = (int)$_POST['stream_id'];
    } else {
        $status_reason = 'invalid_stream_id';
    }

    if (isset($_POST['match_id']) && filter_var($_POST['match_id'], FILTER_VALIDATE_INT)) {
        $match_id_for_redirect = (int)$_POST['match_id'];
    }
    
    if ($stream_id_to_delete !== null) {
        try {
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
                                                $status_reason = 'not_deleted_or_already_gone';
                    }
                } else {
                    $status_reason = 'execute_failed';
                }
            } else {
                $status_reason = 'stream_not_found';
            }
        } catch (PDOException $e) {
                        $status_reason = 'pdo_exception_' . $e->getCode();
        }
    } else {
        if ($status_reason === 'unknown_error') {              $status_reason = 'missing_stream_id';
        }
    }
} else {
        $status_reason = 'invalid_request_method';
}

$redirect_url = "index.php?status=" . $status_message_type . "&reason=" . $status_reason;
if ($match_id_for_redirect !== null) {
    $redirect_url .= "#match-" . $match_id_for_redirect;
}

header("Location: " . $redirect_url);
exit;
?>

