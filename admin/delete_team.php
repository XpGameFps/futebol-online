<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../../FutOnline_config/config.php';
define('TEAM_LOGO_UPLOAD_DIR_FOR_DELETE', '../uploads/logos/teams/');

$status_type = 'team_delete_error';
$reason = 'unknown_error'; 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!function_exists('validate_csrf_token')) {
        require_once 'csrf_utils.php';
    }
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $status_type = 'team_delete_error';
        $reason = 'csrf_failure';
        header("Location: manage_teams.php?status=".$status_type."&reason=".$reason);
        exit;
    }

    if (isset($_POST['team_id']) && filter_var($_POST['team_id'], FILTER_VALIDATE_INT)) {
        $team_id_to_delete = (int)$_POST['team_id'];
        try {
            
                    $logo_filename = null;
        $stmt_get = $pdo->prepare("SELECT logo_filename FROM teams WHERE id = :id");
        $stmt_get->bindParam(':id', $team_id_to_delete, PDO::PARAM_INT);
        $stmt_get->execute();
        if ($stmt_get->rowCount() == 1) {
            $logo_filename = $stmt_get->fetchColumn();
        }
        unset($stmt_get); 
                                $stmt_del = $pdo->prepare("DELETE FROM teams WHERE id = :id");
        $stmt_del->bindParam(':id', $team_id_to_delete, PDO::PARAM_INT);
        if ($stmt_del->execute()) {
            if ($stmt_del->rowCount() > 0) {
                                if ($logo_filename) {
                    $file_path_to_delete = TEAM_LOGO_UPLOAD_DIR_FOR_DELETE . $logo_filename;
                    if (file_exists($file_path_to_delete)) {
                        if(!unlink($file_path_to_delete)) {
                            error_log("Failed to delete team logo file: ". $file_path_to_delete . " for team ID: " . $team_id_to_delete);
                                                    }
                    }
                }
                $status_type = 'team_deleted';
                $reason = 'success';
            } else {
                $reason = 'not_found';             }
        } else {
            $reason = 'execute_failed';
        }
        } catch (PDOException $e) {
            $reason = 'pdo_exception_'.$e->getCode();
            error_log("PDO exception in delete_team.php: ".$e->getMessage());
        }
    } else {
        $reason = 'invalid_id_provided';     }
} else {
    $reason = 'invalid_request_method'; }

header("Location: manage_teams.php?status=".$status_type."&reason=".$reason);
exit;
?>

