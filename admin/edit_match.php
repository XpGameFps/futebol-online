<?php
require_once 'auth_check.php';
require_once '../config.php';

define('MATCH_COVER_UPLOAD_DIR', '../uploads/covers/matches/');
define('DEFAULT_COVER_UPLOAD_DIR', '../uploads/defaults/');
define('MAX_COVER_FILE_SIZE', 2 * 1024 * 1024); // 2MB
$allowed_cover_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

$page_title = "Editar Jogo";
$message = '';
$match_id = null;

// Fetch default cover filename from settings
$default_cover_filename_from_settings = null;
$default_cover_setting_key = 'default_match_cover';
try {
    $stmt_get_default_cover = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = :key");
    $stmt_get_default_cover->bindParam(':key', $default_cover_setting_key, PDO::PARAM_STR);
    $stmt_get_default_cover->execute();
    $default_cover_result = $stmt_get_default_cover->fetch(PDO::FETCH_ASSOC);
    if ($default_cover_result && !empty($default_cover_result['setting_value'])) {
        if (file_exists(DEFAULT_COVER_UPLOAD_DIR . $default_cover_result['setting_value'])) {
            $default_cover_filename_from_settings = $default_cover_result['setting_value'];
        } else {
            error_log("Default cover file not found: " . DEFAULT_COVER_UPLOAD_DIR . $default_cover_result['setting_value']);
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching default cover in edit_match.php: " . $e->getMessage());
}

// Form data variables
$team_home_id_val = null;
$team_away_id_val = null;
$match_time_form_value = '';
$description = '';
$current_league_id = null;
$current_cover_filename = null;
$meta_description = '';
$meta_keywords = '';

// Fetch all leagues for the dropdown
$all_leagues = [];
try {
    $stmt_all_leagues = $pdo->query("SELECT id, name FROM leagues ORDER BY name ASC");
    $all_leagues = $stmt_all_leagues->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("PDOException in " . __FILE__ . " (fetching all leagues): " . $e->getMessage());
    $message = '<p style="color:red;">Ocorreu um erro no banco de dados ao carregar as ligas. Por favor, tente novamente.</p>';
}

// Fetch all Teams for dropdowns
$teams_for_dropdown_edit = [];
if (isset($pdo)) {
    try {
        $stmt_teams_edit = $pdo->query("SELECT id, name FROM teams ORDER BY name ASC");
        $teams_for_dropdown_edit = $stmt_teams_edit->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("PDOException in " . __FILE__ . " (fetching all teams): " . $e->getMessage());
        $message .= '<p style="color:red;">Ocorreu um erro no banco de dados ao carregar os times. Por favor, tente novamente.</p>';
    }
}

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $match_id = (int)$_GET['id'];
} else if (isset($_POST['match_id']) && filter_var($_POST['match_id'], FILTER_VALIDATE_INT)) {
    $match_id = (int)$_POST['match_id'];
}

if ($match_id === null) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $_SESSION['general_message']['admin_index'] = '<p style="color:red;">ID do jogo inválido ou ausente.</p>';
    }
    header("Location: index.php?status=edit_error&reason=invalid_match_id");
    exit;
}

