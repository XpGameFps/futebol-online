<?php
require_once 'auth_check.php';
require_once '../config.php';
define('TEAM_LOGO_UPLOAD_DIR_FOR_DELETE', '../uploads/logos/teams/');

$status_type = 'team_delete_error';
$reason = 'unknown_error'; // Default reason

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['team_id']) && filter_var($_POST['team_id'], FILTER_VALIDATE_INT)) {
    $team_id_to_delete = (int)$_POST['team_id'];
    try {
        // First, get the filename of the logo to delete it from the server
        $logo_filename = null;
        $stmt_get = $pdo->prepare("SELECT logo_filename FROM teams WHERE id = :id");
        $stmt_get->bindParam(':id', $team_id_to_delete, PDO::PARAM_INT);
        $stmt_get->execute();
        if ($stmt_get->rowCount() == 1) {
            $logo_filename = $stmt_get->fetchColumn();
        }
        unset($stmt_get); // Close statement

        // Proceed with deletion from database
        // Note: If matches table used team_id as FK with ON DELETE SET NULL, this would be handled by DB.
        // For now, we are just deleting the team. Impact on matches to be handled when matches table is altered.
        $stmt_del = $pdo->prepare("DELETE FROM teams WHERE id = :id");
        $stmt_del->bindParam(':id', $team_id_to_delete, PDO::PARAM_INT);
        if ($stmt_del->execute()) {
            if ($stmt_del->rowCount() > 0) {
                // If DB deletion was successful and there was a logo filename, try to delete the file
                if ($logo_filename) {
                    $file_path_to_delete = TEAM_LOGO_UPLOAD_DIR_FOR_DELETE . $logo_filename;
                    if (file_exists($file_path_to_delete)) {
                        if(!unlink($file_path_to_delete)) {
                            error_log("Failed to delete team logo file: ". $file_path_to_delete . " for team ID: " . $team_id_to_delete);
                            // Not critical enough to change success status of DB deletion
                        }
                    }
                }
                $status_type = 'team_deleted';
                $reason = 'success';
            } else {
                $reason = 'not_found'; // Team ID was valid but not found (e.g., already deleted)
            }
        } else {
            $reason = 'execute_failed';
        }
    } catch (PDOException $e) {
        $reason = 'pdo_exception_'.$e->getCode();
        error_log("PDO exception in delete_team.php: ".$e->getMessage());
    }
} else {
    $reason = 'invalid_request'; // Not a POST request or team_id not set/invalid
}

header("Location: manage_teams.php?status=".$status_type."&reason=".$reason);
exit;
?>
