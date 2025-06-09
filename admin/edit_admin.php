<?php
require_once 'auth_check.php';
require_once '../config.php';

$page_title = "Administrador"; // Será "Adicionar Administrador" ou "Editar Administrador"
$message = '';
$error_message = '';

// Determinar ação: adicionar ou editar
$action = $_GET['action'] ?? (isset($_GET['id']) ? 'edit' : 'add');
$admin_id_to_edit = null;
if (isset($_GET['id'])) $admin_id_to_edit = (int)$_GET['id'];
elseif (isset($_POST['admin_id'])) $admin_id_to_edit = (int)$_POST['admin_id']; // Para persistir em caso de erro no POST de update

// Variáveis para os campos do formulário
$username_val = '';
$email_val = ''; // Assumindo que a coluna email será adicionada
$is_superadmin_val = 0; // Default para novos admins

if ($action === 'edit') {
    if (empty($admin_id_to_edit) || $admin_id_to_edit <= 0) { // Verificação mais robusta
        $_SESSION['admin_flash_message'] = ['type' => 'error', 'text' => 'ID de administrador inválido para edição.'];
        header("Location: manage_admins.php");
        exit;
    }
    $page_title = "Editar Administrador";

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($error_message)) { // Só busca se não for um POST com erro já definido ou um POST bem-sucedido.
        try {
        $stmt_fetch = $pdo->prepare("SELECT username, email, is_superadmin FROM admins WHERE id = :id");
        $stmt_fetch->bindParam(':id', $admin_id_to_edit, PDO::PARAM_INT);
        $stmt_fetch->execute();
        $admin_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

        if (!$admin_data) {
            $_SESSION['admin_flash_message'] = ['type' => 'error', 'text' => 'Administrador não encontrado.'];
            header("Location: manage_admins.php");
            exit;
        }
        $username_val = $admin_data['username'];
        $email_val = $admin_data['email'] ?? '';
        $is_superadmin_val = $admin_data['is_superadmin'] ?? 0;

    } catch (PDOException $e) {
        $error_message = "Erro ao carregar dados do administrador: " . $e->getMessage();
        // Considerar redirecionar ou mostrar erro mais crítico
    }
    // Se admin logado não for superadmin E não for o próprio usuário, não permitir edição.
    if (!($_SESSION['admin_is_superadmin'] ?? false) && ($_SESSION['admin_id'] ?? null) != $admin_id_to_edit) {
         $_SESSION['admin_flash_message'] = ['type' => 'error', 'text' => 'Você não tem permissão para editar este administrador.'];
         header("Location: manage_admins.php");
         exit;
    }

} elseif ($action === 'add') {
    $page_title = "Adicionar Novo Administrador";
} else {
    $_SESSION['admin_flash_message'] = ['type' => 'error', 'text' => 'Ação inválida.'];
    header("Location: manage_admins.php");
    exit;
}

