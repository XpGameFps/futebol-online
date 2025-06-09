<?php
// admin/manage_channels.php
require_once 'auth_check.php'; // Session start and login check
require_once '../config.php'; // Database connection

// Define base path for channel logos for display - relative to this script's location
define('CHANNELS_LOGO_BASE_PATH_RELATIVE_TO_ADMIN', '../uploads/logos/channels/');

// Handle messages (existing message handling)
$message = '';
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $reason = isset($_GET['reason']) ? htmlspecialchars($_GET['reason']) : '';
    if ($status == 'channel_added') {
        $message = '<p style="color:green;">Canal de TV adicionado com sucesso!</p>';
    } elseif ($status == 'channel_add_error') {
        if ($reason == 'file_upload_error') {
            $upload_error_msg = isset($_GET['err_msg']) ? htmlspecialchars(urldecode($_GET['err_msg'])) : 'Erro desconhecido no upload.';
            $message = '<p style="color:red;">Erro ao adicionar canal: Problema no upload do logo. ' . $upload_error_msg . '</p>';
        } else {
            $message = '<p style="color:red;">Erro ao adicionar canal: ' . $reason . '</p>';
        }
    } elseif ($status == 'channel_deleted') {
        $message = '<p style="color:green;">Canal de TV excluído com sucesso!</p>';
    } elseif ($status == 'channel_delete_error') {
        $message = '<p style="color:red;">Erro ao excluir canal: ' . $reason . '</p>';
    }
}

// Fetch existing channels
$channels = [];
try {
    // Assuming 'logo_filename' is the new column name after update_schema_v3.sql
    $stmt = $pdo->query("SELECT id, name, logo_filename, stream_url, sort_order FROM tv_channels ORDER BY sort_order ASC, name ASC");
    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message .= '<p style="color:red;">Erro ao buscar canais: ' . $e->getMessage() . '</p>';
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
            <div> <!-- Group for main nav links -->
                <a href="index.php">Painel Principal (Jogos)</a>
                <a href="manage_leagues.php">Gerenciar Ligas</a>
                <a href="manage_channels.php">Gerenciar Canais TV</a>
                <a href="manage_settings.php">Configurações</a> <!-- New Link -->
            </div>
            <div class="nav-user-info"> <!-- Group for user info and logout -->
                Usuário: <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?> |
                <a href="logout.php" class="logout-link">Logout</a>
            </div>
        </nav>
        <h1>Gerenciar Canais de TV</h1>

        <?php if(!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <h2>Adicionar Novo Canal de TV</h2>
        <!-- IMPORTANT: Added enctype for file upload -->
        <form action="add_channel.php" method="POST" enctype="multipart/form-data">
            <div>
                <label for="name">Nome do Canal:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div>
                <label for="logo_file">Logo do Canal (opcional, PNG, JPG, GIF, max 1MB):</label>
                <input type="file" id="logo_file" name="logo_file" accept="image/png, image/jpeg, image/gif">
            </div>
            <div>
                <label for="stream_url">URL do Stream Principal:</label>
                <input type="url" id="stream_url" name="stream_url" required placeholder="https://example.com/live.m3u8">
            </div>
            <div>
                <label for="sort_order">Ordem de Classificação (opcional, menor = primeiro):</label>
                <input type="number" id="sort_order" name="sort_order" value="0">
            </div>
            <div>
                <button type="submit">Adicionar Canal</button>
            </div>
        </form>

        <h2>Canais Cadastrados</h2>
        <?php if (empty($channels)): ?>
            <p>Nenhum canal de TV cadastrado ainda.</p>
        <?php else: ?>
            <div class="table-responsive-wrapper"> {/* ADDED WRAPPER */}
              <table>
                  <thead>
                      <tr>
                          <th>ID</th>
                        <th>Logo</th>
                        <th>Nome</th>
                        <th>URL do Stream</th>
                        <th>Ordem</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($channels as $channel): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($channel['id']); ?></td>
                            <td>
                                <?php if (!empty($channel['logo_filename'])): ?>
                                    <img src="<?php echo CHANNELS_LOGO_BASE_PATH_RELATIVE_TO_ADMIN . htmlspecialchars($channel['logo_filename']); ?>"
                                         alt="Logo <?php echo htmlspecialchars($channel['name']); ?>" class="logo">
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($channel['name']); ?></td>
                            <td><?php echo htmlspecialchars($channel['stream_url']); ?></td>
                            <td><?php echo htmlspecialchars($channel['sort_order']); ?></td>
                            <td>
                                <form action="delete_channel.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este canal de TV?');" style="display:inline;">
                                    <input type="hidden" name="channel_id" value="<?php echo $channel['id']; ?>">
                                    <button type="submit" class="delete-button">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
