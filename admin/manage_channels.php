<?php
require_once 'auth_check.php'; require_once __DIR__ . '/../../FutOnline_config/config.php';

if (!function_exists('generate_csrf_token')) {
    require_once 'csrf_utils.php';
}
$csrf_token = generate_csrf_token();

define('CHANNELS_LOGO_BASE_PATH_RELATIVE_TO_ADMIN', '../uploads/logos/channels/');

$message = '';
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $reason = isset($_GET['reason']) ? htmlspecialchars($_GET['reason']) : '';
    if ($status == 'channel_added') {
        $message = '<p style="color:green;">Canal de TV adicionado com sucesso!</p>';
    } elseif ($status == 'channel_deleted') {
        $message = '<p style="color:green;">Canal de TV excluído com sucesso!</p>';
    } elseif ($status == 'channel_delete_error') {
        $message = '<p style="color:red;">Erro ao excluir canal: ' . $reason . '</p>';
    } elseif ($status == 'edit_error') {
         $message = '<p style="color:red;">Erro na edição do canal: ' . $reason . '</p>';
    } elseif ($status == 'saved_stream_updated') {         $message = '<p style="color:green;">Stream salvo atualizado com sucesso!</p>';
    }
}

$add_channel_form_error = '';
if (isset($_SESSION['form_error_message']['add_channel'])) {
    $add_channel_form_error = '<p style="color:red; background-color: #f8d7da; border:1px solid #f5c6cb; padding:10px; border-radius:4px;">' . htmlspecialchars($_SESSION['form_error_message']['add_channel']) . '</p>';
    unset($_SESSION['form_error_message']['add_channel']);
}

$form_data_add_channel = $_SESSION['form_data']['add_channel'] ?? [];

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
        $message .= '<p style="color:red;">Ocorreu um erro no banco de dados ao buscar a biblioteca de streams. Por favor, tente novamente.</p>';
    }
}

