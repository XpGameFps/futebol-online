<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../../FutOnline_config/config.php';

if (!function_exists('generate_csrf_token')) {
    require_once 'csrf_utils.php';
}
$csrf_token = generate_csrf_token(); 
$page_title = "Gerenciar Administradores";
$message = ''; $error_message = ''; 
if (isset($_SESSION['admin_flash_message'])) {
    $flash = $_SESSION['admin_flash_message'];
    if ($flash['type'] === 'success') {
        $message = htmlspecialchars($flash['text']);
    } else {
        $error_message = htmlspecialchars($flash['text']);
    }
    unset($_SESSION['admin_flash_message']);
}

$admins = [];
try {
    $stmt = $pdo->query("SELECT id, username, email, is_superadmin, created_at FROM admins ORDER BY username ASC");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erro ao buscar administradores: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - Painel Admin</title>
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .is-superadmin-true { color: green; font-weight: bold; }
        .is-superadmin-false { color: #555; }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-layout">
            <?php require_once 'templates/navigation.php'; ?>
            <div class="main-content">
                <h1><?php echo htmlspecialchars($page_title); ?></h1>

                <?php if (!empty($message)): ?>
                    <div class="message success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if (!empty($error_message)): ?>
                    <div class="message error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <p><a href="edit_admin.php?action=add" class="button">Adicionar Novo Administrador</a></p>
                <?php if (empty($admins) && empty($error_message)): ?>
                    <p>Nenhum administrador encontrado.</p>
                <?php elseif (!empty($admins)): ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuário</th>
                                <th>Email</th>
                                <th>Super Admin?</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin_user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admin_user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($admin_user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($admin_user['email'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="is-superadmin-<?php echo ($admin_user['is_superadmin'] ?? 0) ? 'true' : 'false'; ?>">
                                            <?php echo ($admin_user['is_superadmin'] ?? 0) ? 'Sim' : 'Não'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($admin_user['created_at']))); ?></td>
                                    <td>
                                        <?php if (($_SESSION['admin_id'] ?? null) == $admin_user['id'] || ($_SESSION['admin_is_superadmin'] ?? false)): ?>
                                            <a href="edit_admin.php?id=<?php echo $admin_user['id']; ?>" class="edit-button">Editar</a>
                                        <?php else: ?>
                                            <span style="color:#aaa;">Editar</span>
                                        <?php endif; ?>

                                        <?php if (($_SESSION['admin_is_superadmin'] ?? false) && ($_SESSION['admin_id'] ?? null) != $admin_user['id']): ?>
                                            <form action="delete_admin.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este administrador?');" style="display:inline; margin-left:5px;">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                <input type="hidden" name="admin_id_to_delete" value="<?php echo $admin_user['id']; ?>">
                                                <button type="submit" class="delete-button">Excluir</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color:#aaa; margin-left:5px;">Excluir</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

