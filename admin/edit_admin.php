<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'auth_check.php';
require_once __DIR__ . '/../../FutOnline_config/config.php';
$csrf_token = '';

$page_title = "Administrador";
$message = '';
$error_message = '';

$action = $_GET['action'] ?? (isset($_GET['id']) ? 'edit' : 'add');
$admin_id_to_edit = null;
if (isset($_GET['id'])) {
    $admin_id_to_edit = (int)$_GET['id'];
} elseif (isset($_POST['admin_id'])) {
    $admin_id_to_edit = (int)$_POST['admin_id'];
}
$username_val = '';
$email_val = '';
$is_superadmin_val = 0;

if ($action === 'edit') {
    if (empty($admin_id_to_edit) || $admin_id_to_edit <= 0) {
        $_SESSION['admin_flash_message'] = ['type' => 'error', 'text' => 'ID de administrador inválido para edição.'];
        header("Location: manage_admins.php");
        exit;
    }
    $page_title = "Editar Administrador";

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !empty($error_message)) {
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
            if (empty($username_val)) $username_val = $admin_data['username'];
            if (empty($email_val)) $email_val = $admin_data['email'] ?? '';
            if (!isset($_POST['is_superadmin'])) $is_superadmin_val = $admin_data['is_superadmin'] ?? 0;
        } catch (PDOException $e) {
            error_log("PDOException in " . __FILE__ . " (loading admin data for edit, ID: {$admin_id_to_edit}): " . $e->getMessage());
            $error_message = "Ocorreu um erro no banco de dados ao carregar os dados do administrador. Por favor, tente novamente.";
        }
        if (!($_SESSION['admin_is_superadmin'] ?? false) && ($_SESSION['admin_id'] ?? null) != $admin_id_to_edit) {
            $_SESSION['admin_flash_message'] = ['type' => 'error', 'text' => 'Você não tem permissão para editar este administrador (ID: '.$admin_id_to_edit.').'];
            header("Location: manage_admins.php");
            exit;
        }
    }
} elseif ($action === 'add') {
    $page_title = "Adicionar Novo Administrador";
} else {
    $_SESSION['admin_flash_message'] = ['type' => 'error', 'text' => 'Ação inválida.'];
    header("Location: manage_admins.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_admin'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $error_message = "Falha na verificação de segurança (CSRF). Por favor, tente submeter o formulário novamente.";
        $username_val = trim($_POST['username'] ?? $username_val);
        $email_val = trim($_POST['email'] ?? $email_val);
        if (isset($_POST['is_superadmin']) && ($_SESSION['admin_is_superadmin'] ?? false)) {
            $is_superadmin_val = 1;
        } elseif ($action === 'edit' && $admin_id_to_edit) {
        } else {
            $is_superadmin_val = 0;
        }
    } else {
        $current_action = $_POST['action_type'] ?? 'add';
        $username_val = trim($_POST['username'] ?? '');
        $email_val = trim($_POST['email'] ?? null);
        $password_val = $_POST['password'] ?? '';
        $password_confirm_val = $_POST['password_confirm'] ?? '';
        if (($_SESSION['admin_is_superadmin'] ?? false)) {
            $is_superadmin_val = isset($_POST['is_superadmin']) ? 1 : 0;
        } else {
            if ($current_action === 'add') {
                $is_superadmin_val = 0;
            }
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
                    $check_params = [':username' => $username_val];
                    $sql_check_parts = ["username = :username"];
                    if (!empty($email_val)) {
                        $sql_check_parts[] = "email = :email";
                        $check_params[':email'] = $email_val;
                    }
                    $stmt_check = $pdo->prepare("SELECT id, username, email FROM admins WHERE " . implode(" OR ", $sql_check_parts));
                    $stmt_check->execute($check_params);
                    if ($stmt_check->rowCount() > 0) {
                        $existing_admin = $stmt_check->fetch();
                        if (strtolower($existing_admin['username']) === strtolower($username_val)) {
                            $error_message = "Este nome de usuário já está em uso.";
                        } elseif (!empty($email_val) && strtolower($existing_admin['email'] ?? '') === strtolower($email_val)) {
                            $error_message = "Este email já está em uso.";
                        } else {
                            $error_message = "Nome de usuário ou email já cadastrado.";
                        }
                    } else {
                        $password_hash = password_hash($password_val, PASSWORD_DEFAULT);
                        $sql_insert = "INSERT INTO admins (username, password_hash, email, is_superadmin) VALUES (:username, :password_hash, :email, :is_superadmin)";
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
                }
            }
        } elseif ($current_action === 'edit') {
            $admin_id_being_edited = (int)($_POST['admin_id'] ?? 0);
            if ($admin_id_being_edited <= 0) {
                $error_message = "ID de administrador inválido para atualização.";
            } elseif (!($_SESSION['admin_is_superadmin'] ?? false) && ($_SESSION['admin_id'] ?? null) != $admin_id_being_edited) {
                $error_message = "Você não tem permissão para editar este administrador.";
            } else {
                if (empty($username_val)) {
                    $error_message = "Nome de usuário é obrigatório.";
                } elseif (!empty($email_val) && !filter_var($email_val, FILTER_VALIDATE_EMAIL)) {
                    $error_message = "Formato de email inválido.";
                } else {
                    try {
                        $check_dupe_params = [':username' => $username_val, ':id' => $admin_id_being_edited];
                        $sql_dupe_parts = ["username = :username"];
                        if (!empty($email_val)) {
                            $sql_dupe_parts[] = "email = :email";
                            $check_dupe_params[':email'] = $email_val;
                        }
                        $stmt_check_dupe = $pdo->prepare("SELECT id FROM admins WHERE (" . implode(" OR ", $sql_dupe_parts) . ") AND id != :id");
                        $stmt_check_dupe->execute($check_dupe_params);
                        if ($stmt_check_dupe->rowCount() > 0) {
                            $error_message = "Nome de usuário ou email já em uso por outro administrador.";
                        } else {
                            $new_password_hash = null;
                            if (!empty($password_val)) {
                                if ($password_val !== $password_confirm_val) {
                                    $error_message = "As novas senhas não coincidem.";
                                } else {
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
                                    if (empty($error_message)) {
                                        $new_password_hash = password_hash($password_val, PASSWORD_DEFAULT);
                                    }
                                }
                            }
                            if (empty($error_message)) {
                                if (($_SESSION['admin_is_superadmin'] ?? false) && ($admin_id_being_edited == ($_SESSION['admin_id'] ?? null)) && $is_superadmin_val == 0) {
                                    $stmt_check_current_status = $pdo->prepare("SELECT is_superadmin FROM admins WHERE id = :id");
                                    $stmt_check_current_status->bindParam(':id', $admin_id_being_edited, PDO::PARAM_INT);
                                    $stmt_check_current_status->execute();
                                    $current_db_status = $stmt_check_current_status->fetch(PDO::FETCH_ASSOC);
                                    if ($current_db_status && $current_db_status['is_superadmin'] == 1) {
                                        $stmt_count_supers = $pdo->prepare("SELECT COUNT(id) as superadmin_count FROM admins WHERE is_superadmin = 1");
                                        $stmt_count_supers->execute();
                                        $super_count_result = $stmt_count_supers->fetch(PDO::FETCH_ASSOC);
                                        $total_superadmins = $super_count_result ? (int)$super_count_result['superadmin_count'] : 0;
                                        if ($total_superadmins <= 1) {
                                            $error_message = "Você não pode remover seu próprio status de super administrador pois você é o único restante.";
                                            $is_superadmin_val = 1;
                                        }
                                    }
                                }
                            }
                            if (empty($error_message)) {
                                $sql_update_parts = ["username = :username", "email = :email_val"];
                                $params_update = [
                                    ':username' => $username_val,
                                    ':email_val' => empty($email_val) ? null : $email_val,
                                    ':id' => $admin_id_being_edited
                                ];
                                if ($new_password_hash !== null) {
                                    $sql_update_parts[] = "password_hash = :password_hash";
                                    $params_update[':password_hash'] = $new_password_hash;
                                }
                                if (($_SESSION['admin_is_superadmin'] ?? false)) {
                                    $sql_update_parts[] = "is_superadmin = :is_superadmin";
                                    $params_update[':is_superadmin'] = $is_superadmin_val;
                                }
                                $sql_update = "UPDATE admins SET " . implode(", ", $sql_update_parts) . " WHERE id = :id";
                                $stmt_update = $pdo->prepare($sql_update);
                                $executed_successfully = $stmt_update->execute($params_update);
                                if ($executed_successfully) {
                                    $affected_rows = $stmt_update->rowCount();
                                    if ($affected_rows > 0) {
                                        $_SESSION['admin_flash_message'] = ['type' => 'success', 'text' => 'Administrador atualizado com sucesso!'];
                                        if (($_SESSION['admin_id'] ?? null) == $admin_id_being_edited && isset($_SESSION['admin_username']) && $_SESSION['admin_username'] != $username_val) {
                                            $_SESSION['admin_username'] = $username_val;
                                        }
                                        header("Location: manage_admins.php");
                                        exit;
                                    } else {
                                        $error_message = "Nenhuma alteração foi detectada nos dados para o administrador (ID: " . $admin_id_being_edited . "). Nenhuma atualização realizada.";
                                        error_log("Admin Update Warning (ID: {$admin_id_being_edited}): execute() was true, but rowCount() was 0. Submitted params: " . json_encode($params_update));
                                    }
                                } else {
                                    $pdo_error_info = $stmt_update->errorInfo();
                                    $error_message = "Erro ao atualizar administrador no banco de dados. Detalhe: " . ($pdo_error_info[2] ?? 'Sem detalhes');
                                    error_log("Admin Update Failed (ID: {$admin_id_being_edited}): Query: {$sql_update} Params: " . json_encode($params_update) . " PDO Error: " . ($pdo_error_info[2] ?? 'N/A'));
                                }
                            }
                        }
                    } catch (PDOException $e) {
                        error_log("PDOException in " . __FILE__ . " (update admin ID: {$admin_id_being_edited}): " . $e->getMessage());
                        $error_message = "Ocorreu um erro no banco de dados ao atualizar o administrador. Por favor, tente novamente.";
                    }
                }
            }
        }
    }
    if (!empty($error_message)) {
    }
}

