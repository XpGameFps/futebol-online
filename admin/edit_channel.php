<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../../FutOnline_config/config.php';

if (!function_exists('generate_csrf_token')) {
    require_once 'csrf_utils.php';
}
$csrf_token = generate_csrf_token(true); 
define('CHANNEL_LOGO_UPLOAD_DIR', '../uploads/logos/channels/');
define('MAX_FILE_SIZE', 1024 * 1024); $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

$page_title = "Editar Canal de TV";
$message = '';
$channel_id = null;
$channel_name = '';
$current_logo_filename = null;
$stream_url = '';
$sort_order = 0;
$meta_description = ''; $meta_keywords = '';    
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $channel_id = (int)$_GET['id'];
} else if (isset($_POST['channel_id']) && filter_var($_POST['channel_id'], FILTER_VALIDATE_INT)) {
    $channel_id = (int)$_POST['channel_id'];
}

if ($channel_id === null) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $_SESSION['general_message']['manage_channels'] = '<p style="color:red;">ID do canal inválido ou ausente.</p>';
    }
    header("Location: manage_channels.php?status=edit_error&reason=invalid_id");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST" || !empty($message)) {
    try {
        $stmt_fetch = $pdo->prepare("SELECT name, logo_filename, stream_url, sort_order, meta_description, meta_keywords FROM tv_channels WHERE id = :id");
        $stmt_fetch->bindParam(':id', $channel_id, PDO::PARAM_INT);
        $stmt_fetch->execute();
        $channel = $stmt_fetch->fetch(PDO::FETCH_ASSOC);
        if (!$channel) {
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                 $_SESSION['general_message']['manage_channels'] = '<p style="color:red;">Canal não encontrado para edição.</p>';
            }
            header("Location: manage_channels.php?status=edit_error&reason=not_found");
            exit;
        }
        $channel_name = $channel['name'];
        $current_logo_filename = $channel['logo_filename'];
        $stream_url = $channel['stream_url'];
        $sort_order = $channel['sort_order'];
        $meta_description = $channel['meta_description'];         $meta_keywords = $channel['meta_keywords'];           } catch (PDOException $e) {
        error_log("PDOException in " . __FILE__ . " (fetching channel data for ID: " . $channel_id . "): " . $e->getMessage());
        $message = '<p style="color:red;">Ocorreu um erro no banco de dados ao carregar os dados do canal. Por favor, tente novamente.</p>';
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_channel'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $message = '<p style="color:red;">Falha na verificação de segurança (CSRF). Por favor, tente novamente.</p>';
                $csrf_token = generate_csrf_token(true);
    } else {
        $new_channel_name = trim($_POST['name'] ?? '');
        $new_stream_url = trim($_POST['stream_url'] ?? '');
        $new_sort_order_input = trim($_POST['sort_order'] ?? '0');
        $new_meta_description = trim($_POST['meta_description'] ?? null);
        $new_meta_keywords = trim($_POST['meta_keywords'] ?? null);

                $channel_name = $new_channel_name;
        $stream_url = $new_stream_url;
        $sort_order = $new_sort_order_input;         $meta_description = $new_meta_description;
        $meta_keywords = $new_meta_keywords;

        $new_logo_filename_to_save = $current_logo_filename;         $file_was_moved_in_this_request = false;         $upload_error_message = '';

        if (empty($new_channel_name) || empty($new_stream_url)) {
            $message = '<p style="color:red;">Nome do canal e URL do stream são obrigatórios.</p>';
        } elseif (!filter_var($new_stream_url, FILTER_VALIDATE_URL)) {
            $message = '<p style="color:red;">URL do stream inválida.</p>';
        } elseif (!is_numeric($new_sort_order_input)) {
            $message = '<p style="color:red;">Ordem de classificação deve ser um número.</p>';
        } else {
            $new_sort_order = (int)$new_sort_order_input; 
            if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == UPLOAD_ERR_OK) {
                $file_tmp_path = $_FILES['logo_file']['tmp_name'];
                $file_name = $_FILES['logo_file']['name'];
                $file_size = $_FILES['logo_file']['size'];
                $file_type = $_FILES['logo_file']['type'];
                $file_ext_array = explode('.', $file_name);
                $file_extension = strtolower(end($file_ext_array));

                if ($file_size > MAX_FILE_SIZE) { 
                    $upload_error_message = 'Arquivo muito grande (max 1MB).'; 
                } elseif (!in_array($file_type, $allowed_mime_types)) { 
                    $upload_error_message = 'Tipo de arquivo inválido (PNG, JPG, GIF).'; 
                } else {
                                        $image_info = @getimagesize($file_tmp_path);
                    if ($image_info === false) {
                        $upload_error_message = 'Arquivo inválido. Conteúdo não reconhecido como imagem.';
                    } else {
                                                $new_uploaded_filename = uniqid('channel_', true) . '.' . $file_extension;
                        $destination_path = CHANNEL_LOGO_UPLOAD_DIR . $new_uploaded_filename;
                        if (!is_dir(CHANNEL_LOGO_UPLOAD_DIR)) { @mkdir(CHANNEL_LOGO_UPLOAD_DIR, 0755, true); }
                        if (move_uploaded_file($file_tmp_path, $destination_path)) {
                            if ($current_logo_filename && file_exists(CHANNEL_LOGO_UPLOAD_DIR . $current_logo_filename)) {
                                 if ($current_logo_filename != $new_uploaded_filename) {                                     @unlink(CHANNEL_LOGO_UPLOAD_DIR . $current_logo_filename);
                                }
                            }
                            $new_logo_filename_to_save = $new_uploaded_filename;
                            $file_was_moved_in_this_request = true;                         } else { 
                            $upload_error_message = 'Falha ao mover novo arquivo de logo.'; 
                        }
                    }
                }
                if (!empty($upload_error_message)) { 
                    $message = '<p style="color:red;">Erro no upload: ' . $upload_error_message . '</p>'; 
                }
            } elseif (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['logo_file']['error'] != UPLOAD_ERR_OK) {
                $message = '<p style="color:red;">Erro no upload. Código: ' . $_FILES['logo_file']['error'] . '</p>';
            }

            if (empty($message) || (!empty($message) && empty($upload_error_message) && isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == UPLOAD_ERR_NO_FILE)) {
                try {
                    $sql_update = "UPDATE tv_channels SET name = :name, logo_filename = :logo_filename, stream_url = :stream_url, sort_order = :sort_order, meta_description = :meta_description, meta_keywords = :meta_keywords WHERE id = :id";
                    $stmt_update = $pdo->prepare($sql_update);
                    $stmt_update->bindParam(':name', $new_channel_name, PDO::PARAM_STR);
                    $stmt_update->bindParam(':stream_url', $new_stream_url, PDO::PARAM_STR);
                    $stmt_update->bindParam(':sort_order', $new_sort_order, PDO::PARAM_INT);
                    $stmt_update->bindParam(':id', $channel_id, PDO::PARAM_INT);
                    if ($new_logo_filename_to_save === null) { 
                        $stmt_update->bindValue(':logo_filename', null, PDO::PARAM_NULL); 
                    } else { 
                        $stmt_update->bindParam(':logo_filename', $new_logo_filename_to_save, PDO::PARAM_STR); 
                    }
                    if ($new_meta_description === null) { 
                        $stmt_update->bindValue(":meta_description", null, PDO::PARAM_NULL); 
                    } else { 
                        $stmt_update->bindParam(":meta_description", $new_meta_description, PDO::PARAM_STR); 
                    }
                    if ($new_meta_keywords === null) { 
                        $stmt_update->bindValue(":meta_keywords", null, PDO::PARAM_NULL); 
                    } else { 
                        $stmt_update->bindParam(":meta_keywords", $new_meta_keywords, PDO::PARAM_STR); 
                    }

                    if ($stmt_update->execute()) {
                        $current_logo_filename = $new_logo_filename_to_save;                         $_SESSION['general_message']['manage_channels'] = '<p style="color:green;">Canal de TV atualizado com sucesso!</p>';
                        header("Location: manage_channels.php?status=saved_channel_updated");
                        exit;
                    } else {
                        $message = '<p style="color:red;">Erro ao atualizar canal no banco de dados.</p>';
                        if ($file_was_moved_in_this_request && $new_logo_filename_to_save) {
                            $filePathToDelete = CHANNEL_LOGO_UPLOAD_DIR . $new_logo_filename_to_save;
                            if (file_exists($filePathToDelete)) {
                                @unlink($filePathToDelete);
                            }
                        }
                    }
                } catch (PDOException $e) {
                    error_log("PDOException in " . __FILE__ . " (updating channel ID: " . $channel_id . "): " . $e->getMessage());
                    $message = '<p style="color:red;">Ocorreu um erro no banco de dados ao atualizar o canal. Por favor, tente novamente.</p>';
                    if ($file_was_moved_in_this_request && $new_logo_filename_to_save) {
                        $filePathToDelete = CHANNEL_LOGO_UPLOAD_DIR . $new_logo_filename_to_save;
                        if (file_exists($filePathToDelete)) {
                            @unlink($filePathToDelete);
                        }
                    }
                }
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_SESSION['general_message']['manage_channels'])) {
    if(empty($message)) { 
        $message = $_SESSION['general_message']['manage_channels']; 
    }
    unset($_SESSION['general_message']['manage_channels']);
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
    <div class="container" style="max-width:700px;">
        <div class="admin-layout">
            <?php require_once 'templates/navigation.php'; ?>
            <div class="main-content">
                <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <?php if(!empty($message)) echo "<div class='message'>{$message}</div>"; ?>

        <?php if ($channel_id && (isset($channel) && $channel || $_SERVER["REQUEST_METHOD"] == "POST")): ?>
        <form action="edit_channel.php?id=<?php echo $channel_id; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="channel_id" value="<?php echo $channel_id; ?>">
            <div><label for="name">Nome do Canal:</label><input type="text" id="name" name="name" value="<?php echo htmlspecialchars($channel_name); ?>" required></div>
            <div><label for="stream_url">URL do Stream Principal:</label><input type="url" id="stream_url" name="stream_url" value="<?php echo htmlspecialchars($stream_url); ?>" required></div>
            <div><label for="sort_order">Ordem de Classificação:</label><input type="number" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($sort_order); ?>" required></div>
            <div>
                <label for="logo_file">Logo do Canal (PNG, JPG, GIF, max 1MB):</label>
                <?php if ($current_logo_filename): ?>
                    <p>Logo Atual: <img src="<?php echo CHANNEL_LOGO_UPLOAD_DIR . htmlspecialchars($current_logo_filename); ?>" alt="Logo Atual" style="max-height: 50px; vertical-align: middle; margin-bottom:5px; border:1px solid #eee;"></p>
                    <p style="font-size:0.8em; color:#555;">Envie um novo arquivo para substituir. Se nenhum for enviado, o logo atual será mantido.</p>
                <?php else: ?>
                     <p style="font-size:0.8em; color:#555;">Nenhum logo cadastrado. Envie um arquivo.</p>
                <?php endif; ?>
                <input type="file" id="logo_file" name="logo_file" accept="image/png, image/jpeg, image/gif">
            </div>
            <div><label for="meta_description_channel_edit">Meta Descrição SEO (opcional):</label><textarea id="meta_description_channel_edit" name="meta_description" rows="3"><?php echo htmlspecialchars($meta_description ?? ''); ?></textarea></div>
            <div><label for="meta_keywords_channel_edit">Meta Keywords SEO (opcional, separadas por vírgula):</label><input type="text" id="meta_keywords_channel_edit" name="meta_keywords" value="<?php echo htmlspecialchars($meta_keywords ?? ''); ?>" placeholder="ex: tv ao vivo, canal X"></div>
            <div><button type="submit" name="update_channel">Salvar Alterações</button> <a href="manage_channels.php" style="margin-left:10px;">Cancelar</a></div>
        </form>
        <?php elseif(empty($message)):
            echo '<p style="color:red;">Não foi possível carregar os dados do canal para edição.</p>';
            echo '<p><a href="manage_channels.php">Voltar para Canais</a></p>';
        endif; ?>
            </div>
        </div>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const onlineUsersCountElement = document.getElementById('online-users-count');

    function fetchOnlineUsers() {
        if (!onlineUsersCountElement) return;

        fetch('get_online_users.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' . response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data && data.status === 'success') {
                    onlineUsersCountElement.textContent = data.online_count;
                } else {
                    onlineUsersCountElement.textContent = '--';
                }
            })
            .catch(error => {
                onlineUsersCountElement.textContent = 'Err';
                console.error('Fetch error for online users:', error);
            });
    }
    fetchOnlineUsers();
    setInterval(fetchOnlineUsers, 30000);
});
</script>
</body>
</html>

