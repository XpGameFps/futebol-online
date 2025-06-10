<?php
require_once 'auth_check.php';
require_once '../config.php';

// Ensure csrf_utils.php is loaded (auth_check.php should have already included it)
if (!function_exists('generate_csrf_token')) {
    require_once 'csrf_utils.php';
}
// Generate a token. If form processing below fails and form is re-displayed,
// a new token will be generated for the redisplayed form.
$csrf_token = generate_csrf_token(true); // Force regenerate for fresh form display or re-display

define('LEAGUE_LOGO_UPLOAD_DIR', '../uploads/logos/leagues/'); // Relative to current admin folder
define('MAX_FILE_SIZE', 1024 * 1024); // 1MB
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

$page_title = "Editar Liga";
$message = '';
$league_id = null;
$league_name = '';
$current_logo_filename = null;

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $league_id = (int)$_GET['id'];
} else if (isset($_POST['league_id']) && filter_var($_POST['league_id'], FILTER_VALIDATE_INT)) {
    $league_id = (int)$_POST['league_id'];
}

if ($league_id === null) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $_SESSION['general_message']['manage_leagues'] = '<p style="color:red;">ID da liga inválido ou ausente.</p>';
    }
    header("Location: manage_leagues.php?status=edit_error&reason=invalid_id");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST" || !empty($message)) {
    try {
        $stmt_fetch = $pdo->prepare("SELECT name, logo_filename FROM leagues WHERE id = :id");
        $stmt_fetch->bindParam(':id', $league_id, PDO::PARAM_INT);
        $stmt_fetch->execute();
        $league = $stmt_fetch->fetch(PDO::FETCH_ASSOC);
        if (!$league) {
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                 $_SESSION['general_message']['manage_leagues'] = '<p style="color:red;">Liga não encontrada para edição.</p>';
            }
            header("Location: manage_leagues.php?status=edit_error&reason=not_found");
            exit;
        }
        $league_name = $league['name'];
        $current_logo_filename = $league['logo_filename'];
    } catch (PDOException $e) {
        $message = '<p style="color:red;">Erro ao buscar dados da liga: ' . $e->getMessage() . '</p>';
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_league'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $message = '<p style="color:red;">Falha na verificação de segurança (CSRF). Por favor, tente novamente.</p>';
        // Regenerate token for the form if it's redisplayed with this error
        $csrf_token = generate_csrf_token(true);
        // Do NOT proceed with processing, allow the script to fall through to re-display the form with the error and new token.
    } else {
        // ... (rest of the existing POST processing logic)
        $new_league_name = trim($_POST['name'] ?? '');
        $league_name = $new_league_name; // For repopulation
        $new_logo_filename_to_save = $current_logo_filename; // Initialize with current, might change if new file uploaded
        $file_was_moved_in_this_request = false; // Flag to track if a new file was moved
        $upload_error_message = '';

        if (empty($new_league_name)) {
        $message = '<p style="color:red;">O nome da liga não pode ser vazio.</p>';
    } else {
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['logo_file']['tmp_name'];
            $file_name = $_FILES['logo_file']['name'];
            $file_size = $_FILES['logo_file']['size'];
            $file_type = $_FILES['logo_file']['type'];
            $file_ext_array = explode('.', $file_name);
            $file_extension = strtolower(end($file_ext_array));

            if ($file_size > MAX_FILE_SIZE) { $upload_error_message = 'Arquivo muito grande. Máximo 1MB.'; }
            elseif (!in_array($file_type, $allowed_mime_types)) { $upload_error_message = 'Tipo de arquivo inválido. Apenas PNG, JPG, GIF.'; }
            else {
                // getimagesize check
                $image_info = @getimagesize($file_tmp_path);
                if ($image_info === false) {
                    $upload_error_message = 'Arquivo inválido. Conteúdo não reconhecido como imagem.';
                } else {
                    // Proceed with move_uploaded_file only if getimagesize passed
                    $new_uploaded_filename = uniqid('league_', true) . '.' . $file_extension;
                    $destination_path = LEAGUE_LOGO_UPLOAD_DIR . $new_uploaded_filename;
                    if (!is_dir(LEAGUE_LOGO_UPLOAD_DIR)) { @mkdir(LEAGUE_LOGO_UPLOAD_DIR, 0755, true); }
                    if (move_uploaded_file($file_tmp_path, $destination_path)) {
                        // Successfully moved new file
                        if ($current_logo_filename && file_exists(LEAGUE_LOGO_UPLOAD_DIR . $current_logo_filename)) {
                            // If there was an old logo, and it's different from the new one, delete old.
                            // This check might be redundant if $new_uploaded_filename is always unique.
                            if ($current_logo_filename != $new_uploaded_filename) {
                                @unlink(LEAGUE_LOGO_UPLOAD_DIR . $current_logo_filename);
                            }
                        }
                        $new_logo_filename_to_save = $new_uploaded_filename; // This is the new file's name
                        $file_was_moved_in_this_request = true; // Mark that a new file was physically moved
                    } else { $upload_error_message = 'Falha ao mover novo arquivo de logo.'; }
                }
            }
            if (!empty($upload_error_message)) { $message = '<p style="color:red;">Erro no upload do logo: ' . $upload_error_message . '</p>'; }
        } elseif (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['logo_file']['error'] != UPLOAD_ERR_OK) {
             $message = '<p style="color:red;">Erro no upload do logo. Código: ' . $_FILES['logo_file']['error'] . '</p>';
        }

        if (empty($message) || (!empty($message) && empty($upload_error_message) && isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == UPLOAD_ERR_NO_FILE)) {
            try {
                $stmt_check_name = $pdo->prepare("SELECT id FROM leagues WHERE name = :name AND id != :id");
                $stmt_check_name->bindParam(':name', $new_league_name, PDO::PARAM_STR);
                $stmt_check_name->bindParam(':id', $league_id, PDO::PARAM_INT);
                $stmt_check_name->execute();
                if ($stmt_check_name->rowCount() > 0) {
                    $message = '<p style="color:red;">Este nome de liga já está em uso por outra liga.</p>';
                } else {
                    $sql_update = "UPDATE leagues SET name = :name, logo_filename = :logo_filename WHERE id = :id";
                    $stmt_update = $pdo->prepare($sql_update);
                    $stmt_update->bindParam(':name', $new_league_name, PDO::PARAM_STR);
                    $stmt_update->bindParam(':id', $league_id, PDO::PARAM_INT);
                    if ($new_logo_filename_to_save === null) {
                        $stmt_update->bindValue(':logo_filename', null, PDO::PARAM_NULL);
                    } else {
                        $stmt_update->bindParam(':logo_filename', $new_logo_filename_to_save, PDO::PARAM_STR);
                    }
                    if ($stmt_update->execute()) {
                        $current_logo_filename = $new_logo_filename_to_save;
                        $_SESSION['general_message']['manage_leagues'] = '<p style="color:green;">Liga atualizada com sucesso!</p>';
                        header("Location: manage_leagues.php?status=saved_league_updated"); // Using a generic status for now
                        exit;
                    } else {
                        $message = '<p style="color:red;">Erro ao atualizar liga no banco de dados.</p>';
                        if ($file_was_moved_in_this_request && $new_logo_filename_to_save) {
                            $filePathToDelete = LEAGUE_LOGO_UPLOAD_DIR . $new_logo_filename_to_save;
                            if (file_exists($filePathToDelete)) {
                                @unlink($filePathToDelete);
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                 if ($e->getCode() == '23000' && strpos($e->getMessage(), "Duplicate entry") !== false) {
                     $message = '<p style="color:red;">Erro: O nome da liga já existe.</p>';
                 } else {
                    $message = '<p style="color:red;">Erro de banco de dados: ' . $e->getMessage() . '</p>';
                 }
                 // Cleanup uploaded file on PDOException as well
                if ($file_was_moved_in_this_request && $new_logo_filename_to_save) {
                    $filePathToDelete = LEAGUE_LOGO_UPLOAD_DIR . $new_logo_filename_to_save;
                    if (file_exists($filePathToDelete)) {
                        @unlink($filePathToDelete);
                    }
                }
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_SESSION['general_message']['manage_leagues'])) {
    if(empty($message)) {
        $message = $_SESSION['general_message']['manage_leagues'];
    }
    unset($_SESSION['general_message']['manage_leagues']);
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
        <nav>
            <div>
                <a href="index.php">Painel Principal (Jogos)</a>
                <a href="manage_leagues.php">Gerenciar Ligas</a>
                <a href="manage_channels.php">Gerenciar Canais TV</a>
                <a href="manage_teams.php">Gerenciar Times</a>
                <a href="manage_saved_streams.php">Biblioteca de Streams</a>
                <a href="manage_item_reports.php">Reportes de Itens</a>
                <a href="manage_settings.php">Configurações</a>
            </div>
            <div class="nav-user-info">
                <span id="online-users-indicator" style="margin-right: 15px; color: #007bff; font-weight:bold;">
                    Online: <span id="online-users-count">--</span>
                </span>
                 Usuário: <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?> |
                <a href="logout.php" class="logout-link">Logout</a>
            </div>
        </nav>
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <?php if(!empty($message)) echo "<div class='message'>{$message}</div>"; ?>

        <?php if ($league_id && (isset($league) && $league || $_SERVER["REQUEST_METHOD"] == "POST")): ?>
        <form action="edit_league.php?id=<?php echo $league_id; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="league_id" value="<?php echo $league_id; ?>">
            <div>
                <label for="name">Nome da Liga:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($league_name); ?>" required>
            </div>
            <div>
                <label for="logo_file">Logo da Liga (PNG, JPG, GIF, max 1MB):</label>
                <?php if ($current_logo_filename): ?>
                    <p>Logo Atual: <img src="<?php echo LEAGUE_LOGO_UPLOAD_DIR . htmlspecialchars($current_logo_filename); ?>" alt="Logo Atual" style="max-height: 50px; vertical-align: middle; margin-bottom:5px; border:1px solid #eee;"></p>
                    <p style="font-size:0.8em; color:#555;">Envie um novo arquivo para substituir o logo atual. Se nenhum arquivo for enviado, o logo atual será mantido.</p>
                <?php else: ?>
                    <p style="font-size:0.8em; color:#555;">Nenhum logo cadastrado. Envie um arquivo.</p>
                <?php endif; ?>
                <input type="file" id="logo_file" name="logo_file" accept="image/png, image/jpeg, image/gif">
            </div>
            <div>
                <button type="submit" name="update_league">Salvar Alterações</button>
                <a href="manage_leagues.php" style="margin-left: 10px;">Cancelar</a>
            </div>
        </form>
        <?php elseif(empty($message)):
             echo '<p style="color:red;">Não foi possível carregar os dados da liga para edição.</p>';
             echo '<p><a href="manage_leagues.php">Voltar para Ligas</a></p>';
        endif; ?>
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
