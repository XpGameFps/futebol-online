<?php
// admin/manage_channels.php
require_once 'auth_check.php'; // Handles session_start()
require_once '../config.php';

define('CHANNELS_LOGO_BASE_PATH_RELATIVE_TO_ADMIN', '../uploads/logos/channels/');

// Handle general status messages from GET
$message = '';
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $reason = isset($_GET['reason']) ? htmlspecialchars($_GET['reason']) : '';
    if ($status == 'channel_added') { // From add_channel.php success
        $message = '<p style="color:green;">Canal de TV adicionado com sucesso!</p>';
    } elseif ($status == 'channel_deleted') {
        $message = '<p style="color:green;">Canal de TV excluído com sucesso!</p>';
    } elseif ($status == 'channel_delete_error') {
        $message = '<p style="color:red;">Erro ao excluir canal: ' . $reason . '</p>';
    } elseif ($status == 'edit_error') { // From edit_channel.php redirect if ID invalid or not found
         $message = '<p style="color:red;">Erro na edição do canal: ' . $reason . '</p>';
    }
    // Errors from add_channel.php itself are now handled by session messages
}

// Display and clear form error message for Add Channel if it exists from session
$add_channel_form_error = '';
if (isset($_SESSION['form_error_message']['add_channel'])) {
    $add_channel_form_error = '<p style="color:red; background-color: #f8d7da; border:1px solid #f5c6cb; padding:10px; border-radius:4px;">' . htmlspecialchars($_SESSION['form_error_message']['add_channel']) . '</p>';
    unset($_SESSION['form_error_message']['add_channel']);
}

// Retrieve form data from session for pre-filling
$form_data_add_channel = $_SESSION['form_data']['add_channel'] ?? [];
// Unset after using it for pre-filling, done after the form HTML.

// Fetch existing channels
$channels = [];
try {
    $stmt = $pdo->query("SELECT id, name, logo_filename, stream_url, sort_order FROM tv_channels ORDER BY sort_order ASC, name ASC");
    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message .= '<p style="color:red;">Erro ao buscar canais: ' . $e->getMessage() . '</p>'; // Append to general message
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Canais de TV - Painel Admin</title>
    <link rel="stylesheet" href="css/admin_style.css">
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
        <h1>Gerenciar Canais de TV</h1>

        <?php if (!empty($message)) echo "<div class='message'>{$message}</div>"; ?>
        <?php if (!empty($add_channel_form_error)) echo "<div class='message'>{$add_channel_form_error}</div>"; ?>

        <h2 id="add-channel-form">Adicionar Novo Canal de TV</h2>
        <form action="add_channel.php" method="POST" enctype="multipart/form-data">
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
                <label for="stream_url">URL do Stream Principal:</label>
                <input type="url" id="stream_url" name="stream_url" value="<?php echo htmlspecialchars($form_data_add_channel['stream_url'] ?? ''); ?>" required placeholder="https://example.com/live.m3u8">
            </div>
            <div>
                <label for="sort_order">Ordem de Classificação (opcional, menor = primeiro):</label>
                <input type="number" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($form_data_add_channel['sort_order'] ?? '0'); ?>">
            </div>
            <div>
                <label for="meta_description_channel">Meta Descrição SEO (opcional):</label>
                <textarea id="meta_description_channel" name="meta_description" rows="3"><?php echo htmlspecialchars($form_data_add_channel['meta_description'] ?? ''); ?></textarea>
            </div>
            <div>
                <label for="meta_keywords_channel">Meta Keywords SEO (opcional, separadas por vírgula):</label>
                <input type="text" id="meta_keywords_channel" name="meta_keywords" value="<?php echo htmlspecialchars($form_data_add_channel['meta_keywords'] ?? ''); ?>" placeholder="ex: tv ao vivo, canal X">
            </div>
            <div>
                <button type="submit">Adicionar Canal</button>
            </div>
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
</body>
</html>
