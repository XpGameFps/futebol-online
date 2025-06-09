<?php
require_once 'auth_check.php';
require_once '../config.php';

define('MATCH_COVER_UPLOAD_DIR', '../uploads/covers/matches/');
define('MAX_COVER_FILE_SIZE', 2 * 1024 * 1024); // 2MB
$allowed_cover_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

$page_title = "Editar Jogo";
$message = '';
$match_id = null;

// Form data variables
$team_home_id_val = null; // Changed from text to ID
$team_away_id_val = null; // Changed from text to ID
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
    $message = '<p style="color:red;">Erro ao carregar lista de ligas: ' . $e->getMessage() . '</p>';
}

// Fetch all Teams for dropdowns
$teams_for_dropdown_edit = [];
if (isset($pdo)) {
    try {
        $stmt_teams_edit = $pdo->query("SELECT id, name FROM teams ORDER BY name ASC");
        $teams_for_dropdown_edit = $stmt_teams_edit->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $message .= '<p style="color:red;">Erro ao buscar times: ' . $e->getMessage() . '</p>'; }
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
// Fetch current match data if not a POST request for update, or if POST already has an error message
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['update_match']) || !empty($message)) {
    try {
        // Query assumes matches table uses home_team_id and away_team_id
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
        // Populate only if not a POST request that's being re-displayed due to an error
        if ($_SERVER["REQUEST_METHOD"] != "POST" || empty($message)) {
            $team_home_id_val = $match['home_team_id']; // Use ID
            $team_away_id_val = $match['away_team_id']; // Use ID
            $match_time_form_value = (new DateTime($match['match_time']))->format('Y-m-d\TH:i');
            $description = $match['description'];
            $current_league_id = $match['league_id'];
            $current_cover_filename = $match['cover_image_filename'];
            $meta_description = $match['meta_description'];
            $meta_keywords = $match['meta_keywords'];
        }
        $match_data_loaded = true;
    } catch (Exception $e) {
        $message = '<p style="color:red;">Erro ao buscar dados do jogo: ' . $e->getMessage() . '</p>';
    }
}


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_match'])) {
    $new_home_team_id = trim($_POST['home_team_id'] ?? '');
    $new_away_team_id = trim($_POST['away_team_id'] ?? '');
    $match_time_input = trim($_POST['match_time'] ?? '');
    $description_val = trim($_POST['description'] ?? null); // Use _val to avoid conflict
    $new_league_id_input = trim($_POST['league_id'] ?? '');
    $new_meta_description = trim($_POST['meta_description'] ?? null);
    $new_meta_keywords = trim($_POST['meta_keywords'] ?? null);

    // For form repopulation on error
    $team_home_id_val = $new_home_team_id;
    $team_away_id_val = $new_away_team_id;
    $match_time_form_value = $match_time_input;
    $description = $description_val;
    $current_league_id = $new_league_id_input; // For select repopulation
    $meta_description = $new_meta_description;
    $meta_keywords = $new_meta_keywords;
    // $current_cover_filename is handled by upload logic below

    // Re-fetch current_cover_filename if it wasn't set (e.g. direct POST or error on previous GET)
    if ($current_cover_filename === null && $match_id) {
        $stmt_refetch_cover_edit = $pdo->prepare("SELECT cover_image_filename FROM matches WHERE id = :id");
        $stmt_refetch_cover_edit->bindParam(':id', $match_id, PDO::PARAM_INT);
        $stmt_refetch_cover_edit->execute();
        $cover_data_edit = $stmt_refetch_cover_edit->fetch(PDO::FETCH_ASSOC);
        if ($cover_data_edit) $current_cover_filename = $cover_data_edit['cover_image_filename'];
    }
    $new_cover_filename_to_save = $current_cover_filename;
    $upload_error_message = '';

    // Validation for new team ID fields
    if (empty($new_home_team_id) || !filter_var($new_home_team_id, FILTER_VALIDATE_INT)) { $message = '<p style="color:red;">Time da casa é obrigatório.</p>'; }
    elseif (empty($new_away_team_id) || !filter_var($new_away_team_id, FILTER_VALIDATE_INT)) { $message = '<p style="color:red;">Time visitante é obrigatório.</p>'; }
    elseif ($new_home_team_id === $new_away_team_id) { $message = '<p style="color:red;">Times da casa e visitante não podem ser o mesmo.</p>'; }
    elseif (empty($match_time_input)) { $message = '<p style="color:red;">Data e hora da partida são obrigatórios.</p>';}
    else {
        try {
            $dt = new DateTime($match_time_input);
            $formatted_match_time_for_db = $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) { $message = '<p style="color:red;">Formato de data/hora inválido.</p>'; }

        if (empty($message)) {
            // Cover Image Upload Handling (same as add_match.php, including old file deletion)
            if (isset($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] == UPLOAD_ERR_OK) {
                $file_tmp_path = $_FILES['cover_image_file']['tmp_name']; $file_name = $_FILES['cover_image_file']['name'];
                $file_size = $_FILES['cover_image_file']['size']; $file_type = $_FILES['cover_image_file']['type'];
                $file_ext_array = explode('.', $file_name); $file_extension = strtolower(end($file_ext_array));
                if ($file_size > MAX_COVER_FILE_SIZE) { $upload_error_message = 'Arquivo de capa muito grande (max 2MB).'; }
                elseif (!in_array($file_type, $allowed_cover_mime_types)) { $upload_error_message = 'Tipo de arquivo de capa inválido (PNG, JPG, GIF).'; }
                else {
                    $new_uploaded_filename = uniqid('match_cover_', true) . '.' . $file_extension;
                    $destination_path = MATCH_COVER_UPLOAD_DIR . $new_uploaded_filename;
                    if (!is_dir(MATCH_COVER_UPLOAD_DIR)) { @mkdir(MATCH_COVER_UPLOAD_DIR, 0755, true); }
                    if (move_uploaded_file($file_tmp_path, $destination_path)) {
                        if ($current_cover_filename && file_exists(MATCH_COVER_UPLOAD_DIR . $current_cover_filename)) {
                            @unlink(MATCH_COVER_UPLOAD_DIR . $current_cover_filename);
                        }
                        $new_cover_filename_to_save = $new_uploaded_filename;
                    } else { $upload_error_message = 'Falha ao mover novo arquivo de capa.'; }
                }
                if (!empty($upload_error_message)) { $message = '<p style="color:red;">Erro no upload da capa: ' . $upload_error_message . '</p>'; }
            } elseif (isset($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['cover_image_file']['error'] != UPLOAD_ERR_OK) {
                $message = '<p style="color:red;">Erro no upload da capa. Código: ' . $_FILES['cover_image_file']['error'] . '</p>';
            }

            if (empty($message) || (!empty($message) && empty($upload_error_message) && isset($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] == UPLOAD_ERR_NO_FILE)) {
                $new_league_id = null;
                if (!empty($new_league_id_input) && filter_var($new_league_id_input, FILTER_VALIDATE_INT)) {
                    $new_league_id = (int)$new_league_id_input;
                }
                try {
                    // SQL assumes matches table will be altered to use home_team_id, away_team_id
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
                    if ($description_val === null) { $stmt_update->bindValue(':description', null, PDO::PARAM_NULL); } else { $stmt_update->bindParam(':description', $description_val, PDO::PARAM_STR); }
                    if ($new_league_id === null) { $stmt_update->bindValue(':league_id', null, PDO::PARAM_NULL); } else { $stmt_update->bindParam(':league_id', $new_league_id, PDO::PARAM_INT); }
                    if ($new_cover_filename_to_save === null) { $stmt_update->bindValue(':cover_image_filename', null, PDO::PARAM_NULL); } else { $stmt_update->bindParam(':cover_image_filename', $new_cover_filename_to_save, PDO::PARAM_STR); }
                    if ($new_meta_description === null) { $stmt_update->bindValue(":meta_description", null, PDO::PARAM_NULL); } else { $stmt_update->bindParam(":meta_description", $new_meta_description, PDO::PARAM_STR); }
                    if ($new_meta_keywords === null) { $stmt_update->bindValue(":meta_keywords", null, PDO::PARAM_NULL); } else { $stmt_update->bindParam(":meta_keywords", $new_meta_keywords, PDO::PARAM_STR); }
                    $stmt_update->bindParam(':id', $match_id, PDO::PARAM_INT);

                    if ($stmt_update->execute()) {
                        // Update current variables for form re-display after successful update
                        $current_home_team_id = $new_home_team_id;
                        $current_away_team_id = $new_away_team_id;
                        $current_league_id = $new_league_id;
                        $current_cover_filename = $new_cover_filename_to_save;
                        // $description, $meta_description, $meta_keywords are already updated from POST
                        $_SESSION['general_message']['admin_index'] = '<p style="color:green;">Jogo atualizado com sucesso!</p>';
                        header("Location: index.php?status=match_updated#match-" . $match_id);
                        exit;
                    } else { $message = '<p style="color:red;">Erro ao atualizar jogo no banco de dados.</p>'; }
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), "FOREIGN KEY (`league_id`)") !== false) { $message = '<p style="color:red;">Erro: ID da liga inválido.</p>'; }
                    elseif (strpos($e->getMessage(), "FOREIGN KEY (`home_team_id`)") !== false || strpos($e->getMessage(), "FOREIGN KEY (`away_team_id`)") !== false) { $message = '<p style="color:red;">Erro: ID do time inválido.</p>';}
                    else { $message = '<p style="color:red;">Erro de BD: ' . $e->getMessage() . '</p>'; }
                }
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_SESSION['general_message']['admin_index'])) {
    if(empty($message)) { $message = $_SESSION['general_message']['admin_index']; }
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
                <label for="cover_image_file">Imagem de Capa (PNG, JPG, GIF, max 2MB):</label>
                <?php if ($current_cover_filename): ?>
                    <p>Capa Atual: <img src="<?php echo MATCH_COVER_UPLOAD_DIR . htmlspecialchars($current_cover_filename); ?>" alt="Capa Atual" style="max-height: 80px; vertical-align: middle; margin-bottom:5px; border:1px solid #eee;"></p>
                    <p style="font-size:0.8em; color:#555;">Envie um novo arquivo para substituir. Se nenhum for enviado, a capa atual será mantida.</p>
                <?php else: ?>
                     <p style="font-size:0.8em; color:#555;">Nenhuma capa cadastrada. Envie um arquivo.</p>
                <?php endif; ?>
                <input type="file" id="cover_image_file" name="cover_image_file" accept="image/png, image/jpeg, image/gif">
            </div>
            <div><label for="description">Descrição (opcional):</label><textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($description ?? ''); ?></textarea></div>
            <div><label for="meta_description">Meta Descrição SEO (opcional, máx ~160 caracteres):</label><textarea id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($meta_description ?? ''); ?></textarea></div>
            <div><label for="meta_keywords">Meta Keywords SEO (opcional, separadas por vírgula):</label><input type="text" id="meta_keywords" name="meta_keywords" value="<?php echo htmlspecialchars($meta_keywords ?? ''); ?>" placeholder="ex: futebol, ao vivo, time A vs time B"></div>
            <div><button type="submit" name="update_match">Salvar Alterações</button> <a href="index.php<?php echo $match_id ? '#match-'.$match_id : ''; ?>" style="margin-left: 10px;">Cancelar</a></div>
        </form>
        <?php elseif(empty($message)):
            echo '<p style="color:red;">Não foi possível carregar os dados do jogo para edição.</p>';
            echo '<p><a href="index.php">Voltar para Lista de Jogos</a></p>';
        endif; ?>
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
});
</script>
</body>
</html>
