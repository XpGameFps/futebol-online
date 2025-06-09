<?php
// admin/index.php
require_once 'auth_check.php';
require_once '../config.php';

// Handle messages
$message = '';
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $reason = isset($_GET['reason']) ? htmlspecialchars($_GET['reason']) : '';
    if ($status == 'match_added') {
        $message = '<p style="color:green;">Jogo adicionado com sucesso!</p>';
    } elseif ($status == 'match_add_error') {
        if ($reason == 'file_upload_error') {
            $upload_error_msg = isset($_GET['err_msg']) ? htmlspecialchars(urldecode($_GET['err_msg'])) : 'Erro desconhecido no upload da capa.';
            $message = '<p style="color:red;">Erro ao adicionar jogo: Problema no upload da imagem de capa. ' . $upload_error_msg . '</p>';
        } else {
            $message = '<p style="color:red;">Erro ao adicionar jogo: ' . htmlspecialchars($reason) . '</p>';
        }
    } elseif ($status == 'match_deleted') {
        $message = '<p style="color:green;">Jogo excluído com sucesso!</p>';
    } elseif ($status == 'match_delete_error') {
        $message = '<p style="color:red;">Erro ao excluir jogo: ' . htmlspecialchars($reason) . '</p>';
    } elseif ($status == 'stream_added') {
        $message = '<p style="color:green;">Stream adicionado com sucesso!</p>';
    } elseif ($status == 'stream_add_error') {
        $message = '<p style="color:red;">Erro ao adicionar stream: ' . htmlspecialchars($reason) . '</p>';
    } elseif ($status == 'stream_deleted') {
        $message = '<p style="color:green;">Stream excluído com sucesso!</p>';
    } elseif ($status == 'stream_delete_error') {
        $message = '<p style="color:red;">Erro ao excluir stream: ' . htmlspecialchars($reason) . '</p>';
    } elseif ($status == 'stream_updated') {
        $message = '<p style="color:green;">Stream atualizado com sucesso!</p>';
    } elseif ($status == 'stream_update_error') {
        $message = '<p style="color:red;">Erro ao atualizar stream: ' . htmlspecialchars($reason) . '</p>';
    } elseif ($status == 'stream_edit_error') {
        $message = '<p style="color:red;">Erro ao editar stream: ' . htmlspecialchars($reason) . '</p>';
    }
}


// Fetch existing leagues for the dropdown
$leagues_for_dropdown = [];
try {
    $stmt_leagues = $pdo->query("SELECT id, name FROM leagues ORDER BY name ASC");
    $leagues_for_dropdown = $stmt_leagues->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // $message .= '<p style="color:red;">Erro ao buscar ligas para o formulário: ' . $e->getMessage() . '</p>';
}

// Determine view type for matches (upcoming/all or past)
$view_type = $_GET['view'] ?? 'upcoming';

$matches_sql_condition = "m.match_time >= NOW()";
$matches_order_by = "m.match_time ASC";
$page_subtitle = "Próximos Jogos / Jogos Recentes";

if ($view_type === 'past') {
    $matches_sql_condition = "m.match_time < NOW()";
    $matches_order_by = "m.match_time DESC";
    $page_subtitle = "Jogos Passados";
}

