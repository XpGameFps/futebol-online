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
    // $new_cover_filename_to_save will be determined by the site's default cover setting.
    // $current_cover_filename is fetched earlier for context but isn't used for saving the new state directly.
    // The concept of uploading a new file or reverting is removed.
    // The match cover will always be set to the default site cover (if one exists).

    // $new_cover_filename_to_save should be based on $default_cover_filename_from_settings
    $new_cover_filename_to_save = $default_cover_filename_from_settings; // This can be null if no default is set

    // $upload_error_message is no longer relevant here as there's no upload.
    // $message might still be populated by other validation errors.

    // The main validation logic for other fields:
    $formatted_match_time_for_db = null;
    // Cover image upload/revert logic has been removed.
    // Validation proceeds if basic fields are present.
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
    // Removed elseif for 'revert_to_default_cover' as it's no longer an action.

    // Proceed with DB update if $message is still empty (meaning basic validations passed)
    // and $formatted_match_time_for_db is set.
    if (empty($message) && $formatted_match_time_for_db) {
        // The check for revert_to_default_cover related messages is removed.
        // if (isset($_POST['revert_to_default_cover']) && strpos($message, 'Imagem da partida revertida para a capa padrão.') !== false && strlen($message) < 100) {
        //    $message = '';
        // } // This is no longer needed.
        // if (empty($message) && $formatted_match_time_for_db) { // This inner if is now the outer if.
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
                    $_SESSION['general_message']['admin_index'] = '<p style="color:green;">Jogo atualizado com sucesso!</p>';
                    // Removed message specific to reverting cover.
                    header("Location: index.php?status=match_updated#match-" . $match_id);
                    exit;
                } else {
                    $message = '<p style="color:red;">Erro ao atualizar jogo no banco de dados.</p>';
                    // $file_was_moved_in_this_request is no longer relevant.
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), "FOREIGN KEY (`league_id`)") !== false) {
                    $message = '<p style="color:red;">Erro: ID da liga inválido.</p>';
                } elseif (strpos($e->getMessage(), "FOREIGN KEY (`home_team_id`)") !== false || strpos($e->getMessage(), "FOREIGN KEY (`away_team_id`)") !== false) {
                    $message = '<p style="color:red;">Erro: ID do time inválido.</p>';
                } else {
                    error_log("PDOException in " . __FILE__ . " (updating match ID: " . $match_id . "): " . $e->getMessage());
                    $message = '<p style="color:red;">Ocorreu um erro no banco de dados ao atualizar o jogo. Por favor, tente novamente.</p>';
                }
                // $file_was_moved_in_this_request is no longer relevant for unlinking.
            }
        // } // This was the end of the inner if, now part of the outer condition.
        // The condition for this elseif is now always false as 'revert_to_default_cover' is removed.
        // } elseif (empty($message) && !$formatted_match_time_for_db && !isset($_POST['revert_to_default_cover'])) {
        // Thus, it can be removed or simplified. If $formatted_match_time_for_db is not set, $message would already contain an error.
        // The check `!$formatted_match_time_for_db` is implicitly handled because the main `if (empty($message) && $formatted_match_time_for_db)`
        // would not pass if $formatted_match_time_for_db is null.
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_SESSION['general_message']['admin_index'])) {
    if (empty($message)) {
        $message = $_SESSION['general_message']['admin_index'];
    }
    unset($_SESSION['general_message']['admin_index']);
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
    <div class="container" style="max-width: 800px;">
        <div class="admin-layout">
            <?php require_once 'templates/navigation.php'; ?>
            <div class="main-content">
                <h1><?php echo htmlspecialchars($page_title); ?></h1>
                <?php if (!empty($message)) echo "<div class='message'>{$message}</div>"; ?>

                <?php if ($match_id && ($match_data_loaded || $_SERVER["REQUEST_METHOD"] == "POST")): ?>
                <form action="edit_match.php?id=<?php echo $match_id; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
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
                    <div>
                        <label>Imagem de Capa:</label>
                        <?php
                        // Display logic will now always try to show the site's default cover.
                        $display_image_src = null;
                        if ($default_cover_filename_from_settings) {
                            $potential_src = DEFAULT_COVER_UPLOAD_DIR . htmlspecialchars($default_cover_filename_from_settings);
                            if (file_exists($potential_src)) {
                                $display_image_src = $potential_src;
                            } else {
                                 error_log("Default cover file (from settings) not found for display in edit_match.php: " . $potential_src);
                            }
                        }

                        if ($display_image_src):
                        ?>
                            <p>Capa Padrão do Site: <img src="<?php echo $display_image_src; ?>?t=<?php echo time(); ?>" alt="Capa Padrão do Site" style="max-height: 80px; vertical-align: middle; margin-bottom:5px; border:1px solid #eee;"></p>
                            <p style="font-size:0.8em; color:#555;">Esta é a capa padrão definida para o site. Não é possível carregar uma capa específica para este jogo.</p>
                        <?php else: ?>
                            <p style="font-size:0.8em; color:#555;">Nenhuma capa padrão do site está configurada ou o arquivo não foi encontrado. Verifique as <a href="manage_settings.php">configurações do site</a>.</p>
                        <?php endif; ?>
                        <?php // Cover image file input and revert button are removed. ?>
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