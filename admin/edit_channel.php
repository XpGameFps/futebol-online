<?php
require_once 'auth_check.php';
require_once '../config.php';

define('CHANNEL_LOGO_UPLOAD_DIR', '../uploads/logos/channels/'); // Relative to current admin folder
define('MAX_FILE_SIZE', 1024 * 1024); // 1MB
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

$page_title = "Editar Canal de TV";
$message = '';
$channel_id = null;
$channel_name = '';
$current_logo_filename = null;
$stream_url = '';
$sort_order = 0;
$meta_description = '';
$meta_keywords = '';

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $channel_id = (int)$_GET['id'];
} else if (isset($_POST['channel_id']) && filter_var($_POST['channel_id'], FILTER_VALIDATE_INT)) {
    $channel_id = (int)$_POST['channel_id'];
}

if ($channel_id === null) {
    header("Location: manage_channels.php?status=edit_error&reason=invalid_id");
    exit;
}

// Fetch current channel data
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['update_channel'])) {
    try {
        $stmt_fetch = $pdo->prepare("SELECT name, logo_filename, stream_url, sort_order, meta_description, meta_keywords FROM tv_channels WHERE id = :id");
        $stmt_fetch->bindParam(':id', $channel_id, PDO::PARAM_INT); // Ensure $channel_id is defined from GET/POST
        $stmt_fetch->execute();
        $channel = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

        if ($channel) {
            $channel_name = $channel['name'];
            $current_logo_filename = $channel['logo_filename'];
            $stream_url = $channel['stream_url'];
            $sort_order = $channel['sort_order'];
            // Assign new SEO fields
            $meta_description = $channel['meta_description'];
            $meta_keywords = $channel['meta_keywords'];
        } else { header("Location: manage_channels.php?status=edit_error&reason=not_found"); exit; }
    } catch (Exception $e) { $message = '<p style="color:red;">Erro ao buscar dados do canal: ' . $e->getMessage() . '</p>'; }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_channel'])) {
    $new_channel_name = trim($_POST['name'] ?? '');
    $new_stream_url = trim($_POST['stream_url'] ?? '');
    $new_sort_order_input = trim($_POST['sort_order'] ?? '0');
    $new_logo_filename_to_save = $current_logo_filename;
    $upload_error_message = '';
    // Retrieve new SEO fields
    $new_meta_description = trim($_POST['meta_description'] ?? null);
    $new_meta_keywords = trim($_POST['meta_keywords'] ?? null);

    if (empty($new_channel_name) || empty($new_stream_url)) {
        $message = '<p style="color:red;">Nome do canal e URL do stream são obrigatórios.</p>';
    } elseif (!filter_var($new_stream_url, FILTER_VALIDATE_URL)) {
        $message = '<p style="color:red;">URL do stream inválida.</p>';
    } elseif (!is_numeric($new_sort_order_input)) {
        $message = '<p style="color:red;">Ordem de classificação deve ser um número.</p>';
    } else {
        $new_sort_order = (int)$new_sort_order_input;

        // File Upload Handling
        // This logic needs to be fully present. Condensed for brevity in prompt.
        // Re-fetch $current_logo_filename if not set from GET
        if ($current_logo_filename === null && $channel_id) {
            $stmt_refetch_logo_edit = $pdo->prepare("SELECT logo_filename FROM tv_channels WHERE id = :id");
            $stmt_refetch_logo_edit->bindParam(':id', $channel_id, PDO::PARAM_INT);
            $stmt_refetch_logo_edit->execute();
            $logo_data_edit = $stmt_refetch_logo_edit->fetch(PDO::FETCH_ASSOC);
            if ($logo_data_edit) $current_logo_filename = $logo_data_edit['logo_filename'];
        }
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['logo_file']['tmp_name'];
            // ... (Identical file upload logic as in add_channel.php, including old file deletion)
            $file_name = $_FILES['logo_file']['name'];
            $file_size = $_FILES['logo_file']['size'];
            $file_type = $_FILES['logo_file']['type'];
            $file_ext_array = explode('.', $file_name);
            $file_extension = strtolower(end($file_ext_array));

            if ($file_size > MAX_FILE_SIZE) { $upload_error_message = 'Arquivo muito grande (max 1MB).'; }
            elseif (!in_array($file_type, $allowed_mime_types)) { $upload_error_message = 'Tipo de arquivo inválido (PNG, JPG, GIF).'; }
            else {
                $new_uploaded_filename = uniqid('channel_', true) . '.' . $file_extension;
                $destination_path = CHANNEL_LOGO_UPLOAD_DIR . $new_uploaded_filename;
                if (!is_dir(CHANNEL_LOGO_UPLOAD_DIR)) { @mkdir(CHANNEL_LOGO_UPLOAD_DIR, 0755, true); }
                if (move_uploaded_file($file_tmp_path, $destination_path)) {
                    if ($current_logo_filename && file_exists(CHANNEL_LOGO_UPLOAD_DIR . $current_logo_filename)) {
                        @unlink(CHANNEL_LOGO_UPLOAD_DIR . $current_logo_filename);
                    }
                    $new_logo_filename_to_save = $new_uploaded_filename;
                } else { $upload_error_message = 'Falha ao mover novo arquivo de logo.'; }
            }
            if (!empty($upload_error_message)) { $message = '<p style="color:red;">Erro no upload: ' . $upload_error_message . '</p>'; }
        } elseif (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['logo_file']['error'] != UPLOAD_ERR_OK) {
            $message = '<p style="color:red;">Erro no upload. Código: ' . $_FILES['logo_file']['error'] . '</p>';
        }
        // End File Upload Handling

        if (empty($message) || (!empty($message) && empty($upload_error_message) && isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == UPLOAD_ERR_NO_FILE) ) {
            // Update database
            try {
                $sql_update = "UPDATE tv_channels SET
                                name = :name, logo_filename = :logo_filename, stream_url = :stream_url,
                                sort_order = :sort_order, meta_description = :meta_description, meta_keywords = :meta_keywords
                              WHERE id = :id";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->bindParam(':name', $new_channel_name, PDO::PARAM_STR);
                $stmt_update->bindParam(':stream_url', $new_stream_url, PDO::PARAM_STR);
                $stmt_update->bindParam(':sort_order', $new_sort_order, PDO::PARAM_INT);
                $stmt_update->bindParam(':id', $channel_id, PDO::PARAM_INT);

                if ($new_logo_filename_to_save === null) { $stmt_update->bindValue(':logo_filename', null, PDO::PARAM_NULL); }
                else { $stmt_update->bindParam(':logo_filename', $new_logo_filename_to_save, PDO::PARAM_STR); }

                // Bind new SEO params
                if ($new_meta_description === null) { $stmt_update->bindValue(":meta_description", null, PDO::PARAM_NULL); }
                else { $stmt_update->bindParam(":meta_description", $new_meta_description, PDO::PARAM_STR); }
                if ($new_meta_keywords === null) { $stmt_update->bindValue(":meta_keywords", null, PDO::PARAM_NULL); }
                else { $stmt_update->bindParam(":meta_keywords", $new_meta_keywords, PDO::PARAM_STR); }

                if ($stmt_update->execute()) {
                    $current_logo_filename = $new_logo_filename_to_save;
                    $meta_description = $new_meta_description; // Update for form
                    $meta_keywords = $new_meta_keywords; // Update for form
                    $channel_name = $new_channel_name;
                    $stream_url = $new_stream_url;
                    $sort_order = $new_sort_order;
                    $message = '<p style="color:green;">Canal de TV atualizado com sucesso! <a href="manage_channels.php">Voltar para Canais</a></p>';
                } else { $message = '<p style="color:red;">Erro ao atualizar canal no banco de dados.</p>'; }
            } catch (PDOException $e) { $message = '<p style="color:red;">Erro de banco de dados: ' . $e->getMessage() . '</p>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - Painel Admin</title>
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>
    <div class="container">
        <nav>
             <div>
                <a href="index.php">Painel Principal (Jogos)</a>
                <a href="manage_leagues.php">Gerenciar Ligas</a>
                <a href="manage_channels.php">Gerenciar Canais TV</a>
                <a href="manage_settings.php">Configurações</a>
            </div>
            <div class="nav-user-info">
                Usuário: <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?> |
                <a href="logout.php" class="logout-link">Logout</a>
            </div>
        </nav>
        <h1><?php echo htmlspecialchars($page_title); ?></h1>

        <?php if(!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($channel_id && $channel): ?>
        <form action="edit_channel.php?id=<?php echo $channel_id; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="channel_id" value="<?php echo $channel_id; ?>">
            <div>
                <label for="name">Nome do Canal:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($channel_name); ?>" required>
            </div>
            <div>
                <label for="stream_url">URL do Stream Principal:</label>
                <input type="url" id="stream_url" name="stream_url" value="<?php echo htmlspecialchars($stream_url); ?>" required>
            </div>
            <div>
                <label for="sort_order">Ordem de Classificação:</label>
                <input type="number" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($sort_order); ?>" required>
            </div>
            <div>
                <label for="logo_file">Logo do Canal (PNG, JPG, GIF, max 1MB):</label>
                <?php if ($current_logo_filename): ?>
                    <p>Logo Atual: <img src="<?php echo CHANNEL_LOGO_UPLOAD_DIR . htmlspecialchars($current_logo_filename); ?>" alt="Logo Atual" style="max-height: 50px; vertical-align: middle; margin-bottom:5px; border:1px solid #eee;"></p>
                    <p style="font-size:0.8em; color:#555;">Envie um novo arquivo para substituir.</p>
                <?php else: ?>
                     <p style="font-size:0.8em; color:#555;">Nenhum logo. Envie um arquivo.</p>
                <?php endif; ?>
                <input type="file" id="logo_file" name="logo_file" accept="image/png, image/jpeg, image/gif">
            </div>

            <!-- New SEO Fields -->
            <div>
                <label for="meta_description_channel_edit">Meta Descrição SEO (opcional, máx ~160 caracteres):</label>
                <textarea id="meta_description_channel_edit" name="meta_description" rows="3"><?php echo htmlspecialchars($meta_description ?? ''); ?></textarea>
            </div>
            <div>
                <label for="meta_keywords_channel_edit">Meta Keywords SEO (opcional, separadas por vírgula):</label>
                <input type="text" id="meta_keywords_channel_edit" name="meta_keywords" value="<?php echo htmlspecialchars($meta_keywords ?? ''); ?>" placeholder="ex: tv ao vivo, canal X, esportes">
            </div>

            <div><button type="submit" name="update_channel">Salvar Alterações</button>
                <a href="manage_channels.php" style="margin-left: 10px;">Cancelar</a>
            </div>
        </form>
        <?php elseif(empty($message)): // If $channel is not set and no other message is already set from initial fetch error ?>
             <p style="color:red;">Não foi possível carregar os dados do canal para edição.</p>
             <p><a href="manage_channels.php">Voltar para Canais</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
