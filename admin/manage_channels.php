<?php
session_start();
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
    <!-- Existing CSS from previous step, ensure it's complete -->
    <style>
        /* Ensure all necessary CSS from previous steps is here */
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; padding:0; background-color: #f4f7f6; color: #333; }
        .container { width: 90%; max-width: 1000px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        nav { display: flex; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        nav a { margin-right: 15px; text-decoration: none; color: #007bff; font-weight: bold; }
        nav a:hover { text-decoration: underline; color: #0056b3; }
        nav a.action-link { margin-left: auto; }
        h1, h2 { color: #333; }
        h1 { text-align: center; margin-bottom:30px; }
        h2 { margin-top: 30px; border-bottom: 2px solid #007bff; padding-bottom: 5px; color: #007bff;}
        form div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="url"], input[type="number"], input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 1em; }
        input[type="file"] { padding: 3px; } /* Specific padding for file input */
        button[type="submit"] { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; transition: background-color 0.3s ease; }
        button[type="submit"]:hover { background-color: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: middle; word-break: break-all; }
        th { background-color: #f0f0f0; }
        td img.logo { max-height: 30px; max-width: 100px; vertical-align: middle; margin-right: 5px; border: 1px solid #eee; }
        .delete-button { background-color: #dc3545; color: white; padding: 5px 10px; border:none; border-radius:4px; cursor:pointer; font-size:0.9em; text-decoration:none; display:inline-block; }
        .delete-button:hover { background-color: #c82333; }
        .message { margin-bottom: 20px; }
        .message p { padding: 15px; border-radius: 5px; font-weight: bold; margin:0; }
        .message p[style*="color:green;"] { background-color: #d4edda; color: #155724 !important; border: 1px solid #c3e6cb; }
        .message p[style*="color:red;"] { background-color: #f8d7da; color: #721c24 !important; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <nav>
            <a href="index.php">Painel Principal (Jogos)</a>
            <a href="manage_leagues.php">Gerenciar Ligas</a>
            <a href="manage_channels.php">Gerenciar Canais TV</a>
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
