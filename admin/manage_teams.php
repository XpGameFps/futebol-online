<?php
require_once 'auth_check.php'; // Handles session_start()
require_once '../config.php';

if (!function_exists('generate_csrf_token')) {
    require_once 'csrf_utils.php';
}
$csrf_token = generate_csrf_token(); // Generate once for all forms on this page load

define('TEAM_LOGO_UPLOAD_DIR', '../uploads/logos/teams/'); // New directory for team logos
define('MAX_FILE_SIZE_TEAM_LOGO', 1024 * 1024); // 1MB
$allowed_mime_types_team_logo = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];


$page_title = "Gerenciar Times";
$message = ''; // For general status messages
$form_error_message_add_team = ''; // For add form specific errors, to avoid conflict with $message
// $form_data = $_SESSION['form_data']['add_team'] ?? []; // Moved down

// Handle general status messages from GET (delete, edit success/error)
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $reason = isset($_GET['reason']) ? htmlspecialchars($_GET['reason']) : '';
    if ($status == 'team_added') {
        $message = '<p style="color:green;">Time adicionado com sucesso!</p>';
    } elseif ($status == 'team_deleted') {
        $message = '<p style="color:green;">Time excluído com sucesso!</p>';
    } elseif ($status == 'team_delete_error') {
        $message = '<p style="color:red;">Erro ao excluir time: ' . $reason . '</p>';
    } elseif ($status == 'team_updated') { // From edit_team.php
        $message = '<p style="color:green;">Time atualizado com sucesso!</p>';
    } elseif ($status == 'team_edit_error') { // From edit_team.php
        $message = '<p style="color:red;">Erro na edição do time: ' . $reason . '</p>';
    }
}
// Handle message from edit_team.php success via session
if (isset($_SESSION['general_message']['manage_teams'])) {
    if(empty($message)) { // Prioritize GET messages if both exist for some reason
         $message = $_SESSION['general_message']['manage_teams'];
    }
    unset($_SESSION['general_message']['manage_teams']);
}


