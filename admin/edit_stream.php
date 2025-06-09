<?php
require_once 'auth_check.php';
require_once '../config.php';

$page_title = "Editar Stream";
$message = '';
$stream_id = null;
$match_id_for_redirect = null; // To redirect back to the correct match anchor

// Form data variables
$stream_label = '';
$stream_url = '';

// Get stream_id and optional match_id for redirect
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
    header("Location: " . $redirect_url);
    exit;
}

// Fetch current stream data for pre-filling the form
// Also fetch match_id if not already provided, for robust redirection
$stream_data_fetched = false; // Flag to check if initial data load was successful
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['update_stream'])) {
    try {
        $stmt_fetch = $pdo->prepare("SELECT stream_label, stream_url, match_id FROM streams WHERE id = :id");
        $stmt_fetch->bindParam(':id', $stream_id, PDO::PARAM_INT);
        $stmt_fetch->execute();
        $stream = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

        if (!$stream) {
            $redirect_url = $match_id_for_redirect ? "index.php?status=stream_edit_error&reason=stream_not_found#match-" . $match_id_for_redirect : "index.php?status=stream_edit_error&reason=stream_not_found";
            header("Location: " . $redirect_url);
            exit;
        }
        $stream_label = $stream['stream_label'];
        $stream_url = $stream['stream_url'];
        if ($match_id_for_redirect === null) {
            $match_id_for_redirect = $stream['match_id'];
        }
        $stream_data_fetched = true;

    } catch (PDOException $e) {
        $message = '<p style="color:red;">Erro ao buscar dados do stream: ' . $e->getMessage() . '</p>';
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stream'])) {
    // Use submitted values to repopulate form in case of error
    $stream_label = trim($_POST['stream_label'] ?? '');
    $stream_url = trim($_POST['stream_url'] ?? '');
    // $stream_id is already set from POST or GET at the top
    // $match_id_for_redirect is also already set

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
                header("Location: index.php?status=stream_updated" . $redirect_anchor);
                exit;
            } else {
                $message = '<p style="color:red;">Erro ao atualizar stream no banco de dados.</p>';
            }
        } catch (PDOException $e) {
            $message = '<p style="color:red;">Erro de banco de dados: ' . $e->getMessage() . '</p>';
        }
    }
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
        <nav>
            <div>
                <a href="index.php<?php echo $match_id_for_redirect ? '#match-' . $match_id_for_redirect : ''; ?>">Painel Principal (Jogos)</a>
                <a href="manage_leagues.php">Gerenciar Ligas</a>
                <a href="manage_channels.php">Gerenciar Canais TV</a>
                <a href="manage_settings.php">Configurações</a>
            </div>
            <div class="nav-user-info">
                Usuário: <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?> |
                <a href="logout.php" class="logout-link">Logout</a>
            </div>
        </nav>
        <h1><?php echo htmlspecialchars($page_title); ?></h1>

        <?php if(!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php
        // Show form if:
        // 1. It's a GET request AND data was successfully fetched.
        // 2. It's a POST request (meaning form was submitted, possibly with errors, so we show it again for correction).
        if ( ($stream_id && $stream_data_fetched && $_SERVER["REQUEST_METHOD"] != "POST") || ($_SERVER["REQUEST_METHOD"] == "POST") ):
        ?>
        <form action="edit_stream.php?id=<?php echo $stream_id; ?><?php echo $match_id_for_redirect ? '&match_id=' . $match_id_for_redirect : ''; ?>" method="POST">
            <input type="hidden" name="stream_id" value="<?php echo $stream_id; ?>">
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
        <?php elseif(empty($message)): // If initial data fetch failed and no other message is set
             echo '<p style="color:red;">Não foi possível carregar os dados do stream para edição. Verifique se o ID é válido.</p>';
             echo '<p><a href="index.php' . ($match_id_for_redirect ? '#match-' . $match_id_for_redirect : '') . '">Voltar</a></p>';
        endif; ?>
    </div>
</body>
</html>
