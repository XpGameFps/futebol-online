<?php
require_once 'auth_check.php'; // Ensures admin is logged in
require_once '../config.php'; // Database connection

if (!function_exists('generate_csrf_token')) {
    require_once 'csrf_utils.php';
}
$csrf_token = generate_csrf_token(true); // Regenerate on each load for fresh forms

define('SITE_LOGO_UPLOAD_DIR', '../uploads/site/');
define('MAX_LOGO_FILE_SIZE', 1024 * 512); // 512KB
$allowed_logo_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];

$site_name_key = 'site_name';
$site_logo_key = 'site_logo_filename';
$site_display_format_key = 'site_display_format';

$page_title = "Configurações do Site";
$message = '';
$cookie_banner_text_key = 'cookie_banner_text';

// Initialize variables for all settings
$current_cookie_banner_text = '';
$current_site_name = '';
$current_site_logo_filename = null;
$current_site_display_format = 'text'; // Default

// Handle form submission to update settings
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $message = '<p style="color:red;">Falha na verificação de segurança (CSRF). Por favor, tente novamente.</p>';
        $csrf_token = generate_csrf_token(true); // Regenerate for form re-display
        // Allow to fall through to re-display the page with the message and new token
    } else {
        // Nest the existing 'if (isset($_POST['save_cookie_banner']))'
        // and 'elseif (isset($_POST['save_site_identity']))' blocks inside this else.
        if (isset($_POST['save_cookie_banner'])) {
            $new_text = $_POST['cookie_banner_text'] ?? '';

        try {
            $sql = "INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':key', $cookie_banner_text_key, PDO::PARAM_STR);
            $stmt->bindParam(':value', $new_text, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $message = '<p style="color:green;">Texto do banner de cookies atualizado com sucesso!</p>';
            } else {
                $message = '<p style="color:red;">Erro ao atualizar o texto do banner de cookies.</p>'; // Generic enough already
            }
        } catch (PDOException $e) {
            error_log("PDOException in " . __FILE__ . " (save_cookie_banner): " . $e->getMessage());
            $message = '<p style="color:red;">Ocorreu um erro no banco de dados ao salvar as configurações do banner de cookies. Por favor, tente novamente.</p>';
        }
    } elseif (isset($_POST['save_site_identity'])) {
        $new_site_name = trim($_POST['site_name'] ?? 'FutOnline');
        $new_site_display_format = $_POST['site_display_format'] ?? 'text';

        // Initialize $new_logo_filename_to_save with the value fetched at the start of the script
        // (which is $current_site_logo_filename, but let's use the more specific DB one for comparison later)
        $stmt_get_current_logo_for_init = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = :key");
        $stmt_get_current_logo_for_init->bindParam(':key', $site_logo_key, PDO::PARAM_STR);
        $stmt_get_current_logo_for_init->execute();
        $initial_logo_result = $stmt_get_current_logo_for_init->fetch(PDO::FETCH_ASSOC);
        $initial_db_logo_filename = $initial_logo_result ? $initial_logo_result['setting_value'] : null;
        $new_logo_filename_to_save = $initial_db_logo_filename; // Start with current DB value

        $file_was_moved_for_site_logo = false; // Flag for site logo

        // Fetch current logo filename before attempting to save new one, for deletion logic
        $stmt_get_current_logo = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = :key");
        $stmt_get_current_logo->bindParam(':key', $site_logo_key, PDO::PARAM_STR);
        $stmt_get_current_logo->execute();
        $logo_result = $stmt_get_current_logo->fetch(PDO::FETCH_ASSOC);
        if ($logo_result) {
            $current_site_logo_filename_db = $logo_result['setting_value'];
        } else {
            $current_site_logo_filename_db = null;
        }

        // File Upload Handling for Site Logo
        if (isset($_FILES['site_logo_file']) && $_FILES['site_logo_file']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['site_logo_file']['tmp_name'];
            $file_name = $_FILES['site_logo_file']['name'];
            $file_size = $_FILES['site_logo_file']['size'];
            $file_type = $_FILES['site_logo_file']['type'];
            $file_ext_array = explode('.', $file_name);
            $file_extension = strtolower(end($file_ext_array));

            if ($file_size > MAX_LOGO_FILE_SIZE) {
                $message .= '<p style="color:red;">Logo muito grande (max 512KB).</p>';
            } elseif (!in_array($file_type, $allowed_logo_mime_types)) {
                $message .= '<p style="color:red;">Tipo de arquivo inválido para logo (aceito: JPG, PNG, GIF, SVG).</p>';
            } else {
                // getimagesize check
                $image_info = @getimagesize($file_tmp_path);
                if ($image_info === false) {
                    $message .= '<p style="color:red;">Arquivo inválido. Conteúdo não reconhecido como imagem para o logo.</p>';
                }

                // Proceed only if no errors so far (including getimagesize)
                if (empty($message)) {
                    $new_uploaded_filename = uniqid('site_logo_', true) . '.' . $file_extension;
                    $destination_path = SITE_LOGO_UPLOAD_DIR . $new_uploaded_filename;
                    if (!is_dir(SITE_LOGO_UPLOAD_DIR)) {
                        if (!@mkdir(SITE_LOGO_UPLOAD_DIR, 0755, true)) {
                            $message .= '<p style="color:red;">Falha ao criar diretório de logo do site.</p>';
                        }
                    }
                    // Re-check $message after directory creation attempt
                    if (empty($message) && move_uploaded_file($file_tmp_path, $destination_path)) {
                        // Delete old logo if a new one is successfully uploaded AND old logo existed
                        if ($current_site_logo_filename_db && file_exists(SITE_LOGO_UPLOAD_DIR . $current_site_logo_filename_db)) {
                             if ($current_site_logo_filename_db != $new_uploaded_filename) {
                                @unlink(SITE_LOGO_UPLOAD_DIR . $current_site_logo_filename_db);
                            }
                        }
                        $new_logo_filename_to_save = $new_uploaded_filename; // This is the new file's name
                        $file_was_moved_for_site_logo = true;    // Mark that a new file was physically moved
                    } elseif(empty($message)) { // Only set move error if no prior error (like directory creation or getimagesize)
                        $message .= '<p style="color:red;">Falha ao mover arquivo de logo do site.</p>';
                    }
                }
            }
        } elseif (isset($_FILES['site_logo_file']) && $_FILES['site_logo_file']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['site_logo_file']['error'] != UPLOAD_ERR_OK) {
            $message .= '<p style="color:red;">Erro no upload do logo. Código: ' . $_FILES['site_logo_file']['error'] . '</p>';
        }

        if (empty($message)) { // Proceed only if no upload errors or other critical errors
            try {
                $settings_to_save = [
                    $site_name_key => $new_site_name,
                    $site_display_format_key => $new_site_display_format,
                    // Only update logo filename if a new one was uploaded or if it was explicitly changed
                    // If format is 'text', we might want to clear the logo filename or keep it. We'll keep it for now.
                    $site_logo_key => $new_logo_filename_to_save
                ];

                $sql_insert_settings = "INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)
                                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
                $stmt_insert_settings = $pdo->prepare($sql_insert_settings);

                foreach ($settings_to_save as $key => $value) {
                    $stmt_insert_settings->bindParam(':key', $key, PDO::PARAM_STR);
                    if ($value === null) {
                        $stmt_insert_settings->bindValue(':value', null, PDO::PARAM_NULL);
                    } else {
                        $stmt_insert_settings->bindParam(':value', $value, PDO::PARAM_STR);
                    }
                    $stmt_insert_settings->execute();
                }
                $message = '<p style="color:green;">Configurações de identidade do site atualizadas com sucesso!</p>';
            } catch (PDOException $e) {
                error_log("PDOException in " . __FILE__ . " (save_site_identity): " . $e->getMessage());
                $message = '<p style="color:red;">Ocorreu um erro no banco de dados ao salvar as configurações de identidade do site. Por favor, tente novamente.</p>';
                // Cleanup uploaded site logo on PDOException if a new one was moved
                if ($file_was_moved_for_site_logo && $new_logo_filename_to_save && $new_logo_filename_to_save !== $initial_db_logo_filename) {
                    $filePathToDelete = SITE_LOGO_UPLOAD_DIR . $new_logo_filename_to_save;
                    if (file_exists($filePathToDelete)) {
                        @unlink($filePathToDelete);
                    }
                }
            }
        }
        // If an action was processed, a redirect usually happens.
        // If not (e.g. only a message is set due to an internal error after CSRF pass),
        // the $csrf_token should be regenerated if the form is to be displayed again with an error from DB etc.
        // The current structure sets $message and re-displays.
        // If $message was set due to a processing error (not CSRF), regenerate token for the redisplayed form.
        if (!empty($message) && strpos($message, 'sucesso') === false) { // If message is an error
             $csrf_token = generate_csrf_token(true);
        }
    }
}