// Handle Add Team form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_team'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['form_error_message']['add_team'] = "Falha na verificação de segurança (CSRF). Por favor, tente novamente.";
        // The redirect 'Location: manage_teams.php#add-team-form' will cause a new token generation on next load.
        header("Location: manage_teams.php#add-team-form"); // This causes a reload, new token will be generated.
        exit;
    }
    // ... rest of add_team processing logic

    $_SESSION['form_data']['add_team'] = $_POST;
    if (isset($_FILES['logo_file']) && !empty($_FILES['logo_file']['name'])) {
        $_SESSION['form_data']['add_team']['logo_filename_tmp'] = $_FILES['logo_file']['name'];
    }

    $team_name = trim($_POST['team_name'] ?? '');
    $primary_color_hex = trim($_POST['primary_color_hex'] ?? '');
    $logo_filename_to_save = null;
    $upload_error_message = '';

    if (empty($team_name)) {
        $_SESSION['form_error_message']['add_team'] = "Nome do time é obrigatório.";
    } elseif (!empty($primary_color_hex) && !preg_match('/^#([a-f0-9]{6}|[a-f0-9]{3})$/i', $primary_color_hex)) {
        $_SESSION['form_error_message']['add_team'] = "Formato da Cor Primária Hex inválido (ex: #RRGGBB ou #RGB).";
    } else {
        // File Upload Handling
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['logo_file']['tmp_name'];
            $file_name = $_FILES['logo_file']['name'];
            $file_size = $_FILES['logo_file']['size'];
            $file_type = $_FILES['logo_file']['type'];
            $file_ext_array = explode('.', $file_name);
            $file_extension = strtolower(end($file_ext_array));

            if ($file_size > MAX_FILE_SIZE_TEAM_LOGO) { $upload_error_message = 'Logo muito grande (max 1MB).'; }
            elseif (!in_array($file_type, $allowed_mime_types_team_logo)) { $upload_error_message = 'Tipo de arquivo inválido para logo (aceito: JPG, PNG, GIF, SVG).'; }
            else {
                // getimagesize check
                $image_info = @getimagesize($file_tmp_path);
                if ($image_info === false) {
                    $upload_error_message = 'Arquivo inválido. Conteúdo não reconhecido como imagem.';
                } else {
                    // Proceed with move_uploaded_file only if getimagesize passed
                    $new_file_name = uniqid('team_logo_', true) . '.' . $file_extension;
                    $destination_path = TEAM_LOGO_UPLOAD_DIR . $new_file_name;
                    if (!is_dir(TEAM_LOGO_UPLOAD_DIR)) { if(!@mkdir(TEAM_LOGO_UPLOAD_DIR, 0755, true)) {$upload_error_message = 'Falha ao criar diretório de logos de times.';} }

                    // Ensure $upload_error_message is checked after directory creation attempt as well
                    if(empty($upload_error_message) && move_uploaded_file($file_tmp_path, $destination_path)) {
                        $logo_filename_to_save = $new_file_name;
                    } else { if(empty($upload_error_message)) $upload_error_message = 'Falha ao mover arquivo de logo do time.'; }
                }
            }
            if(!empty($upload_error_message)) $_SESSION['form_error_message']['add_team'] = $upload_error_message;
        } elseif (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['logo_file']['error'] != UPLOAD_ERR_OK) {
            $_SESSION['form_error_message']['add_team'] = 'Erro no upload do logo. Código: ' . $_FILES['logo_file']['error'];
        }
        // End File Upload Handling

        if (empty($_SESSION['form_error_message']['add_team'])) {
            try {
                $stmt_check = $pdo->prepare("SELECT id FROM teams WHERE name = :name");
                $stmt_check->bindParam(':name', $team_name, PDO::PARAM_STR);
                $stmt_check->execute();
                if ($stmt_check->rowCount() > 0) {
                    $_SESSION['form_error_message']['add_team'] = "Já existe um time com este nome.";
                    if ($logo_filename_to_save && file_exists(TEAM_LOGO_UPLOAD_DIR . $logo_filename_to_save)) { @unlink(TEAM_LOGO_UPLOAD_DIR . $logo_filename_to_save); unset($_SESSION['form_data']['add_team']['logo_filename_tmp']);}
                } else {
                    $sql_insert = "INSERT INTO teams (name, logo_filename, primary_color_hex) VALUES (:name, :logo_filename, :primary_color_hex)";
                    $stmt_insert = $pdo->prepare($sql_insert);
                    $stmt_insert->bindParam(':name', $team_name, PDO::PARAM_STR);

                    if ($logo_filename_to_save === null) { $stmt_insert->bindValue(':logo_filename', null, PDO::PARAM_NULL); }
                    else { $stmt_insert->bindParam(':logo_filename', $logo_filename_to_save, PDO::PARAM_STR); }

                    $color_to_save = !empty($primary_color_hex) ? $primary_color_hex : null;
                    if ($color_to_save === null) { $stmt_insert->bindValue(':primary_color_hex', null, PDO::PARAM_NULL); }
                    else { $stmt_insert->bindParam(':primary_color_hex', $color_to_save, PDO::PARAM_STR); }

                    if ($stmt_insert->execute()) {
                        unset($_SESSION['form_data']['add_team']);
                        unset($_SESSION['form_error_message']['add_team']);
                        header("Location: manage_teams.php?status=team_added");
                        exit;
                    } else {
                        $_SESSION['form_error_message']['add_team'] = "Erro ao salvar time.";
                        if ($logo_filename_to_save && file_exists(TEAM_LOGO_UPLOAD_DIR . $logo_filename_to_save)) { @unlink(TEAM_LOGO_UPLOAD_DIR . $logo_filename_to_save); unset($_SESSION['form_data']['add_team']['logo_filename_tmp']);}
                    }
                }
            } catch (PDOException $e) {
                if ($e->getCode() == '23000') {
                    $_SESSION['form_error_message']['add_team'] = "Erro: O nome do time já existe."; // Specific, user-friendly
                } else {
                    error_log("PDOException in " . __FILE__ . " (adding team): " . $e->getMessage());
                    $_SESSION['form_error_message']['add_team'] = "Ocorreu um erro no banco de dados ao adicionar o time. Por favor, tente novamente.";
                }
                if ($logo_filename_to_save && file_exists(TEAM_LOGO_UPLOAD_DIR . $logo_filename_to_save)) { @unlink(TEAM_LOGO_UPLOAD_DIR . $logo_filename_to_save); unset($_SESSION['form_data']['add_team']['logo_filename_tmp']);}
            }
        }
    }
    header("Location: manage_teams.php#add-team-form");
    exit;
}

// Display and clear form error message from session
if (isset($_SESSION['form_error_message']['add_team'])) {
    $form_error_message_add_team = '<p style="color:red; background-color: #f8d7da; border:1px solid #f5c6cb; padding:10px; border-radius:4px;">' . htmlspecialchars($_SESSION['form_error_message']['add_team']) . '</p>';
    unset($_SESSION['form_error_message']['add_team']);
}
$form_data = $_SESSION['form_data']['add_team'] ?? []; // Use for pre-filling

