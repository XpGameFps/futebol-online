<?php
require_once 'auth_check.php';
require_once '../config.php';

// Fallback for CSRF utility functions if not already included by auth_check.php
if (!function_exists('generate_csrf_token')) {
    require_once 'csrf_utils.php'; // Path confirmed from previous subtask
}

$page_title = "Editar Stream";
$message = '';
$stream_id = null;
$match_id_for_redirect = null;

$stream_label = '';
$stream_url = '';

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $stream_id = (int)$_GET['id'];
} else if (isset($_POST['stream_id']) && filter_var($_POST['stream_id'], FILTER_VALIDATE_INT)) {
    $stream_id = (int)$_POST['stream_id'];
}

if (isset($_GET['match_id']) && filter_var($_GET['match_id'], FILTER_VALIDATE_INT)) {
    $match_id_for_redirect = (int)$_GET['match_id'];
} elseif (isset($_POST['match_id_for_redirect']) && filter_var($_POST['match_id_for_redirect'], FILTER_VALIDATE_INT)) {
    $match_id_for_redirect = (int)$_POST['match_id_for_redirect'];
}

if ($stream_id === null) {
    $redirect_url = $match_id_for_redirect ? "index.php?status=stream_edit_error&reason=invalid_stream_id#match-" . $match_id_for_redirect : "index.php?status=stream_edit_error&reason=invalid_stream_id";
    if ($_SERVER["REQUEST_METHOD"] == "POST") { $_SESSION['general_message']['admin_index'] = '<p style="color:red;">ID do stream inválido ou ausente.</p>'; }
    header("Location: " . $redirect_url);
    exit;
}

$stream_data_fetched = false;
if ($_SERVER["REQUEST_METHOD"] != "POST" || !empty($message)) {
    try {
        $stmt_fetch = $pdo->prepare("SELECT stream_label, stream_url, match_id FROM streams WHERE id = :id");
        $stmt_fetch->bindParam(':id', $stream_id, PDO::PARAM_INT);
        $stmt_fetch->execute();
        $stream = $stmt_fetch->fetch(PDO::FETCH_ASSOC);
        if (!$stream) {
            $redirect_url = $match_id_for_redirect ? "index.php?status=stream_edit_error&reason=stream_not_found#match-" . $match_id_for_redirect : "index.php?status=stream_edit_error&reason=stream_not_found";
            if ($_SERVER["REQUEST_METHOD"] == "POST") { $_SESSION['general_message']['admin_index'] = '<p style="color:red;">Stream não encontrado para edição.</p>';}
            header("Location: " . $redirect_url);
            exit;
        }
        $stream_label = $stream['stream_label'];
        $stream_url = $stream['stream_url'];
        if ($match_id_for_redirect === null) { $match_id_for_redirect = $stream['match_id']; }
        $stream_data_fetched = true;
    } catch (PDOException $e) { $message = '<p style="color:red;">Erro ao buscar dados do stream: ' . $e->getMessage() . '</p>'; }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stream'])) {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $message = '<p style="color:red;">Falha na verificação de segurança (CSRF). Por favor, tente novamente.</p>';
        // Regenerate token for the form if it's redisplayed with this error
        $csrf_token = generate_csrf_token(true);
        // Fall through to re-display the form with the message
    } else {
        // Existing POST processing logic starts here
        $stream_label = trim($_POST['stream_label'] ?? '');
        $stream_url = trim($_POST['stream_url'] ?? '');

        if (empty($stream_label) || empty($stream_url)) {
            $message = '<p style="color:red;">Rótulo do Stream e URL são obrigatórios.</p>';
        } elseif (!filter_var($stream_url, FILTER_VALIDATE_URL)) {
            $message = '<p style="color:red;">URL do Stream inválida.</p>';
        } else {
            try {
                $sql_update = "UPDATE streams SET stream_label = :stream_label, stream_url = :stream_url WHERE id = :id";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->bindParam(':stream_label', $stream_label, PDO::PARAM_STR);
            $stmt_update->bindParam(':stream_url', $stream_url, PDO::PARAM_STR);
            $stmt_update->bindParam(':id', $stream_id, PDO::PARAM_INT);

            if ($stmt_update->execute()) {
                $redirect_anchor = $match_id_for_redirect ? "#match-" . $match_id_for_redirect : "";
                if (session_status() == PHP_SESSION_NONE) session_start();
                $_SESSION['general_message']['admin_index'] = '<p style="color:green;">Stream atualizado com sucesso!</p>';
                header("Location: index.php?status=stream_updated" . $redirect_anchor);
                exit;
            } else { $message = '<p style="color:red;">Erro ao atualizar stream no banco de dados.</p>'; }
            } catch (PDOException $e) { $message = '<p style="color:red;">Erro de banco de dados: ' . $e->getMessage() . '</p>'; }
        }
    } // End of the new "else" block for CSRF validation
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_SESSION['general_message']['admin_index'])) {
    if(empty($message)) { $message = $_SESSION['general_message']['admin_index']; }
    unset($_SESSION['general_message']['admin_index']);
}

// Generate CSRF token for the form
// This should be done before any HTML output for the form.
// If the form is re-displayed due to an error (including CSRF error),
// $csrf_token might already be set by the POST handling block.
if (empty($csrf_token)) { // Generate only if not already set
    $csrf_token = generate_csrf_token(true);
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
    <div class="container" style="max-width: 700px;">
        <div class="admin-layout">
            <?php require_once 'templates/navigation.php'; ?>
            <div class="main-content">
                <h1><?php echo htmlspecialchars($page_title); ?></h1>

        <?php if(!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($stream_id && ($stream_data_fetched || $_SERVER["REQUEST_METHOD"] == "POST") ): ?>
        <form action="edit_stream.php?id=<?php echo $stream_id; ?><?php echo $match_id_for_redirect ? '&match_id=' . $match_id_for_redirect : ''; ?>" method="POST">
            <input type="hidden" name="stream_id" value="<?php echo $stream_id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <?php if ($match_id_for_redirect): ?>
                <input type="hidden" name="match_id_for_redirect" value="<?php echo $match_id_for_redirect; ?>">
            <?php endif; ?>

            <div>
                <label for="stream_label">Rótulo do Stream:</label>
                <input type="text" id="stream_label" name="stream_label" value="<?php echo htmlspecialchars($stream_label); ?>" required>
            </div>
            <div>
                <label for="stream_url">URL do Stream:</label>
                <input type="url" id="stream_url" name="stream_url" value="<?php echo htmlspecialchars($stream_url); ?>" required placeholder="https://example.com/stream">
            </div>
            <div>
                <button type="submit" name="update_stream">Salvar Alterações</button>
                <a href="index.php<?php echo $match_id_for_redirect ? '#match-' . $match_id_for_redirect : ''; ?>" style="margin-left: 10px;">Cancelar</a>
            </div>
        </form>
        <?php elseif(empty($message)):
             echo '<p style="color:red;">Não foi possível carregar os dados do stream para edição. Verifique se o ID é válido.</p>';
             echo '<p><a href="index.php' . ($match_id_for_redirect ? '#match-' . $match_id_for_redirect : '') . '">Voltar</a></p>';
        endif; ?>
            </div> <!-- end main-content -->
        </div> <!-- end admin-layout -->
    </div> <!-- end container -->
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
