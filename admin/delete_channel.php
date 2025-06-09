<?php
session_start();
require_once '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["channel_id"]))) {
        header("Location: manage_channels.php?status=channel_delete_error&reason=missing_id");
        exit;
    }

    $channel_id = trim($_POST["channel_id"]);

    if (!filter_var($channel_id, FILTER_VALIDATE_INT)) {
        header("Location: manage_channels.php?status=channel_delete_error&reason=invalid_id");
        exit;
    }

    try {
        $sql = "DELETE FROM tv_channels WHERE id = :channel_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":channel_id", $channel_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                header("Location: manage_channels.php?status=channel_deleted");
            } else {
                header("Location: manage_channels.php?status=channel_delete_error&reason=not_found");
            }
            exit;
        } else {
            header("Location: manage_channels.php?status=channel_delete_error&reason=execute_failed");
            exit;
        }
    } catch (PDOException $e) {
        // error_log("PDOException in delete_channel.php: " . $e->getMessage());
        header("Location: manage_channels.php?status=channel_delete_error&reason=pdo_exception_" . $e->getCode());
        exit;
    }
} else {
    header("Location: manage_channels.php");
    exit;
}
?>
