<?php
session_start();
require_once '../config.php';

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
        $sql = "DELETE FROM leagues WHERE id = :league_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":league_id", $league_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                header("Location: manage_leagues.php?status=league_deleted");
            } else {
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
