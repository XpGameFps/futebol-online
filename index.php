<?php
// Main page - List matches
require_once 'config.php'; // Database connection

$matches = [];
$error_message = '';

try {
    // Fetch upcoming or recent matches, ordered by match time.
    // For simplicity, let's fetch all matches ordered by most recent first.
    // A more advanced query might filter for match_time >= NOW() for upcoming games.
    $stmt = $pdo->query("SELECT id, team_home, team_away, match_time, description FROM matches ORDER BY match_time DESC");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erro ao buscar jogos: " . $e->getMessage();
    // In a production environment, log this error instead of displaying to user directly.
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jogos de Futebol Ao Vivo</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #1a1a1a; /* Metallic black base */
            color: #e0e0e0; /* Light gray text for contrast */
        }
        header {
            background-color: #0d0d0d; /* Darker metallic black */
            color: #00ff00; /* Green accent */
            padding: 1.5em 0;
            text-align: center;
            border-bottom: 3px solid #00ff00; /* Green accent line */
        }
        header h1 {
            margin: 0;
            font-size: 2.5em;
        }
        .container {
            max-width: 1200px; /* Max width for very large screens */
            width: 90%; /* Responsive width */
            margin: 20px auto;
            overflow: hidden;
            padding: 20px;
        }
        .match-list {
            list-style: none;
            padding: 0;
        }
        .match-list-item {
            background-color: #2c2c2c; /* Slightly lighter metallic black for items */
            border: 1px solid #00ff00; /* Green border */
            margin-bottom: 15px;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 255, 0, 0.1); /* Subtle green glow */
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .match-list-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 255, 0, 0.2); /* Enhanced green glow on hover */
        }
        .match-list-item a {
            text-decoration: none;
            color: #00ff00; /* Green links */
            font-size: 1.8em;
            font-weight: bold;
        }
        .match-list-item a:hover {
            text-decoration: underline;
        }
        .match-time {
            font-size: 1em;
            color: #a0a0a0; /* Lighter gray for time */
            margin-top: 5px;
            margin-bottom: 10px;
        }
        .match-description {
            font-size: 1.1em;
            color: #c0c0c0; /* Medium gray for description */
            margin-top: 10px;
        }
        .no-matches, .error-message {
            text-align: center;
            font-size: 1.2em;
            color: #ffcc00; /* Yellow for notices/errors */
            padding: 20px;
            background-color: #2c2c2c;
            border: 1px solid #ffcc00;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Jogos de Hoje</h1>
    </header>
    <div class="container">
        <?php if (!empty($error_message)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php elseif (empty($matches)): ?>
            <p class="no-matches">Nenhum jogo programado no momento. Volte mais tarde!</p>
        <?php else: ?>
            <ul class="match-list">
                <?php foreach ($matches as $match): ?>
                    <li class="match-list-item">
                        <a href="match.php?id=<?php echo htmlspecialchars($match['id']); ?>">
                            <?php echo htmlspecialchars($match['team_home']); ?> vs <?php echo htmlspecialchars($match['team_away']); ?>
                        </a>
                        <p class="match-time">
                            Horário: <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($match['match_time']))); ?>
                        </p>
                        <?php if (!empty($match['description'])): ?>
                            <p class="match-description"><?php echo nl2br(htmlspecialchars($match['description'])); ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
