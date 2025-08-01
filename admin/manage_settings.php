<?php
require_once 'auth_check.php'; require_once __DIR__ . '/../../FutOnline_config/config.php'; 
if (!function_exists('generate_csrf_token')) {
    require_once 'csrf_utils.php';
}
$csrf_token = generate_csrf_token(true); 
define('SITE_LOGO_UPLOAD_DIR', '../uploads/site/');
define('MAX_LOGO_FILE_SIZE', 1024 * 512); $allowed_logo_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];

$site_name_key = 'site_name';
$site_logo_key = 'site_logo_filename';
$site_display_format_key = 'site_display_format';

$seo_homepage_title_key = 'seo_homepage_title';
$seo_homepage_description_key = 'seo_homepage_description';
$seo_homepage_keywords_key = 'seo_homepage_keywords';

define('DEFAULT_COVER_UPLOAD_DIR', '../uploads/defaults/'); define('MAX_DEFAULT_COVER_SIZE', 2 * 1024 * 1024); $allowed_default_cover_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
$default_cover_setting_key = 'default_match_cover';

$show_past_matches_homepage_key = 'show_past_matches_homepage';
$homepage_matches_limit_key = 'homepage_matches_limit';

$page_title = "Configurações do Site";
$message = '';
$cookie_banner_text_key = 'cookie_banner_text';

$current_cookie_banner_text = '';
$current_default_cover = null; $current_site_name = '';
$current_site_logo_filename = null;
$current_site_display_format = 'text'; $current_seo_homepage_title = '';
$current_seo_homepage_description = '';
$current_seo_homepage_keywords = '';
$current_show_past_matches_homepage = '0';
$current_homepage_matches_limit = '12';

