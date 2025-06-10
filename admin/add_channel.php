<?php
// admin/add_channel.php
require_once 'auth_check.php'; // Handles session_start()
require_once '../config.php';

define('CHANNEL_LOGO_UPLOAD_DIR', '../uploads/logos/channels/');
define('MAX_FILE_SIZE', 1024 * 1024); // 1MB
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF token validation
    if (!function_exists('validate_csrf_token')) {
        require_once 'csrf_utils.php';
    }
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['form_error_message']['add_channel'] = "Falha na verificação de segurança (CSRF). Por favor, tente novamente.";
        header("Location: manage_channels.php#add-channel-form");
        exit;
    }

    $_SESSION['form_data']['add_channel'] = $_POST;
    if (isset($_FILES['logo_file']) && !empty($_FILES['logo_file']['name'])) {
        $_SESSION['form_data']['add_channel']['logo_filename_tmp'] = $_FILES['logo_file']['name'];
    }

    $name = trim($_POST["name"] ?? '');
    $stream_url_manual = trim($_POST["stream_url"] ?? '');
    $sort_order_input = trim($_POST["sort_order"] ?? '0');
    $meta_description = trim($_POST['meta_description'] ?? null);
    $meta_keywords = trim($_POST['meta_keywords'] ?? null);

    $saved_stream_id = trim($_POST["saved_stream_id"] ?? '');
    $save_to_library = isset($_POST['save_to_library']);
    $library_stream_name = trim($_POST['library_stream_name'] ?? '');
    $is_manual_entry = ($_POST["is_manual_entry_channel_add"] ?? 'true') === 'true';

    $final_stream_url = '';
    $logo_filename_to_save = null;
    $upload_error_message = '';

    // Basic field validation
    if (empty($name)) { // stream_url is validated after determining if it's manual or from library
        $_SESSION['form_error_message']['add_channel'] = "Nome do canal é obrigatório.";
        header("Location: manage_channels.php#add-channel-form"); exit;
    }
    if (!is_numeric($sort_order_input)) {
        $_SESSION['form_error_message']['add_channel'] = "Ordem de classificação deve ser um número.";
        header("Location: manage_channels.php#add-channel-form"); exit;
    }
    $sort_order = (int)$sort_order_input;

    // Determine Final Stream URL
    if (!empty($saved_stream_id) && filter_var($saved_stream_id, FILTER_VALIDATE_INT) && !$is_manual_entry) {
        try {
            $stmt_get_saved = $pdo->prepare("SELECT stream_url_value FROM saved_stream_urls WHERE id = :id");
            $stmt_get_saved->bindParam(':id', $saved_stream_id, PDO::PARAM_INT);
            $stmt_get_saved->execute();
            $saved_url_data = $stmt_get_saved->fetch(PDO::FETCH_ASSOC);
            if ($saved_url_data) {
                $final_stream_url = $saved_url_data['stream_url_value'];
            } else {
                $_SESSION['form_error_message']['add_channel'] = "Stream salvo selecionado não encontrado.";
                header("Location: manage_channels.php#add-channel-form"); exit;
            }
        } catch (PDOException $e) {
            $_SESSION['form_error_message']['add_channel'] = "Erro ao buscar stream da biblioteca: " . $e->getMessage();
            header("Location: manage_channels.php#add-channel-form"); exit;
        }
    } else {
        $final_stream_url = $stream_url_manual;
        if (empty($final_stream_url)) {
            $_SESSION['form_error_message']['add_channel'] = "URL do Stream é obrigatória se não selecionar da biblioteca.";
            header("Location: manage_channels.php#add-channel-form"); exit;
        }
        if (!filter_var($final_stream_url, FILTER_VALIDATE_URL)) {
            $_SESSION['form_error_message']['add_channel'] = "URL do Stream (manual) inválida.";
            header("Location: manage_channels.php#add-channel-form"); exit;
        }
    }

    // Save to library if manual entry and checkbox checked
    if ($is_manual_entry && $save_to_library) {
        if (empty($library_stream_name)) {
            $_SESSION['form_error_message']['add_channel'] = "Nome para Biblioteca é obrigatório ao salvar nova URL.";
            header("Location: manage_channels.php#add-channel-form"); exit;
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
            } // else: Name conflict, silently don't save or add specific warning
        } catch (PDOException $e) { /* Silently fail saving to library or add specific error to session message later */ }
    }

    // --- Logo File Upload Handling ---
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['logo_file']['tmp_name'];
        $file_name = $_FILES['logo_file']['name'];
        $file_size = $_FILES['logo_file']['size'];
        $file_type = $_FILES['logo_file']['type'];
        $file_ext_array = explode('.', $file_name);
        $file_extension = strtolower(end($file_ext_array));

        if ($file_size > MAX_FILE_SIZE) { $upload_error_message = 'Arquivo muito grande (max 1MB).'; }
        elseif (!in_array($file_type, $allowed_mime_types)) { $upload_error_message = 'Tipo de arquivo inválido (PNG, JPG, GIF).'; }
        else {
            // getimagesize check
            $image_info = @getimagesize($file_tmp_path);
            if ($image_info === false) {
                $upload_error_message = 'Arquivo inválido. Conteúdo não reconhecido como imagem.';
            } else {
                // Proceed with move_uploaded_file only if getimagesize passed
                $new_file_name = uniqid('channel_', true) . '.' . $file_extension;
                $destination_path = CHANNEL_LOGO_UPLOAD_DIR . $new_file_name;
                if (!is_dir(CHANNEL_LOGO_UPLOAD_DIR)) { @mkdir(CHANNEL_LOGO_UPLOAD_DIR, 0755, true); }
                if (move_uploaded_file($file_tmp_path, $destination_path)) {
                    $logo_filename_to_save = $new_file_name;
                    if(isset($_SESSION['form_data']['add_channel']['logo_filename_tmp'])) {
                        unset($_SESSION['form_data']['add_channel']['logo_filename_tmp']);
                    }
                } else { $upload_error_message = 'Falha ao mover arquivo de logo do canal.'; }
            }
        }
    } elseif (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['logo_file']['error'] != UPLOAD_ERR_OK) {
        $upload_error_message = 'Erro no upload do logo do canal. Código: ' . $_FILES['logo_file']['error'];
    }

    if (!empty($upload_error_message)) {
        $_SESSION['form_error_message']['add_channel'] = $upload_error_message;
        header("Location: manage_channels.php#add-channel-form");
        exit;
    }
    // --- End Logo File Upload Handling ---

    try {
        $sql = "INSERT INTO tv_channels (name, logo_filename, stream_url, sort_order, meta_description, meta_keywords)
                VALUES (:name, :logo_filename, :stream_url, :sort_order, :meta_description, :meta_keywords)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":name", $name, PDO::PARAM_STR);
        $stmt->bindParam(":stream_url", $final_stream_url, PDO::PARAM_STR);
        $stmt->bindParam(":sort_order", $sort_order, PDO::PARAM_INT);

        if ($logo_filename_to_save === null) { $stmt->bindValue(":logo_filename", null, PDO::PARAM_NULL); }
        else { $stmt->bindParam(":logo_filename", $logo_filename_to_save, PDO::PARAM_STR); }

        if ($meta_description === null) { $stmt->bindValue(":meta_description", null, PDO::PARAM_NULL); }
        else { $stmt->bindParam(":meta_description", $meta_description, PDO::PARAM_STR); }
        if ($meta_keywords === null) { $stmt->bindValue(":meta_keywords", null, PDO::PARAM_NULL); }
        else { $stmt->bindParam(":meta_keywords", $meta_keywords, PDO::PARAM_STR); }

        if ($stmt->execute()) {
            unset($_SESSION['form_data']['add_channel']);
            unset($_SESSION['form_error_message']['add_channel']);
            header("Location: manage_channels.php?status=channel_added");
        } else {
            $_SESSION['form_error_message']['add_channel'] = "Erro ao adicionar canal no banco de dados.";
            if ($logo_filename_to_save && file_exists(CHANNEL_LOGO_UPLOAD_DIR . $logo_filename_to_save)) {
                 @unlink(CHANNEL_LOGO_UPLOAD_DIR . $logo_filename_to_save);
                 // $_SESSION['form_data']['add_channel']['logo_filename_tmp'] would have been unset if upload was "successful"
                 // but if it failed before DB, it might still be there. This ensures it's gone if file is unlinked.
                 if(isset($_SESSION['form_data']['add_channel']['logo_filename_tmp'])) unset($_SESSION['form_data']['add_channel']['logo_filename_tmp']);
            }
            header("Location: manage_channels.php#add-channel-form");
        }
        exit;
    } catch (PDOException $e) {
        $_SESSION['form_error_message']['add_channel'] = "Erro de BD: " . $e->getMessage();
        if ($logo_filename_to_save && file_exists(CHANNEL_LOGO_UPLOAD_DIR . $logo_filename_to_save)) {
             @unlink(CHANNEL_LOGO_UPLOAD_DIR . $logo_filename_to_save);
             if(isset($_SESSION['form_data']['add_channel']['logo_filename_tmp'])) unset($_SESSION['form_data']['add_channel']['logo_filename_tmp']);
        }
        header("Location: manage_channels.php#add-channel-form");
        exit;
    }
} else {
    // Clear stale form data if accessed via GET
    if(isset($_SESSION['form_data']['add_channel'])) unset($_SESSION['form_data']['add_channel']);
    if(isset($_SESSION['form_error_message']['add_channel'])) unset($_SESSION['form_error_message']['add_channel']);
    header("Location: manage_channels.php");
    exit;
}
?>
