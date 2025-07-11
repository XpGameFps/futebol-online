<?php
require_once __DIR__ . '/../../../FutOnline_config/config.php';
require_once '../auth_check.php';
require_once '../csrf_utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $_SESSION['error_message'] = "Falha na verificação CSRF. Ação de alternância de status não permitida.";
        header("Location: ../manage_banners.php");
        exit;
    }

    $banner_id = isset($_POST['banner_id']) ? (int)$_POST['banner_id'] : 0;
    $current_status = isset($_POST['current_status']) ? (int)$_POST['current_status'] : null;

    if ($banner_id <= 0 || $current_status === null) {
        $_SESSION['error_message'] = "Dados inválidos para alternar status do banner.";
        header("Location: ../manage_banners.php");
        exit;
    }

    $new_status = $current_status == 1 ? 0 : 1;

    try {
        $stmt = $pdo->prepare("UPDATE banners SET is_active = :new_status WHERE id = :id");
        $stmt->bindParam(':new_status', $new_status, PDO::PARAM_INT);
        $stmt->bindParam(':id', $banner_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Status do banner atualizado com sucesso!";
        } else {
            $_SESSION['error_message'] = "Erro ao atualizar status do banner.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erro no banco de dados ao atualizar status: " . $e->getMessage();
    }

    header("Location: ../manage_banners.php");
    exit;

} else {
    $_SESSION['error_message'] = "Ação não permitida.";
    header("Location: ../manage_banners.php");
    exit;
}
?>

