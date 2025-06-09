<?php
session_start();
require_once '../config.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["match_id"])) || empty(trim($_POST["stream_url"])) || empty(trim($_POST["stream_label"]))) {
        header("Location: index.php?status=stream_add_error&reason=missing_fields");
        exit;
    }

    $match_id = trim($_POST["match_id"]);
    $stream_url = trim($_POST["stream_url"]);
    $stream_label = trim($_POST["stream_label"]);

    // Validate match_id is an integer
    if (!filter_var($match_id, FILTER_VALIDATE_INT)) {
        header("Location: index.php?status=stream_add_error&reason=invalid_match_id");
        exit;
    }

    // Validate stream_url (basic validation)
    if (!filter_var($stream_url, FILTER_VALIDATE_URL)) {
        header("Location: index.php?status=stream_add_error&reason=invalid_url&match_id=" . htmlspecialchars($match_id));
        exit;
    }

    try {
        // Check if match_id exists
        $checkSql = "SELECT id FROM matches WHERE id = :match_id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->bindParam(":match_id", $match_id, PDO::PARAM_INT);
        $checkStmt->execute();
        if ($checkStmt->rowCount() == 0) {
            header("Location: index.php?status=stream_add_error&reason=match_not_found&match_id=" . htmlspecialchars($match_id));
            exit;
        }

        $sql = "INSERT INTO streams (match_id, stream_url, stream_label) VALUES (:match_id, :stream_url, :stream_label)";

        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":match_id", $match_id, PDO::PARAM_INT);
            $stmt->bindParam(":stream_url", $stream_url, PDO::PARAM_STR);
            $stmt->bindParam(":stream_label", $stream_label, PDO::PARAM_STR);

            if ($stmt->execute()) {
                header("Location: index.php?status=stream_added#match-" . htmlspecialchars($match_id)); // Redirect to the specific match area
                exit;
            } else {
                header("Location: index.php?status=stream_add_error&reason=execute_failed&match_id=" . htmlspecialchars($match_id));
                exit;
            }
        }
        unset($stmt);
    } catch (PDOException $e) {
        // error_log("PDOException in add_stream.php: " . $e->getMessage());
        header("Location: index.php?status=stream_add_error&reason=pdo_exception&match_id=" . htmlspecialchars($match_id));
        exit;
    }

    unset($pdo);
} else {
    header("Location: index.php");
    exit;
}
?>
