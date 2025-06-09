<?php
session_start();
require_once '../config.php'; // Ensure DB connection is available

// Handle messages
$message = '';
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status == 'match_added') {
        $message = '<p style="color:green;">Jogo adicionado com sucesso!</p>';
    } elseif ($status == 'match_add_error') {
        $message = '<p style="color:red;">Erro ao adicionar jogo: ' . htmlspecialchars($_GET['reason'] ?? '') . '</p>';
    } elseif ($status == 'match_deleted') {
        $message = '<p style="color:green;">Jogo excluído com sucesso!</p>';
    } elseif ($status == 'match_delete_error') {
        $message = '<p style="color:red;">Erro ao excluir jogo: ' . htmlspecialchars($_GET['reason'] ?? '') . '</p>';
    } elseif ($status == 'stream_added') {
        $message = '<p style="color:green;">Stream adicionado com sucesso!</p>';
    } elseif ($status == 'stream_add_error') {
        $message = '<p style="color:red;">Erro ao adicionar stream: ' . htmlspecialchars($_GET['reason'] ?? '') . '</p>';
    }
}

// Fetch existing matches to display
$matches = [];
try {
    $stmt = $pdo->query("SELECT id, team_home, team_away, match_time, description FROM matches ORDER BY match_time DESC");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message .= '<p style="color:red;">Erro ao buscar jogos: ' . $e->getMessage() . '</p>';
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; padding:0; background-color: #f4f7f6; color: #333; }
        .container {
            width: 90%;
            max-width: 1000px; /* Max width for admin panel */
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        nav { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        nav a { margin-right: 15px; text-decoration: none; color: #007bff; font-weight: bold; }
        nav a:hover { text-decoration: underline; color: #0056b3; }
        hr { margin-top: 30px; margin-bottom: 30px; border: 0; border-top: 1px solid #eee; }
        h1, h2, h3 { color: #333; }
        h1 { text-align: center; margin-bottom:30px; }
        h2 { margin-top: 30px; border-bottom: 2px solid #007bff; padding-bottom: 5px; color: #007bff;}
        h3 { margin-top:10px; margin-bottom:5px; color: #555;}
        form div, .match-item div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"],
        input[type="datetime-local"],
        input[type="url"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
        }
        textarea { resize: vertical; min-height: 80px; }
        button[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        button[type="submit"]:hover { background-color: #0056b3; }

        .match-item {
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            background-color: #fdfdfd;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .match-item h3 { margin-top: 0; color: #007bff; }
        .match-item p { margin-bottom: 5px; line-height: 1.6; }

        .add-stream-form {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #ccc;
        }
        .add-stream-form button[type="submit"] { background-color: #28a745; }
        .add-stream-form button[type="submit"]:hover { background-color: #218838; }

        .delete-button {
            background-color: #dc3545;
            color: white;
            padding: 8px 15px; /* Adjusted padding */
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
        }
        .delete-button:hover { background-color: #c82333; }

        /* Status Messages */
        .message p {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        .message p[style*="color:green;"] { /* Selects existing inline style */
            background-color: #d4edda;
            color: #155724 !important; /* Important to override inline */
            border: 1px solid #c3e6cb;
        }
        .message p[style*="color:red;"] { /* Selects existing inline style */
            background-color: #f8d7da;
            color: #721c24 !important; /* Important to override inline */
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Painel Administrativo</h1>
        <nav>
            <a href="index.php">Ver Jogos</a>
            <a href="#add-match-form">Adicionar Novo Jogo</a>
        </nav>

        <?php if(!empty($message)): ?>
            <div class="message"><?php echo $message; // Display feedback messages ?></div>
        <?php endif; ?>

        <h2 id="add-match-form">Adicionar Novo Jogo</h2>
        <form action="add_match.php" method="POST">
            <div>
                <label for="team_home">Time da Casa:</label>
                <input type="text" id="team_home" name="team_home" required>
            </div>
            <div>
                <label for="team_away">Time Visitante:</label>
                <input type="text" id="team_away" name="team_away" required>
            </div>
            <div>
                <label for="match_time">Data e Hora da Partida:</label>
                <input type="datetime-local" id="match_time" name="match_time" required>
            </div>
            <div>
                <label for="description">Descrição (opcional):</label>
                <textarea id="description" name="description"></textarea>
            </div>
            <div>
                <button type="submit">Adicionar Jogo</button>
            </div>
        </form>

        <hr>
        <h2>Jogos Cadastrados</h2>
        <?php if (empty($matches)): ?>
            <p>Nenhum jogo cadastrado ainda.</p>
        <?php else: ?>
            <?php foreach ($matches as $match): ?>
                <div class="match-item" id="match-<?php echo $match['id']; ?>">
                    <h3><?php echo htmlspecialchars($match['team_home']); ?> vs <?php echo htmlspecialchars($match['team_away']); ?></h3>
                    <p><strong>Horário:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($match['match_time']))); ?></p>
                    <?php if (!empty($match['description'])): ?>
                        <p><strong>Descrição:</strong> <?php echo nl2br(htmlspecialchars($match['description'])); ?></p>
                    <?php endif; ?>

                    <h4>Adicionar Stream para este Jogo</h4>
                    <form action="add_stream.php" method="POST" class="add-stream-form">
                        <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                        <div>
                            <label for="stream_url_<?php echo $match['id']; ?>">URL do Stream:</label>
                            <input type="url" id="stream_url_<?php echo $match['id']; ?>" name="stream_url" required placeholder="https://example.com/stream">
                        </div>
                        <div>
                            <label for="stream_label_<?php echo $match['id']; ?>">Rótulo do Stream (ex: Opção 1 HD):</label>
                            <input type="text" id="stream_label_<?php echo $match['id']; ?>" name="stream_label" required placeholder="Opção 1 HD">
                        </div>
                        <div>
                            <button type="submit">Adicionar Stream</button>
                        </div>
                    </form>
                    <form action="delete_match.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este jogo? Esta ação não pode ser desfeita.');" style="margin-top: 10px;">
                        <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                        <button type="submit" class="delete-button">Excluir Jogo</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
