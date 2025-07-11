<?php
require_once 'auth_check.php'; require_once __DIR__ . '/../../FutOnline_config/config.php';   
if (!isset($_SESSION['admin_is_superadmin']) || !$_SESSION['admin_is_superadmin']) {
    $_SESSION['admin_flash_message'] = ['type' => 'error', 'text' => 'Você não tem permissão para excluir administradores.'];
    header("Location: manage_admins.php");
    exit;
}

$message_type = 'error'; $message_text = 'Ocorreu um erro desconhecido.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_id_to_delete'])) {
        if (!function_exists('validate_csrf_token')) {
        require_once 'csrf_utils.php';
    }

    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['admin_flash_message'] = ['type' => 'error', 'text' => 'Falha na verificação de segurança (CSRF). Ação de exclusão não permitida.'];
        header("Location: manage_admins.php");
        exit;
    }
        $admin_id_to_delete = (int)$_POST['admin_id_to_delete'];

    if ($admin_id_to_delete <= 0) {
        $message_text = 'ID de administrador inválido para exclusão.';
    } elseif ($admin_id_to_delete == ($_SESSION['admin_id'] ?? 0)) {
        $message_text = 'Você não pode excluir a si mesmo.';
    } else {
        try {
                        $stmt_check_super = $pdo->prepare("SELECT COUNT(id) as superadmin_count FROM admins WHERE is_superadmin = 1");
            $stmt_check_super->execute();
            $superadmin_count_result = $stmt_check_super->fetch(PDO::FETCH_ASSOC);
            $total_superadmins = $superadmin_count_result ? (int)$superadmin_count_result['superadmin_count'] : 0;

            $stmt_is_target_super = $pdo->prepare("SELECT is_superadmin FROM admins WHERE id = :id");
            $stmt_is_target_super->bindParam(':id', $admin_id_to_delete, PDO::PARAM_INT);
            $stmt_is_target_super->execute();
            $target_admin_data = $stmt_is_target_super->fetch(PDO::FETCH_ASSOC);
            $target_is_superadmin = $target_admin_data ? (bool)$target_admin_data['is_superadmin'] : false;

            if ($target_is_superadmin && $total_superadmins <= 1) {
                $message_text = 'Não é possível excluir o último super administrador.';
            } else {
                                $stmt_delete = $pdo->prepare("DELETE FROM admins WHERE id = :id");
                $stmt_delete->bindParam(':id', $admin_id_to_delete, PDO::PARAM_INT);

                if ($stmt_delete->execute()) {
                    if ($stmt_delete->rowCount() > 0) {
                        $message_type = 'success';
                        $message_text = 'Administrador excluído com sucesso!';
                    } else {
                        $message_text = 'Administrador não encontrado ou já excluído.';
                    }
                } else {
                    $message_text = 'Erro ao excluir administrador do banco de dados.';
                }
            }
        } catch (PDOException $e) {
            error_log("PDOException in " . __FILE__ . " (deleting admin ID: " . $admin_id_to_delete . "): " . $e->getMessage());
            $message_text = "Ocorreu um erro no banco de dados ao excluir o administrador. Por favor, tente novamente.";
                    }
    }
} else {
    $message_text = 'Requisição inválida para excluir administrador.';
}

$_SESSION['admin_flash_message'] = ['type' => $message_type, 'text' => $message_text];
header("Location: manage_admins.php");
exit;
?>

