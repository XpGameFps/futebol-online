<?php
// admin/delete_channel.php
require_once 'auth_check.php';
require_once '../config.php';

define('CHANNEL_LOGO_UPLOAD_DIR_FOR_DELETE', '../uploads/logos/channels/');

$channel_id = null;
$status_message_type = 'channel_delete_error';
$status_reason = 'unknown_error';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF token validation
    if (!function_exists('validate_csrf_token')) {
        require_once 'csrf_utils.php';
    }
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $status_message_type = 'channel_delete_error';
        $status_reason = 'csrf_failure';
        header("Location: manage_channels.php?status=" . $status_message_type . "&reason=" . $status_reason);
        exit;
    }

    if (isset($_POST['channel_id']) && filter_var($_POST['channel_id'], FILTER_VALIDATE_INT)) {
        $channel_id = (int)$_POST['channel_id'];
    } else {
        $status_reason = 'invalid_id_provided';
    }

    if ($channel_id !== null) {
        try {
            $logo_filename_to_delete = null;
            $stmt_get_logo = $pdo->prepare("SELECT logo_filename FROM tv_channels WHERE id = :channel_id");
            $stmt_get_logo->bindParam(':channel_id', $channel_id, PDO::PARAM_INT);
            $stmt_get_logo->execute();
            if ($stmt_get_logo->rowCount() == 1) {
                $channel_data = $stmt_get_logo->fetch(PDO::FETCH_ASSOC);
                $logo_filename_to_delete = $channel_data['logo_filename'];
            }
            unset($stmt_get_logo);

            $stmt_delete_db = $pdo->prepare("DELETE FROM tv_channels WHERE id = :channel_id");
            $stmt_delete_db->bindParam(':channel_id', $channel_id, PDO::PARAM_INT);

            if ($stmt_delete_db->execute()) {
                if ($stmt_delete_db->rowCount() > 0) {
                    // Canal foi deletado com sucesso, agora deletar reportes associados
                    try {
                        $stmt_delete_reports = $pdo->prepare(
                            "DELETE FROM player_reports WHERE item_id = :item_id AND item_type = 'channel'"
                        );
                        $stmt_delete_reports->bindParam(':item_id', $channel_id, PDO::PARAM_INT);
                        $stmt_delete_reports->execute();
                    } catch (PDOException $e_reports) {
                        error_log("PDOException ao deletar reportes para channel_id {$channel_id}: " . $e_reports->getMessage());
                    }

                    // Código existente para deletar logo
                    if ($logo_filename_to_delete) {
                        $file_path_to_delete = CHANNEL_LOGO_UPLOAD_DIR_FOR_DELETE . $logo_filename_to_delete;
                        if (file_exists($file_path_to_delete)) {
                            if (unlink($file_path_to_delete)) {
                                // File deleted
                            } else {
                                error_log("Failed to delete channel logo file: " . $file_path_to_delete . " for channel ID: " . $channel_id . ". Check permissions.");
                            }
                        }
                    }
                    $status_message_type = 'channel_deleted';
                    $status_reason = 'success';
                } else {
                    $status_reason = 'not_found';
                }
            } else {
                $status_reason = 'db_error'; // Changed from 'execute_failed'
            }
        } catch (PDOException $e) {
            $status_reason = 'db_error'; // Changed from 'pdo_exception_...'
            error_log("PDOException in delete_channel.php: " . $e->getMessage());
        }
    } else {
         if ($status_reason === 'unknown_error') $status_reason = 'missing_id';
    }
} else { $status_reason = 'invalid_request_method'; }

header("Location: manage_channels.php?status=" . $status_message_type . "&reason=" . $status_reason);
exit;
?>
