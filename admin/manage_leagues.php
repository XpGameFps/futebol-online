<?php
// admin/manage_leagues.php
require_once 'auth_check.php'; // Session start and login check
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
          <div class="table-responsive-wrapper"> {/* ADDED WRAPPER */}
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
                                <a href="edit_league.php?id=<?php echo $league['id']; ?>" class="edit-button" style="margin-right: 5px;">Editar</a>
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
