<?php
// Single match page - Display player and stream options
require_once 'config.php'; // Database connection

$match_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$match = null;
// Fetch Leagues for Header
$header_leagues = [];
if (isset($pdo)) { // Check if $pdo is set from config.php
    try {
        $stmt_header_leagues = $pdo->query("SELECT id, name FROM leagues ORDER BY name ASC");
        $header_leagues = $stmt_header_leagues->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Silently fail for header leagues, or log error
    }
}
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
    <?php // Style block removed, will be linked from header.php ?>
</head>
<body>
<?php require_once 'templates/header.php'; ?>
<main class="main-content">
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
</main>
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
<?php require_once 'templates/footer.php'; ?>
</body>
</html>
