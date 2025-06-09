<?php
// admin/index.php
require_once 'auth_check.php';
require_once '../config.php';

// Handle messages
$message = ''; // Ensure $message is initialized
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $reason = isset($_GET['reason']) ? htmlspecialchars($_GET['reason']) : '';
    if ($status == 'match_added') {
        $message = '<p style="color:green;">Jogo adicionado com sucesso!</p>';
    } elseif ($status == 'match_add_error') {
        if ($reason == 'file_upload_error') {
            $upload_error_msg = isset($_GET['err_msg']) ? htmlspecialchars(urldecode($_GET['err_msg'])) : 'Erro desconhecido no upload da capa.';
            $message = '<p style="color:red;">Erro ao adicionar jogo: Problema no upload da imagem de capa. ' . $upload_error_msg . '</p>';
        } else {
            $message = '<p style="color:red;">Erro ao adicionar jogo: ' . htmlspecialchars($reason) . '</p>';
        }
    } elseif ($status == 'match_deleted') {
        $message = '<p style="color:green;">Jogo excluído com sucesso!</p>';
    } elseif ($status == 'match_delete_error') {
        $message = '<p style="color:red;">Erro ao excluir jogo: ' . htmlspecialchars($reason) . '</p>';
    } elseif ($status == 'stream_added') {
        $message = '<p style="color:green;">Stream adicionado com sucesso!</p>';
    } elseif ($status == 'stream_add_error') {
        $message = '<p style="color:red;">Erro ao adicionar stream: ' . htmlspecialchars($reason) . '</p>';
    }
}


// Fetch existing leagues for the dropdown
$leagues_for_dropdown = [];
try {
    $stmt_leagues = $pdo->query("SELECT id, name FROM leagues ORDER BY name ASC");
    $leagues_for_dropdown = $stmt_leagues->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // $message .= '<p style="color:red;">Erro ao buscar ligas para o formulário: ' . $e->getMessage() . '</p>';
}

// Determine view type for matches (upcoming/all or past)
$view_type = $_GET['view'] ?? 'upcoming'; // Default to 'upcoming'

$matches_sql_condition = "m.match_time >= NOW()"; // Alias m for matches table
$matches_order_by = "m.match_time ASC"; // Alias m for matches table
$page_subtitle = "Próximos Jogos / Jogos Recentes";

if ($view_type === 'past') {
    $matches_sql_condition = "m.match_time < NOW()"; // Alias m for matches table
    $matches_order_by = "m.match_time DESC"; // Show most recent past games first // Alias m for matches table
    $page_subtitle = "Jogos Passados";
}

