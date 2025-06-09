<?php
session_start();
require_once '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"] ?? '');
    $logo_url = !empty(trim($_POST["logo_url"])) ? trim($_POST["logo_url"]) : null;
    $stream_url = trim($_POST["stream_url"] ?? '');
    $sort_order = trim($_POST["sort_order"] ?? '0');

    if (empty($name) || empty($stream_url)) {
        header("Location: manage_channels.php?status=channel_add_error&reason=empty_fields");
        exit;
    }
    if ($logo_url && !filter_var($logo_url, FILTER_VALIDATE_URL)) {
        header("Location: manage_channels.php?status=channel_add_error&reason=invalid_logo_url");
        exit;
    }
    if (!filter_var($stream_url, FILTER_VALIDATE_URL)) {
        header("Location: manage_channels.php?status=channel_add_error&reason=invalid_stream_url");
        exit;
    }
    if (!is_numeric($sort_order)) {
        header("Location: manage_channels.php?status=channel_add_error&reason=invalid_sort_order");
        exit;
    }
    $sort_order = (int)$sort_order;

    try {
        $sql = "INSERT INTO tv_channels (name, logo_url, stream_url, sort_order) VALUES (:name, :logo_url, :stream_url, :sort_order)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":name", $name, PDO::PARAM_STR);
        $stmt->bindParam(":logo_url", $logo_url, PDO::PARAM_STR);
        $stmt->bindParam(":stream_url", $stream_url, PDO::PARAM_STR);
        $stmt->bindParam(":sort_order", $sort_order, PDO::PARAM_INT);

        if ($stmt->execute()) {
            header("Location: manage_channels.php?status=channel_added");
        } else {
            header("Location: manage_channels.php?status=channel_add_error&reason=execute_failed");
        }
        exit;
    } catch (PDOException $e) {
        // error_log("PDOException in add_channel.php: " . $e->getMessage());
        header("Location: manage_channels.php?status=channel_add_error&reason=pdo_exception_" . $e->getCode());
        exit;
    }
} else {
    header("Location: manage_channels.php");
    exit;
}
?>
