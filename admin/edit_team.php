<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../../FutOnline_config/config.php';

if (!function_exists('generate_csrf_token')) {
    require_once 'csrf_utils.php';
}

define('TEAM_LOGO_UPLOAD_DIR', '../uploads/logos/teams/');
define('MAX_FILE_SIZE_TEAM_LOGO', 1024 * 1024);
$allowed_mime_types_team_logo = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];

$page_title = "Editar Time"; $message = ''; $team_id = null;
$team_name = ''; $current_logo_filename = null; $primary_color_hex = '';

$csrf_token = ''; if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $team_id = (int)$_GET['id'];
} elseif (isset($_POST['team_id']) && filter_var($_POST['team_id'], FILTER_VALIDATE_INT)) {
    $team_id = (int)$_POST['team_id'];
}

if ($team_id === null) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {         $_SESSION['general_message']['manage_teams'] = '<p style="color:red;">ID do time inválido ou ausente na submissão.</p>';
    }
        header("Location: manage_teams.php?status=team_edit_error&reason=invalid_id");
    exit;
}

$team_data_loaded = false;
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['update_team']) || !empty($message)) {
    try {
        $stmt_fetch = $pdo->prepare("SELECT name, logo_filename, primary_color_hex FROM teams WHERE id = :id");
        $stmt_fetch->bindParam(':id', $team_id, PDO::PARAM_INT);
        $stmt_fetch->execute();
        $item = $stmt_fetch->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                 $_SESSION['general_message']['manage_teams'] = '<p style="color:red;">Time não encontrado para edição.</p>';
            }
            header("Location: manage_teams.php?status=team_edit_error&reason=not_found");
            exit;
        }
                if ($_SERVER["REQUEST_METHOD"] != "POST" || empty($message)) {
            $team_name = $item['name'];
            $current_logo_filename = $item['logo_filename'];
            $primary_color_hex = $item['primary_color_hex'];
        }
        $team_data_loaded = true;
    } catch (PDOException $e) {
        error_log("PDOException in " . __FILE__ . " (fetching team data for ID: " . $team_id . "): " . $e->getMessage());
        $message = '<p style="color:red;">Ocorreu um erro no banco de dados ao carregar os dados do time. Por favor, tente novamente.</p>';
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_team'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $message = '<p style="color:red;">Falha na verificação de segurança (CSRF). Por favor, tente novamente.</p>';
        
            } else {
                $new_team_name = trim($_POST['team_name'] ?? '');
        $new_primary_color_hex = trim($_POST['primary_color_hex'] ?? '');

                $team_name = $new_team_name;
        $primary_color_hex = $new_primary_color_hex;
        
                        $db_current_logo_filename = null;         if ($team_id) {             try {
                $stmt_get_logo = $pdo->prepare("SELECT logo_filename FROM teams WHERE id = :id");
                $stmt_get_logo->bindParam(':id', $team_id, PDO::PARAM_INT);
                $stmt_get_logo->execute();
                $result_logo = $stmt_get_logo->fetch(PDO::FETCH_ASSOC);
                if ($result_logo) {
                    $db_current_logo_filename = $result_logo['logo_filename'];
                } else {
                                                            if (empty($message)) $message = '<p style="color:red;">Erro: Time não encontrado ao tentar verificar logo atual.</p>';
                                    }
            } catch (PDOException $e) {
                error_log("PDOException in " . __FILE__ . " (fetching current logo for team ID: " . $team_id . "): " . $e->getMessage());
                if (empty($message)) $message = '<p style="color:red;">Ocorreu um erro no banco de dados ao verificar o logo atual. Por favor, tente novamente.</p>';
                            }
        } else {
                        if (empty($message)) $message = '<p style="color:red;">ID do time inválido ao tentar verificar logo atual.</p>';
        }

                        $current_logo_filename = $db_current_logo_filename;
        $new_logo_filename_to_save = $db_current_logo_filename; 
        $file_was_moved_in_this_request = false;         $upload_error_message = '';

        if (empty($new_team_name)) {
            $message = '<p style="color:red;">Nome do time é obrigatório.</p>';
        } elseif (!empty($new_primary_color_hex) && !preg_match('/^#([a-f0-9]{6}|[a-f0-9]{3})$/i', $new_primary_color_hex)) {
            $message = '<p style="color:red;">Formato da Cor Primária Hex inválido (ex: #RRGGBB ou #RGB).</p>';
        } else {
                        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == UPLOAD_ERR_OK) {
                $file_tmp_path = $_FILES['logo_file']['tmp_name'];
                $file_name = $_FILES['logo_file']['name'];
                $file_size = $_FILES['logo_file']['size'];
                $file_type = $_FILES['logo_file']['type'];
                $file_ext_array = explode('.', $file_name);
                $file_extension = strtolower(end($file_ext_array));

                if ($file_size > MAX_FILE_SIZE_TEAM_LOGO) { $upload_error_message = 'Logo muito grande (max 1MB).'; }
                elseif (!in_array($file_type, $allowed_mime_types_team_logo)) { $upload_error_message = 'Tipo de arquivo inválido para logo (JPG, PNG, GIF, SVG).'; }
                else {
                                        $image_info = @getimagesize($file_tmp_path);
                    if ($image_info === false) {
                        $upload_error_message = 'Arquivo inválido. Conteúdo não reconhecido como imagem.';
                    } else {
                                                $new_uploaded_filename = uniqid('team_logo_', true) . '.' . $file_extension;
                        $destination_path = TEAM_LOGO_UPLOAD_DIR . $new_uploaded_filename;
                        if (!is_dir(TEAM_LOGO_UPLOAD_DIR)) { if(!@mkdir(TEAM_LOGO_UPLOAD_DIR, 0755, true)) {$upload_error_message = 'Falha ao criar diretório de logos.';} }

                        if(empty($upload_error_message) && move_uploaded_file($file_tmp_path, $destination_path)) {
                                                        if ($current_logo_filename && file_exists(TEAM_LOGO_UPLOAD_DIR . $current_logo_filename)) {
                                if ($current_logo_filename != $new_uploaded_filename) {                                     @unlink(TEAM_LOGO_UPLOAD_DIR . $current_logo_filename);
                                }
                            }
                            $new_logo_filename_to_save = $new_uploaded_filename;
                            $file_was_moved_in_this_request = true;                         } else { if(empty($upload_error_message)) $upload_error_message = 'Falha ao mover novo logo.'; }
                    }
                }
                if(!empty($upload_error_message)) $message = '<p style="color:red;">Erro no Upload: '.$upload_error_message.'</p>';
            } elseif (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['logo_file']['error'] != UPLOAD_ERR_OK) {
                $message = '<p style="color:red;">Erro no Upload do Logo. Código: ' . $_FILES['logo_file']['error'] . '</p>';
            }

                        if (empty($message) || (!empty($message) && empty($upload_error_message) && (!isset($_FILES['logo_file']) || $_FILES['logo_file']['error'] == UPLOAD_ERR_NO_FILE))) {
                try {
                    $stmt_check_name = $pdo->prepare("SELECT id FROM teams WHERE name = :name AND id != :id");
                    $stmt_check_name->bindParam(':name', $new_team_name, PDO::PARAM_STR);
                    $stmt_check_name->bindParam(':id', $team_id, PDO::PARAM_INT);
                    $stmt_check_name->execute();
                    if ($stmt_check_name->rowCount() > 0) {
                        $message = '<p style="color:red;">Este nome de time já está em uso por outro time.</p>';
                    } else {
                        $sql_update = "UPDATE teams SET name = :name, logo_filename = :logo_filename, primary_color_hex = :primary_color_hex WHERE id = :id";
                        $stmt_update = $pdo->prepare($sql_update);
                        $stmt_update->bindParam(':name', $new_team_name, PDO::PARAM_STR);
                        $stmt_update->bindParam(':id', $team_id, PDO::PARAM_INT);

                        $color_to_save_edit = !empty($new_primary_color_hex) ? $new_primary_color_hex : null;
                        if ($new_logo_filename_to_save === null) { $stmt_update->bindValue(':logo_filename', null, PDO::PARAM_NULL); }
                        else { $stmt_update->bindParam(':logo_filename', $new_logo_filename_to_save, PDO::PARAM_STR); }
                        if ($color_to_save_edit === null) { $stmt_update->bindValue(':primary_color_hex', null, PDO::PARAM_NULL); }
                        else { $stmt_update->bindParam(':primary_color_hex', $color_to_save_edit, PDO::PARAM_STR); }

                        if ($stmt_update->execute()) {
                                                        $current_logo_filename = $new_logo_filename_to_save;
                                                        header("Location: manage_teams.php?status=team_updated");
                            exit;
                        } else {
                            $message = '<p style="color:red;">Erro ao atualizar time no banco de dados.</p>';
                            if ($file_was_moved_in_this_request && $new_logo_filename_to_save) {
                                $filePathToDelete = TEAM_LOGO_UPLOAD_DIR . $new_logo_filename_to_save;
                                if (file_exists($filePathToDelete)) {
                                    @unlink($filePathToDelete);
                                }
                            }
                        }
                    }
                } catch (PDOException $e) {
                     if ($e->getCode() == '23000' && strpos($e->getMessage(), "unique_team_name") !== false) {
                        $message = '<p style="color:red;">Erro: O nome do time já existe.</p>';                     } else {
                        error_log("PDOException in " . __FILE__ . " (updating team ID: " . $team_id . "): " . $e->getMessage());
                        $message = '<p style="color:red;">Ocorreu um erro no banco de dados ao atualizar o time. Por favor, tente novamente.</p>';
                    }
                    if ($file_was_moved_in_this_request && $new_logo_filename_to_save) {
                        $filePathToDelete = TEAM_LOGO_UPLOAD_DIR . $new_logo_filename_to_save;
                        if (file_exists($filePathToDelete)) {
                            @unlink($filePathToDelete);
                        }
                    }
                }
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "GET" || !empty($message)) {
    $csrf_token = generate_csrf_token(true); }
?>
<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title><?php echo htmlspecialchars($page_title); ?> - Painel Admin</title><link rel="stylesheet" href="css/admin_style.css">
<style>.color-preview { display: inline-block; width: 20px; height: 20px; border: 1px solid #ccc; vertical-align: middle; margin-left: 5px; }</style>
</head>
<body>
    <div class="container" style="max-width:700px;">
        <div class="admin-layout">
            <?php require_once 'templates/navigation.php'; ?>
            <div class="main-content">
                <h1><?php echo htmlspecialchars($page_title); ?></h1>
    <?php if(!empty($message)) echo "<div class='message'>{$message}</div>"; ?>

    <?php if ($team_id && ($team_data_loaded || $_SERVER["REQUEST_METHOD"] == "POST")): ?>
    <form action="edit_team.php?id=<?php echo $team_id; ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
        <div><label for="team_name">Nome do Time:</label><input type="text" id="team_name" name="team_name" value="<?php echo htmlspecialchars($team_name); ?>" required></div>
        <div>
            <label for="logo_file">Logo do Time (PNG, JPG, GIF, SVG, max 1MB):</label>
            <?php if($current_logo_filename):?>
                <p>Logo Atual: <img src="<?php echo TEAM_LOGO_UPLOAD_DIR.htmlspecialchars($current_logo_filename);?>" alt="Logo Atual" style="max-height:50px; vertical-align: middle; margin-bottom:5px; border:1px solid #eee;"></p>
                <p style="font-size:0.8em; color:#555;">Envie um novo arquivo para substituir. Se nenhum for enviado, o logo atual será mantido.</p>
            <?php else: ?>
                <p style="font-size:0.8em; color:#555;">Nenhum logo cadastrado. Envie um arquivo.</p>
            <?php endif; ?>
            <input type="file" id="logo_file" name="logo_file" accept="image/png, image/jpeg, image/gif, image/svg+xml">
        </div>
        <div>
            <label for="primary_color_hex">Cor Primária Hex (ex: #RRGGBB):</label>
            <input type="text" id="primary_color_hex" name="primary_color_hex" value="<?php echo htmlspecialchars($primary_color_hex ?? ''); ?>" placeholder="#FFFFFF">
            <?php if(!empty($primary_color_hex)): ?>
                 <span class="color-preview" style="background-color:<?php echo htmlspecialchars($primary_color_hex); ?>"></span>
            <?php endif; ?>
        </div>
        <div><button type="submit" name="update_team">Salvar Alterações</button> <a href="manage_teams.php" style="margin-left:10px;">Cancelar</a></div>
    </form>
    <?php elseif(empty($message)):
        echo '<p style="color:red;">Time não encontrado ou ID inválido.</p>';
        echo '<p><a href="manage_teams.php">Voltar para Gerenciar Times</a></p>';
    endif; ?>
            </div>         </div>     </div> <script> document.addEventListener('DOMContentLoaded', function() {
    const onlineUsersCountElement = document.getElementById('online-users-count');
    function fetchOnlineUsers() {
        if (!onlineUsersCountElement) return;
        fetch('get_online_users.php').then(response => response.json()).then(data => {
            if (data && data.status === 'success') onlineUsersCountElement.textContent = data.online_count;
            else onlineUsersCountElement.textContent = '--';
        }).catch(() => onlineUsersCountElement.textContent = 'Err');
    }
    fetchOnlineUsers(); setInterval(fetchOnlineUsers, 30000);
});
</script>
</body></html>

