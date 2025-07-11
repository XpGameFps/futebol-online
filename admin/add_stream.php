<?php
require_once 'auth_check.php'; require_once __DIR__ . '/../../FutOnline_config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!function_exists('validate_csrf_token')) {         require_once 'csrf_utils.php';
    }
        if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $match_id_str_csrf_err = trim($_POST["match_id"] ?? '');
        $_SESSION['form_error_message']['add_stream_general'] = "Falha na verificação de segurança (CSRF) ao adicionar stream. Por favor, tente novamente.";
        if (!empty($match_id_str_csrf_err) && filter_var($match_id_str_csrf_err, FILTER_VALIDATE_INT)) {
             header("Location: index.php#match-" . $match_id_str_csrf_err);
        } else {
             header("Location: index.php");
        }
        exit;
    }
        $match_id_str = trim($_POST["match_id"] ?? '');     $stream_label = trim($_POST["stream_label"] ?? '');
    $stream_url_manual = trim($_POST["stream_url"] ?? '');
    $saved_stream_id = trim($_POST["saved_stream_id"] ?? '');

    $save_to_library = isset($_POST['save_to_library']);
    $library_stream_name = trim($_POST['library_stream_name'] ?? '');
        $is_manual_entry_key = "is_manual_entry_" . $match_id_str;
    $is_manual_entry = ($_POST[$is_manual_entry_key] ?? 'true') === 'true';

    $final_stream_url = '';

        if (empty($match_id_str) || !filter_var($match_id_str, FILTER_VALIDATE_INT)) {
        $_SESSION['form_error_message']['add_stream_general'] = "ID do jogo inválido ou ausente ao tentar adicionar stream.";
        header("Location: index.php");
        exit;
    }
    $match_id = (int)$match_id_str; 
        $_SESSION['form_data']['add_stream'][$match_id] = $_POST;


    if (empty($stream_label)) {
        $_SESSION['form_error_message']['add_stream'][$match_id] = "Rótulo do Stream é obrigatório.";
        header("Location: index.php#match-" . $match_id); exit;
    }

    if (!empty($saved_stream_id) && filter_var($saved_stream_id, FILTER_VALIDATE_INT) && !$is_manual_entry) {
                try {
            $stmt_get_saved = $pdo->prepare("SELECT stream_url_value FROM saved_stream_urls WHERE id = :id");
            $stmt_get_saved->bindParam(':id', $saved_stream_id, PDO::PARAM_INT);
            $stmt_get_saved->execute();
            $saved_url_data = $stmt_get_saved->fetch(PDO::FETCH_ASSOC);
            if ($saved_url_data) {
                $final_stream_url = $saved_url_data['stream_url_value'];
            } else {
                $_SESSION['form_error_message']['add_stream'][$match_id] = "Stream salvo selecionado não encontrado na biblioteca.";
                header("Location: index.php#match-" . $match_id); exit;
            }
        } catch (PDOException $e) {
            error_log("PDOException in " . __FILE__ . " (fetching saved stream ID " . $saved_stream_id . " for match " . $match_id . "): " . $e->getMessage());
            $_SESSION['form_error_message']['add_stream'][$match_id] = "Ocorreu um erro no banco de dados ao buscar o stream da biblioteca. Por favor, tente novamente.";
            header("Location: index.php#match-" . $match_id); exit;
        }
    } else {
                $final_stream_url = $stream_url_manual;
        if (empty($final_stream_url)) {
            $_SESSION['form_error_message']['add_stream'][$match_id] = "URL do Stream é obrigatória se não selecionar da biblioteca.";
            header("Location: index.php#match-" . $match_id); exit;
        }
        if (!filter_var($final_stream_url, FILTER_VALIDATE_URL)) {
            $_SESSION['form_error_message']['add_stream'][$match_id] = "URL do Stream (manual) inválida.";
            header("Location: index.php#match-" . $match_id); exit;
        }
    }

    if ($is_manual_entry && $save_to_library) {
        if (empty($library_stream_name)) {
            $_SESSION['form_error_message']['add_stream'][$match_id] = "Nome para Biblioteca é obrigatório ao salvar nova URL.";
            header("Location: index.php#match-" . $match_id); exit;
        }
        try {
            $stmt_check_lib = $pdo->prepare("SELECT id FROM saved_stream_urls WHERE stream_name = :name");
            $stmt_check_lib->bindParam(':name', $library_stream_name, PDO::PARAM_STR);
            $stmt_check_lib->execute();
            if ($stmt_check_lib->rowCount() == 0) {
                $sql_save_lib = "INSERT INTO saved_stream_urls (stream_name, stream_url_value) VALUES (:name, :url)";
                $stmt_save_lib = $pdo->prepare($sql_save_lib);
                $stmt_save_lib->bindParam(':name', $library_stream_name, PDO::PARAM_STR);
                $stmt_save_lib->bindParam(':url', $final_stream_url, PDO::PARAM_STR);
                $stmt_save_lib->execute();
            } else {
                                            }
        } catch (PDOException $e) {
            error_log("PDOException in " . __FILE__ . " (saving stream to library for match " . $match_id . "): " . $e->getMessage());
                        $existing_error = $_SESSION['form_error_message']['add_stream'][$match_id] ?? '';
            $_SESSION['form_error_message']['add_stream'][$match_id] = trim($existing_error . " (Aviso: Falha ao tentar salvar o stream na biblioteca. Verifique se já existe um com o mesmo nome.)");
        }
    }

    try {
        $stmt_check_match = $pdo->prepare("SELECT id FROM matches WHERE id = :match_id");
        $stmt_check_match->bindParam(":match_id", $match_id, PDO::PARAM_INT);
        $stmt_check_match->execute();
        if ($stmt_check_match->rowCount() == 0) {
            $_SESSION['form_error_message']['add_stream_general'] = "Jogo não encontrado ao tentar adicionar stream.";
            header("Location: index.php"); exit;
        }

        $sql_add = "INSERT INTO streams (match_id, stream_url, stream_label) VALUES (:match_id, :stream_url, :stream_label)";
        $stmt_add = $pdo->prepare($sql_add);
        $stmt_add->bindParam(":match_id", $match_id, PDO::PARAM_INT);
        $stmt_add->bindParam(":stream_url", $final_stream_url, PDO::PARAM_STR);
        $stmt_add->bindParam(":stream_label", $stream_label, PDO::PARAM_STR);

        if ($stmt_add->execute()) {
            unset($_SESSION['form_data']['add_stream'][$match_id]);
                                    if(strpos($_SESSION['form_error_message']['add_stream'][$match_id] ?? '', 'Aviso:') === false) {
                 unset($_SESSION['form_error_message']['add_stream'][$match_id]);
            }
            $redirect_status = isset($_SESSION['form_error_message']['add_stream'][$match_id]) ? "stream_added_with_library_warning" : "stream_added";

            header("Location: index.php?status=".$redirect_status."#match-" . $match_id);
        } else {
            $_SESSION['form_error_message']['add_stream'][$match_id] = "Erro ao adicionar stream ao jogo.";
            header("Location: index.php#match-" . $match_id);
        }
        exit;
    } catch (PDOException $e) {
        error_log("PDOException in " . __FILE__ . " (adding stream to match " . $match_id . "): " . $e->getMessage());
        $_SESSION['form_error_message']['add_stream'][$match_id] = "Ocorreu um erro no banco de dados ao adicionar o stream ao jogo. Por favor, tente novamente.";
        header("Location: index.php#match-" . $match_id);
        exit;
    }
} else {
    if(isset($_SESSION['form_error_message']['add_stream_general'])) unset($_SESSION['form_error_message']['add_stream_general']);
    header("Location: index.php");
    exit;
}
?>

