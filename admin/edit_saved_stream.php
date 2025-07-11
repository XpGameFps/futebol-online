<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../../FutOnline_config/config.php';

if (!function_exists('generate_csrf_token')) {
    require_once 'csrf_utils.php';
}
$csrf_token = generate_csrf_token(true);
$page_title = "Editar Stream Salvo";
$message = '';
$saved_stream_id = null;
$stream_name = '';
$stream_url_value = '';

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $saved_stream_id = (int)$_GET['id'];
} elseif (isset($_POST['saved_stream_id']) && filter_var($_POST['saved_stream_id'], FILTER_VALIDATE_INT)) {
    $saved_stream_id = (int)$_POST['saved_stream_id'];
}

if ($saved_stream_id === null) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $_SESSION['general_message']['manage_saved_streams'] = '<p style="color:red;">ID do stream salvo inválido ou ausente.</p>';
    }
    header("Location: manage_saved_streams.php?status=saved_stream_edit_error&reason=invalid_id");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST" || !empty($message)) {
    try {
        $stmt_fetch = $pdo->prepare("SELECT stream_name, stream_url_value FROM saved_stream_urls WHERE id = :id");
        $stmt_fetch->bindParam(':id', $saved_stream_id, PDO::PARAM_INT);
        $stmt_fetch->execute();
        $item = $stmt_fetch->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $_SESSION['general_message']['manage_saved_streams'] = '<p style="color:red;">Stream salvo não encontrado para edição.</p>';
            }
            header("Location: manage_saved_streams.php?status=saved_stream_edit_error&reason=not_found");
            exit;
        }
        $stream_name = $item['stream_name'];
        $stream_url_value = $item['stream_url_value'];
    } catch (PDOException $e) {
        error_log("PDOException in " . __FILE__ . " (fetching saved stream ID: " . $saved_stream_id . "): " . $e->getMessage());
        $message = '<p style="color:red;">Ocorreu um erro no banco de dados ao carregar o stream salvo. Por favor, tente novamente.</p>';
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_saved_stream'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $message = '<p style="color:red;">Falha na verificação de segurança (CSRF). Por favor, tente novamente.</p>';
        $csrf_token = generate_csrf_token(true);
    } else {
        $new_stream_name = trim($_POST['stream_name'] ?? '');
        $new_stream_url_value = trim($_POST['stream_url_value'] ?? '');
        $stream_name = $new_stream_name;
        $stream_url_value = $new_stream_url_value;
        if (empty($new_stream_name) || empty($new_stream_url_value)) {
            $message = '<p style="color:red;">Nome do Stream e URL são obrigatórios.</p>';
        } elseif (!filter_var($new_stream_url_value, FILTER_VALIDATE_URL)) {
            $message = '<p style="color:red;">URL do Stream inválida.</p>';
        } else {
            try {
                $stmt_check = $pdo->prepare("SELECT id FROM saved_stream_urls WHERE stream_name = :stream_name AND id != :id");
                $stmt_check->bindParam(':stream_name', $new_stream_name, PDO::PARAM_STR);
                $stmt_check->bindParam(':id', $saved_stream_id, PDO::PARAM_INT);
                $stmt_check->execute();
                if ($stmt_check->rowCount() > 0) {
                    $message = '<p style="color:red;">Já existe um stream salvo com este nome.</p>';
                } else {
                    $sql_update = "UPDATE saved_stream_urls SET stream_name = :stream_name, stream_url_value = :stream_url_value WHERE id = :id";
                    $stmt_update = $pdo->prepare($sql_update);
                    $stmt_update->bindParam(':stream_name', $new_stream_name, PDO::PARAM_STR);
                    $stmt_update->bindParam(':stream_url_value', $new_stream_url_value, PDO::PARAM_STR);
                    $stmt_update->bindParam(':id', $saved_stream_id, PDO::PARAM_INT);
                    if ($stmt_update->execute()) {
                        if (session_status() == PHP_SESSION_NONE) session_start();
                        $_SESSION['general_message']['manage_saved_streams'] = '<p style="color:green;">Stream salvo atualizado com sucesso!</p>';
                        header("Location: manage_saved_streams.php?status=saved_stream_updated");
                        exit;
                    } else {
                        $message = '<p style="color:red;">Erro ao atualizar stream salvo no banco de dados.</p>';
                    }
                }
            } catch (PDOException $e) {
                if ($e->getCode() == '23000' && strpos($e->getMessage(), "unique_stream_name") !== false) {
                    $message = '<p style="color:red;">Erro: O nome do stream já existe na biblioteca.</p>';
                } else {
                    error_log("PDOException in " . __FILE__ . " (updating saved stream ID: " . $saved_stream_id . "): " . $e->getMessage());
                    $message = '<p style="color:red;">Ocorreu um erro no banco de dados ao atualizar o stream salvo. Por favor, tente novamente.</p>';
                }
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_SESSION['general_message']['manage_saved_streams'])) {
    if (empty($message)) {
        $message = $_SESSION['general_message']['manage_saved_streams'];
    }
    unset($_SESSION['general_message']['manage_saved_streams']);
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
    <div class="container" style="max-width:700px;">
        <div class="admin-layout">
            <?php require_once 'templates/navigation.php'; ?>
            <div class="main-content">
                <h1><?php echo htmlspecialchars($page_title); ?></h1>
                <?php if (!empty($message)) echo "<div class='message'>{$message}</div>"; ?>
                <?php if ($saved_stream_id && ((isset($item) && $item) || $_SERVER["REQUEST_METHOD"] == "POST")): ?>
                    <form action="edit_saved_stream.php?id=<?php echo $saved_stream_id; ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="saved_stream_id" value="<?php echo $saved_stream_id; ?>">
                        <div>
                            <label for="stream_name">Nome do Stream (Identificador):</label>
                            <input type="text" id="stream_name" name="stream_name" value="<?php echo htmlspecialchars($stream_name); ?>" required>
                        </div>
                        <div>
                            <label for="stream_url_value">URL do Stream:</label>
                            <input type="url" id="stream_url_value" name="stream_url_value" value="<?php echo htmlspecialchars($stream_url_value); ?>" required placeholder="https://">
                        </div>
                        <div>
                            <button type="submit" name="update_saved_stream">Salvar Alterações</button>
                            <a href="manage_saved_streams.php" style="margin-left:10px;">Cancelar</a>
                        </div>
                    </form>
                <?php elseif (empty($message)): ?>
                    <p style="color:red;">Não foi possível carregar os dados do stream salvo para edição. Verifique se o ID é válido.</p>
                    <p><a href="manage_saved_streams.php">Voltar para Biblioteca de Streams</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

