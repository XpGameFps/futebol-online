<?php
// Single match page - Display player and stream options
require_once 'config.php'; // Database connection

$match_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$match = null;
$streams = [];
$error_message = '';

if ($match_id > 0) {
    try {
        // Fetch match details
        $stmt_match = $pdo->prepare("SELECT id, team_home, team_away, match_time, description FROM matches WHERE id = :match_id");
        $stmt_match->bindParam(':match_id', $match_id, PDO::PARAM_INT);
        $stmt_match->execute();
        $match = $stmt_match->fetch(PDO::FETCH_ASSOC);

        if ($match) {
            // Fetch associated streams
            $stmt_streams = $pdo->prepare("SELECT id, stream_url, stream_label FROM streams WHERE match_id = :match_id ORDER BY stream_label ASC");
            $stmt_streams->bindParam(':match_id', $match_id, PDO::PARAM_INT);
            $stmt_streams->execute();
            $streams = $stmt_streams->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error_message = "Jogo não encontrado.";
        }
    } catch (PDOException $e) {
        $error_message = "Erro ao buscar detalhes do jogo: " . $e->getMessage();
        // In a production environment, log this error.
    }
} else {
    $error_message = "ID do jogo não especificado.";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $match ? htmlspecialchars($match['team_home'] . " vs " . $match['team_away']) : "Assistir Jogo"; ?> - Futebol Online</title>
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
            padding: 1em 0; /* Reduced padding slightly */
            text-align: center;
            border-bottom: 3px solid #00ff00; /* Green accent line */
        }
        header h1 {
            margin: 0;
            font-size: 2em; /* Reduced size slightly */
        }
        .container {
            max-width: 1200px; /* Max width for very large screens */
            width: 90%;
            margin: 20px auto;
            padding: 20px;
            background-color: #2c2c2c;
            border: 1px solid #00ff00; /* Green border for container */
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 255, 0, 0.1); /* Subtle green glow */
        }
        .match-details h2 {
            color: #00ff00; /* Green for match title */
            font-size: 1.8em;
            text-align: center;
            margin-bottom: 10px;
        }
        .match-details .match-time, .match-details .match-description {
            text-align: center;
            color: #c0c0c0;
            margin-bottom: 15px;
        }
        .player-container {
            width: 100%;
            max-width: 800px; /* Max width for the player */
            margin: 20px auto; /* Center the player */
            border: 2px solid #00ff00; /* Green border for player */
            border-radius: 5px;
            overflow: hidden; /* Ensures iframe fits rounded borders */
        }
        .player-container iframe {
            display: block; /* Removes extra space below iframe */
            width: 100%;
            height: 450px; /* Standard 16:9 aspect ratio height for width 800px */
            border: none;
            background-color: #000; /* Black background for iframe area */
        }
        .stream-options {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
        }
        .stream-options h3 {
            color: #00ff00;
            margin-bottom: 10px;
        }
        .stream-options ul {
            list-style: none;
            padding: 0;
            display: flex; /* Arrange stream options in a row */
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
            justify-content: center; /* Center the items */
        }
        .stream-options li {
            margin: 5px;
        }
        .stream-button {
            background-color: #005c00; /* Darker green for buttons */
            color: #ffffff;
            border: 1px solid #00ff00; /* Green border */
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
            font-weight: bold;
        }
        .stream-button:hover, .stream-button.active {
            background-color: #00ff00; /* Bright green on hover/active */
            color: #0d0d0d; /* Dark text for contrast on active */
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            background-color: #005c00;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            border: 1px solid #00ff00;
        }
        .back-link:hover {
            background-color: #00ff00;
            color: #0d0d0d;
        }
        .error-message, .info-message {
            text-align: center;
            font-size: 1.2em;
            color: #ffcc00; /* Yellow for notices/errors */
            padding: 20px;
            background-color: #222;
            border: 1px solid #ffcc00;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <header>
        <h1><?php echo $match ? htmlspecialchars($match['team_home'] . " vs " . $match['team_away']) : "Assistir Jogo"; ?></h1>
    </header>

    <div class="container">
        <?php if (!empty($error_message)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php elseif ($match): ?>
            <div class="match-details">
                <h2><?php echo htmlspecialchars($match['team_home']); ?> vs <?php echo htmlspecialchars($match['team_away']); ?></h2>
                <p class="match-time">
                    Horário: <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($match['match_time']))); ?>
                </p>
                <?php if (!empty($match['description'])): ?>
                    <p class="match-description"><?php echo nl2br(htmlspecialchars($match['description'])); ?></p>
                <?php endif; ?>
            </div>

            <div class="player-container">
                <iframe id="streamPlayer" src="about:blank" allowfullscreen></iframe>
                <!-- Consider adding sandbox attributes to iframe for security if content is untrusted -->
            </div>

            <?php if (!empty($streams)): ?>
                <div class="stream-options">
                    <h3>Opções de Transmissão:</h3>
                    <ul>
                        <?php foreach ($streams as $index => $stream): ?>
                            <li>
                                <button class="stream-button" data-stream-url="<?php echo htmlspecialchars($stream['stream_url']); ?>">
                                    <?php echo htmlspecialchars($stream['stream_label']); ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <p class="info-message">Nenhuma opção de stream disponível para este jogo no momento.</p>
            <?php endif; ?>

        <?php else: ?>
            <!-- This case should ideally be caught by $error_message already -->
            <p class="error-message">Detalhes do jogo não puderam ser carregados.</p>
        <?php endif; ?>

        <a href="index.php" class="back-link">Voltar para a Lista de Jogos</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const player = document.getElementById('streamPlayer');
            const streamButtons = document.querySelectorAll('.stream-button');

            streamButtons.forEach(function(button, index) {
                button.addEventListener('click', function() {
                    const streamUrl = this.getAttribute('data-stream-url');
                    if (player && streamUrl) {
                        player.src = streamUrl;
                    }
                    // Optional: highlight active button
                    streamButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                });

                // Automatically load the first stream if available
                if (index === 0 && player) {
                    const firstStreamUrl = button.getAttribute('data-stream-url');
                    if (firstStreamUrl) {
                        player.src = firstStreamUrl;
                        button.classList.add('active');
                    }
                }
            });

            // If no streams are available and player exists, maybe set a default message in iframe
            if (streamButtons.length === 0 && player) {
                // This could be a local HTML page or a simple message
                // player.src = 'no_streams_available.html';
                // For now, about:blank is fine, or handled by the PHP message.
            }
        });
    </script>
</body>
</html>
