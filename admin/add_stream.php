<?php
// admin/add_stream.php
require_once 'auth_check.php'; // Handles session_start()
require_once '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $match_id = trim($_POST["match_id"] ?? '');
    $stream_url = trim($_POST["stream_url"] ?? '');
    $stream_label = trim($_POST["stream_label"] ?? '');

    // Store POST data in session, keyed by match_id for this form context
    if (!empty($match_id) && filter_var($match_id, FILTER_VALIDATE_INT)) {
        $_SESSION['form_data']['add_stream'][(int)$match_id] = $_POST;
    }

    // Basic Validations
    if (empty($match_id) || !filter_var($match_id, FILTER_VALIDATE_INT)) {
        $_SESSION['form_error_message']['add_stream_general'] = "ID do jogo inválido ou ausente ao tentar adicionar stream.";
        // Cannot redirect to a specific match anchor if match_id is invalid
        header("Location: index.php");
        exit;
    }
    // Ensure $match_id is an integer for array key usage
    $match_id = (int)$match_id;

    if (empty($stream_url) || empty($stream_label)) {
        $_SESSION['form_error_message']['add_stream'][$match_id] = "URL do Stream e Rótulo são obrigatórios.";
        header("Location: index.php#match-" . $match_id);
        exit;
    }
    if (!filter_var($stream_url, FILTER_VALIDATE_URL)) {
        $_SESSION['form_error_message']['add_stream'][$match_id] = "URL do Stream inválida.";
        header("Location: index.php#match-" . $match_id);
        exit;
    }

    try {
        // Check if match_id exists
        $checkSql = "SELECT id FROM matches WHERE id = :match_id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->bindParam(":match_id", $match_id, PDO::PARAM_INT);
        $checkStmt->execute();
        if ($checkStmt->rowCount() == 0) {
            $_SESSION['form_error_message']['add_stream'][$match_id] = "Jogo não encontrado para associar o stream.";
            header("Location: index.php#match-" . $match_id);
            exit;
        }

        $sql = "INSERT INTO streams (match_id, stream_url, stream_label) VALUES (:match_id, :stream_url, :stream_label)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":match_id", $match_id, PDO::PARAM_INT);
        $stmt->bindParam(":stream_url", $stream_url, PDO::PARAM_STR);
        $stmt->bindParam(":stream_label", $stream_label, PDO::PARAM_STR);

        if ($stmt->execute()) {
            unset($_SESSION['form_data']['add_stream'][$match_id]);
            unset($_SESSION['form_error_message']['add_stream'][$match_id]);
            header("Location: index.php?status=stream_added#match-" . $match_id);
        } else {
            $_SESSION['form_error_message']['add_stream'][$match_id] = "Erro ao adicionar stream no banco de dados.";
            header("Location: index.php#match-" . $match_id);
        }
        exit;
    } catch (PDOException $e) {
        $_SESSION['form_error_message']['add_stream'][$match_id] = "Erro de BD: " . $e->getMessage();
        header("Location: index.php#match-" . $match_id);
        exit;
    }
} else {
    // Not a POST request, redirect to admin index. Clear any general add_stream error.
    if(isset($_SESSION['form_error_message']['add_stream_general'])) unset($_SESSION['form_error_message']['add_stream_general']);
    header("Location: index.php");
    exit;
}
?>
