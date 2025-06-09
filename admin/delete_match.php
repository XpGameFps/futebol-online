<?php
// admin/delete_match.php
require_once 'auth_check.php'; // Ensures admin is logged in
require_once '../config.php'; // Database connection

define('MATCH_COVER_UPLOAD_DIR_FOR_DELETE', '../uploads/covers/matches/');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["match_id"]))) {
        header("Location: index.php?status=match_delete_error&reason=missing_id");
        exit;
    }
    $match_id = trim($_POST["match_id"]);
    if (!filter_var($match_id, FILTER_VALIDATE_INT)) {
        header("Location: index.php?status=match_delete_error&reason=invalid_id");
        exit;
    }

    try {
        // Get cover image filename before deleting DB record
        $cover_image_filename_to_delete = null;
        $stmt_get_cover = $pdo->prepare("SELECT cover_image_filename FROM matches WHERE id = :match_id");
        $stmt_get_cover->bindParam(":match_id", $match_id, PDO::PARAM_INT);
        $stmt_get_cover->execute();
        if ($stmt_get_cover->rowCount() == 1) {
            $match_data = $stmt_get_cover->fetch(PDO::FETCH_ASSOC);
            $cover_image_filename_to_delete = $match_data['cover_image_filename'];
        }
        unset($stmt_get_cover);

        // Delete DB record (streams associated are deleted by ON DELETE CASCADE)
        $stmt_delete_db = $pdo->prepare("DELETE FROM matches WHERE id = :match_id");
        $stmt_delete_db->bindParam(":match_id", $match_id, PDO::PARAM_INT);

        if ($stmt_delete_db->execute()) {
            if ($stmt_delete_db->rowCount() > 0) {
                // If DB deletion successful, try to delete cover image file
                if ($cover_image_filename_to_delete) {
                    $file_path_to_delete = MATCH_COVER_UPLOAD_DIR_FOR_DELETE . $cover_image_filename_to_delete;
                    if (file_exists($file_path_to_delete)) {
                        if (!unlink($file_path_to_delete)) {
                            // error_log("Failed to delete match cover image: " . $file_path_to_delete);
                        }
                    }
                }
                header("Location: index.php?status=match_deleted");
            } else {
                header("Location: index.php?status=match_delete_error&reason=not_found");
            }
            exit;
        } else {
            header("Location: index.php?status=match_delete_error&reason=execute_failed");
            exit;
        }
    } catch (PDOException $e) {
        header("Location: index.php?status=match_delete_error&reason=pdo_exception_" . $e->getCode());
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
?>