// Fetch existing matches to display
$matches = [];
try {
    $sql_fetch_matches = "SELECT m.id, m.team_home, m.team_away, m.match_time, m.description, m.cover_image_filename, l.name as league_name
                          FROM matches m
                          LEFT JOIN leagues l ON m.league_id = l.id
                          WHERE {$matches_sql_condition}
                          ORDER BY {$matches_order_by}";
    $stmt_matches = $pdo->query($sql_fetch_matches);
    $matches = $stmt_matches->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message .= '<p style="color:red;">Erro ao buscar jogos: ' . $e->getMessage() . '</p>';
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin - Gerenciar Jogos</title>
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        /* Additional styles specific to this page or minor overrides if necessary */
        .stream-list { list-style: none; padding-left: 0; margin-top: 10px; }
        .stream-list li {
            border-bottom: 1px solid #eee;
            padding: 8px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stream-list li:last-child { border-bottom: none; }
        .stream-details { flex-grow: 1; }
        .stream-actions a, .stream-actions button { margin-left: 5px; font-size: 0.85em; padding: 3px 7px;}
        .stream-label { font-weight: bold; }
        .stream-url { font-size: 0.9em; color: #555; word-break: break-all; }
    </style>
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
        <h1>Painel Administrativo - Jogos</h1>

        <?php if(!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php
        // Handle general add_stream error if redirected from add_stream.php due to invalid match_id at POST stage
        $general_add_stream_error = '';
        if (isset($_SESSION['form_error_message']['add_stream_general'])) {
            $general_add_stream_error = '<p style="color:red; background-color: #f8d7da; border:1px solid #f5c6cb; padding:10px; border-radius:4px;">' . htmlspecialchars($_SESSION['form_error_message']['add_stream_general']) . '</p>';
            unset($_SESSION['form_error_message']['add_stream_general']);
        }
        ?>
        <?php if (!empty($general_add_stream_error)) echo "<div class='message'>{$general_add_stream_error}</div>"; ?>

        <h2 id="add-match-form">Adicionar Novo Jogo</h2>
        <?php
        // Display and clear form error message for Add Match if it exists
        $add_match_form_error = '';
        if (isset($_SESSION['form_error_message']['add_match'])) {
            $add_match_form_error = '<div class="message"><p style="color:red; background-color: #f8d7da; border:1px solid #f5c6cb; padding:10px; border-radius:4px;">' . htmlspecialchars($_SESSION['form_error_message']['add_match']) . '</p></div>';
            unset($_SESSION['form_error_message']['add_match']);
        }
        $form_data_add_match = $_SESSION['form_data']['add_match'] ?? []; // Get once for efficiency
        ?>
        <?php if (!empty($add_match_form_error)) echo $add_match_form_error; ?>
        <form action="add_match.php" method="POST" enctype="multipart/form-data">
            <div><label for="team_home">Time da Casa:</label><input type="text" id="team_home" name="team_home" value="<?php echo htmlspecialchars($form_data_add_match['team_home'] ?? ''); ?>" required></div>
            <div><label for="team_away">Time Visitante:</label><input type="text" id="team_away" name="team_away" value="<?php echo htmlspecialchars($form_data_add_match['team_away'] ?? ''); ?>" required></div>
            <div><label for="match_time">Data e Hora da Partida:</label><input type="datetime-local" id="match_time" name="match_time" value="<?php echo htmlspecialchars($form_data_add_match['match_time'] ?? ''); ?>" required></div>
            <div><label for="league_id">Liga (Opcional):</label><select id="league_id" name="league_id"><option value="">-- Selecionar Liga --</option><?php foreach ($leagues_for_dropdown as $league_opt) { $selected_league = (isset($form_data_add_match['league_id']) && $form_data_add_match['league_id'] == $league_opt['id']) ? 'selected' : ''; echo '<option value="'.htmlspecialchars($league_opt['id']).'" '.$selected_league.'>'.htmlspecialchars($league_opt['name']).'</option>'; } ?></select></div>
            <div>
                <label for="cover_image_file">Imagem de Capa (opcional, PNG, JPG, GIF, max 2MB):</label>
                <input type="file" id="cover_image_file" name="cover_image_file" accept="image/png, image/jpeg, image/gif">
                <?php if (!empty($form_data_add_match['cover_image_filename_tmp'])): ?>
                    <p style="font-size:0.8em; color:blue;">Arquivo previamente selecionado: <?php echo htmlspecialchars($form_data_add_match['cover_image_filename_tmp']); ?> (selecione novamente se desejar manter ou alterar)</p>
                <?php endif; ?>
            </div>
            <div><label for="description">Descrição (opcional):</label><textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($form_data_add_match['description'] ?? ''); ?></textarea></div>
            <div><label for="meta_description">Meta Descrição SEO (opcional, máx ~160 caracteres):</label><textarea id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($form_data_add_match['meta_description'] ?? ''); ?></textarea></div>
            <div><label for="meta_keywords">Meta Keywords SEO (opcional, separadas por vírgula):</label><input type="text" id="meta_keywords" name="meta_keywords" value="<?php echo htmlspecialchars($form_data_add_match['meta_keywords'] ?? ''); ?>" placeholder="ex: futebol, ao vivo, time A vs time B"></div>
            <div><button type="submit">Adicionar Jogo</button></div>
        </form>
        <?php
        if (isset($_SESSION['form_data']['add_match'])) {
            unset($_SESSION['form_data']['add_match']);
        }
        ?>
        <hr>

        <h2><?php echo htmlspecialchars($page_subtitle ?? 'Lista de Jogos'); ?></h2>
        <div class="view-switcher">
            <a href="index.php?view=upcoming" class="<?php echo ($view_type ?? 'upcoming') === 'upcoming' ? 'active-view' : ''; ?>">Próximos/Recentes</a>
            <a href="index.php?view=past" class="<?php echo ($view_type ?? 'upcoming') === 'past' ? 'active-view' : ''; ?>">Jogos Passados</a>
        </div>

        <?php if (empty($matches)): ?>
            <p>Nenhum jogo encontrado para esta visualização.</p>
        <?php else: ?>
            <?php foreach ($matches as $match): ?>
                <div class="match-item" id="match-<?php echo $match['id']; ?>">
                    <h3><?php echo htmlspecialchars($match['team_home']); ?> vs <?php echo htmlspecialchars($match['team_away']); ?></h3>
                    <?php if (!empty($match['cover_image_filename'])): ?>
                        <img src="../uploads/covers/matches/<?php echo htmlspecialchars($match['cover_image_filename']); ?>" alt="Capa" class="match-cover-admin">
                    <?php endif; ?>
                    <p><strong>Horário:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($match['match_time']))); ?></p>
                    <p><strong>Liga:</strong> <?php echo htmlspecialchars($match['league_name'] ?? 'N/A'); ?></p>
                    <?php if (!empty($match['description'])): ?>
                        <p><strong>Descrição:</strong> <?php echo nl2br(htmlspecialchars($match['description'])); ?></p>
                    <?php endif; ?>

                    <div style="margin-top:10px;">
                        <a href="edit_match.php?id=<?php echo $match['id']; ?>" class="edit-button" style="margin-right: 5px; margin-bottom:5px; display:inline-block;">Editar Jogo</a>
                        <form action="delete_match.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este jogo? Esta ação não pode ser desfeita e removerá também a capa e todos os streams associados.');" style="display:inline-block;">
                            <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                            <button type="submit" class="delete-button">Excluir Jogo</button>
                        </form>
                    </div>

                    <hr style="margin-top:15px; margin-bottom:10px;">
                    <h4>Streams Cadastrados:</h4>
                    <?php
                    $match_streams = [];
                    if (isset($pdo)) {
                        try {
                            $stmt_streams = $pdo->prepare("SELECT id, stream_url, stream_label FROM streams WHERE match_id = :match_id ORDER BY id ASC");
                            $stmt_streams->bindParam(':match_id', $match['id'], PDO::PARAM_INT);
                            $stmt_streams->execute();
                            $match_streams = $stmt_streams->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            echo '<p style="color:red;">Erro ao buscar streams.</p>';
                        }
                    }
                    ?>
                    <?php if (empty($match_streams)): ?>
                        <p style="font-size:0.9em; color:#555;">Nenhum stream cadastrado para este jogo.</p>
                    <?php else: ?>
                        <ul class="stream-list">
                            <?php foreach ($match_streams as $stream): ?>
                                <li>
                                    <div class="stream-details">
                                        <span class="stream-label"><?php echo htmlspecialchars($stream['stream_label']); ?></span><br>
                                        <span class="stream-url"><?php echo htmlspecialchars($stream['stream_url']); ?></span>
                                    </div>
                                    <div class="stream-actions">
                                        <a href="edit_stream.php?id=<?php echo $stream['id']; ?>&match_id=<?php echo $match['id']; ?>" class="edit-button">Editar</a>
                                        <form action="delete_stream.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este stream?');" style="display:inline;">
                                            <input type="hidden" name="stream_id" value="<?php echo $stream['id']; ?>">
                                            <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                                            <button type="submit" class="delete-button">Excluir</button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php
                    // Retrieve and clear form data/error for this specific match's add stream form
                    $add_stream_form_data_key = 'add_stream'; // Main key for add_stream related session data
                    $current_match_id = $match['id']; // Current match ID for context

                    $add_stream_form_data = $_SESSION['form_data'][$add_stream_form_data_key][$current_match_id] ?? [];
                    $add_stream_form_error = '';
                    if (isset($_SESSION['form_error_message'][$add_stream_form_data_key][$current_match_id])) {
                        $add_stream_form_error = '<p style="color:red; font-size:0.9em; margin-top:5px;">' . htmlspecialchars($_SESSION['form_error_message'][$add_stream_form_data_key][$current_match_id]) . '</p>';
                        unset($_SESSION['form_error_message'][$add_stream_form_data_key][$current_match_id]);
                        // If sub-array becomes empty, unset it too.
                        if (empty($_SESSION['form_error_message'][$add_stream_form_data_key])) {
                            unset($_SESSION['form_error_message'][$add_stream_form_data_key]);
                        }
                    }
                    // Unset form data for this specific stream form after retrieving it
                    if (isset($_SESSION['form_data'][$add_stream_form_data_key][$current_match_id])) {
                        unset($_SESSION['form_data'][$add_stream_form_data_key][$current_match_id]);
                        // If sub-array becomes empty, unset it too.
                        if (empty($_SESSION['form_data'][$add_stream_form_data_key])) {
                            unset($_SESSION['form_data'][$add_stream_form_data_key]);
                        }
                    }

                    $open_details = !empty($add_stream_form_error);
                    ?>
                    <details style="margin-top:15px;" <?php echo $open_details ? 'open' : ''; ?>>
                        <summary style="cursor:pointer; color:#007bff; font-weight:bold; padding:5px; background-color:#f0f0f0; border-radius:4px;">Adicionar Novo Stream</summary>
                        <?php if (!empty($add_stream_form_error)) echo $add_stream_form_error; // Display error inside details ?>
                        <form action="add_stream.php" method="POST" class="add-stream-form" style="margin-top:10px; padding:10px; border:1px solid #eee; border-radius:4px;">
                            <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                            <div>
                                <label for="stream_url_<?php echo $match['id']; ?>">URL do Stream:</label>
                                <input type="url" id="stream_url_<?php echo $match['id']; ?>" name="stream_url"
                                       value="<?php echo htmlspecialchars($add_stream_form_data['stream_url'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label for="stream_label_<?php echo $match['id']; ?>">Rótulo do Stream:</label>
                                <input type="text" id="stream_label_<?php echo $match['id']; ?>" name="stream_label"
                                       value="<?php echo htmlspecialchars($add_stream_form_data['stream_label'] ?? ''); ?>" required>
                            </div>
                            <div><button type="submit">Salvar Stream</button></div>
                        </form>
                    </details>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
