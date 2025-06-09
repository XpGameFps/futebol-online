<?php
session_start();
require_once '../config.php'; // Database connection

// Define base path for logos for display - relative to this script's location
define('LEAGUES_LOGO_BASE_PATH_RELATIVE_TO_ADMIN', '../uploads/logos/leagues/');


// Handle messages (existing message handling)
$message = '';
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $reason = isset($_GET['reason']) ? htmlspecialchars($_GET['reason']) : '';
    if ($status == 'league_added') {
        $message = '<p style="color:green;">Liga adicionada com sucesso!</p>';
    } elseif ($status == 'league_add_error') {
        if ($reason == 'league_name_exists') {
            $message = '<p style="color:red;">Erro ao adicionar liga: Este nome de liga já existe. Por favor, escolha outro nome.</p>';
        } elseif ($reason == 'file_upload_error') {
            $upload_error_msg = isset($_GET['err_msg']) ? htmlspecialchars(urldecode($_GET['err_msg'])) : 'Erro desconhecido no upload.';
            $message = '<p style="color:red;">Erro ao adicionar liga: Problema no upload do logo. ' . $upload_error_msg . '</p>';
        } else {
            $message = '<p style="color:red;">Erro ao adicionar liga: ' . $reason . '</p>';
        }
    } elseif ($status == 'league_deleted') {
        $message = '<p style="color:green;">Liga excluída com sucesso!</p>';
    } elseif ($status == 'league_delete_error') {
        $message = '<p style="color:red;">Erro ao excluir liga: ' . $reason . '</p>';
    }
}


// Fetch existing leagues
$leagues = [];
try {
    // Assuming 'logo_filename' is the new column name after update_schema_v3.sql
    $stmt = $pdo->query("SELECT id, name, logo_filename FROM leagues ORDER BY name ASC");
    $leagues = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message .= '<p style="color:red;">Erro ao buscar ligas: ' . $e->getMessage() . '</p>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Ligas - Painel Admin</title>
    <!-- Existing CSS, ensure it's complete -->
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
        input[type="text"], input[type="url"], input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 1em; }
        input[type="file"] { padding: 3px; } /* Specific padding for file input */
        button[type="submit"] { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; transition: background-color 0.3s ease; }
        button[type="submit"]:hover { background-color: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: middle; }
        th { background-color: #f0f0f0; }
        td img.logo { max-height: 30px; max-width: 100px; vertical-align: middle; margin-right: 5px; border:1px solid #eee; }
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
        <h1>Gerenciar Ligas</h1>

        <?php if(!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <h2>Adicionar Nova Liga</h2>
        <!-- IMPORTANT: Added enctype for file upload -->
        <form action="add_league.php" method="POST" enctype="multipart/form-data">
            <div>
                <label for="name">Nome da Liga:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div>
                <label for="logo_file">Logo da Liga (opcional, PNG, JPG, GIF, max 1MB):</label>
                <input type="file" id="logo_file" name="logo_file" accept="image/png, image/jpeg, image/gif">
            </div>
            <div>
                <button type="submit">Adicionar Liga</button>
            </div>
        </form>

        <h2>Ligas Cadastradas</h2>
        <?php if (empty($leagues)): ?>
            <p>Nenhuma liga cadastrada ainda.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Logo</th>
                        <th>Nome</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leagues as $league): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($league['id']); ?></td>
                            <td>
                                <?php if (!empty($league['logo_filename'])): ?>
                                    <img src="<?php echo LEAGUES_LOGO_BASE_PATH_RELATIVE_TO_ADMIN . htmlspecialchars($league['logo_filename']); ?>"
                                         alt="Logo <?php echo htmlspecialchars($league['name']); ?>" class="logo">
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($league['name']); ?></td>
                            <td>
                                <form action="delete_league.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta liga? Os jogos associados terão a liga removida (definida como NULA), mas não serão excluídos.');" style="display:inline;">
                                    <input type="hidden" name="league_id" value="<?php echo $league['id']; ?>">
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