$match_data_loaded = false;
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['update_match']) || !empty($message)) {
    try {
        $stmt_fetch = $pdo->prepare("SELECT home_team_id, away_team_id, match_time, description, league_id, cover_image_filename, meta_description, meta_keywords FROM matches WHERE id = :id");
        $stmt_fetch->bindParam(':id', $match_id, PDO::PARAM_INT);
        $stmt_fetch->execute();
        $match = $stmt_fetch->fetch(PDO::FETCH_ASSOC);
        if (!$match) {
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $_SESSION['general_message']['admin_index'] = '<p style="color:red;">Jogo não encontrado para edição.</p>';
            }
            header("Location: index.php?status=edit_error&reason=match_not_found");
            exit;
        }
        if ($_SERVER["REQUEST_METHOD"] != "POST" || empty($message)) {
            $team_home_id_val = $match['home_team_id'];
            $team_away_id_val = $match['away_team_id'];
            $match_time_form_value = (new DateTime($match['match_time']))->format('Y-m-d\TH:i');
            $description = $match['description'];
            $current_league_id = $match['league_id'];
            $current_cover_filename = $match['cover_image_filename'];
            $meta_description = $match['meta_description'];
            $meta_keywords = $match['meta_keywords'];
        }
        $match_data_loaded = true;
    } catch (Exception $e) {
        error_log("Exception in " . __FILE__ . " (fetching match data for ID: " . $match_id . "): " . $e->getMessage());
        $message = '<p style="color:red;">Ocorreu um erro ao carregar os dados do jogo. Por favor, tente novamente.</p>';
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_match'])) {
    $new_home_team_id = trim($_POST['home_team_id'] ?? '');
    $new_away_team_id = trim($_POST['away_team_id'] ?? '');
    $match_time_input = trim($_POST['match_time'] ?? '');
    $description_val = trim($_POST['description'] ?? null);
    $new_league_id_input = trim($_POST['league_id'] ?? '');
    $new_meta_description = trim($_POST['meta_description'] ?? null);
    $new_meta_keywords = trim($_POST['meta_keywords'] ?? null);

    $team_home_id_val = $new_home_team_id;
    $team_away_id_val = $new_away_team_id;
    $match_time_form_value = $match_time_input;
    $description = $description_val;
    $current_league_id = $new_league_id_input;
    $meta_description = $new_meta_description;
    $meta_keywords = $new_meta_keywords;

    $file_was_moved_in_this_request = false;

    if ($_SERVER["REQUEST_METHOD"] == "POST" && !$match_data_loaded && $match_id) {
        $stmt_refetch_match_post = $pdo->prepare("SELECT cover_image_filename FROM matches WHERE id = :id");
        $stmt_refetch_match_post->bindParam(':id', $match_id, PDO::PARAM_INT);
        $stmt_refetch_match_post->execute();
        $match_post_data = $stmt_refetch_match_post->fetch(PDO::FETCH_ASSOC);
        if ($match_post_data) {
            $current_cover_filename = $match_post_data['cover_image_filename'];
        }
    }
    // Initialize cover-related variables
    $new_cover_filename_to_save = $current_cover_filename; // Start with current, might change
    $new_file_uploaded_path_edit = null; // For cleaning up a new file if DB op fails
    $old_specific_cover_to_delete = null; // For cleaning up old specific file if replaced or reverted

    // Handle "Revert to Default" Checkbox
    if (isset($_POST['revert_to_default_cover']) && $_POST['revert_to_default_cover'] == '1') {
        if ($current_cover_filename && $current_cover_filename !== $default_cover_filename_from_settings) {
            // Only mark for deletion if it's a specific cover (not default and not null)
             if (file_exists(MATCH_COVER_UPLOAD_DIR . $current_cover_filename)) {
                $old_specific_cover_to_delete = MATCH_COVER_UPLOAD_DIR . $current_cover_filename;
            }
        }
        $new_cover_filename_to_save = $default_cover_filename_from_settings; // This can be null if no default is set
        $message .= '<p style="color:blue;">Imagem da partida será revertida para a capa padrão do site (se definida).</p>';
    }
    // Handle New File Upload (only if "Revert to Default" is NOT checked)
    elseif (isset($_FILES['new_cover_image_file']) && $_FILES['new_cover_image_file']['error'] == UPLOAD_ERR_OK) {
        $uploaded_file = $_FILES['new_cover_image_file'];

        if ($uploaded_file['size'] > MAX_COVER_FILE_SIZE) {
            $message .= '<p style="color:red;">Erro: O novo arquivo da capa excede o tamanho máximo de ' . (MAX_COVER_FILE_SIZE / 1024 / 1024) . 'MB.</p>';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $uploaded_file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_type, $allowed_cover_mime_types)) {
                $message .= '<p style="color:red;">Erro: Tipo de arquivo da nova capa inválido. Permitidos: JPG, PNG, GIF.</p>';
            } else {
                $file_extension = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION);
                $unique_edit_cover_filename = 'match_cover_' . uniqid() . '.' . $file_extension;
                $destination_path = MATCH_COVER_UPLOAD_DIR . $unique_edit_cover_filename;

                if (move_uploaded_file($uploaded_file['tmp_name'], $destination_path)) {
                    $new_file_uploaded_path_edit = $destination_path; // Track for potential cleanup

                    // If there was an old specific cover, mark it for deletion
                    if ($current_cover_filename && $current_cover_filename !== $default_cover_filename_from_settings) {
                         if (file_exists(MATCH_COVER_UPLOAD_DIR . $current_cover_filename)) {
                            $old_specific_cover_to_delete = MATCH_COVER_UPLOAD_DIR . $current_cover_filename;
                        }
                    }
                    $new_cover_filename_to_save = $unique_edit_cover_filename;
                    $message .= '<p style="color:green;">Nova imagem de capa carregada com sucesso. Será salva com as alterações.</p>';
                    $file_was_moved_in_this_request = true; // Used to prevent duplicate success messages
                } else {
                    $message .= '<p style="color:red;">Erro ao mover o novo arquivo da capa para o diretório de uploads.</p>';
                }
            }
        }
    }

    // The main validation logic for other fields:
    $formatted_match_time_for_db = null;
    if (empty($new_home_team_id) || !filter_var($new_home_team_id, FILTER_VALIDATE_INT)) {
        $message .= '<p style="color:red;">Time da casa é obrigatório.</p>';
    } elseif (empty($new_away_team_id) || !filter_var($new_away_team_id, FILTER_VALIDATE_INT)) {
        $message .= '<p style="color:red;">Time visitante é obrigatório.</p>';
    } elseif ($new_home_team_id === $new_away_team_id) {
        $message .= '<p style="color:red;">Times da casa e visitante não podem ser o mesmo.</p>';
    } elseif (empty($match_time_input)) {
        $message .= '<p style="color:red;">Data e hora da partida são obrigatórios.</p>';
    } else {
        try {
            $dt = new DateTime($match_time_input);
            $formatted_match_time_for_db = $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $message .= '<p style="color:red;">Formato de data/hora inválido.</p>';
        }
    }

    // Proceed with DB update if critical validation errors did not occur for essential fields
    // Message might contain non-critical info like "cover will be reverted"
    $critical_error_check = preg_match('/Erro:/i', $message) && !preg_match('/Erro ao mover o novo arquivo da capa/i', $message) && !preg_match('/Erro: O novo arquivo da capa excede/i', $message) && !preg_match('/Erro: Tipo de arquivo da nova capa inválido/i', $message);

    if (!$critical_error_check && $formatted_match_time_for_db) {
        // Clear non-critical messages if we are about to proceed with DB update successfully
        if (strpos($message, 'Imagem da partida será revertida para a capa padrão do site (se definida).') !== false && strlen($message) < 150) {
             if (!$file_was_moved_in_this_request) $message = ''; // Clear if only revert message
        }
        if (strpos($message, 'Nova imagem de capa carregada com sucesso. Será salva com as alterações.') !== false && strlen($message) < 150) {
             $message = ''; // Clear if only upload success message
        }

        $new_league_id = null;
            if (!empty($new_league_id_input) && filter_var($new_league_id_input, FILTER_VALIDATE_INT)) {
                $new_league_id = (int)$new_league_id_input;
            }
            try {
                $sql_update = "UPDATE matches SET
                                home_team_id = :home_team_id, away_team_id = :away_team_id,
                                match_time = :match_time, description = :description, league_id = :league_id,
                                cover_image_filename = :cover_image_filename,
                                meta_description = :meta_description, meta_keywords = :meta_keywords
                              WHERE id = :id";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->bindParam(':home_team_id', $new_home_team_id, PDO::PARAM_INT);
                $stmt_update->bindParam(':away_team_id', $new_away_team_id, PDO::PARAM_INT);
                $stmt_update->bindParam(':match_time', $formatted_match_time_for_db, PDO::PARAM_STR);
                if ($description_val === null) {
                    $stmt_update->bindValue(':description', null, PDO::PARAM_NULL);
                } else {
                    $stmt_update->bindParam(':description', $description_val, PDO::PARAM_STR);
                }
                if ($new_league_id === null) {
                    $stmt_update->bindValue(':league_id', null, PDO::PARAM_NULL);
                } else {
                    $stmt_update->bindParam(':league_id', $new_league_id, PDO::PARAM_INT);
                }
                if ($new_cover_filename_to_save === null) {
                    $stmt_update->bindValue(':cover_image_filename', null, PDO::PARAM_NULL);
                } else {
                    $stmt_update->bindParam(':cover_image_filename', $new_cover_filename_to_save, PDO::PARAM_STR);
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
                $stmt_update->bindParam(':id', $match_id, PDO::PARAM_INT);

                if ($stmt_update->execute()) {
                    // Success, now delete old specific cover if marked
                    if ($old_specific_cover_to_delete && file_exists($old_specific_cover_to_delete)) {
                        @unlink($old_specific_cover_to_delete);
                    }
                    // Combine messages if any, or set default success message
                    $final_success_message = '<p style="color:green;">Jogo atualizado com sucesso!</p>';
                    if(!empty($message) && !preg_match('/Erro:/i', $message)) { // Append non-error messages from cover handling
                        $final_success_message = $message . $final_success_message;
                    }
                    $_SESSION['general_message']['admin_index'] = $final_success_message;
                    header("Location: index.php?status=match_updated#match-" . $match_id);
                    exit;
                } else {
                    $message = '<p style="color:red;">Erro ao atualizar jogo no banco de dados.</p>';
                    // If DB update failed, and a new file was uploaded in this attempt, delete it
                    if ($new_file_uploaded_path_edit && file_exists($new_file_uploaded_path_edit)) {
                        @unlink($new_file_uploaded_path_edit);
                    }
                }
            } catch (PDOException $e) {
                // If DB update failed, and a new file was uploaded in this attempt, delete it
                if ($new_file_uploaded_path_edit && file_exists($new_file_uploaded_path_edit)) {
                    @unlink($new_file_uploaded_path_edit);
                }
                if (strpos($e->getMessage(), "FOREIGN KEY (`league_id`)") !== false) {
                    $message = '<p style="color:red;">Erro: ID da liga inválido.</p>';
                } elseif (strpos($e->getMessage(), "FOREIGN KEY (`home_team_id`)") !== false || strpos($e->getMessage(), "FOREIGN KEY (`away_team_id`)") !== false) {
                    $message = '<p style="color:red;">Erro: ID do time inválido.</p>';
                } else {
                    error_log("PDOException in " . __FILE__ . " (updating match ID: " . $match_id . "): " . $e->getMessage());
                    $message = '<p style="color:red;">Ocorreu um erro no banco de dados ao atualizar o jogo. Por favor, tente novamente.</p>';
                }
            }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_SESSION['general_message']['admin_index'])) {
    if (empty($message) || $message == '<p style="color:green;">Jogo atualizado com sucesso!</p>' || $message == '<p style="color:blue;">Imagem da partida será revertida para a capa padrão do site (se definida).</p>' || $message == '<p style="color:green;">Nova imagem de capa carregada com sucesso. Será salva com as alterações.</p>') {
        $message = $_SESSION['general_message']['admin_index'];
    }
    unset($_SESSION['general_message']['admin_index']);
}