if ($_SERVER["REQUEST_METHOD"] == "POST") {     if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $message = '<p style="color:red;">Falha na verificação de segurança (CSRF). Por favor, tente novamente.</p>';
        $csrf_token = generate_csrf_token(true);             } else {
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
                    $message = '<p style="color:red;">Erro ao atualizar o texto do banner de cookies.</p>';                 }
            } catch (PDOException $e) {
                error_log("PDOException in " . __FILE__ . " (save_cookie_banner): " . $e->getMessage());
                $message = '<p style="color:red;">Ocorreu um erro no banco de dados ao salvar as configurações do banner de cookies. Por favor, tente novamente.</p>';
            }
        } elseif (isset($_POST['save_site_identity'])) {
            $new_site_name = trim($_POST['site_name'] ?? 'FutOnline');
            $new_site_display_format = $_POST['site_display_format'] ?? 'text';

                        $stmt_get_current_logo = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = :key");
            $stmt_get_current_logo->bindParam(':key', $site_logo_key, PDO::PARAM_STR);
            $stmt_get_current_logo->execute();
            $logo_result = $stmt_get_current_logo->fetch(PDO::FETCH_ASSOC);
            $current_site_logo_filename_db = $logo_result ? $logo_result['setting_value'] : null;

            $new_logo_filename_to_save = $current_site_logo_filename_db;             $file_was_moved_for_site_logo = false;             $upload_error_message = ''; 
                        if (isset($_FILES['site_logo_file']) && $_FILES['site_logo_file']['error'] == UPLOAD_ERR_OK) {
                $file_tmp_path = $_FILES['site_logo_file']['tmp_name'];
                $file_name = $_FILES['site_logo_file']['name'];
                $file_size = $_FILES['site_logo_file']['size'];
                $file_type = $_FILES['site_logo_file']['type'];
                $file_ext_array = explode('.', $file_name);
                $file_extension = strtolower(end($file_ext_array));

                if ($file_size > MAX_LOGO_FILE_SIZE) {
                    $upload_error_message = 'Logo muito grande (max 512KB).';
                } elseif (!in_array($file_type, $allowed_logo_mime_types)) {
                    $upload_error_message = 'Tipo de arquivo inválido para logo (aceito: JPG, PNG, GIF, SVG).';
                } else {
                                        $image_info = @getimagesize($file_tmp_path);
                    if ($image_info === false) {
                        $upload_error_message = 'Arquivo inválido. Conteúdo não reconhecido como imagem para o logo.';
                    }
                }

                                if (empty($upload_error_message)) {
                    $new_uploaded_filename = uniqid('site_logo_', true) . '.' . $file_extension;
                    $destination_path = SITE_LOGO_UPLOAD_DIR . $new_uploaded_filename;
                    if (!is_dir(SITE_LOGO_UPLOAD_DIR)) {
                        if (!@mkdir(SITE_LOGO_UPLOAD_DIR, 0755, true)) {
                            $upload_error_message = 'Falha ao criar diretório de logo do site.';
                        }
                    }
                                        if (empty($upload_error_message) && move_uploaded_file($file_tmp_path, $destination_path)) {
                                                if ($current_site_logo_filename_db && file_exists(SITE_LOGO_UPLOAD_DIR . $current_site_logo_filename_db)) {
                             if ($current_site_logo_filename_db != $new_uploaded_filename) {
                                @unlink(SITE_LOGO_UPLOAD_DIR . $current_site_logo_filename_db);
                            }
                        }
                        $new_logo_filename_to_save = $new_uploaded_filename;                         $file_was_moved_for_site_logo = true;                        } elseif(empty($upload_error_message)) {                         $upload_error_message = 'Falha ao mover arquivo de logo do site.';
                    }
                }
            } elseif (isset($_FILES['site_logo_file']) && $_FILES['site_logo_file']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['site_logo_file']['error'] != UPLOAD_ERR_OK) {
                $upload_error_message = 'Erro no upload do logo. Código: ' . $_FILES['site_logo_file']['error'];
            }

                        if (!empty($upload_error_message)) {
                 $message .= '<p style="color:red;">' . $upload_error_message . '</p>';
            }


            if (empty($message)) {                 try {
                    $settings_to_save = [
                        $site_name_key => $new_site_name,
                        $site_display_format_key => $new_site_display_format,
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
                                        if ($file_was_moved_for_site_logo && $new_logo_filename_to_save && $new_logo_filename_to_save !== $current_site_logo_filename_db) {
                        $filePathToDelete = SITE_LOGO_UPLOAD_DIR . $new_logo_filename_to_save;
                        if (file_exists($filePathToDelete)) {
                            @unlink($filePathToDelete);
                        }
                    }
                }
            }
        } elseif (isset($_POST['save_seo_settings'])) {
            $new_seo_title = trim($_POST['seo_homepage_title'] ?? '');
            $new_seo_description = trim($_POST['seo_homepage_description'] ?? '');
            $new_seo_keywords = trim($_POST['seo_homepage_keywords'] ?? '');

            $seo_settings_to_save = [
                $seo_homepage_title_key => $new_seo_title,
                $seo_homepage_description_key => $new_seo_description,
                $seo_homepage_keywords_key => $new_seo_keywords,
            ];

            try {
                $sql_insert_seo_settings = "INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)
                                            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
                $stmt_insert_seo_settings = $pdo->prepare($sql_insert_seo_settings);

                foreach ($seo_settings_to_save as $key => $value) {
                    $stmt_insert_seo_settings->bindParam(':key', $key, PDO::PARAM_STR);
                    $stmt_insert_seo_settings->bindParam(':value', $value, PDO::PARAM_STR);
                    $stmt_insert_seo_settings->execute();
                }
                $message = '<p style="color:green;">Configurações de SEO da Homepage atualizadas com sucesso!</p>';
            } catch (PDOException $e) {
                error_log("PDOException in " . __FILE__ . " (save_seo_settings): " . $e->getMessage());
                $message = '<p style="color:red;">Ocorreu um erro no banco de dados ao salvar as configurações de SEO da Homepage. Por favor, tente novamente.</p>';
            }
                } elseif (isset($_POST['save_default_cover'])) {
                        if (isset($_FILES['default_cover_image_file']) && $_FILES['default_cover_image_file']['error'] == UPLOAD_ERR_OK) {
                $file_tmp_path = $_FILES['default_cover_image_file']['tmp_name'];
                $file_name = $_FILES['default_cover_image_file']['name'];
                $file_size = $_FILES['default_cover_image_file']['size'];
                $file_type = $_FILES['default_cover_image_file']['type'];
                $file_ext_array = explode('.', $file_name);
                $file_extension = strtolower(end($file_ext_array));

                if ($file_size > MAX_DEFAULT_COVER_SIZE) {
                    $message = '<p style="color:red;">Arquivo de capa padrão muito grande (Max 2MB).</p>';
                } elseif (!in_array($file_type, $allowed_default_cover_mime_types)) {
                    $message = '<p style="color:red;">Tipo de arquivo inválido para capa padrão (JPG, PNG, GIF).</p>';
                } else {
                    $image_info = @getimagesize($file_tmp_path);
                    if ($image_info === false) {
                        $message = '<p style="color:red;">Arquivo de capa padrão inválido. Conteúdo não é uma imagem reconhecida.</p>';
                    } else {
                        $standardized_filename = 'default_match_cover.' . $file_extension;
                        $destination_path = DEFAULT_COVER_UPLOAD_DIR . $standardized_filename;
                        if (!is_dir(DEFAULT_COVER_UPLOAD_DIR)) {
                            if (!@mkdir(DEFAULT_COVER_UPLOAD_DIR, 0755, true)) {
                                $message = '<p style="color:red;">Falha ao criar diretório de upload para imagens padrão.</p>';
                                error_log("Failed to create upload directory: " . DEFAULT_COVER_UPLOAD_DIR);
                            }
                        }
                        if (empty($message)) {
                            if ($current_default_cover && $current_default_cover !== $standardized_filename && file_exists(DEFAULT_COVER_UPLOAD_DIR . $current_default_cover)) {
                                @unlink(DEFAULT_COVER_UPLOAD_DIR . $current_default_cover);
                            }
                            if (move_uploaded_file($file_tmp_path, $destination_path)) {
                                try {
                                    $stmt_upsert = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = :value");
                                    $stmt_upsert->bindParam(':key', $default_cover_setting_key, PDO::PARAM_STR);
                                    $stmt_upsert->bindParam(':value', $standardized_filename, PDO::PARAM_STR);
                                    $stmt_upsert->execute();
                                    $current_default_cover = $standardized_filename;
                                    $message = '<p style="color:green;">Imagem de capa padrão salva com sucesso.</p>';
                                } catch (PDOException $e) {
                                    $message = '<p style="color:red;">Erro ao salvar configuração de capa padrão no banco: ' . $e->getMessage() . '</p>';
                                    if (file_exists($destination_path)) @unlink($destination_path);
                                }
                            } else {
                                $message = '<p style="color:red;">Falha ao mover arquivo de capa padrão para o destino.</p>';
                            }
                        }
                    }
                }
            } else if (isset($_FILES['default_cover_image_file']) && $_FILES['default_cover_image_file']['error'] != UPLOAD_ERR_NO_FILE) {
                $message = '<p style="color:red;">Erro no upload da capa padrão: Código ' . $_FILES['default_cover_image_file']['error'] . '</p>';
            } else {
                $message = '<p style="color:orange;">Nenhum arquivo selecionado para upload da capa padrão.</p>';
            }
        } elseif (isset($_POST['delete_default_cover'])) {
                        if ($current_default_cover && file_exists(DEFAULT_COVER_UPLOAD_DIR . $current_default_cover)) {
                @unlink(DEFAULT_COVER_UPLOAD_DIR . $current_default_cover);
            }
            try {
                $stmt_delete = $pdo->prepare("DELETE FROM site_settings WHERE setting_key = :key");
                $stmt_delete->bindParam(':key', $default_cover_setting_key, PDO::PARAM_STR);
                $stmt_delete->execute();
                $current_default_cover = null;
                $message = '<p style="color:green;">Imagem de capa padrão removida.</p>';
            } catch (PDOException $e) {
                $message = '<p style="color:red;">Erro ao remover configuração de capa padrão do banco: ' . $e->getMessage() . '</p>';
            }
                } elseif (isset($_POST['save_homepage_settings'])) {
                    $show_past_matches_homepage = isset($_POST['show_past_matches_homepage']) ? '1' : '0';
    $homepage_matches_limit = in_array($_POST['homepage_matches_limit'] ?? '12', ['12','24','30','50']) ? $_POST['homepage_matches_limit'] : '12';
    try {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->bindParam(':key', $show_past_matches_homepage_key, PDO::PARAM_STR);
        $stmt->bindParam(':value', $show_past_matches_homepage, PDO::PARAM_STR);
        $stmt->execute();
        $stmt2 = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt2->bindParam(':key', $homepage_matches_limit_key, PDO::PARAM_STR);
        $stmt2->bindParam(':value', $homepage_matches_limit, PDO::PARAM_STR);
        $stmt2->execute();
        $message = '<p style=\"color:green;\">Configurações de exibição de jogos na homepage atualizadas!</p>';
        $current_show_past_matches_homepage = $show_past_matches_homepage;
        $current_homepage_matches_limit = $homepage_matches_limit;
    } catch (PDOException $e) {
        $message = '<p style=\"color:red;\">Erro ao salvar configuração: ' . $e->getMessage() . '</p>';
    }
                } elseif (isset($_POST['save_social_theme_settings'])) {
            $settings_to_save = [
                'social_facebook' => trim($_POST['social_facebook'] ?? ''),
                'social_instagram' => trim($_POST['social_instagram'] ?? ''),
                'social_twitter' => trim($_POST['social_twitter'] ?? ''),
                'social_youtube' => trim($_POST['social_youtube'] ?? ''),
                'theme_primary_color' => trim($_POST['theme_primary_color'] ?? ''),
                'theme_secondary_color' => trim($_POST['theme_secondary_color'] ?? ''),
                'theme_bg_color' => trim($_POST['theme_bg_color'] ?? ''),
                'theme_text_color' => trim($_POST['theme_text_color'] ?? ''),
            ];
            try {
                $sql = "INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
                $stmt = $pdo->prepare($sql);
                foreach ($settings_to_save as $key => $value) {
                    $stmt->bindParam(':key', $key, PDO::PARAM_STR);
                    $stmt->bindParam(':value', $value, PDO::PARAM_STR);
                    $stmt->execute();
                }
                $message = '<p style="color:green;">Configurações de redes sociais e cores do tema atualizadas com sucesso!</p>';
            } catch (PDOException $e) {
                $message = '<p style="color:red;">Erro ao salvar configurações: ' . $e->getMessage() . '</p>';
            }
                } elseif (isset($_POST['reset_theme_colors'])) {
            $default_theme_colors = [
                'theme_primary_color' => '#00ff00',
                'theme_secondary_color' => '#0d0d0d',
                'theme_bg_color' => '#1a1a1a',
                'theme_text_color' => '#e0e0e0',
            ];
            try {
                $sql = "INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
                $stmt = $pdo->prepare($sql);
                foreach ($default_theme_colors as $key => $value) {
                    $stmt->bindParam(':key', $key, PDO::PARAM_STR);
                    $stmt->bindParam(':value', $value, PDO::PARAM_STR);
                    $stmt->execute();
                }
                $message = '<p style="color:green;">Cores do tema resetadas para os valores padrão com sucesso!</p>';
            } catch (PDOException $e) {
                $message = '<p style="color:red;">Erro ao resetar cores do tema: ' . $e->getMessage() . '</p>';
            }
                }
                if (!empty($message) && strpos($message, 'sucesso') === false) {              $csrf_token = generate_csrf_token(true);
        }
    } } 

