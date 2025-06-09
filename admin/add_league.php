<?php
session_start();
require_once '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["name"]))) {
        header("Location: manage_leagues.php?status=league_add_error&reason=empty_name");
        exit;
    }

    $name = trim($_POST["name"]);
    $logo_url = !empty(trim($_POST["logo_url"])) ? trim($_POST["logo_url"]) : null;

    if ($logo_url && !filter_var($logo_url, FILTER_VALIDATE_URL)) {
        header("Location: manage_leagues.php?status=league_add_error&reason=invalid_url");
        exit;
    }

    try {
        // Check if it's a unique constraint violation (code 23000) for 'name'
        // This is an application-level check. The DB also has a UNIQUE constraint.
        $checkSql = "SELECT id FROM leagues WHERE name = :name";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->bindParam(":name", $name, PDO::PARAM_STR);
        $checkStmt->execute();
        if ($checkStmt->rowCount() > 0) {
            header("Location: manage_leagues.php?status=league_add_error&reason=name_exists");
            exit;
        }

        $sql = "INSERT INTO leagues (name, logo_url) VALUES (:name, :logo_url)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":name", $name, PDO::PARAM_STR);
        $stmt->bindParam(":logo_url", $logo_url, PDO::PARAM_STR);

        if ($stmt->execute()) {
            header("Location: manage_leagues.php?status=league_added");
        } else {
            header("Location: manage_leagues.php?status=league_add_error&reason=execute_failed");
        }
        exit;
    } catch (PDOException $e) {
        // Check if it's a unique constraint violation (code 23000) for 'name'
        if ($e->getCode() == '23000' && strpos($e->getMessage(), "Duplicate entry") !== false && strpos($e->getMessage(), "for key 'leagues.name'") !== false) {
             header("Location: manage_leagues.php?status=league_add_error&reason=league_name_exists");
        } else {
            // error_log("PDOException in add_league.php: " . $e->getMessage());
            // For other PDO errors, you might still want a generic message or a code
            header("Location: manage_leagues.php?status=league_add_error&reason=database_error_" . $e->getCode());
        }
        exit;
    }
} else {
    header("Location: manage_leagues.php");
    exit;
}
?>
