<?php
require_once __DIR__ . '/../../../FutOnline_config/config.php';
require_once '../auth_check.php';
require_once '../csrf_utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $_SESSION['error_message'] = "Falha na verificação CSRF. Ação de exclusão não permitida.";
        header("Location: ../manage_banners.php");
        exit;
    }

    $banner_id = isset($_POST['banner_id']) ? (int)$_POST['banner_id'] : 0;
    $image_path = isset($_POST['image_path']) ? trim($_POST['image_path']) : '';

    if ($banner_id <= 0) {
        $_SESSION['error_message'] = "ID de banner inválido para exclusão.";
        header("Location: ../manage_banners.php");
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM banners WHERE id = :id");
        $stmt->bindParam(':id', $banner_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if (!empty($image_path)) {
                $full_image_path = '../../uploads/banners/' . $image_path;
                if (file_exists($full_image_path) && is_writable($full_image_path)) {
                    if (!unlink($full_image_path)) {
                        error_log("Falha ao excluir o arquivo de imagem: " . $full_image_path);
                    }
                } elseif (!empty($image_path) && !file_exists($full_image_path)) {
                    // Arquivo não existe, nada a fazer
                } elseif (!empty($image_path)) {
                    error_log("Arquivo de imagem não encontrado ou sem permissão de escrita: " . $full_image_path);
                }
            }
            $pdo->commit();
            $_SESSION['success_message'] = "Banner excluído com sucesso!";
        } else {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Erro ao excluir banner do banco de dados.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Erro no banco de dados ao excluir banner: " . $e->getMessage();
    }

    header("Location: ../manage_banners.php");
    exit;

} else {
    $_SESSION['error_message'] = "Ação não permitida.";
    header("Location: ../manage_banners.php");
    exit;
}
?>

