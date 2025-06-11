<?php
// admin/index.php
require_once 'auth_check.php';
$csrf_token = generate_csrf_token();
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
    error_log("PDOException in " . __FILE__ . " (fetching leagues): " . $e->getMessage());
    $message .= '<p style="color:red;">Ocorreu um erro no banco de dados ao buscar ligas. Por favor, tente novamente mais tarde.</p>';
}

// Fetch Teams for dropdowns
$teams_for_dropdown = [];
if (isset($pdo)) {
    try {
        $stmt_teams = $pdo->query("SELECT id, name FROM teams ORDER BY name ASC");
        $teams_for_dropdown = $stmt_teams->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("PDOException in " . __FILE__ . " (fetching teams): " . $e->getMessage());
        $message .= '<p style="color:red;">Ocorreu um erro no banco de dados ao buscar times. Por favor, tente novamente mais tarde.</p>';
    }
}


// Determine view type for matches (upcoming/all or past)
$view_type = $_GET['view'] ?? 'upcoming';
$page_subtitle = "Próximos Jogos / Jogos Recentes"; // Default subtitle

$sql_fetch_matches_base = "SELECT
                              m.id,
                              m.match_time,
                              m.description,
                              m.cover_image_filename,
                              m.league_id,
                              ht.name AS home_team_name,
                              at.name AS away_team_name,
                              l.name as league_name
                          FROM matches m
                          LEFT JOIN teams ht ON m.home_team_id = ht.id
                          LEFT JOIN teams at ON m.away_team_id = at.id
                          LEFT JOIN leagues l ON m.league_id = l.id";

if ($view_type === 'past') {
    $sql_fetch_matches = $sql_fetch_matches_base . " WHERE m.match_time < NOW() ORDER BY m.match_time DESC";
    $page_subtitle = "Jogos Passados";
} else { // Default to 'upcoming'
    $view_type = 'upcoming'; // Ensure $view_type is explicitly set for the active-view class later
    $sql_fetch_matches = $sql_fetch_matches_base . " WHERE m.match_time >= NOW() ORDER BY m.match_time ASC";
    // $page_subtitle remains as default
}

// Fetch existing matches to display
$matches = [];
try {
    // $sql_fetch_matches is now a complete and safe query string
    $stmt_matches = $pdo->query($sql_fetch_matches);
    $matches = $stmt_matches->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("PDOException in " . __FILE__ . " (fetching matches, view: " . $view_type . "): " . $e->getMessage());
    $message .= '<p style="color:red;">Ocorreu um erro no banco de dados ao buscar jogos. Por favor, tente novamente mais tarde.</p>';
    $matches = []; // Ensure matches is empty on error
}

