<?php
session_start();
require_once '../config.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["match_id"]))) {
        header("Location: index.php?status=match_delete_error&reason=missing_id");
        exit;
    }

    $match_id = trim($_POST["match_id"]);

    // Validate match_id is an integer
    if (!filter_var($match_id, FILTER_VALIDATE_INT)) {
        header("Location: index.php?status=match_delete_error&reason=invalid_id");
        exit;
    }

    try {
        // First, check if the match exists (optional, but good practice)
        $checkSql = "SELECT id FROM matches WHERE id = :match_id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->bindParam(":match_id", $match_id, PDO::PARAM_INT);
        $checkStmt->execute();

        if ($checkStmt->rowCount() == 0) {
            header("Location: index.php?status=match_delete_error&reason=match_not_found");
            exit;
        }

        // Proceed with deletion
        // ON DELETE CASCADE in the streams table's foreign key will handle deleting associated streams.
        $sql = "DELETE FROM matches WHERE id = :match_id";

        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":match_id", $match_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    header("Location: index.php?status=match_deleted");
                    exit;
                } else {
                    // Match ID might have been valid but already deleted by another process
                    header("Location: index.php?status=match_delete_error&reason=not_deleted_maybe_already_gone");
                    exit;
                }
            } else {
                // error_log("Error executing delete statement: " . implode(":", $stmt->errorInfo()));
                header("Location: index.php?status=match_delete_error&reason=execute_failed");
                exit;
            }
        }
        unset($stmt);
    } catch (PDOException $e) {
        // error_log("PDOException in delete_match.php: " . $e->getMessage());
        header("Location: index.php?status=match_delete_error&reason=pdo_exception");
        exit;
    }

    unset($pdo);
} else {
    // If not a POST request, redirect to admin page
    header("Location: index.php");
    exit;
}
?>