// Fetch existing matches to display
$matches = [];
try {
    // Added league_name to the SELECT query using a LEFT JOIN
    $sql_fetch_matches = "SELECT m.id, m.team_home, m.team_away, m.match_time, m.description, m.cover_image_filename, l.name as league_name
                          FROM matches m
                          LEFT JOIN leagues l ON m.league_id = l.id
                          WHERE {$matches_sql_condition}
                          ORDER BY {$matches_order_by}";
    $stmt_matches = $pdo->query($sql_fetch_matches);
    $matches = $stmt_matches->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message .= '<p style="color:red;">Erro ao buscar jogos: ' . $e->getMessage() . '</p>';
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin - Gerenciar Jogos</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; padding:0; background-color: #f4f7f6; color: #333; }
        .container { width: 90%; max-width: 1200px; /* Increased max-width */ margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        nav { display: flex; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; justify-content: space-between; }
        nav a { margin-right: 15px; text-decoration: none; color: #007bff; font-weight: bold; }
        nav a:hover { text-decoration: underline; color: #0056b3; }
        .nav-user-info { font-size: 0.9em; color: #555; }
        .logout-link { color: #dc3545; font-weight: bold; text-decoration: none; }
        .logout-link:hover { text-decoration: underline; color: #c82333; }
        hr { margin-top: 30px; margin-bottom: 30px; border: 0; border-top: 1px solid #eee; }
        h1, h2, h3 { color: #333; }
        h1 { text-align: center; margin-bottom:30px; }
        h2 { margin-top: 30px; border-bottom: 2px solid #007bff; padding-bottom: 5px; color: #007bff;}
        h3 { margin-top:10px; margin-bottom:5px; color: #555;}
        form div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="datetime-local"], input[type="url"], textarea, select, input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 1em; }
        input[type="file"] { padding: 3px; }
        button[type="submit"] { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; transition: background-color 0.3s ease; }
        button[type="submit"]:hover { background-color: #0056b3; }
        .match-item { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; background-color: #fdfdfd; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .match-item h3 { margin-top: 0; color: #007bff; }
        .match-item p { margin-bottom: 5px; line-height: 1.6; }
        .add-stream-form { margin-top: 15px; padding-top: 15px; border-top: 1px dashed #ccc; }
        .add-stream-form button[type="submit"] { background-color: #28a745; }
        .add-stream-form button[type="submit"]:hover { background-color: #218838; }
        .delete-button { background-color: #dc3545; color: white; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9em; }
        .delete-button:hover { background-color: #c82333; }
        .message { margin-bottom: 20px; }
        .message p { padding: 15px; border-radius: 5px; font-weight: bold; margin:0; }
        .message p[style*="color:green;"] { background-color: #d4edda; color: #155724 !important; border: 1px solid #c3e6cb; }
        .message p[style*="color:red;"] { background-color: #f8d7da; color: #721c24 !important; border: 1px solid #f5c6cb; }
        .view-switcher { margin-bottom: 20px; text-align:center; }
        .view-switcher a { margin: 0 10px; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight:bold; }
        .view-switcher a.active-view { background-color: #007bff; color: white; }
        .view-switcher a:not(.active-view) { background-color: #e9ecef; color: #007bff; }
        .match-cover-admin { max-width: 100px; max-height: 60px; object-fit: cover; border-radius: 3px; margin-bottom: 5px; display:block; }
    </style>
</head>
<body>
    <div class="container">
        <nav>
            <div> <!-- Group for main nav links -->
                <a href="index.php">Painel Principal (Jogos)</a>
                <a href="manage_leagues.php">Gerenciar Ligas</a>
                <a href="manage_channels.php">Gerenciar Canais TV</a>
                <a href="manage_settings.php">Configurações</a> <!-- New Link -->
            </div>
            <div class="nav-user-info"> <!-- Group for user info and logout -->
                Usuário: <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?> |
                <a href="logout.php" class="logout-link">Logout</a>
            </div>
        </nav>
        <h1>Painel Administrativo - Jogos</h1>

        <?php if(!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <h2 id="add-match-form">Adicionar Novo Jogo</h2>
        <form action="add_match.php" method="POST" enctype="multipart/form-data">
            <div><label for="team_home">Time da Casa:</label><input type="text" id="team_home" name="team_home" required></div>
            <div><label for="team_away">Time Visitante:</label><input type="text" id="team_away" name="team_away" required></div>
            <div><label for="match_time">Data e Hora da Partida:</label><input type="datetime-local" id="match_time" name="match_time" required></div>
            <div><label for="league_id">Liga (Opcional):</label><select id="league_id" name="league_id"><option value="">-- Selecionar Liga --</option><?php foreach ($leagues_for_dropdown as $league) { echo '<option value="'.htmlspecialchars($league['id']).'">'.htmlspecialchars($league['name']).'</option>'; } ?></select></div>
            <div><label for="cover_image_file">Imagem de Capa (opcional, PNG, JPG, GIF, max 2MB):</label><input type="file" id="cover_image_file" name="cover_image_file" accept="image/png, image/jpeg, image/gif"></div>
            <div><label for="description">Descrição (opcional):</label><textarea id="description" name="description"></textarea></div>
            <div><button type="submit">Adicionar Jogo</button></div>
        </form>
        <hr>

        <h2><?php echo htmlspecialchars($page_subtitle); ?></h2>
        <div class="view-switcher">
            <a href="index.php?view=upcoming" class="<?php echo $view_type === 'upcoming' ? 'active-view' : ''; ?>">Próximos/Recentes</a>
            <a href="index.php?view=past" class="<?php echo $view_type === 'past' ? 'active-view' : ''; ?>">Jogos Passados</a>
        </div>

        <?php if (empty($matches)): ?>
            <p>Nenhum jogo encontrado para esta visualização.</p>
        <?php else: ?>
            <?php foreach ($matches as $match): ?>
                <div class="match-item" id="match-<?php echo $match['id']; ?>">
                    <h3><?php echo htmlspecialchars($match['team_home']); ?> vs <?php echo htmlspecialchars($match['team_away']); ?></h3>
                    <?php if (!empty($match['cover_image_filename'])): ?>
                        <img src="../uploads/covers/matches/<?php echo htmlspecialchars($match['cover_image_filename']); ?>" alt="Capa" class="match-cover-admin">
                    <?php endif; ?>
                    <p><strong>Horário:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($match['match_time']))); ?></p>
                    <p><strong>Liga:</strong> <?php echo htmlspecialchars($match['league_name'] ?? 'N/A'); ?></p>
                    <?php if (!empty($match['description'])): ?>
                        <p><strong>Descrição:</strong> <?php echo nl2br(htmlspecialchars($match['description'])); ?></p>
                    <?php endif; ?>

                    <form action="delete_match.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este jogo? Esta ação não pode ser desfeita.');" style="margin-top: 10px; display:inline-block;">
                        <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                        <button type="submit" class="delete-button">Excluir Jogo</button>
                    </form>

                    <details style="margin-top:10px; display:inline-block; margin-left:10px;">
                        <summary style="cursor:pointer; color:#007bff; font-weight:bold;">Adicionar Stream</summary>
                        <form action="add_stream.php" method="POST" class="add-stream-form" style="margin-top:5px;">
                            <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                            <div><label for="stream_url_<?php echo $match['id']; ?>">URL:</label><input type="url" id="stream_url_<?php echo $match['id']; ?>" name="stream_url" required></div>
                            <div><label for="stream_label_<?php echo $match['id']; ?>">Rótulo:</label><input type="text" id="stream_label_<?php echo $match['id']; ?>" name="stream_label" required></div>
                            <div><button type="submit">Salvar Stream</button></div>
                        </form>
                    </details>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