if (function_exists('generate_csrf_token')) {
    $csrf_token = generate_csrf_token(true);
} else {
    $csrf_token = 'csrf_error_not_loaded_critical';
    if (empty($error_message)) $error_message = "Erro crítico: Funções CSRF não estão disponíveis.";
}
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
                <form action="edit_admin.php<?php echo $action === 'edit' ? '?id='.$admin_id_to_edit : '?action=add'; ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
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
                            <?php if (($_SESSION['admin_id'] ?? null) == $admin_id_to_edit): ?>
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
                    <?php else: ?>
                        <div>
                            <label for="password">Senha:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div>
                            <label for="password_confirm">Confirmar Senha:</label>
                            <input type="password" id="password_confirm" name="password_confirm" required>
                        </div>
                    <?php endif; ?>
                    <?php if ($_SESSION['admin_is_superadmin'] ?? false): ?>
                        <div>
                            <input type="checkbox" id="is_superadmin" name="is_superadmin" value="1" <?php echo ($is_superadmin_val ?? 0) ? 'checked' : ''; ?> >
                            <label for="is_superadmin" style="display:inline; font-weight:normal;">É Super Administrador?</label>
                        </div>
                    <?php elseif ($action === 'edit' && ($is_superadmin_val ?? 0)): ?>
                        <input type="hidden" name="is_superadmin" value="1">
                        <p><strong>Status:</strong> Super Administrador (não pode ser alterado por você)</p>
                    <?php endif; ?>
                    <div>
                        <button type="submit" name="save_admin">Salvar</button>
                        <a href="manage_admins.php" style="margin-left: 10px;">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