try {
    $stmt_get_all = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    $all_settings = $stmt_get_all->fetchAll(PDO::FETCH_KEY_PAIR);

    $current_cookie_banner_text = $all_settings[$cookie_banner_text_key] ?? 'Este site utiliza cookies para melhorar a sua experiência. Ao continuar navegando, você concorda com o nosso uso de cookies.';
    $current_site_name = $all_settings[$site_name_key] ?? 'FutOnline';
    $current_site_logo_filename = $all_settings[$site_logo_key] ?? null;
    $current_site_display_format = $all_settings[$site_display_format_key] ?? 'text';

    $current_seo_homepage_title = $all_settings[$seo_homepage_title_key] ?? 'Título Padrão da Homepage';
    $current_seo_homepage_description = $all_settings[$seo_homepage_description_key] ?? 'Descrição padrão para a homepage.';
    $current_seo_homepage_keywords = $all_settings[$seo_homepage_keywords_key] ?? 'palavra1, palavra2, palavra3';
    $current_default_cover = $all_settings[$default_cover_setting_key] ?? null;
    $current_show_past_matches_homepage = $all_settings[$show_past_matches_homepage_key] ?? '0';
    $current_homepage_matches_limit = $all_settings[$homepage_matches_limit_key] ?? '12';

} catch (PDOException $e) {
    error_log("PDOException in " . __FILE__ . " (fetching all settings): " . $e->getMessage());
    $message .= '<p style="color:red;">Ocorreu um erro no banco de dados ao carregar as configurações do site. Por favor, tente novamente.</p>';
        $current_cookie_banner_text = $current_cookie_banner_text ?: 'Este site utiliza cookies para melhorar a sua experiência. Ao continuar navegando, você concorda com o nosso uso de cookies.';
    $current_site_name = $current_site_name ?: 'FutOnline';
    $current_site_logo_filename = $current_site_logo_filename ?: null;
    $current_site_display_format = $current_site_display_format ?: 'text';
    $current_seo_homepage_title = $current_seo_homepage_title ?: 'Título Padrão da Homepage';
    $current_seo_homepage_description = $current_seo_homepage_description ?: 'Descrição padrão para a homepage.';
    $current_seo_homepage_keywords = $current_seo_homepage_keywords ?: 'palavra1, palavra2, palavra3';
    $current_default_cover = $current_default_cover ?: null;
    $current_show_past_matches_homepage = $current_show_past_matches_homepage ?: '0';
}

