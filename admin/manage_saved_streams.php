<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../../FutOnline_config/config.php';

if (!function_exists('generate_csrf_token')) {
    require_once 'csrf_utils.php';
}
$csrf_token = generate_csrf_token();
$page_title = "Biblioteca de Streams";
$message = '';
$form_error_message = '';
$form_data = $_SESSION['form_data']['add_saved_stream'] ?? [];

if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $reason = isset($_GET['reason']) ? htmlspecialchars($_GET['reason']) : '';
    if ($status == 'saved_stream_added') {
        $message = '<p style="color:green;">Stream salvo na biblioteca com sucesso!</p>';
    } elseif ($status == 'saved_stream_deleted') {
        $message = '<p style="color:green;">Stream salvo excluído com sucesso!</p>';
    } elseif ($status == 'saved_stream_delete_error') {
        $message = '<p style="color:red;">Erro ao excluir stream salvo: ' . $reason . '</p>';
    } elseif ($status == 'saved_stream_updated') {
        $message = '<p style="color:green;">Stream salvo atualizado com sucesso!</p>';
    } elseif ($status == 'saved_stream_edit_error') {
        $message = '<p style="color:red;">Erro na edição do stream salvo: ' . $reason . '</p>';
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_saved_stream'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['form_error_message']['add_saved_stream'] = "Falha na verificação de segurança (CSRF). Por favor, tente novamente.";
        header("Location: manage_saved_streams.php#add-saved-stream-form");
        exit;
    }
    $_SESSION['form_data']['add_saved_stream'] = $_POST;
    $stream_name = trim($_POST['stream_name'] ?? '');
    $stream_url_value = trim($_POST['stream_url_value'] ?? '');

    if (empty($stream_name) || empty($stream_url_value)) {
        $_SESSION['form_error_message']['add_saved_stream'] = "Nome do Stream e URL são obrigatórios.";
    } elseif (!filter_var($stream_url_value, FILTER_VALIDATE_URL)) {
        $_SESSION['form_error_message']['add_saved_stream'] = "URL do Stream inválida.";
    } else {
        try {
            $stmt_check = $pdo->prepare("SELECT id FROM saved_stream_urls WHERE stream_name = :stream_name");
            $stmt_check->bindParam(':stream_name', $stream_name, PDO::PARAM_STR);
            $stmt_check->execute();
            if ($stmt_check->rowCount() > 0) {
                $_SESSION['form_error_message']['add_saved_stream'] = "Já existe um stream salvo com este nome.";
            } else {
                $sql_insert = "INSERT INTO saved_stream_urls (stream_name, stream_url_value) VALUES (:stream_name, :stream_url_value)";
                $stmt_insert = $pdo->prepare($sql_insert);
                $stmt_insert->bindParam(':stream_name', $stream_name, PDO::PARAM_STR);
                $stmt_insert->bindParam(':stream_url_value', $stream_url_value, PDO::PARAM_STR);

                if ($stmt_insert->execute()) {
                    unset($_SESSION['form_data']['add_saved_stream']);
                    unset($_SESSION['form_error_message']['add_saved_stream']);
                    header("Location: manage_saved_streams.php?status=saved_stream_added");
                    exit;
                } else {
                    $_SESSION['form_error_message']['add_saved_stream'] = "Erro ao salvar stream na biblioteca.";
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000' && strpos($e->getMessage(), "unique_stream_name") !== false) {
                $_SESSION['form_error_message']['add_saved_stream'] = "Erro: O nome do stream já existe na biblioteca.";
            } else {
                error_log("PDOException in " . __FILE__ . " (adding saved stream): " . $e->getMessage());
                $_SESSION['form_error_message']['add_saved_stream'] = "Ocorreu um erro no banco de dados ao salvar o stream. Por favor, tente novamente.";
            }
        }
    }
    header("Location: manage_saved_streams.php#add-saved-stream-form");
    exit;
}


if (isset($_SESSION['form_error_message']['add_saved_stream'])) {
    $form_error_message = '<p style="color:red; background-color: #f8d7da; border:1px solid #f5c6cb; padding:10px; border-radius:4px;">' . htmlspecialchars($_SESSION['form_error_message']['add_saved_stream']) . '</p>';
    unset($_SESSION['form_error_message']['add_saved_stream']);
}
$form_data = $_SESSION['form_data']['add_saved_stream'] ?? [];


$saved_streams = [];
try {
    $stmt_list = $pdo->query("SELECT id, stream_name, stream_url_value, created_at FROM saved_stream_urls ORDER BY stream_name ASC");
    $saved_streams = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("PDOException in " . __FILE__ . " (fetching saved_streams list): " . $e->getMessage());
    $message .= '<p style="color:red;">Ocorreu um erro no banco de dados ao buscar os streams salvos. Por favor, tente novamente.</p>';
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

                <?php if (!empty($message)) echo "<div class='message'>{$message}</div>"; ?>
                <?php if (!empty($form_error_message)) echo "<div class='message'>{$form_error_message}</div>"; ?>

                <h2 id="add-saved-stream-form">Adicionar Novo Stream à Biblioteca</h2>
                <form action="manage_saved_streams.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div>
                        <label for="stream_name">Nome do Stream (Identificador):</label>
                        <input type="text" id="stream_name" name="stream_name" value="<?php echo htmlspecialchars($form_data['stream_name'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label for="stream_url_value">URL do Stream:</label>
                        <input type="url" id="stream_url_value" name="stream_url_value" value="<?php echo htmlspecialchars($form_data['stream_url_value'] ?? ''); ?>" required placeholder="https://example.com/stream.m3u8">
                    </div>
                    <div>
                        <button type="submit" name="add_saved_stream">Salvar na Biblioteca</button>
                    </div>
                </form>
                <?php if (isset($_SESSION['form_data']['add_saved_stream'])) unset($_SESSION['form_data']['add_saved_stream']); ?>

                <hr>
                <h2>Streams Salvos na Biblioteca</h2>
                <?php if (empty($saved_streams)): ?>
                    <p>Nenhum stream salvo na biblioteca ainda.</p>
                <?php else: ?>
                    <div class="table-responsive-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome do Stream</th>
                                <th>URL</th>
                                <th>Adicionado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($saved_streams as $ss_item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ss_item['id']); ?></td>
                                    <td><?php echo htmlspecialchars($ss_item['stream_name']); ?></td>
                                    <td style="word-break: break-all;"><?php echo htmlspecialchars($ss_item['stream_url_value']); ?></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($ss_item['created_at']))); ?></td>
                                    <td>
                                        <a href="edit_saved_stream.php?id=<?php echo $ss_item['id']; ?>" class="edit-button" style="margin-right: 5px;">Editar</a>
                                        <form action="delete_saved_stream.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este stream salvo da biblioteca?');" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="saved_stream_id" value="<?php echo $ss_item['id']; ?>">
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
</body>
</html>

