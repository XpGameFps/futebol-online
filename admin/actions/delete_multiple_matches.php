<?php
require_once '../auth_check.php';
require_once __DIR__ . '/../../../FutOnline_config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php?view=past&status=error&reason=invalid_request_method");
    exit;
}

if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    header("Location: ../index.php?view=past&status=error&reason=csrf_failure");
    exit;
}

if (empty($_POST['match_ids']) || !is_array($_POST['match_ids'])) {
    header("Location: ../index.php?view=past&status=error&reason=no_matches_selected");
    exit;
}

$match_ids_input = $_POST['match_ids'];
$match_ids_to_process = array_values(array_unique(array_filter($match_ids_input, 'ctype_digit')));

if (empty($match_ids_to_process)) {
    header("Location: ../index.php?view=past&status=error&reason=invalid_match_ids_after_filter");
    exit;
}

$posted_selected_count = count($match_ids_to_process);
$actually_deleted_count = 0;
$error_during_db_operation = false;

$details_of_intended_deletions = [];
if (!empty($match_ids_to_process)) {
    try {
        $placeholders_select = implode(',', array_fill(0, count($match_ids_to_process), '?'));
        $sql_select_details = "SELECT id, cover_image_filename FROM matches WHERE id IN ({$placeholders_select}) AND DATE(match_time) < CURDATE()";
        $stmt_select_details = $pdo->prepare($sql_select_details);
        $stmt_select_details->execute($match_ids_to_process);
        $details_of_intended_deletions = $stmt_select_details->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("PDOException in delete_multiple_matches.php while fetching match details: " . $e->getMessage());
        header("Location: ../index.php?view=past&status=delete_error&reason=db_error_fetching_details&selected=" . $posted_selected_count);
        exit;
    }
}

if (empty($details_of_intended_deletions)) {
    header("Location: ../index.php?view=past&status=matches_deleted_none_conditions&not_deleted_conditions=" . $posted_selected_count . "&selected=" . $posted_selected_count);
    exit;
}

$ids_for_db_delete = array_column($details_of_intended_deletions, 'id');
$count_intended_for_deletion = count($ids_for_db_delete);

try {
    $pdo->beginTransaction();

    if (!empty($ids_for_db_delete)) {
        $placeholders_streams = implode(',', array_fill(0, count($ids_for_db_delete), '?'));
        $sql_delete_streams = "DELETE FROM streams WHERE match_id IN ({$placeholders_streams})";
        $stmt_delete_streams = $pdo->prepare($sql_delete_streams);
        if (!$stmt_delete_streams->execute($ids_for_db_delete)) {
            $error_info_streams = $stmt_delete_streams->errorInfo();
            error_log("Error deleting streams for multiple matches (non-critical for main tx): " . ($error_info_streams[2] ?? 'Unknown DB error'));
        }
    }

    if (!empty($ids_for_db_delete)) {
        $placeholders_matches = implode(',', array_fill(0, count($ids_for_db_delete), '?'));
        $sql_delete_matches = "DELETE FROM matches WHERE id IN ({$placeholders_matches})";
        $stmt_delete_matches = $pdo->prepare($sql_delete_matches);
        if ($stmt_delete_matches->execute($ids_for_db_delete)) {
            $actually_deleted_count = $stmt_delete_matches->rowCount();
        } else {
            $error_info_matches = $stmt_delete_matches->errorInfo();
            error_log("Critical error executing delete_multiple_matches (matches table): " . ($error_info_matches[2] ?? 'Unknown DB error'));
            $error_during_db_operation = true;
        }
    } else {
        $actually_deleted_count = 0;
    }

    if ($error_during_db_operation || $actually_deleted_count != $count_intended_for_deletion) {
        if ($actually_deleted_count != $count_intended_for_deletion && !$error_during_db_operation) {
            error_log("Bulk Delete Mismatch: Intended to delete {$count_intended_for_deletion}, but actually deleted {$actually_deleted_count}. Rolling back.");
        }
        $pdo->rollBack();
        $error_during_db_operation = true;
    } else {
        $pdo->commit();
        if ($actually_deleted_count > 0) {
            if (!defined('BULK_DELETE_MATCH_COVER_PATH')) {
                define('BULK_DELETE_MATCH_COVER_PATH', '../../uploads/covers/matches/');
            }
            foreach ($details_of_intended_deletions as $match_detail) {
                $match_id_to_clean = $match_detail['id'];
                $cover_image_filename_to_delete = $match_detail['cover_image_filename'];
                try {
                    $stmt_delete_reports = $pdo->prepare(
                        "DELETE FROM player_reports WHERE item_id = :item_id AND item_type = 'match'"
                    );
                    $stmt_delete_reports->bindParam(':item_id', $match_id_to_clean, PDO::PARAM_INT);
                    if (!$stmt_delete_reports->execute()) {
                        $report_error_info = $stmt_delete_reports->errorInfo();
                        error_log("Bulk Delete: Error deleting player reports for match_id {$match_id_to_clean}: " . ($report_error_info[2] ?? 'Unknown DB error'));
                    }
                } catch (PDOException $e_reports) {
                    error_log("Bulk Delete: PDOException deleting player reports for match_id {$match_id_to_clean}: " . $e_reports->getMessage());
                }
                if (!empty($cover_image_filename_to_delete)) {
                    $file_path_to_delete = BULK_DELETE_MATCH_COVER_PATH . $cover_image_filename_to_delete;
                    if (is_file($file_path_to_delete)) {
                        if (!unlink($file_path_to_delete)) {
                            error_log("Bulk Delete: Failed to delete match cover image '$file_path_to_delete' for match ID: $match_id_to_clean. Check permissions.");
                        }
                    }
                }
            }
        }
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("PDOException in delete_multiple_matches.php: " . $e->getMessage());
    $error_during_db_operation = true;
}

if ($error_during_db_operation) {
    header("Location: ../index.php?view=past&status=delete_error&reason=db_error_on_match_delete&selected=" . $posted_selected_count);
} else {
    if ($actually_deleted_count > 0 && $actually_deleted_count == $posted_selected_count) {
        header("Location: ../index.php?view=past&status=matches_deleted_success_total&count=" . $actually_deleted_count);
    } elseif ($actually_deleted_count > 0 && $actually_deleted_count < $posted_selected_count) {
        $not_deleted_count = $posted_selected_count - $actually_deleted_count;
        header("Location: ../index.php?view=past&status=matches_deleted_partial_conditions&deleted=" . $actually_deleted_count . "&not_deleted_conditions=" . $not_deleted_count . "&selected=" . $posted_selected_count);
    } elseif ($actually_deleted_count == 0 && $posted_selected_count > 0) {
        error_log("Bulk Delete: Logic error - 0 deleted but error_during_db_operation is false. Posted: $posted_selected_count");
        header("Location: ../index.php?view=past&status=delete_error&reason=zero_deleted_unexpectedly&selected=" . $posted_selected_count);
    } elseif ($posted_selected_count == 0) {
        error_log("delete_multiple_matches.php: Reached total_selected_count == 0 in final feedback logic.");
        header("Location: ../index.php?view=past&status=error&reason=no_valid_matches_input_final_logic_error");
    } else {
        error_log("delete_multiple_matches.php: Reached unexpected feedback state. Deleted: $actually_deleted_count, Selected: $posted_selected_count");
        header("Location: ../index.php?view=past&status=delete_error&reason=unknown_processing_state_feedback");
    }
}
exit;
?>