// --- Carregar valores atuais das redes sociais e cores do tema ---
$social_keys = [
    'social_facebook', 'social_instagram', 'social_twitter', 'social_youtube',
    'theme_primary_color', 'theme_secondary_color', 'theme_bg_color', 'theme_text_color'
];
$current_social_facebook = $current_social_instagram = $current_social_twitter = $current_social_youtube = '';
$current_theme_primary_color = $current_theme_secondary_color = $current_theme_bg_color = $current_theme_text_color = '';

$sql_social_theme = "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('social_facebook','social_instagram','social_twitter','social_youtube','theme_primary_color','theme_secondary_color','theme_bg_color','theme_text_color')";
$stmt_social_theme = $pdo->query($sql_social_theme);
while ($row = $stmt_social_theme->fetch(PDO::FETCH_ASSOC)) {
    switch ($row['setting_key']) {
        case 'social_facebook': $current_social_facebook = $row['setting_value']; break;
        case 'social_instagram': $current_social_instagram = $row['setting_value']; break;
        case 'social_twitter': $current_social_twitter = $row['setting_value']; break;
        case 'social_youtube': $current_social_youtube = $row['setting_value']; break;
        case 'theme_primary_color': $current_theme_primary_color = $row['setting_value']; break;
        case 'theme_secondary_color': $current_theme_secondary_color = $row['setting_value']; break;
        case 'theme_bg_color': $current_theme_bg_color = $row['setting_value']; break;
        case 'theme_text_color': $current_theme_text_color = $row['setting_value']; break;
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
        <div class="admin-layout">
            <?php require_once 'templates/navigation.php'; ?>
            <div class="main-content">
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

        <hr style="margin-top: 30px; margin-bottom: 30px;">

        <section id="default-match-cover-settings">
            <h2>Imagem de Capa Padrão para Jogos</h2>

            <form action="manage_settings.php" method="POST" enctype="multipart/form-data" style="margin-bottom: 20px;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div>
                    <label for="default_cover_image_file">Nova Imagem de Capa Padrão (JPG, PNG, GIF, max 2MB):</label>
                    <input type="file" id="default_cover_image_file" name="default_cover_image_file" accept="image/png, image/jpeg, image/gif">
                </div>
                <div>
                    <button type="submit" name="save_default_cover">Salvar Nova Imagem Padrão</button>
                </div>
            </form>

            <?php if ($current_default_cover && file_exists(DEFAULT_COVER_UPLOAD_DIR . $current_default_cover)): ?>
                <div style="margin-bottom: 20px;">
                    <p><strong>Capa Padrão Atual:</strong></p>
                    <img src="<?php echo htmlspecialchars(DEFAULT_COVER_UPLOAD_DIR . $current_default_cover); ?>?t=<?php echo time();  ?>" alt="Capa Padrão Atual" style="max-width: 300px; max-height: 200px; border: 1px solid #ccc;">
                    <form action="manage_settings.php" method="POST" style="margin-top: 10px;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <button type="submit" name="delete_default_cover" onclick="return confirm('Tem certeza que deseja remover a imagem de capa padrão?');" class="delete-button">Remover Imagem Padrão</button>
                    </form>
                </div>
            <?php elseif ($current_default_cover): ?>
                <p style="color:orange;">Imagem de capa padrão configurada ("<?php echo htmlspecialchars($current_default_cover); ?>") mas arquivo não encontrado no servidor.</p>
                 <form action="manage_settings.php" method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <button type="submit" name="delete_default_cover" class="delete-button">Limpar Configuração de Imagem Padrão</button>
                </form>
            <?php else: ?>
                <p>Nenhuma imagem de capa padrão configurada.</p>
            <?php endif; ?>
        </section>

        <hr style="margin-top: 30px; margin-bottom: 30px;">

        <section id="homepage-seo-settings">
            <h2>SEO da Homepage</h2>
            <form action="manage_settings.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div>
                    <label for="seo_homepage_title">Título SEO:</label>
                    <input type="text" id="seo_homepage_title" name="seo_homepage_title" value="<?php echo htmlspecialchars($current_seo_homepage_title); ?>" maxlength="120">
                    <p style="font-size:0.8em; color:#555;">O título que aparecerá nos resultados de busca e na aba do navegador (recomendado: 50-60 caracteres).</p>
                </div>
                <div>
                    <label for="seo_homepage_description">Meta Descrição:</label>
                    <textarea id="seo_homepage_description" name="seo_homepage_description" rows="3" maxlength="255"><?php echo htmlspecialchars($current_seo_homepage_description); ?></textarea>
                    <p style="font-size:0.8em; color:#555;">Uma breve descrição da sua homepage (recomendado: 150-160 caracteres).</p>
                </div>
                <div>
                    <label for="seo_homepage_keywords">Meta Palavras-chave (separadas por vírgula):</label>
                    <input type="text" id="seo_homepage_keywords" name="seo_homepage_keywords" value="<?php echo htmlspecialchars($current_seo_homepage_keywords); ?>" maxlength="255">
                    <p style="font-size:0.8em; color:#555;">Palavras-chave relevantes para a sua homepage.</p>
                </div>
                <div>
                    <button type="submit" name="save_seo_settings">Salvar Configurações de SEO</button>
                </div>
            </form>
        </section>

        <hr style="margin-top: 30px; margin-bottom: 30px;">

        <section id="homepage-matches-settings">
            <h2>Exibição de Jogos na Homepage</h2>
            <form action="manage_settings.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div style="margin-bottom:10px;">
                    <input type="checkbox" id="show_past_matches_homepage" name="show_past_matches_homepage" value="1" <?php echo ($current_show_past_matches_homepage == '1') ? 'checked' : ''; ?>>
                    <label for="show_past_matches_homepage" style="display:inline; font-weight:normal;">Exibir jogos passados na homepage (ficarão após os próximos jogos)</label>
                </div>
                <div style="margin-bottom:10px;">
                    <label for="homepage_matches_limit">Quantidade de jogos na homepage:</label>
                    <select id="homepage_matches_limit" name="homepage_matches_limit">
                        <option value="12" <?php echo ($current_homepage_matches_limit == '12') ? 'selected' : ''; ?>>12</option>
                        <option value="24" <?php echo ($current_homepage_matches_limit == '24') ? 'selected' : ''; ?>>24</option>
                        <option value="30" <?php echo ($current_homepage_matches_limit == '30') ? 'selected' : ''; ?>>30</option>
                        <option value="50" <?php echo ($current_homepage_matches_limit == '50') ? 'selected' : ''; ?>>50</option>
                    </select>
                </div>
                <div>
                    <button type="submit" name="save_homepage_settings">Salvar Configuração</button>
                </div>
            </form>
        </section>

        <hr style="margin-top: 30px; margin-bottom: 30px;">

        <section id="social-theme-settings">
            <h2>Redes Sociais & Cores do Tema</h2>
            <?php if (!empty($message) && (isset($_POST['save_social_theme_settings']) || isset($_POST['reset_theme_colors']))): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <form action="manage_settings.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div>
                    <label for="social_facebook">Facebook:</label>
                    <input type="url" id="social_facebook" name="social_facebook" value="<?php echo htmlspecialchars($current_social_facebook ?? ''); ?>" placeholder="https://facebook.com/sua_pagina">
                </div>
                <div>
                    <label for="social_instagram">Instagram:</label>
                    <input type="url" id="social_instagram" name="social_instagram" value="<?php echo htmlspecialchars($current_social_instagram ?? ''); ?>" placeholder="https://instagram.com/sua_pagina">
                </div>
                <div>
                    <label for="social_twitter">Twitter:</label>
                    <input type="url" id="social_twitter" name="social_twitter" value="<?php echo htmlspecialchars($current_social_twitter ?? ''); ?>" placeholder="https://twitter.com/sua_pagina">
                </div>
                <div>
                    <label for="social_youtube">YouTube:</label>
                    <input type="url" id="social_youtube" name="social_youtube" value="<?php echo htmlspecialchars($current_social_youtube ?? ''); ?>" placeholder="https://youtube.com/sua_pagina">
                </div>
                <hr>
                <div>
                    <label for="theme_primary_color">Cor Primária:</label>
                    <input type="color" id="theme_primary_color" name="theme_primary_color" value="<?php echo htmlspecialchars($current_theme_primary_color ?? '#00ff00'); ?>">
                </div>
                <div>
                    <label for="theme_secondary_color">Cor Secundária:</label>
                    <input type="color" id="theme_secondary_color" name="theme_secondary_color" value="<?php echo htmlspecialchars($current_theme_secondary_color ?? '#0d0d0d'); ?>">
                </div>
                <div>
                    <label for="theme_bg_color">Cor de Fundo:</label>
                    <input type="color" id="theme_bg_color" name="theme_bg_color" value="<?php echo htmlspecialchars($current_theme_bg_color ?? '#1a1a1a'); ?>">
                </div>
                <div>
                    <label for="theme_text_color">Cor do Texto:</label>
                    <input type="color" id="theme_text_color" name="theme_text_color" value="<?php echo htmlspecialchars($current_theme_text_color ?? '#e0e0e0'); ?>">
                </div>
                <div style="margin-top:15px;">
                    <button type="submit" name="save_social_theme_settings">Salvar Redes Sociais & Cores</button>
                </div>
            </form>
            
            <!-- Formulário separado para resetar cores do tema -->
            <form action="manage_settings.php" method="POST" style="margin-top:15px; padding-top:15px; border-top:1px solid #ddd;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div>
                    <p style="margin-bottom:10px; color:#666; font-size:14px;">
                        <strong>⚠️ Atenção:</strong> Esta ação irá resetar todas as cores do tema para os valores padrão.
                    </p>
                    <button type="submit" name="reset_theme_colors" 
                            style="background-color:#dc3545; color:white; border:none; padding:12px 20px; border-radius:6px; cursor:pointer; font-size:14px; transition:background-color 0.2s;" 
                            onmouseover="this.style.backgroundColor='#c82333'" 
                            onmouseout="this.style.backgroundColor='#dc3545'"
                            onclick="return confirm('⚠️ ATENÇÃO: Esta ação irá resetar todas as cores do tema para os valores padrão do FutOnline.\n\n🎨 Cores que serão resetadas:\n• Cor Primária: Verde (#00ff00)\n• Cor Secundária: Preto (#0d0d0d)\n• Cor de Fundo: Cinza Escuro (#1a1a1a)\n• Cor do Texto: Cinza Claro (#e0e0e0)\n\nTem certeza que deseja continuar?');">
                        🔄 Resetar Cores para Padrão
                    </button>
                </div>
            </form>
        </section>

        <hr style="margin-top: 30px; margin-bottom: 30px;">
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
                    throw new Error('Network response was not ok: ' + response.statusText);
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