// Fetch existing teams
$teams = [];
try {
    $stmt_list_teams = $pdo->query("SELECT id, name, logo_filename, primary_color_hex, created_at FROM teams ORDER BY name ASC");
    $teams = $stmt_list_teams->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("PDOException in " . __FILE__ . " (fetching teams list): " . $e->getMessage());
    $message .= '<p style="color:red;">Ocorreu um erro no banco de dados ao buscar os times. Por favor, tente novamente.</p>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - Painel Admin</title>
    <link rel="stylesheet" href="css/admin_style.css">
    <style>.color-preview { display: inline-block; width: 20px; height: 20px; border: 1px solid #ccc; margin-left: 5px; vertical-align: middle; }</style>
</head>
<body>
    <div class="container">
        <div class="admin-layout">
            <?php require_once 'templates/navigation.php'; ?>
            <div class="main-content">
                <h1><?php echo htmlspecialchars($page_title); ?></h1>
    <?php if (!empty($message)) echo "<div class='message'>{$message}</div>"; ?>
    <?php if (!empty($form_error_message_add_team)) echo "<div class='message'>{$form_error_message_add_team}</div>"; ?>

    <h2 id="add-team-form">Adicionar Novo Time</h2>
    <form action="manage_teams.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <div><label for="team_name">Nome do Time:</label><input type="text" id="team_name" name="team_name" value="<?php echo htmlspecialchars($form_data['team_name'] ?? ''); ?>" required></div>
        <div><label for="logo_file">Logo do Time (PNG, JPG, GIF, SVG, max 1MB):</label><input type="file" id="logo_file" name="logo_file" accept="image/png, image/jpeg, image/gif, image/svg+xml"> <?php if (!empty($form_data['logo_filename_tmp'])): ?><p style="font-size:0.8em; color:blue;">Sel: <?php echo htmlspecialchars($form_data['logo_filename_tmp']); ?></p><?php endif; ?></div>
        <div><label for="primary_color_hex">Cor Primária Hex (ex: #RRGGBB):</label><input type="text" id="primary_color_hex" name="primary_color_hex" value="<?php echo htmlspecialchars($form_data['primary_color_hex'] ?? ''); ?>" placeholder="#FFFFFF"></div>
        <div><button type="submit" name="add_team">Adicionar Time</button></div>
    </form>
    <?php if (isset($_SESSION['form_data']['add_team'])) unset($_SESSION['form_data']['add_team']); ?>

    <hr><h2>Times Cadastrados</h2>
    <?php if (empty($teams)): ?><p>Nenhum time cadastrado.</p><?php else: ?><div class="table-responsive-wrapper"><table>
        <thead><tr><th>ID</th><th>Logo</th><th>Nome</th><th>Cor Primária</th><th>Adicionado</th><th>Ações</th></tr></thead>
        <tbody><?php foreach ($teams as $team_item): ?><tr>
            <td><?php echo htmlspecialchars($team_item['id']); ?></td>
            <td><?php if(!empty($team_item['logo_filename'])): ?><img src="<?php echo TEAM_LOGO_UPLOAD_DIR . htmlspecialchars($team_item['logo_filename']); ?>" alt="Logo" class="team-logo-list-item"><?php else: ?>N/A<?php endif; ?></td>
            <td><?php echo htmlspecialchars($team_item['name']); ?></td>
            <td><?php echo htmlspecialchars($team_item['primary_color_hex'] ?? 'N/A'); ?> <?php if(!empty($team_item['primary_color_hex'])): ?><span class="color-preview" style="background-color:<?php echo htmlspecialchars($team_item['primary_color_hex']); ?>"></span><?php endif; ?></td>
            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($team_item['created_at']))); ?></td>
            <td>
                <a href="edit_team.php?id=<?php echo $team_item['id']; ?>" class="edit-button" style="margin-right:5px;">Editar</a>
                <form action="delete_team.php" method="POST" onsubmit="return confirm('Tem certeza? Isso pode afetar jogos existentes se não forem atualizados para remover este time.');" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="team_id" value="<?php echo $team_item['id']; ?>">
                    <button type="submit" class="delete-button">Excluir</button>
                </form>
            </td>
        </tr><?php endforeach; ?></tbody>
    </table></div><?php endif; ?>
            </div> <!-- end main-content -->
        </div> <!-- end admin-layout -->
    </div> <!-- end container -->
<script> // JS for online user counter
document.addEventListener('DOMContentLoaded', function() {
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
