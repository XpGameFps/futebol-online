<?php
// admin/delete_league.php
require_once 'auth_check.php';
require_once '../config.php';

define('LEAGUE_LOGO_UPLOAD_DIR_FOR_DELETE', '../uploads/logos/leagues/'); // Path relative to this admin script

$league_id = null;
$status_message_type = 'league_delete_error';
$status_reason = 'unknown_error';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF token validation
    if (!function_exists('validate_csrf_token')) { // Should be loaded by auth_check.php
        require_once 'csrf_utils.php';
    }
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $status_message_type = 'league_delete_error';
        $status_reason = 'csrf_failure';
        header("Location: manage_leagues.php?status=" . $status_message_type . "&reason=" . $status_reason);
        exit;
    }

    if (isset($_POST['league_id']) && filter_var($_POST['league_id'], FILTER_VALIDATE_INT)) {
        $league_id = (int)$_POST['league_id'];
    } else {
        $status_reason = 'invalid_id_provided';
    }

    if ($league_id !== null) {
        try {
            $logo_filename_to_delete = null;
            $stmt_get_logo = $pdo->prepare("SELECT logo_filename FROM leagues WHERE id = :league_id");
            $stmt_get_logo->bindParam(':league_id', $league_id, PDO::PARAM_INT);
            $stmt_get_logo->execute();
            if ($stmt_get_logo->rowCount() == 1) {
                $league_data = $stmt_get_logo->fetch(PDO::FETCH_ASSOC);
                $logo_filename_to_delete = $league_data['logo_filename'];
            }
            unset($stmt_get_logo); // Close statement

            $stmt_delete_db = $pdo->prepare("DELETE FROM leagues WHERE id = :league_id");
            $stmt_delete_db->bindParam(':league_id', $league_id, PDO::PARAM_INT);

            if ($stmt_delete_db->execute()) {
                if ($stmt_delete_db->rowCount() > 0) {
                    if ($logo_filename_to_delete) {
                        $file_path_to_delete = LEAGUE_LOGO_UPLOAD_DIR_FOR_DELETE . $logo_filename_to_delete;
                        if (file_exists($file_path_to_delete)) {
                            if (unlink($file_path_to_delete)) {
                                // File deleted successfully
                            } else {
                                error_log("Failed to delete league logo file: " . $file_path_to_delete . " for league ID: " . $league_id . ". Check permissions.");
                            }
                        } else {
                            // error_log("League logo file not found for deletion: " . $file_path_to_delete);
                        }
                    }
                    $status_message_type = 'league_deleted';
                    $status_reason = 'success';
                } else { $status_reason = 'not_found'; } // No rows affected, likely already deleted or invalid ID
            } else { $status_reason = 'db_error'; } // Changed from 'execute_failed'
        } catch (PDOException $e) {
            $status_reason = 'db_error'; // Changed from 'pdo_exception_...'
            error_log("PDOException in delete_league.php: " . $e->getMessage());
        }
    } else {
        // If $league_id was null after initial check (e.g. not set in POST or not an int)
        if ($status_reason === 'unknown_error') $status_reason = 'missing_id';
    }
} else { $status_reason = 'invalid_request_method'; }

header("Location: manage_leagues.php?status=" . $status_message_type . "&reason=" . $status_reason);
exit;
?>
