<?php
// admin/delete_match.php
require_once 'auth_check.php';
require_once '../config.php';

// Ensure csrf_utils.php is loaded (auth_check.php should have already included it)
if (!function_exists('validate_csrf_token')) {
    // Fallback if somehow not loaded, though auth_check.php should handle this.
    require_once 'csrf_utils.php';
}

define('MATCH_COVER_UPLOAD_DIR_FOR_DELETE', '../uploads/covers/matches/');

$match_id = null;
$status_message_type = 'match_delete_error';
$status_reason = 'unknown_error';
$match_id_for_redirect_anchor = null; // For redirecting to #match-ID if needed, though typically not for delete

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        // Set appropriate status messages for CSRF failure
        $status_message_type = 'match_delete_error';
        $status_reason = 'csrf_failure';

        // Redirect back to index or a general error page
        // Avoid using $match_id or other POST data here as it might be from a malicious request
        header("Location: index.php?status=" . $status_message_type . "&reason=" . $status_reason);
        exit;
    }
    if (isset($_POST['match_id']) && filter_var($_POST['match_id'], FILTER_VALIDATE_INT)) {
        $match_id = (int)$_POST['match_id'];
        // If match_id was also passed for anchor purposes (e.g. if deleting from a specific view)
        // $match_id_for_redirect_anchor = $match_id;
    } else {
        $status_reason = 'invalid_id_provided';
    }

    if ($match_id !== null) {
        try {
            $cover_image_filename_to_delete = null;
            $stmt_get_cover = $pdo->prepare("SELECT cover_image_filename FROM matches WHERE id = :match_id");
            $stmt_get_cover->bindParam(':match_id', $match_id, PDO::PARAM_INT);
            $stmt_get_cover->execute();
            if ($stmt_get_cover->rowCount() == 1) {
                $match_data = $stmt_get_cover->fetch(PDO::FETCH_ASSOC);
                $cover_image_filename_to_delete = $match_data['cover_image_filename'];
            }
            unset($stmt_get_cover);

            // Associated streams are deleted by ON DELETE CASCADE in DB schema

            $stmt_delete_db = $pdo->prepare("DELETE FROM matches WHERE id = :match_id");
            $stmt_delete_db->bindParam(':match_id', $match_id, PDO::PARAM_INT);

            if ($stmt_delete_db->execute()) {
                if ($stmt_delete_db->rowCount() > 0) {
                    // Partida foi deletada com sucesso, agora deletar reportes associados
                    try {
                        $stmt_delete_reports = $pdo->prepare(
                            "DELETE FROM player_reports WHERE item_id = :item_id AND item_type = 'match'"
                        );
                        $stmt_delete_reports->bindParam(':item_id', $match_id, PDO::PARAM_INT);
                        $stmt_delete_reports->execute();
                    } catch (PDOException $e_reports) {
                        error_log("PDOException ao deletar reportes para match_id {$match_id}: " . $e_reports->getMessage());
                    }

                    // Código existente para deletar imagem de capa
                    if ($cover_image_filename_to_delete) {
                        $file_path_to_delete = MATCH_COVER_UPLOAD_DIR_FOR_DELETE . $cover_image_filename_to_delete;
                        if (file_exists($file_path_to_delete)) {
                            if (unlink($file_path_to_delete)) {
                                // File deleted
                            } else {
                                error_log("Failed to delete match cover image: " . $file_path_to_delete . " for match ID: " . $match_id . ". Check permissions.");
                            }
                        }
                    }
                    $status_message_type = 'match_deleted';
                    $status_reason = 'success';
                } else {
                    $status_reason = 'not_found';
                }
            } else {
                $status_reason = 'execute_failed';
            }
        } catch (PDOException $e) {
            $status_reason = 'pdo_exception_' . $e->getCode();
            error_log("PDOException in delete_match.php: " . $e->getMessage());
        }
    } else {
        if ($status_reason === 'unknown_error') $status_reason = 'missing_id';
    }
} else { $status_reason = 'invalid_request_method'; }

$redirect_url = "index.php?status=" . $status_message_type . "&reason=" . $status_reason;
// Example: if you wanted to redirect to a specific view after delete:
// if ($view_type_from_form) { $redirect_url .= "&view=" . $view_type_from_form; }
// Or if an anchor was relevant for a specific match context on index.php (though less common for delete)
// if ($match_id_for_redirect_anchor) { $redirect_url .= "#match-" . $match_id_for_redirect_anchor; }

header("Location: " . $redirect_url);
exit;
?>