// Fetch Saved Stream URLs for dropdowns
$saved_stream_urls_list = [];
$saved_streams_json = '[]';
if (isset($pdo)) {
    try {
        $stmt_saved_streams = $pdo->query("SELECT id, stream_name, stream_url_value FROM saved_stream_urls ORDER BY stream_name ASC");
        $saved_stream_urls_list = $stmt_saved_streams->fetchAll(PDO::FETCH_ASSOC);
        $js_friendly_streams = array_map(function($item) {
            return ['id' => $item['id'], 'name' => $item['stream_name'], 'url' => $item['stream_url_value']];
        }, $saved_stream_urls_list);
        $saved_streams_json = json_encode($js_friendly_streams);
    } catch (PDOException $e) {
        error_log("PDOException in " . __FILE__ . " (fetching saved streams): " . $e->getMessage());
        $message .= '<p style="color:red;">Ocorreu um erro no banco de dados ao buscar a biblioteca de streams. Por favor, tente novamente mais tarde.</p>';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin - Gerenciar Jogos</title>
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .stream-list { list-style: none; padding-left: 0; margin-top: 10px; }
        .stream-list li { border-bottom: 1px solid #eee; padding: 8px 0; display: flex; justify-content: space-between; align-items: center; }
        .stream-list li:last-child { border-bottom: none; }
        .stream-details { flex-grow: 1; }
        .stream-actions a, .stream-actions button { margin-left: 5px; font-size: 0.85em; padding: 3px 7px;}
        .stream-label { font-weight: bold; }
        .stream-url { font-size: 0.9em; color: #555; word-break: break-all; }
        .library-name-input { margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-layout">
            <?php require_once 'templates/navigation.php'; ?>
            <div class="main-content">
                <h1>Painel Administrativo - Jogos</h1>

        <?php if(!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php
        $general_add_stream_error = '';
        if (isset($_SESSION['form_error_message']['add_stream_general'])) {
            $general_add_stream_error = '<p style="color:red; background-color: #f8d7da; border:1px solid #f5c6cb; padding:10px; border-radius:4px;">' . htmlspecialchars($_SESSION['form_error_message']['add_stream_general']) . '</p>';
            unset($_SESSION['form_error_message']['add_stream_general']);
        }
        ?>
        <?php if (!empty($general_add_stream_error)) echo "<div class='message'>{$general_add_stream_error}</div>"; ?>

        <h2 id="add-match-form">Adicionar Novo Jogo</h2>
        <?php
        $add_match_form_error = '';
        if (isset($_SESSION['form_error_message']['add_match'])) {
            $add_match_form_error = '<div class="message"><p style="color:red; background-color: #f8d7da; border:1px solid #f5c6cb; padding:10px; border-radius:4px;">' . htmlspecialchars($_SESSION['form_error_message']['add_match']) . '</p></div>';
            unset($_SESSION['form_error_message']['add_match']);
        }
        $form_data_add_match = $_SESSION['form_data']['add_match'] ?? [];
        ?>
        <?php if (!empty($add_match_form_error)) echo $add_match_form_error; ?>
        <form action="add_match.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <fieldset>
                <legend>Informações da Partida</legend>
                <div class="form-row">
                    <div class="form-group-inline">
                        <label for="home_team_id">Time da Casa:</label>
                        <select id="home_team_id" name="home_team_id" required>
                            <option value="">-- Selecionar Time da Casa --</option>
                    <?php foreach ($teams_for_dropdown as $team_opt): ?>
                        <option value="<?php echo htmlspecialchars($team_opt['id']); ?>" <?php echo (isset($form_data_add_match['home_team_id']) && $form_data_add_match['home_team_id'] == $team_opt['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($team_opt['name']); ?>
                        </option>
                    <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group-inline">
                        <label for="away_team_id">Time Visitante:</label>
                        <select id="away_team_id" name="away_team_id" required>
                            <option value="">-- Selecionar Time Visitante --</option>
                    <?php foreach ($teams_for_dropdown as $team_opt): ?>
                        <option value="<?php echo htmlspecialchars($team_opt['id']); ?>" <?php echo (isset($form_data_add_match['away_team_id']) && $form_data_add_match['away_team_id'] == $team_opt['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($team_opt['name']); ?>
                        </option>
                    <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group-inline">
                        <label for="match_time">Data e Hora da Partida:</label>
                        <input type="datetime-local" id="match_time" name="match_time" value="<?php echo htmlspecialchars($form_data_add_match['match_time'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group-inline">
                        <label for="league_id">Liga (Opcional):</label>
                        <select id="league_id" name="league_id">
                            <option value="">-- Selecionar Liga --</option>
                            <?php foreach ($leagues_for_dropdown as $league_opt) { $selected_league = (isset($form_data_add_match['league_id']) && $form_data_add_match['league_id'] == $league_opt['id']) ? 'selected' : ''; echo '<option value="'.htmlspecialchars($league_opt['id']).'" '.$selected_league.'>'.htmlspecialchars($league_opt['name']).'</option>'; } ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="description">Descrição (opcional):</label>
                    <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($form_data_add_match['description'] ?? ''); ?></textarea>
                </div>
            </fieldset>

            <fieldset>
                <legend>Mídia</legend>
                <div>
                    <label for="cover_image_file">Imagem de Capa:</label>
                    <input type="file" id="cover_image_file" name="cover_image_file" accept="image/png, image/jpeg, image/gif">
                    <p class="form-text">Formatos suportados: JPG, PNG, GIF (máx. 2MB).</p>
                    <img id="cover_image_preview" src="#" alt="Preview da Imagem" style="display: none; max-width: 200px; margin-top: 10px;">
                    <?php if (!empty($form_data_add_match['cover_image_filename_tmp'])): ?>
                        <p style="font-size:0.8em; color:blue;">Arquivo previamente selecionado: <?php echo htmlspecialchars($form_data_add_match['cover_image_filename_tmp']); ?> (selecione novamente se desejar manter ou alterar)</p>
                    <?php endif; ?>
                </div>
            </fieldset>

            <fieldset>
                <legend>SEO</legend>
                <div>
                    <label for="meta_description">Meta Descrição (máx ~160 caracteres):</label>
                    <textarea id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($form_data_add_match['meta_description'] ?? ''); ?></textarea>
                    <span id="meta_description_counter" style="display: block; font-size: 0.85em; color: #666; margin-top: 4px;">0/160</span>
                </div>
                <div>
                    <label for="meta_keywords">Meta Keywords (separadas por vírgula):</label>
                    <input type="text" id="meta_keywords" name="meta_keywords" value="<?php echo htmlspecialchars($form_data_add_match['meta_keywords'] ?? ''); ?>" placeholder="palavra1, outra palavra, termo chave">
                </div>
            </fieldset>

            <div><button type="submit" class="btn-add-match">➕ Adicionar Jogo</button></div>
            <div id="form_submission_loader" style="display: none; margin-top: 10px; text-align: center; font-style: italic;">Salvando...</div>
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
                    <h3><?php echo htmlspecialchars($match['home_team_name'] ?? 'Time da Casa N/D'); ?> vs <?php echo htmlspecialchars($match['away_team_name'] ?? 'Time Visitante N/D'); ?></h3>
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
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
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
                            error_log("PDOException in " . __FILE__ . " (fetching streams for match ID " . $match['id'] . "): " . $e->getMessage());
                            echo '<p style="color:red;">Erro ao buscar streams para este jogo.</p>'; // User-facing, but less critical than form-wide errors
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
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
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
                    $add_stream_form_data_key = 'add_stream';
                    $current_match_id = $match['id'];

                    $add_stream_form_data = $_SESSION['form_data'][$add_stream_form_data_key][$current_match_id] ?? [];
                    $add_stream_form_error = '';
                    if (isset($_SESSION['form_error_message'][$add_stream_form_data_key][$current_match_id])) {
                        $add_stream_form_error = '<p style="color:red; font-size:0.9em; margin-top:5px;">' . htmlspecialchars($_SESSION['form_error_message'][$add_stream_form_data_key][$current_match_id]) . '</p>';
                        unset($_SESSION['form_error_message'][$add_stream_form_data_key][$current_match_id]);
                        if (empty($_SESSION['form_error_message'][$add_stream_form_data_key])) {
                            unset($_SESSION['form_error_message'][$add_stream_form_data_key]);
                        }
                    }
                    if (isset($_SESSION['form_data'][$add_stream_form_data_key][$current_match_id])) {
                        unset($_SESSION['form_data'][$add_stream_form_data_key][$current_match_id]);
                        if (empty($_SESSION['form_data'][$add_stream_form_data_key])) {
                            unset($_SESSION['form_data'][$add_stream_form_data_key]);
                        }
                    }
                    $open_details = !empty($add_stream_form_error);
                    ?>
                    <details style="margin-top:15px;" <?php echo $open_details ? 'open' : ''; ?>>
                        <summary style="cursor:pointer; color:#007bff; font-weight:bold; padding:5px; background-color:#f0f0f0; border-radius:4px;">Adicionar Novo Stream</summary>
                        <?php if (!empty($add_stream_form_error)) echo $add_stream_form_error; ?>

                        <form action="add_stream.php" method="POST" class="add-stream-form" style="margin-top:10px; padding:10px; border:1px solid #eee; border-radius:4px;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">

                            <div>
                                <label for="saved_stream_id_<?php echo $match['id']; ?>">Selecionar da Biblioteca (Opcional):</label>
                                <select name="saved_stream_id" id="saved_stream_id_<?php echo $match['id']; ?>" class="saved-stream-select" data-match-id="<?php echo $match['id']; ?>">
                                    <option value="">-- Digitar Manualmente ou Selecionar --</option>
                                    <?php foreach ($saved_stream_urls_list as $saved_stream): ?>
                                        <option value="<?php echo htmlspecialchars($saved_stream['id']); ?>" data-url="<?php echo htmlspecialchars($saved_stream['stream_url_value']); ?>" data-name="<?php echo htmlspecialchars($saved_stream['stream_name']); ?>"
                                            <?php echo (isset($add_stream_form_data['saved_stream_id']) && $add_stream_form_data['saved_stream_id'] == $saved_stream['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($saved_stream['stream_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="stream_label_<?php echo $match['id']; ?>">Rótulo do Stream (para este jogo):</label>
                                <input type="text" id="stream_label_<?php echo $match['id']; ?>" name="stream_label"
                                       value="<?php echo htmlspecialchars($add_stream_form_data['stream_label'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label for="stream_url_<?php echo $match['id']; ?>">URL do Stream:</label>
                                <input type="url" id="stream_url_<?php echo $match['id']; ?>" name="stream_url"
                                       value="<?php echo htmlspecialchars($add_stream_form_data['stream_url'] ?? ''); ?>" required placeholder="https://example.com/stream">
                            </div>
                            <div>
                                <input type="checkbox" id="save_to_library_<?php echo $match['id']; ?>" name="save_to_library" value="1" class="save-to-library-cb" data-match-id="<?php echo $match['id']; ?>" <?php echo isset($add_stream_form_data['save_to_library']) ? 'checked' : ''; ?>>
                                <label for="save_to_library_<?php echo $match['id']; ?>" style="display:inline; font-weight:normal;">Salvar esta URL na biblioteca?</label>
                            </div>
                            <div id="library_name_input_<?php echo $match['id']; ?>" class="library-name-input" style="<?php echo isset($add_stream_form_data['save_to_library']) ? 'display:block;' : 'display:none;'; ?>">
                                <label for="library_stream_name_<?php echo $match['id']; ?>">Nome para Biblioteca (se salvando):</label>
                                <input type="text" id="library_stream_name_<?php echo $match['id']; ?>" name="library_stream_name"
                                       value="<?php echo htmlspecialchars($add_stream_form_data['library_stream_name'] ?? ''); ?>" placeholder="Ex: Fonte Principal HD">
                            </div>
                            <input type="hidden" name="is_manual_entry_<?php echo $match['id']; ?>" id="is_manual_entry_<?php echo $match['id']; ?>" value="<?php echo htmlspecialchars($add_stream_form_data['is_manual_entry_' . $match['id']] ?? 'true'); ?>">

                            <div><button type="submit">Salvar Stream</button></div>
                        </form>
                    </details>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
            </div> <!-- end main-content -->
        </div> <!-- end admin-layout -->
    </div> <!-- end container -->

    <script>
        // Script for Cover Image Preview
        const coverImageInput = document.getElementById('cover_image_file');
        const coverImagePreview = document.getElementById('cover_image_preview');

        if (coverImageInput && coverImagePreview) {
            coverImageInput.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    coverImagePreview.src = URL.createObjectURL(file);
                    coverImagePreview.style.display = 'block';
                } else {
                    coverImagePreview.src = '#';
                    coverImagePreview.style.display = 'none';
                }
            });
        }

        // Script for Meta Description Counter
        const metaDescriptionTextarea = document.getElementById('meta_description');
        const metaDescriptionCounter = document.getElementById('meta_description_counter');
        const metaDescriptionLimit = 160; // Define limit

        if (metaDescriptionTextarea && metaDescriptionCounter) {
            metaDescriptionTextarea.addEventListener('input', function() {
                const currentLength = this.value.length;
                metaDescriptionCounter.textContent = currentLength + '/' + metaDescriptionLimit;
                if (currentLength > metaDescriptionLimit) {
                    metaDescriptionCounter.classList.add('limit-exceeded');
                } else {
                    metaDescriptionCounter.classList.remove('limit-exceeded');
                }
            });
            // Trigger on page load in case there's pre-filled text
            metaDescriptionTextarea.dispatchEvent(new Event('input'));
        }

        const allSavedStreamsData = <?php echo $saved_streams_json ?? '[]'; ?>;

        document.querySelectorAll('.saved-stream-select').forEach(selectElement => {
            selectElement.addEventListener('change', function() {
                const matchId = this.dataset.matchId;
                const streamUrlInput = document.getElementById('stream_url_' + matchId);
                const streamLabelInput = document.getElementById('stream_label_' + matchId);
                const libraryNameInputDiv = document.getElementById('library_name_input_' + matchId);
                const libraryStreamNameInput = document.getElementById('library_stream_name_' + matchId);
                const saveToLibraryCheckbox = document.getElementById('save_to_library_' + matchId);
                const isManualEntryInput = document.getElementById('is_manual_entry_' + matchId);

                const selectedOption = this.options[this.selectedIndex];

                if (this.value && selectedOption.dataset.url) {
                    streamUrlInput.value = selectedOption.dataset.url;
                    if (!streamLabelInput.value.trim()) {
                        streamLabelInput.value = selectedOption.dataset.name;
                    }
                    saveToLibraryCheckbox.checked = false;
                    saveToLibraryCheckbox.disabled = true;
                    libraryNameInputDiv.style.display = 'none';
                    libraryStreamNameInput.value = '';
                    libraryStreamNameInput.required = false;
                    isManualEntryInput.value = 'false';
                } else {
                    saveToLibraryCheckbox.disabled = false;
                    isManualEntryInput.value = 'true';
                    saveToLibraryCheckbox.dispatchEvent(new Event('change'));
                }
            });
            if (selectElement.value && (selectElement.options[selectElement.selectedIndex].dataset.url || "<?php echo htmlspecialchars($form_data_add_match['saved_stream_id'] ?? ''); ?>" === selectElement.value) ) {
                selectElement.dispatchEvent(new Event('change'));
            }
        });

        document.querySelectorAll('.save-to-library-cb').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const matchId = this.dataset.matchId;
                const libraryNameInputDiv = document.getElementById('library_name_input_' + matchId);
                const libraryStreamNameInput = document.getElementById('library_stream_name_' + matchId);
                const streamLabelInput = document.getElementById('stream_label_' + matchId);

                if (this.checked) {
                    libraryNameInputDiv.style.display = 'block';
                    if(!libraryStreamNameInput.value && streamLabelInput.value){
                        libraryStreamNameInput.value = streamLabelInput.value;
                    }
                    libraryStreamNameInput.required = true;
                } else {
                    libraryNameInputDiv.style.display = 'none';
                    libraryStreamNameInput.required = false;
                }
            });
            if (checkbox.checked) { checkbox.dispatchEvent(new Event('change')); }
        });

        // Script for Add Match Form Loader
        const addMatchForm = document.querySelector('form[action="add_match.php"]');
        const addMatchSubmitButton = addMatchForm ? addMatchForm.querySelector('button[type="submit"].btn-add-match') : null;
        const addMatchLoaderDiv = document.getElementById('form_submission_loader');

        if (addMatchForm && addMatchSubmitButton && addMatchLoaderDiv) {
            addMatchForm.addEventListener('submit', function(event) {
                let formIsValid = true;
                addMatchForm.querySelectorAll('[required]').forEach(function(input) {
                    // Check for actual value, not just whitespace
                    if (!input.value.trim()) {
                        formIsValid = false;
                        // Optionally focus the first invalid field, though browser validation usually does this
                        // input.focus();
                    }
                    // For file inputs, checking 'required' means a file must be selected.
                    // However, our cover image is optional. If it were required:
                    // if (input.type === 'file' && input.required && input.files.length === 0) {
                    //    formIsValid = false;
                    // }
                });

                if (formIsValid) {
                    addMatchSubmitButton.disabled = true;
                    addMatchLoaderDiv.style.display = 'block';
                } else {
                    // If form is not valid (e.g. an empty required field),
                    // HTML5 validation should prevent submission by default.
                    // If it didn't, or for extra safety, you could explicitly prevent submission:
                    // event.preventDefault();
                    // And hide loader just in case it was shown by a bypass:
                    addMatchSubmitButton.disabled = false;
                    addMatchLoaderDiv.style.display = 'none';
                }
            });
        }

        // Online users counter script
        document.addEventListener('DOMContentLoaded', function() {
            const onlineUsersCountElement_nav = document.getElementById('online-users-count');
            function fetchOnlineUsers_nav() {
                if (!onlineUsersCountElement_nav) return;
                fetch('get_online_users.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.status === 'success') {
                            onlineUsersCountElement_nav.textContent = data.online_count;
                        } else { onlineUsersCountElement_nav.textContent = '--'; }
                    })
                    .catch(error => {
                        onlineUsersCountElement_nav.textContent = 'Err';
                        console.error('Fetch error for online users (nav):', error);
                    });
            }
            fetchOnlineUsers_nav();
            setInterval(fetchOnlineUsers_nav, 30000);
        });
    </script>
</body>
</html>
