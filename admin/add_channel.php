<?php
require_once 'auth_check.php'; // Session start and login check
require_once '../config.php';

// Define target directory for channel logos (relative to this script's location)
define('CHANNEL_LOGO_UPLOAD_DIR', '../uploads/logos/channels/');
define('MAX_FILE_SIZE', 1024 * 1024); // 1MB
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"] ?? '');
    $stream_url = trim($_POST["stream_url"] ?? '');
    $sort_order = trim($_POST["sort_order"] ?? '0');
    $logo_filename_to_save = null;
    $upload_error_message = '';

    // Basic field validation
    if (empty($name) || empty($stream_url)) {
        header("Location: manage_channels.php?status=channel_add_error&reason=empty_fields");
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

    // --- File Upload Handling ---
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['logo_file']['tmp_name'];
        $file_name = $_FILES['logo_file']['name'];
        $file_size = $_FILES['logo_file']['size'];
        $file_type = $_FILES['logo_file']['type'];
        $file_ext_array = explode('.', $file_name);
        $file_extension = strtolower(end($file_ext_array));

        if ($file_size > MAX_FILE_SIZE) {
            $upload_error_message = 'Arquivo muito grande. Máximo 1MB.';
        } elseif (!in_array($file_type, $allowed_mime_types)) {
            $upload_error_message = 'Tipo de arquivo inválido. Apenas PNG, JPG, GIF são permitidos.';
        } else {
            $new_file_name = uniqid('channel_', true) . '.' . $file_extension;
            $destination_path = CHANNEL_LOGO_UPLOAD_DIR . $new_file_name;

            if (!is_dir(CHANNEL_LOGO_UPLOAD_DIR)) {
                if (!mkdir(CHANNEL_LOGO_UPLOAD_DIR, 0755, true)) {
                     $upload_error_message = 'Falha ao criar diretório de uploads para canais.';
                }
            }

            if (empty($upload_error_message) && move_uploaded_file($file_tmp_path, $destination_path)) {
                $logo_filename_to_save = $new_file_name;
            } else {
                 if(empty($upload_error_message)) $upload_error_message = 'Falha ao mover arquivo upado do canal.';
            }
        }
    } elseif (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['logo_file']['error'] != UPLOAD_ERR_OK) {
        $upload_error_message = 'Erro no upload do arquivo do canal. Código: ' . $_FILES['logo_file']['error'];
    }
    // --- End File Upload Handling ---

    if (!empty($upload_error_message)) {
        header("Location: manage_channels.php?status=channel_add_error&reason=file_upload_error&err_msg=" . urlencode($upload_error_message));
        exit;
    }

    try {
        // Save to database
        // Assuming 'logo_filename' is the column name after update_schema_v3.sql
        $sql = "INSERT INTO tv_channels (name, logo_filename, stream_url, sort_order) VALUES (:name, :logo_filename, :stream_url, :sort_order)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":name", $name, PDO::PARAM_STR);
        $stmt->bindParam(":stream_url", $stream_url, PDO::PARAM_STR);
        $stmt->bindParam(":sort_order", $sort_order, PDO::PARAM_INT);

        if ($logo_filename_to_save === null) {
            $stmt->bindValue(":logo_filename", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":logo_filename", $logo_filename_to_save, PDO::PARAM_STR);
        }

        if ($stmt->execute()) {
            header("Location: manage_channels.php?status=channel_added");
        } else {
            if ($logo_filename_to_save && file_exists(CHANNEL_LOGO_UPLOAD_DIR . $logo_filename_to_save)) {
                // unlink(CHANNEL_LOGO_UPLOAD_DIR . $logo_filename_to_save); // Rollback
            }
            header("Location: manage_channels.php?status=channel_add_error&reason=db_insert_failed");
        }
        exit;

    } catch (PDOException $e) {
        if ($logo_filename_to_save && file_exists(CHANNEL_LOGO_UPLOAD_DIR . $logo_filename_to_save)) {
            // unlink(CHANNEL_LOGO_UPLOAD_DIR . $logo_filename_to_save); // Rollback
        }
        // error_log("PDOException in add_channel.php: " . $e->getMessage());
        header("Location: manage_channels.php?status=channel_add_error&reason=database_error_" . $e->getCode());
        exit;
    }
} else {
    header("Location: manage_channels.php");
    exit;
}
?>
