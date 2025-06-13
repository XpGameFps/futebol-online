<?php
// admin/actions/delete_multiple_matches.php
require_once '../auth_check.php'; // Handles session_start() and CSRF
require_once '../../config.php'; // Database connection

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

$posted_selected_count = count($match_ids_to_process); // How many distinct valid IDs were submitted
$actually_deleted_count = 0;
$error_during_db_operation = false;

$details_of_intended_deletions = []; // To store {id, cover_image_filename} for matches that meet deletion criteria

if (!empty($match_ids_to_process)) {
    try {
        // Prepare placeholders for the IN clause for the SELECT statement
        $placeholders_select = implode(',', array_fill(0, count($match_ids_to_process), '?'));
        
        // SQL to select details of matches that are in the processing list AND are confirmed to be in the past
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

// If no matches from the selection are actually eligible (e.g., all were future-dated or already deleted)
if (empty($details_of_intended_deletions)) {
    header("Location: ../index.php?view=past&status=matches_deleted_none_conditions&not_deleted_conditions=" . $posted_selected_count . "&selected=" . $posted_selected_count);
    exit;
}

$ids_for_db_delete = array_column($details_of_intended_deletions, 'id');
$count_intended_for_deletion = count($ids_for_db_delete);

try {
    $pdo->beginTransaction();

    // Delete streams for the matches intended for deletion
    if (!empty($ids_for_db_delete)) {
        $placeholders_streams = implode(',', array_fill(0, count($ids_for_db_delete), '?'));
        $sql_delete_streams = "DELETE FROM streams WHERE match_id IN ({$placeholders_streams})";
        $stmt_delete_streams = $pdo->prepare($sql_delete_streams);
        
        if (!$stmt_delete_streams->execute($ids_for_db_delete)) {
            $error_info_streams = $stmt_delete_streams->errorInfo();
            error_log("Error deleting streams for multiple matches (non-critical for main tx): " . ($error_info_streams[2] ?? 'Unknown DB error'));
            // Decide if this is a critical error; for now, we log and continue to match deletion attempt
        }
    }

    // Delete matches intended for deletion
    // The match_time < NOW() condition was already applied when selecting $ids_for_db_delete
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

    // Critical check: if the number of actually deleted matches doesn't match our intended count, roll back.
    // This handles cases where NOW() might have shifted or concurrent modifications occurred.
    if ($error_during_db_operation || $actually_deleted_count != $count_intended_for_deletion) {
        if ($actually_deleted_count != $count_intended_for_deletion && !$error_during_db_operation) {
            error_log("Bulk Delete Mismatch: Intended to delete {$count_intended_for_deletion}, but actually deleted {$actually_deleted_count}. Rolling back.");
        }
        $pdo->rollBack();
        $error_during_db_operation = true; // Ensure this is set for feedback
    } else {
        $pdo->commit();

        if ($actually_deleted_count > 0) {
            // Define a specific constant for the cover image path for this script, if not already defined.
            if (!defined('BULK_DELETE_MATCH_COVER_PATH')) { 
                define('BULK_DELETE_MATCH_COVER_PATH', '../../uploads/covers/matches/');
            }
            
            // Iterate through the list of matches that were successfully deleted.
            foreach ($details_of_intended_deletions as $match_detail) {
                $match_id_to_clean = $match_detail['id'];
                $cover_image_filename_to_delete = $match_detail['cover_image_filename'];

                // 1. Delete associated player reports for the current match ID.
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

                // 2. Delete the cover image file, if a specific one is listed.
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
    // Feedback based on $actually_deleted_count and $posted_selected_count
    if ($actually_deleted_count > 0 && $actually_deleted_count == $posted_selected_count) {
        // All posted distinct valid IDs were eligible and deleted
        header("Location: ../index.php?view=past&status=matches_deleted_success_total&count=" . $actually_deleted_count);
    } elseif ($actually_deleted_count > 0 && $actually_deleted_count < $posted_selected_count) {
        // Some of the posted IDs were deleted, others were not eligible from the start
        $not_deleted_count = $posted_selected_count - $actually_deleted_count;
        header("Location: ../index.php?view=past&status=matches_deleted_partial_conditions&deleted=" . $actually_deleted_count . "&not_deleted_conditions=" . $not_deleted_count . "&selected=" . $posted_selected_count);
    } elseif ($actually_deleted_count == 0 && $posted_selected_count > 0) {
        // This case should have been caught by `if (empty($details_of_intended_deletions))` earlier if none were eligible.
        // If reached here, it means some were eligible, but deletion resulted in 0, which should have been a rollback.
        // This indicates an unexpected state, likely an error.
        error_log("Bulk Delete: Logic error - 0 deleted but error_during_db_operation is false. Posted: $posted_selected_count");
        header("Location: ../index.php?view=past&status=delete_error&reason=zero_deleted_unexpectedly&selected=" . $posted_selected_count);
    } elseif ($posted_selected_count == 0) { // No valid IDs in POST initially
        error_log("delete_multiple_matches.php: Reached total_selected_count == 0 in final feedback logic.");
        header("Location: ../index.php?view=past&status=error&reason=no_valid_matches_input_final_logic_error");
    } else {
        error_log("delete_multiple_matches.php: Reached unexpected feedback state. Deleted: $actually_deleted_count, Selected: $posted_selected_count");
        header("Location: ../index.php?view=past&status=delete_error&reason=unknown_processing_state_feedback");
    }
}
exit;
?>