// This part is for displaying the form on GET request or after POST if errors occurred.
// Ensure $current_cover_filename is from the freshly loaded $match data for display.
if ($match_data_loaded && $_SERVER["REQUEST_METHOD"] != "POST" && $match) { // on GET
    $current_cover_filename = $match['cover_image_filename'];
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && $match_id && !$match_data_loaded) { // on POST if initial load failed but have ID
    // Re-fetch just for current cover if needed, though $current_cover_filename should be set from earlier POST logic
    $stmt_refetch_display = $pdo->prepare("SELECT cover_image_filename FROM matches WHERE id = :id");
    $stmt_refetch_display->bindParam(':id', $match_id, PDO::PARAM_INT);
    $stmt_refetch_display->execute();
    $match_display_data = $stmt_refetch_display->fetch(PDO::FETCH_ASSOC);
    if ($match_display_data) {
        $current_cover_filename = $match_display_data['cover_image_filename'];
    }
}
// If it's a POST request, $current_cover_filename was already set at the beginning of POST block.
// If it's GET and $match_data_loaded, it's also set.

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - Painel Admin</title>
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .current-cover-container { margin-bottom: 15px; }
        .current-cover-container img { max-height: 100px; border: 1px solid #ddd; border-radius: 4px; }
        .cover-actions label { display: block; margin-bottom: 5px; }
        .cover-actions input[type="file"] { margin-bottom: 10px; }
        .cover-actions .checkbox-label { font-weight: normal; display: inline-block; margin-left: 5px;}
    </style>
</head>
<body>
    <div class="container" style="max-width: 800px;">
        <div class="admin-layout">
            <?php require_once 'templates/navigation.php'; ?>
            <div class="main-content">
                <h1><?php echo htmlspecialchars($page_title); ?></h1>
                <?php if (!empty($message)) echo "<div class='message'>{$message}</div>"; ?>

                <?php if ($match_id && ($match_data_loaded || $_SERVER["REQUEST_METHOD"] == "POST")): ?>
                <form action="edit_match.php?id=<?php echo $match_id; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">

                    <div>
                        <label for="home_team_id_edit">Time da Casa:</label>
                        <select id="home_team_id_edit" name="home_team_id" required>
                            <option value="">-- Selecionar --</option>
                            <?php foreach ($teams_for_dropdown_edit as $team_opt): ?>
                                <option value="<?php echo htmlspecialchars($team_opt['id']); ?>" <?php echo ($team_home_id_val == $team_opt['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($team_opt['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="away_team_id_edit">Time Visitante:</label>
                        <select id="away_team_id_edit" name="away_team_id" required>
                            <option value="">-- Selecionar --</option>
                            <?php foreach ($teams_for_dropdown_edit as $team_opt): ?>
                                <option value="<?php echo htmlspecialchars($team_opt['id']); ?>" <?php echo ($team_away_id_val == $team_opt['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($team_opt['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label for="match_time">Data e Hora da Partida:</label><input type="datetime-local" id="match_time" name="match_time" value="<?php echo htmlspecialchars($match_time_form_value); ?>" required></div>
                    <div>
                        <label for="league_id_edit">Liga (Opcional):</label>
                        <select id="league_id_edit" name="league_id">
                            <option value="">-- Nenhuma Liga --</option>
                            <?php foreach ($all_leagues as $league_opt): ?>
                                <option value="<?php echo htmlspecialchars($league_opt['id']); ?>" <?php echo ($current_league_id == $league_opt['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($league_opt['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="current-cover-container">
                        <label>Imagem de Capa Atual:</label>
                        <?php
                        $display_image_path = null;
                        $is_default_cover_displayed = false;
                        if ($current_cover_filename && $current_cover_filename !== $default_cover_filename_from_settings && file_exists(MATCH_COVER_UPLOAD_DIR . $current_cover_filename)) {
                            $display_image_path = MATCH_COVER_UPLOAD_DIR . htmlspecialchars($current_cover_filename);
                            echo '<p><img src="' . $display_image_path . '?t=' . time() . '" alt="Capa Atual Específica do Jogo"></p>';
                        } elseif ($default_cover_filename_from_settings && file_exists(DEFAULT_COVER_UPLOAD_DIR . $default_cover_filename_from_settings)) {
                            $display_image_path = DEFAULT_COVER_UPLOAD_DIR . htmlspecialchars($default_cover_filename_from_settings);
                            echo '<p><img src="' . $display_image_path . '?t=' . time() . '" alt="Capa Padrão do Site"> (Padrão do site)</p>';
                            $is_default_cover_displayed = true;
                        } else {
                            echo '<p>Nenhuma imagem de capa definida ou arquivo não encontrado.</p>';
                        }
                        ?>
                    </div>

                    <div class="cover-actions">
                        <label for="new_cover_image_file">Nova Imagem de Capa (opcional, substitui a atual):</label>
                        <input type="file" id="new_cover_image_file" name="new_cover_image_file" accept="image/jpeg,image/png,image/gif" onchange="previewEditCover(event)">
                        <img id="edit_cover_image_preview" src="#" alt="Prévia da nova capa" style="display:none; max-height: 80px; vertical-align: middle; margin-bottom:5px; border:1px solid #eee;">

                        <?php // Show revert checkbox only if a specific cover is set and it's not the same as default, or if no cover is set but there is a default to revert to.
                        $can_revert = ($current_cover_filename && $current_cover_filename !== $default_cover_filename_from_settings) || (!$current_cover_filename && $default_cover_filename_from_settings);
                        if ($can_revert && !$is_default_cover_displayed): // Also, don't show if default is already displayed as current
                        ?>
                        <div>
                            <input type="checkbox" id="revert_to_default_cover" name="revert_to_default_cover" value="1">
                            <label for="revert_to_default_cover" class="checkbox-label">Remover capa específica e usar capa padrão do site (<?php echo $default_cover_filename_from_settings ? htmlspecialchars($default_cover_filename_from_settings) : 'nenhuma definida'; ?>).</label>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div><label for="description">Descrição (opcional):</label><textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($description ?? ''); ?></textarea></div>
                    <div><label for="meta_description">Meta Descrição SEO (opcional, máx ~160 caracteres):</label><textarea id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($meta_description ?? ''); ?></textarea></div>
                    <div><label for="meta_keywords">Meta Keywords SEO (opcional, separadas por vírgula):</label><input type="text" id="meta_keywords" name="meta_keywords" value="<?php echo htmlspecialchars($meta_keywords ?? ''); ?>" placeholder="ex: futebol, ao vivo, time A vs time B"></div>
                    <div><button type="submit" name="update_match">Salvar Alterações</button> <a href="index.php<?php echo $match_id ? '#match-'.$match_id : ''; ?>" style="margin-left: 10px;">Cancelar</a></div>
                </form>
                <?php elseif (empty($message)): ?>
                    <p style="color:red;">Não foi possível carregar os dados do jogo para edição.</p>
                    <p><a href="index.php">Voltar para Lista de Jogos</a></p>
                <?php endif; ?>
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
                if (!response.ok) { throw new Error('Network response was not ok: ' . response.statusText); }
                return response.json();
            })
            .then(data => {
                if (data && data.status === 'success') { onlineUsersCountElement.textContent = data.online_count; }
                else { onlineUsersCountElement.textContent = '--'; }
            })
            .catch(error => {
                onlineUsersCountElement.textContent = 'Err';
                console.error('Fetch error for online users:', error);
            });
    }
    fetchOnlineUsers();
    setInterval(fetchOnlineUsers, 30000);

    const homeTeamEditSelect = document.getElementById('home_team_id_edit');
    if (homeTeamEditSelect) {
        makeSelectSearchable(homeTeamEditSelect);
    }

    const awayTeamEditSelect = document.getElementById('away_team_id_edit');
    if (awayTeamEditSelect) {
        makeSelectSearchable(awayTeamEditSelect);
    }

    const leagueEditSelect = document.getElementById('league_id_edit');
    if (leagueEditSelect) {
        makeSelectSearchable(leagueEditSelect);
    }
});
</script>
<script src="js/searchable_select.js"></script>
</body>
</html>