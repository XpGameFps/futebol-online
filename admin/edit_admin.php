<?php
require_once 'auth_check.php'; // Auth check primeiro
require_once '../config.php'; // Depois config

// Ensure csrf_utils.php is loaded (auth_check.php should have already included it)
if (!function_exists('generate_csrf_token')) {
    // This should ideally not be needed if auth_check.php is always first and correct.
    require_once 'csrf_utils.php';
}
$csrf_token = generate_csrf_token();

// error_reporting(E_ALL); // Tentar habilitar todos os erros
// ini_set('display_errors', 1); // Tentar exibir erros

$page_title = "Administrador";
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
    $page_title = "Editar Administrador"; // Mantenha isso

    // --- INÍCIO DO BLOCO A COMENTAR PARA TESTE ---

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($error_message)) {
        try {
            $stmt_fetch = $pdo->prepare("SELECT username, email, is_superadmin FROM admins WHERE id = :id");
            $stmt_fetch->bindParam(':id', $admin_id_to_edit, PDO::PARAM_INT);
            $stmt_fetch->execute();
            $admin_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

            if (!$admin_data) {
                $_SESSION['admin_flash_message'] = ['type' => 'error', 'text' => 'Administrador não encontrado (ID: '.$admin_id_to_edit.').'];
                header("Location: manage_admins.php");
                exit;
            }
            $username_val = $admin_data['username'];
            $email_val = $admin_data['email'] ?? '';
            $is_superadmin_val = $admin_data['is_superadmin'] ?? 0;

        } catch (PDOException $e) {
            error_log("PDOException in " . __FILE__ . " (loading admin data for edit, ID: {$admin_id_to_edit}): " . $e->getMessage());
            $error_message = "Ocorreu um erro no banco de dados ao carregar os dados do administrador. Por favor, tente novamente.";
        }
        // Permissão
        if (!($_SESSION['admin_is_superadmin'] ?? false) && ($_SESSION['admin_id'] ?? null) != $admin_id_to_edit) {
            $_SESSION['admin_flash_message'] = ['type' => 'error', 'text' => 'Você não tem permissão para editar este administrador (ID: '.$admin_id_to_edit.').'];
            header("Location: manage_admins.php");
            exit;
       }
    }

    // --- FIM DO BLOCO A COMENTAR PARA TESTE ---

} elseif ($action === 'add') {
    $page_title = "Adicionar Novo Administrador";
} else {
    $_SESSION['admin_flash_message'] = ['type' => 'error', 'text' => 'Ação inválida.'];
    header("Location: manage_admins.php");
    exit;
}

