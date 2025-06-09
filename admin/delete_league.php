<?php
require_once 'auth_check.php'; // Ensures admin is logged in, also handles session_start()
require_once '../config.php'; // Database connection

// Define base path for league logos relative to this script's location
define('LEAGUE_LOGO_UPLOAD_DIR_FOR_DELETE', '../uploads/logos/leagues/');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["league_id"]))) {
        header("Location: manage_leagues.php?status=league_delete_error&reason=missing_id");
        exit;
    }

    $league_id = trim($_POST["league_id"]);

    if (!filter_var($league_id, FILTER_VALIDATE_INT)) {
        header("Location: manage_leagues.php?status=league_delete_error&reason=invalid_id");
        exit;
    }

    try {
        // First, get the filename of the logo to delete it from the server
        $logo_filename_to_delete = null;
        $sql_get_logo = "SELECT logo_filename FROM leagues WHERE id = :league_id";
        $stmt_get_logo = $pdo->prepare($sql_get_logo);
        $stmt_get_logo->bindParam(":league_id", $league_id, PDO::PARAM_INT);
        $stmt_get_logo->execute();

        if ($stmt_get_logo->rowCount() == 1) {
            $league_data = $stmt_get_logo->fetch(PDO::FETCH_ASSOC);
            $logo_filename_to_delete = $league_data['logo_filename'];
        }
        unset($stmt_get_logo);

        // Proceed with deletion from database
        $sql_delete_db = "DELETE FROM leagues WHERE id = :league_id";
        $stmt_delete_db = $pdo->prepare($sql_delete_db);
        $stmt_delete_db->bindParam(":league_id", $league_id, PDO::PARAM_INT);

        if ($stmt_delete_db->execute()) {
            if ($stmt_delete_db->rowCount() > 0) {
                // If DB deletion was successful and there was a logo filename, try to delete the file
                if ($logo_filename_to_delete) {
                    $file_path_to_delete = LEAGUE_LOGO_UPLOAD_DIR_FOR_DELETE . $logo_filename_to_delete;
                    if (file_exists($file_path_to_delete)) {
                        if (!unlink($file_path_to_delete)) {
                            // Optional: Log an error if unlink fails, but don't block success message for DB deletion
                            // error_log("Failed to delete league logo file: " . $file_path_to_delete);
                        }
                    }
                }
                header("Location: manage_leagues.php?status=league_deleted");
            } else {
                // League ID might have been valid but already deleted by another process
                header("Location: manage_leagues.php?status=league_delete_error&reason=not_found");
            }
            exit;
        } else {
            header("Location: manage_leagues.php?status=league_delete_error&reason=execute_failed");
            exit;
        }
    } catch (PDOException $e) {
        // error_log("PDOException in delete_league.php: " . $e->getMessage());
        header("Location: manage_leagues.php?status=league_delete_error&reason=pdo_exception_" . $e->getCode());
        exit;
    }
} else {
    header("Location: manage_leagues.php");
    exit;
}
?>