// Lógica de Processamento do Formulário (Adicionar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_admin'])) {
    $current_action = $_POST['action_type'] ?? 'add'; // 'add' or 'update'

    $username_val = trim($_POST['username'] ?? '');
    $email_val = trim($_POST['email'] ?? null); // Email é opcional
    $password_val = $_POST['password'] ?? '';
    $password_confirm_val = $_POST['password_confirm'] ?? '';
    // Só permitir definir is_superadmin na criação se o usuário logado for superadmin
    $is_superadmin_val = (isset($_POST['is_superadmin']) && ($_SESSION['admin_is_superadmin'] ?? false)) ? 1 : 0;

    if ($current_action === 'add') {
        if (empty($username_val)) {
            $error_message = "Nome de usuário é obrigatório.";
        } elseif (empty($password_val)) {
            $error_message = "Senha é obrigatória.";
        } elseif ($password_val !== $password_confirm_val) {
            $error_message = "As senhas não coincidem.";
        } elseif (!empty($email_val) && !filter_var($email_val, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Formato de email inválido.";
        } else {
            try {
                // Verificar se username ou email já existem
                $stmt_check = $pdo->prepare("SELECT id FROM admins WHERE username = :username OR (email IS NOT NULL AND email = :email)");
                $stmt_check->bindParam(':username', $username_val, PDO::PARAM_STR);
                $stmt_check->bindParam(':email', $email_val, PDO::PARAM_STR); // PDO::PARAM_NULL se email for vazio? Melhor tratar string vazia.
                $stmt_check->execute();

                if ($stmt_check->rowCount() > 0) {
                    $existing_admin = $stmt_check->fetch();
                    if (strtolower($existing_admin['username']) === strtolower($username_val)) {
                         $error_message = "Este nome de usuário já está em uso.";
                    } elseif (!empty($email_val) && strtolower($existing_admin['email'] ?? '') === strtolower($email_val)) {
                         $error_message = "Este email já está em uso.";
                    } else {
                        $error_message = "Nome de usuário ou email já cadastrado."; // Genérico
                    }
                } else {
                    // Tudo OK para inserir
                    $password_hash = password_hash($password_val, PASSWORD_DEFAULT);

                    $sql_insert = "INSERT INTO admins (username, password_hash, email, is_superadmin)
                                   VALUES (:username, :password_hash, :email, :is_superadmin)";
                    $stmt_insert = $pdo->prepare($sql_insert);
                    $stmt_insert->bindParam(':username', $username_val, PDO::PARAM_STR);
                    $stmt_insert->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);

                    if (empty($email_val)) {
                        $stmt_insert->bindValue(':email', null, PDO::PARAM_NULL);
                    } else {
                        $stmt_insert->bindParam(':email', $email_val, PDO::PARAM_STR);
                    }
                    $stmt_insert->bindParam(':is_superadmin', $is_superadmin_val, PDO::PARAM_INT);

                    if ($stmt_insert->execute()) {
                        $_SESSION['admin_flash_message'] = ['type' => 'success', 'text' => 'Administrador adicionado com sucesso!'];
                        header("Location: manage_admins.php");
                        exit;
                    } else {
                        $error_message = "Erro ao salvar administrador no banco de dados.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Erro de banco de dados: " . $e->getMessage();
                // Logar $e->getMessage()
            }
        }
    } elseif ($current_action === 'update') {
        $admin_id_being_edited = (int)($_POST['admin_id'] ?? 0); // ID do admin sendo editado
        if ($admin_id_being_edited <= 0) {
            $error_message = "ID de administrador inválido para atualização.";
        } // Validação de permissão (repetida para segurança, caso o formulário seja manipulado)
        elseif (!($_SESSION['admin_is_superadmin'] ?? false) && ($_SESSION['admin_id'] ?? null) != $admin_id_being_edited) {
            $error_message = "Você não tem permissão para editar este administrador.";
        } else {
            // Validação de username e email (semelhante ao 'add', mas excluindo o próprio ID da verificação de duplicidade)
            if (empty($username_val)) {
                $error_message = "Nome de usuário é obrigatório.";
            } elseif (!empty($email_val) && !filter_var($email_val, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Formato de email inválido.";
            } else {
                try {
                    $stmt_check_dupe = $pdo->prepare(
                        "SELECT id FROM admins WHERE (username = :username OR (email IS NOT NULL AND email != '' AND email = :email)) AND id != :id"
                    );
                    $stmt_check_dupe->bindParam(':username', $username_val, PDO::PARAM_STR);
                    $stmt_check_dupe->bindParam(':email', $email_val, PDO::PARAM_STR);
                    $stmt_check_dupe->bindParam(':id', $admin_id_being_edited, PDO::PARAM_INT);
                    $stmt_check_dupe->execute();

                    if ($stmt_check_dupe->rowCount() > 0) {
                         $error_message = "Nome de usuário ou email já em uso por outro administrador.";
                    } else {
                        // Lógica de atualização da senha
                        $new_password_hash = null;
                        if (!empty($password_val)) { // Se uma nova senha foi fornecida
                            if ($password_val !== $password_confirm_val) {
                                $error_message = "As novas senhas não coincidem.";
                            } else {
                                // Se admin está editando a si mesmo, e não é um superadmin editando outro, verificar senha atual
                                if (($_SESSION['admin_id'] ?? null) == $admin_id_being_edited) {
                                    $current_password_val = $_POST['current_password'] ?? '';
                                    if (empty($current_password_val)) {
                                        $error_message = "Senha atual é obrigatória para alterar a senha.";
                                    } else {
                                        $stmt_curr_pass = $pdo->prepare("SELECT password_hash FROM admins WHERE id = :id");
                                        $stmt_curr_pass->bindParam(':id', $admin_id_being_edited, PDO::PARAM_INT);
                                        $stmt_curr_pass->execute();
                                        $admin_curr_data = $stmt_curr_pass->fetch();
                                        if (!$admin_curr_data || !password_verify($current_password_val, $admin_curr_data['password_hash'])) {
                                            $error_message = "Senha atual incorreta.";
                                        }
                                    }
                                }
                                // Se passou nas verificações ou se é superadmin mudando senha de outro, calcula o novo hash
                                if (empty($error_message)) {
                                    $new_password_hash = password_hash($password_val, PASSWORD_DEFAULT);
                                }
                            }
                        }

                        // Se não houve erro até agora, prosseguir com a atualização
                        if (empty($error_message)) {
                            $sql_update_parts = ["username = :username", "email = :email"];
                            $params_update = [
                                ':username' => $username_val,
                                ':email' => empty($email_val) ? null : $email_val,
                                ':id' => $admin_id_being_edited
                            ];

                            if ($new_password_hash !== null) {
                                $sql_update_parts[] = "password_hash = :password_hash";
                                $params_update[':password_hash'] = $new_password_hash;
                            }

                            // Atualizar is_superadmin apenas se o admin logado for superadmin
                            if (($_SESSION['admin_is_superadmin'] ?? false)) {
                                $sql_update_parts[] = "is_superadmin = :is_superadmin";
                                $params_update[':is_superadmin'] = $is_superadmin_val; // $is_superadmin_val já vem do POST
                            } elseif (isset($_POST['is_superadmin']) && $admin_id_being_edited == ($_SESSION['admin_id'] ?? null)) {
                                // Um admin não pode rebaixar a si mesmo se for o único superadmin, ou promover a si mesmo.
                                // Essa lógica mais complexa de "único superadmin" pode ser adicionada depois.
                                // Por ora, se não for superadmin, não pode mudar o status de ninguém.
                                // Se for superadmin e estiver editando a si mesmo, o valor de $is_superadmin_val será usado.
                            }


                            $sql_update = "UPDATE admins SET " . implode(", ", $sql_update_parts) . " WHERE id = :id";
                            $stmt_update = $pdo->prepare($sql_update);

                            if ($stmt_update->execute($params_update)) {
                                $message = 'Administrador atualizado com sucesso!'; // Define $message diretamente
                                // Se o admin atualizou o próprio nome de usuário, atualiza a sessão
                                if (($_SESSION['admin_id'] ?? null) == $admin_id_being_edited && isset($_SESSION['admin_username']) && $_SESSION['admin_username'] != $username_val) {
                                    $_SESSION['admin_username'] = $username_val;
                                }
                                // // header("Location: manage_admins.php"); // Comentado
                                // // exit; // Comentado
                            } else {
                                $pdo_error_info = $stmt_update->errorInfo();
                                $error_message = "Erro ao atualizar administrador no banco de dados. Detalhe: " . ($pdo_error_info[2] ?? 'Sem detalhes');
                                error_log("Admin Update Failed (ID: {$admin_id_being_edited}): Query: {$sql_update} Params: " . json_encode($params_update) . " PDO Error: " . ($pdo_error_info[2] ?? 'N/A'));
                            }
                        }
                    } // Fim da verificação de duplicidade
                } catch (PDOException $e) { // Catch para a verificação de duplicidade e outras exceções PDO
                    $error_message = "Erro de banco de dados (update): " . $e->getMessage();
                     error_log("Admin Update PDOException (ID: {$admin_id_being_edited}): " . $e->getMessage());
                }
            } // Fim da validação de username e email
        } // Fim da validação de ID e permissão
    } // Fim do elseif ($current_action === 'update')
}


// Flash messages (exibidas em manage_admins.php após redirecionamento)
// Se houver erro direto nesta página (sem redirect), $error_message ou $message são usados.

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - Painel Admin</title>
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>
    <div class="container">
        <nav>
            <div>
                <a href="index.php">Painel Principal (Jogos)</a>
                <a href="manage_leagues.php">Gerenciar Ligas</a>
                <a href="manage_channels.php">Gerenciar Canais TV</a>
                <a href="manage_teams.php">Gerenciar Times</a>
                <a href="manage_saved_streams.php">Biblioteca de Streams</a>
                <a href="manage_item_reports.php">Reportes de Itens</a>
                <a href="manage_admins.php" class="<?php echo ($action === 'add' || $action === 'edit') ? 'active-nav' : ''; // Mantém Gerenciar Admins ativo ?>">Gerenciar Admins</a>
                <a href="manage_settings.php">Configurações</a>
            </div>
            <div class="nav-user-info">
                Usuário: <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?> |
                <a href="logout.php" class="logout-link">Logout</a>
            </div>
        </nav>

        <h1><?php echo htmlspecialchars($page_title); ?></h1>

        <?php if (!empty($message)): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="edit_admin.php<?php echo $action === 'edit' ? '?id='.$admin_id_to_edit : '?action=add'; ?>" method="POST">
            <input type="hidden" name="action_type" value="<?php echo $action; ?>">
            <?php if ($action === 'edit' && $admin_id_to_edit): ?>
                <input type="hidden" name="admin_id" value="<?php echo $admin_id_to_edit; ?>">
            <?php endif; ?>

            <div>
                <label for="username">Nome de Usuário:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username_val); ?>" required>
            </div>
            <div>
                <label for="email">Email (opcional):</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email_val); ?>">
            </div>

            <?php if ($action === 'edit'): ?>
                <fieldset style="margin-bottom:15px; padding:10px; border:1px solid #ccc; border-radius:4px;">
                    <legend>Alterar Senha (deixe em branco para não alterar)</legend>
                    <?php if (($_SESSION['admin_id'] ?? null) == $admin_id_to_edit): // Editando o próprio perfil ?>
                        <div>
                            <label for="current_password">Senha Atual:</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>
                    <?php endif; ?>
                    <div>
                        <label for="password">Nova Senha:</label>
                        <input type="password" id="password" name="password">
                    </div>
                    <div>
                        <label for="password_confirm">Confirmar Nova Senha:</label>
                        <input type="password" id="password_confirm" name="password_confirm">
                    </div>
                </fieldset>
            <?php else: // Ação 'add' ?>
                <div>
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div>
                    <label for="password_confirm">Confirmar Senha:</label>
                    <input type="password" id="password_confirm" name="password_confirm" required>
                </div>
            <?php endif; ?>

            <?php if ($_SESSION['admin_is_superadmin'] ?? false): // Apenas superadmins podem definir/mudar status de superadmin ?>
            <div>
                <input type="checkbox" id="is_superadmin" name="is_superadmin" value="1" <?php echo ($is_superadmin_val ?? 0) ? 'checked' : ''; ?>>
                <label for="is_superadmin" style="display:inline; font-weight:normal;">É Super Administrador?</label>
            </div>
            <?php elseif ($action === 'edit' && ($is_superadmin_val ?? 0)): // Se estiver editando um superadmin e não for superadmin, mostra o status mas não permite mudar ?>
                <input type="hidden" name="is_superadmin" value="1">
                <p><strong>Status:</strong> Super Administrador (não pode ser alterado por você)</p>
            <?php endif; ?>


            <div>
                <button type="submit" name="save_admin">Salvar</button>
                <a href="manage_admins.php" style="margin-left: 10px;">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