// Lógica de Processamento do Formulário (Adicionar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_admin'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $error_message = "Falha na verificação de segurança (CSRF). Por favor, tente submeter o formulário novamente.";
        // Values might need to be repopulated if the form is redisplayed with this error
        $username_val = trim($_POST['username'] ?? $username_val); // Keep submitted value or original if POST['username'] not set
        $email_val = trim($_POST['email'] ?? $email_val);
        if (isset($_POST['is_superadmin']) && ($_SESSION['admin_is_superadmin'] ?? false)) {
            $is_superadmin_val = 1;
        } elseif ($action === 'edit' && $admin_id_to_edit) {
            // Keep existing $is_superadmin_val if not changeable by user
            // This part is tricky as $is_superadmin_val is loaded before this POST check for 'edit' action
            // If CSRF fails, we need to ensure $is_superadmin_val is what it should be for the form display
        } else {
            $is_superadmin_val = 0;
        }

    } else {
        // **Start of existing processing logic**
        $current_action = $_POST['action_type'] ?? 'add'; // 'add' or 'update'

        $username_val = trim($_POST['username'] ?? '');
        $email_val = trim($_POST['email'] ?? null);
        $password_val = $_POST['password'] ?? '';
        $password_confirm_val = $_POST['password_confirm'] ?? '';

        if (($_SESSION['admin_is_superadmin'] ?? false)) {
            $is_superadmin_val = isset($_POST['is_superadmin']) ? 1 : 0;
        } else {
            // If not a superadmin, $is_superadmin_val should not be taken from POST for other users.
            // For editing oneself, it's not changeable via checkbox if not superadmin.
            // If adding, it defaults to 0.
            // This means $is_superadmin_val loaded at the start of the script for 'edit' action should be preserved
            // if the current user is not a superadmin.
            // The previously loaded $is_superadmin_val is used if not a superadmin.
            // If it's an 'add' action by a non-superadmin, it will be 0.
            if ($current_action === 'add') $is_superadmin_val = 0;
            // If 'edit' by non-superadmin, $is_superadmin_val is what was loaded or from hidden field.
            // This part needs care to ensure non-superadmins cannot escalate privileges.
            // The hidden field for 'is_superadmin' or checkbox logic already handles this.
        }


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
                    error_log("PDOException in " . __FILE__ . " (add admin - check existing or insert): " . $e->getMessage());
                    $error_message = "Ocorreu um erro no banco de dados ao adicionar o administrador. Por favor, tente novamente.";
                    // Logar $e->getMessage() // Original comment noted to log, which we are now doing with error_log
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
                                    // Additional check for self-demotion of last superadmin
                                    if (($_SESSION['admin_is_superadmin'] ?? false) && // Current logged-in user is a superadmin
                                        ($admin_id_being_edited == ($_SESSION['admin_id'] ?? null)) && // They are editing themselves
                                        $is_superadmin_val == 0) { // And they are trying to remove their own superadmin status (derived from POST earlier)

                                        // Check if their current status in DB is actually superadmin
                                        $stmt_check_current_status = $pdo->prepare("SELECT is_superadmin FROM admins WHERE id = :id");
                                        $stmt_check_current_status->bindParam(':id', $admin_id_being_edited, PDO::PARAM_INT);
                                        $stmt_check_current_status->execute();
                                        $current_db_status = $stmt_check_current_status->fetch(PDO::FETCH_ASSOC);

                                        if ($current_db_status && $current_db_status['is_superadmin'] == 1) {
                                            // They are currently a superadmin and are trying to demote themselves.
                                            // Now check if they are the last one.
                                            $stmt_count_supers = $pdo->prepare("SELECT COUNT(id) as superadmin_count FROM admins WHERE is_superadmin = 1");
                                            $stmt_count_supers->execute();
                                            $super_count_result = $stmt_count_supers->fetch(PDO::FETCH_ASSOC);
                                            $total_superadmins = $super_count_result ? (int)$super_count_result['superadmin_count'] : 0;

                                            if ($total_superadmins <= 1) {
                                                $error_message = "Você não pode remover seu próprio status de super administrador pois você é o único restante.";
                                                $is_superadmin_val = 1; // Revert the change from form, ensure it's used if update proceeds for other fields
                                            }
                                        }
                                    }
                                } // End of self-demotion check block

                                // Re-check error_message before proceeding with building the update, as it might have been set by the self-demotion check
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
                                // $is_superadmin_val would have been corrected by the self-demotion check if necessary.
                                if (($_SESSION['admin_is_superadmin'] ?? false)) {
                                    $sql_update_parts[] = "is_superadmin = :is_superadmin";
                                    $params_update[':is_superadmin'] = $is_superadmin_val;
                                }
                                // Non-superadmins cannot change this status for themselves or others;
                                // the $is_superadmin_val for them is determined by pre-loaded data or defaults to 0 for 'add',
                                // and the checkbox is not shown or is disabled.

                                $sql_update = "UPDATE admins SET " . implode(", ", $sql_update_parts) . " WHERE id = :id";
                                $stmt_update = $pdo->prepare($sql_update);

                                $executed_successfully = $stmt_update->execute($params_update);

                                if ($executed_successfully) {
                                    $affected_rows = $stmt_update->rowCount();
                                    if ($affected_rows > 0) {
                                        $_SESSION['admin_flash_message'] = ['type' => 'success', 'text' => 'Administrador atualizado com sucesso! (' . $affected_rows . ' linha(s) afetada(s))'];
                                        if (($_SESSION['admin_id'] ?? null) == $admin_id_being_edited && isset($_SESSION['admin_username']) && $_SESSION['admin_username'] != $username_val) {
                                            $_SESSION['admin_username'] = $username_val;
                                        }
                                        header("Location: manage_admins.php");
                                        exit;
                                    } else {
                                        // Query ran, but no rows were updated.
                                        // This could be because the data submitted was the same as the existing data,
                                        // or the ID didn't match (though less likely if form loaded correctly).
                                        $error_message = "Nenhuma alteração foi detectada nos dados para o administrador (ID: " . $admin_id_being_edited . "). Nenhuma atualização realizada.";
                                        // Log this as it might be unexpected depending on context
                                        error_log("Admin Update Warning (ID: {$admin_id_being_edited}): execute() was true, but rowCount() was 0. Submitted params: " . json_encode($params_update));
                                    }
                                } else {
                                    // execute() returned false - database error
                                    $pdo_error_info = $stmt_update->errorInfo();
                                    $error_message = "Erro ao atualizar administrador no banco de dados. Detalhe: " . ($pdo_error_info[2] ?? 'Sem detalhes');
                                    error_log("Admin Update Failed (ID: {$admin_id_being_edited}): Query: {$sql_update} Params: " . json_encode($params_update) . " PDO Error: " . ($pdo_error_info[2] ?? 'N/A'));
                                }
                            } // Closing the re-checked if(empty($error_message))
                        } // Fim da verificação de duplicidade
                    } catch (PDOException $e) { // Catch para a verificação de duplicidade, self-demotion checks, e outras exceções PDO
                        error_log("PDOException in " . __FILE__ . " (update admin ID: {$admin_id_being_edited}): " . $e->getMessage());
                        $error_message = "Ocorreu um erro no banco de dados ao atualizar o administrador. Por favor, tente novamente.";
                         error_log("Admin Update PDOException (ID: {$admin_id_being_edited}): " . $e->getMessage()); // This duplicate log can be removed if the one above is sufficient
                    }
                } // Fim da validação de username e email
            } // Fim da validação de ID e permissão
        } // Fim do elseif ($current_action === 'update')
        // **End of existing processing logic**
    }

    // AFTER all POST processing (CSRF check, add/update attempts):
    // If any error occurred that prevents a redirect and will cause the form to be re-rendered,
    // regenerate the CSRF token to ensure the re-rendered form has a fresh one.
    if (!empty($error_message)) {
        if (function_exists('generate_csrf_token')) {
            $csrf_token = generate_csrf_token(true); // Force regeneration
        }
        // Make sure $username_val, $email_val, etc., are correctly set from $_POST
        // if we are re-rendering due to an error AFTER CSRF validation passed initially
        // but an error occurred later in the add/update process.
        // This is important so the user doesn't lose their typed input.
        if (isset($_POST['username'])) $username_val = trim($_POST['username']);
        if (isset($_POST['email'])) $email_val = trim($_POST['email']);
        // For checkbox 'is_superadmin', its value needs to be correctly determined from $_POST
        // if it was part of the submission and an error occurred.
        if (isset($_POST['action_type'])) { // Check if form was actually submitted
            if (($_SESSION['admin_is_superadmin'] ?? false)) { // Only superadmins can set this
                 $is_superadmin_val = isset($_POST['is_superadmin']) ? 1 : 0;
            }
            // If not superadmin, $is_superadmin_val retains its loaded value or default for 'add'
            // (this logic is already handled earlier when $is_superadmin_val is first determined from POST).
        }
    }
} // End of POST processing block


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
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
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
