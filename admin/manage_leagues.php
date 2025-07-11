<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../../FutOnline_config/config.php';

if (!function_exists('generate_csrf_token')) {
    require_once 'csrf_utils.php';
}
$csrf_token = generate_csrf_token();

define('LEAGUES_LOGO_BASE_PATH_RELATIVE_TO_ADMIN', '../uploads/logos/leagues/');

$message = '';
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $reason = isset($_GET['reason']) ? htmlspecialchars($_GET['reason']) : '';
    if ($status == 'league_added') {
        $message = '<p style="color:green;">Liga adicionada com sucesso!</p>';
    } elseif ($status == 'league_deleted') {
        $message = '<p style="color:green;">Liga excluída com sucesso!</p>';
    } elseif ($status == 'league_delete_error') {
        $message = '<p style="color:red;">Erro ao excluir liga: ' . $reason . '</p>';
    } elseif ($status == 'edit_error') {
        $message = '<p style="color:red;">Erro na edição da liga: ' . $reason . '</p>';
    }
}

$add_league_form_error = '';
if (isset($_SESSION['form_error_message']['add_league'])) {
    $add_league_form_error = '<p style="color:red; background-color: #f8d7da; border:1px solid #f5c6cb; padding:10px; border-radius:4px;">' . htmlspecialchars($_SESSION['form_error_message']['add_league']) . '</p>';
    unset($_SESSION['form_error_message']['add_league']);
}

$form_data_add_league = $_SESSION['form_data']['add_league'] ?? [];

$leagues = [];
try {
    $stmt = $pdo->query("SELECT id, name, logo_filename FROM leagues ORDER BY name ASC");
    $leagues = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("PDOException in " . __FILE__ . " (fetching all leagues): " . $e->getMessage());
    $message .= '<p style="color:red;">Ocorreu um erro no banco de dados ao buscar as ligas. Por favor, tente novamente.</p>';
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
        <div class="admin-layout">
            <?php require_once 'templates/navigation.php'; ?>
            <div class="main-content">
                <h1>Gerenciar Ligas</h1>

                <?php if (!empty($message)) echo "<div class='message'>{$message}</div>"; ?>
                <?php if (!empty($add_league_form_error)) echo "<div class='message'>{$add_league_form_error}</div>"; ?>


                <h2 id="add-league-form">Adicionar Nova Liga</h2>
                <form action="add_league.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div>
                        <label for="name">Nome da Liga:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($form_data_add_league['name'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label for="logo_file">Logo da Liga (opcional, PNG, JPG, GIF, max 1MB):</label>
                        <input type="file" id="logo_file" name="logo_file" accept="image/png, image/jpeg, image/gif">
                        <?php if (!empty($form_data_add_league['logo_filename_tmp'])): ?>
                        <p style="font-size:0.8em; color:blue;">Arquivo previamente selecionado: <?php echo htmlspecialchars($form_data_add_league['logo_filename_tmp']); ?> (selecione novamente se desejar manter ou alterar)</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <button type="submit">Adicionar Liga</button>
                    </div>
                </form>
                <?php
                if (isset($_SESSION['form_data']['add_league'])) {
                    unset($_SESSION['form_data']['add_league']);
                }
                ?>

                <hr>
                <h2>Ligas Cadastradas</h2>
                <?php if (empty($leagues)): ?>
                <p>Nenhuma liga cadastrada ainda.</p>
                <?php else: ?>
                <div class="table-responsive-wrapper">
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
                            <?php foreach ($leagues as $league_item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($league_item['id']); ?></td>
                                <td><?php if (!empty($league_item['logo_filename'])): ?><img src="<?php echo LEAGUES_LOGO_BASE_PATH_RELATIVE_TO_ADMIN . htmlspecialchars($league_item['logo_filename']); ?>" alt="Logo <?php echo htmlspecialchars($league_item['name']); ?>" class="logo"><?php else: ?>N/A<?php endif; ?></td>
                                <td><?php echo htmlspecialchars($league_item['name']); ?></td>
                                <td>
                                    <a href="edit_league.php?id=<?php echo $league_item['id']; ?>" class="edit-button" style="margin-right: 5px;">Editar</a>
                                    <form action="delete_league.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta liga? Os jogos associados terão a liga removida (definida como NULA), mas não serão excluídos.');" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="league_id" value="<?php echo $league_item['id']; ?>">
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
document.addEventListener('DOMContentLoaded', function() {
    const onlineUsersCountElement = document.getElementById('online-users-count');

    function fetchOnlineUsers() {
        if (!onlineUsersCountElement) return;

        fetch('get_online_users.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' . response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data && data.status === 'success') {
                    onlineUsersCountElement.textContent = data.online_count;
                } else {
                    onlineUsersCountElement.textContent = '--';
                }
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