// Fetch all current settings from database
try {
    $stmt_get_all = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    $all_settings = $stmt_get_all->fetchAll(PDO::FETCH_KEY_PAIR);

    $current_cookie_banner_text = $all_settings[$cookie_banner_text_key] ?? 'Este site utiliza cookies para melhorar a sua experiência. Ao continuar navegando, você concorda com o nosso uso de cookies.';
    $current_site_name = $all_settings[$site_name_key] ?? 'FutOnline';
    $current_site_logo_filename = $all_settings[$site_logo_key] ?? null;
    $current_site_display_format = $all_settings[$site_display_format_key] ?? 'text';

} catch (PDOException $e) {
    error_log("PDOException in " . __FILE__ . " (fetching all settings): " . $e->getMessage());
    $message .= '<p style="color:red;">Ocorreu um erro no banco de dados ao carregar as configurações do site. Por favor, tente novamente.</p>';
    // Set defaults if DB fetch fails for all
    $current_cookie_banner_text = $current_cookie_banner_text ?: 'Este site utiliza cookies para melhorar a sua experiência. Ao continuar navegando, você concorda com o nosso uso de cookies.';
    $current_site_name = $current_site_name ?: 'FutOnline';
    $current_site_logo_filename = $current_site_logo_filename ?: null;
    $current_site_display_format = $current_site_display_format ?: 'text';
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

        <?php if(!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <section id="cookie-banner-settings">
            <h2>Texto do Banner de Consentimento de Cookies</h2>
            <form action="manage_settings.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div>
                    <label for="cookie_banner_text">Texto do Banner:</label>
                    <textarea id="cookie_banner_text" name="cookie_banner_text" rows="4"><?php echo htmlspecialchars($current_cookie_banner_text); ?></textarea>
                    <p style="font-size:0.8em; color:#555;">Este texto será exibido no banner de cookies no rodapé do site.</p>
                </div>
                <div>
                    <button type="submit" name="save_cookie_banner">Salvar Texto do Banner</button>
                </div>
            </form>
        </section>

        <hr style="margin-top: 30px; margin-bottom: 30px;">

        <section id="site-identity-settings">
            <h2>Identidade do Site</h2>
            <form action="manage_settings.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div>
                    <label for="site_name">Nome do Site:</label>
                    <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($current_site_name); ?>">
                </div>
                <div>
                    <label for="site_logo_file">Logo do Site (PNG, JPG, GIF, SVG, max 512KB):</label>
                    <?php if ($current_site_logo_filename && file_exists(SITE_LOGO_UPLOAD_DIR . $current_site_logo_filename)): ?>
                        <p style="margin-bottom: 10px;">Logo Atual: <img src="<?php echo SITE_LOGO_UPLOAD_DIR . htmlspecialchars($current_site_logo_filename); ?>" alt="Logo Atual" style="max-height: 60px; width:auto; vertical-align: middle; background-color: #f0f0f0; padding: 5px; border-radius:3px;"></p>
                        <p style="font-size:0.8em; color:#555;">Envie um novo arquivo para substituir o logo atual. Se nenhum arquivo for enviado, o logo atual (se houver) será mantido.</p>
                    <?php else: ?>
                        <p style="font-size:0.8em; color:#555;">Nenhum logo cadastrado. Envie um arquivo.</p>
                    <?php endif; ?>
                    <input type="file" id="site_logo_file" name="site_logo_file" accept="<?php echo implode(',', $allowed_logo_mime_types); ?>">
                </div>
                <div>
                    <label>Formato de Exibição no Cabeçalho:</label>
                    <div>
                        <input type="radio" id="display_text" name="site_display_format" value="text" <?php echo ($current_site_display_format === 'text') ? 'checked' : ''; ?>>
                        <label for="display_text" style="display:inline; font-weight:normal;">Exibir Nome do Site</label>
                    </div>
                    <div>
                        <input type="radio" id="display_logo" name="site_display_format" value="logo" <?php echo ($current_site_display_format === 'logo') ? 'checked' : ''; ?>>
                        <label for="display_logo" style="display:inline; font-weight:normal;">Exibir Logo (se existir)</label>
                    </div>
                </div>
                <div>
                    <button type="submit" name="save_site_identity">Salvar Identidade do Site</button>
                </div>
            </form>
        </section>

        <?php // Add other settings sections here in the future if needed ?>

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