$channels = [];
try {
    $stmt = $pdo->query("SELECT id, name, logo_filename, stream_url, sort_order FROM tv_channels ORDER BY sort_order ASC, name ASC");
    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("PDOException in " . __FILE__ . " (fetching channels): " . $e->getMessage());
    $message .= '<p style="color:red;">Ocorreu um erro no banco de dados ao buscar os canais. Por favor, tente novamente.</p>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Canais de TV - Painel Admin</title>
    <link rel="stylesheet" href="css/admin_style.css">
    <style> .library-name-input { margin-top: 5px; } </style>
</head>
<body>
    <div class="container">
        <div class="admin-layout">
            <?php require_once 'templates/navigation.php'; ?>
            <div class="main-content">
                <h1>Gerenciar Canais de TV</h1>

                <?php if (!empty($message)) echo "<div class='message'>{$message}</div>"; ?>
                <?php if (!empty($add_channel_form_error)) echo "<div class='message'>{$add_channel_form_error}</div>"; ?>

                <h2 id="add-channel-form">Adicionar Novo Canal de TV</h2>
                <form action="add_channel.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div>
                        <label for="name">Nome do Canal:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($form_data_add_channel['name'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label for="logo_file">Logo do Canal (opcional, PNG, JPG, GIF, max 1MB):</label>
                        <input type="file" id="logo_file" name="logo_file" accept="image/png, image/jpeg, image/gif">
                        <?php if (!empty($form_data_add_channel['logo_filename_tmp'])): ?>
                            <p style="font-size:0.8em; color:blue;">Arquivo previamente selecionado: <?php echo htmlspecialchars($form_data_add_channel['logo_filename_tmp']); ?> (selecione novamente)</p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="saved_stream_id_channel_add">Selecionar da Biblioteca (Opcional):</label>
                        <select name="saved_stream_id" id="saved_stream_id_channel_add" class="saved-stream-select-channel">
                            <option value="">-- Digitar Manualmente ou Selecionar --</option>
                            <?php foreach ($saved_stream_urls_list as $saved_stream): ?>
                                <option value="<?php echo htmlspecialchars($saved_stream['id']); ?>"
                                        data-url="<?php echo htmlspecialchars($saved_stream['stream_url_value']); ?>"
                                        <?php echo (isset($form_data_add_channel['saved_stream_id']) && $form_data_add_channel['saved_stream_id'] == $saved_stream['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($saved_stream['stream_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="stream_url_channel_add">URL do Stream Principal:</label>
                        <input type="url" id="stream_url_channel_add" name="stream_url" value="<?php echo htmlspecialchars($form_data_add_channel['stream_url'] ?? ''); ?>" required placeholder="https://example.com/live.m3u8">
                    </div>
                    <div>
                        <input type="checkbox" id="save_to_library_channel_add" name="save_to_library" value="1" class="save-to-library-cb-channel"
                               <?php echo !empty($form_data_add_channel['save_to_library']) ? 'checked' : ''; ?>>
                        <label for="save_to_library_channel_add" style="display:inline; font-weight:normal;">Salvar esta URL na biblioteca?</label>
                    </div>
                    <div id="library_name_input_channel_add" class="library-name-input" style="display:<?php echo !empty($form_data_add_channel['save_to_library']) ? 'block' : 'none'; ?>;">
                        <label for="library_stream_name_channel_add">Nome para Biblioteca (se salvando):</label>
                        <input type="text" id="library_stream_name_channel_add" name="library_stream_name"
                               value="<?php echo htmlspecialchars($form_data_add_channel['library_stream_name'] ?? ''); ?>" placeholder="Ex: Fonte Principal HD">
                    </div>
                    <input type="hidden" name="is_manual_entry_channel_add" id="is_manual_entry_channel_add" value="<?php echo htmlspecialchars($form_data_add_channel['is_manual_entry_channel_add'] ?? 'true'); ?>">

                    <div><label for="sort_order">Ordem de Classificação (opcional, menor = primeiro):</label><input type="number" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($form_data_add_channel['sort_order'] ?? '0'); ?>"></div>
                    <div><label for="meta_description_channel">Meta Descrição SEO (opcional):</label><textarea id="meta_description_channel" name="meta_description" rows="3"><?php echo htmlspecialchars($form_data_add_channel['meta_description'] ?? ''); ?></textarea></div>
                    <div><label for="meta_keywords_channel">Meta Keywords SEO (opcional, separadas por vírgula):</label><input type="text" id="meta_keywords_channel" name="meta_keywords" value="<?php echo htmlspecialchars($form_data_add_channel['meta_keywords'] ?? ''); ?>" placeholder="ex: tv ao vivo, canal X"></div>
                    <div><button type="submit" name="add_channel_submit_button">Adicionar Canal</button></div>
                </form>
                <?php
                if (isset($_SESSION['form_data']['add_channel'])) {
                    unset($_SESSION['form_data']['add_channel']);
                }
                ?>

                <hr>
                <h2>Canais Cadastrados</h2>
                <?php if (empty($channels)): ?>
                    <p>Nenhum canal de TV cadastrado ainda.</p>
                <?php else: ?>
                    <div class="table-responsive-wrapper">
                    <table>
                        <thead><tr><th>ID</th><th>Logo</th><th>Nome</th><th>URL do Stream</th><th>Ordem</th><th>Ação</th></tr></thead>
                        <tbody>
                            <?php foreach ($channels as $channel_item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($channel_item['id']); ?></td>
                                    <td><?php if (!empty($channel_item['logo_filename'])): ?><img src="<?php echo CHANNELS_LOGO_BASE_PATH_RELATIVE_TO_ADMIN . htmlspecialchars($channel_item['logo_filename']); ?>" alt="Logo <?php echo htmlspecialchars($channel_item['name']); ?>" class="logo"><?php else: ?>N/A<?php endif; ?></td>
                                    <td><?php echo htmlspecialchars($channel_item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($channel_item['stream_url']); ?></td>
                                    <td><?php echo htmlspecialchars($channel_item['sort_order']); ?></td>
                                    <td>
                                        <a href="edit_channel.php?id=<?php echo $channel_item['id']; ?>" class="edit-button" style="margin-right: 5px;">Editar</a>
                                        <form action="delete_channel.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este canal de TV?');" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="channel_id" value="<?php echo $channel_item['id']; ?>">
                                            <button type="submit" class="delete-button">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        const allSavedStreamsDataChannel = <?php echo $saved_streams_json ?? '[]'; ?>;

        document.querySelectorAll('.saved-stream-select-channel').forEach(selectElement => {
            selectElement.addEventListener('change', function() {
                const streamUrlInput = document.getElementById('stream_url_channel_add');
                const libraryNameInputDiv = document.getElementById('library_name_input_channel_add');
                const saveToLibraryCheckbox = document.getElementById('save_to_library_channel_add');
                const isManualEntryInput = document.getElementById('is_manual_entry_channel_add');

                const selectedOption = this.options[this.selectedIndex];
                const selectedUrl = selectedOption.dataset.url;

                if (this.value && selectedUrl) {
                    streamUrlInput.value = selectedUrl;
                    saveToLibraryCheckbox.checked = false;
                    saveToLibraryCheckbox.disabled = true;
                    libraryNameInputDiv.style.display = 'none';
                    document.getElementById('library_stream_name_channel_add').value = '';
                    isManualEntryInput.value = 'false';
                } else {
                    saveToLibraryCheckbox.disabled = false;
                    isManualEntryInput.value = 'true';
                    saveToLibraryCheckbox.dispatchEvent(new Event('change'));
                }
            });
            if (selectElement.value && (selectElement.options[selectElement.selectedIndex].dataset.url || "<?php echo htmlspecialchars($form_data_add_channel['saved_stream_id'] ?? ''); ?>" === selectElement.value) ) {
                selectElement.dispatchEvent(new Event('change'));
            }
        });

        document.querySelectorAll('.save-to-library-cb-channel').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const libraryNameInputDiv = document.getElementById('library_name_input_channel_add');
                const libraryStreamNameInput = document.getElementById('library_stream_name_channel_add');
                const channelNameInput = document.getElementById('name');

                if (this.checked) {
                    libraryNameInputDiv.style.display = 'block';
                    if (!libraryStreamNameInput.value && channelNameInput.value) {
                        libraryStreamNameInput.value = channelNameInput.value;
                    }
                    libraryStreamNameInput.required = true;
                } else {
                    libraryNameInputDiv.style.display = 'none';
                    libraryStreamNameInput.required = false;
                }
            });
            if (checkbox.checked) { checkbox.dispatchEvent(new Event('change')); }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const savedStreamChannelSelect = document.getElementById('saved_stream_id_channel_add');
            if (savedStreamChannelSelect) {
                makeSelectSearchable(savedStreamChannelSelect);
            }

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
    <script src="js/searchable_select.js"></script>
</body>
</html>

