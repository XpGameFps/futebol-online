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

$match_ids_to_delete = array_filter($_POST['match_ids'], 'ctype_digit'); // Filter for numeric IDs

if (empty($match_ids_to_delete)) {
    header("Location: ../index.php?view=past&status=error&reason=invalid_match_ids");
    exit;
}

$deleted_count = 0;
$error_count = 0;

try {
    $pdo->beginTransaction();

    // First, delete associated streams for all selected matches
    // Create placeholders for match IDs: (?, ?, ?)
    $placeholders_streams = implode(',', array_fill(0, count($match_ids_to_delete), '?'));
    $sql_delete_streams = "DELETE FROM streams WHERE match_id IN ({$placeholders_streams})";
    $stmt_delete_streams = $pdo->prepare($sql_delete_streams);
    $stmt_delete_streams->execute($match_ids_to_delete);
    // We don't track stream deletion count separately for this bulk operation status

    // Then, delete the matches themselves
    $placeholders_matches = implode(',', array_fill(0, count($match_ids_to_delete), '?'));
    $sql_delete_matches = "DELETE FROM matches WHERE id IN ({$placeholders_matches}) AND match_time < NOW()"; // Extra safety: only past
    $stmt_delete_matches = $pdo->prepare($sql_delete_matches);

    if ($stmt_delete_matches->execute($match_ids_to_delete)) {
        $deleted_count = $stmt_delete_matches->rowCount();
    } else {
        $error_info = $stmt_delete_matches->errorInfo();
        error_log("Error deleting multiple matches: " . ($error_info[2] ?? 'Unknown DB error'));
        $error_count = count($match_ids_to_delete); // Assume all failed if execute returns false
    }

    $pdo->commit();

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("PDOException in delete_multiple_matches.php: " . $e->getMessage());
    header("Location: ../index.php?view=past&status=delete_error&reason=" . urlencode("DB_error: " . $e->getCode()));
    exit;
}

if ($deleted_count > 0 && $error_count == 0) {
    header("Location: ../index.php?view=past&status=matches_deleted_multiple&count=" . $deleted_count);
} elseif ($deleted_count > 0 && $error_count > 0) {
    header("Location: ../index.php?view=past&status=matches_deleted_partial&deleted=" . $deleted_count . "&failed=" . $error_count);
} else {
    header("Location: ../index.php?view=past&status=delete_error&reason=none_deleted");
}
exit;
?>
