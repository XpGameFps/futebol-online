<?php
require_once 'config.php'; // Database connection
define('FRONTEND_CHANNELS_LOGO_BASE_PATH', 'uploads/logos/channels/'); // Not used on this page directly, but good for consistency if needed later
define('FRONTEND_MATCH_COVER_BASE_PATH', 'uploads/covers/matches/'); // Not used here

// Fetch Leagues for Header
$header_leagues = [];
if (isset($pdo)) {
    try {
        $stmt_header_leagues = $pdo->query("SELECT id, name FROM leagues ORDER BY name ASC");
        $header_leagues = $stmt_header_leagues->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Silently fail for header leagues or log
    }
}

$channel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$channel = null;
$error_message = '';
$page_specific_title = "Assistir Canal"; // Default title

if ($channel_id > 0) {
    try {
        $stmt_channel = $pdo->prepare("SELECT id, name, stream_url, logo_filename FROM tv_channels WHERE id = :channel_id");
        $stmt_channel->bindParam(':channel_id', $channel_id, PDO::PARAM_INT);
        $stmt_channel->execute();
        $channel = $stmt_channel->fetch(PDO::FETCH_ASSOC);

        if ($channel) {
            $page_specific_title = "Assistir: " . htmlspecialchars($channel['name']);
        } else {
            $error_message = "Canal não encontrado.";
            $page_specific_title = "Canal não encontrado";
        }
    } catch (PDOException $e) {
        $error_message = "Erro ao buscar detalhes do canal: " . $e->getMessage();
        $page_specific_title = "Erro ao buscar canal";
    }
} else {
    $error_message = "ID do canal não especificado.";
    $page_specific_title = "ID do canal inválido";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_specific_title; ?> - FutOnline</title>
    <?php // Style block removed, will be linked from header.php ?>
</head>
<body>
    <?php require_once 'templates/header.php'; // $header_leagues is available here ?>

    <main class="main-content">
        <div class="container">
            <?php if (!empty($error_message)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php elseif ($channel): ?>
                <h1 class="page-title-player"><?php echo htmlspecialchars($channel['name']); ?></h1>

                <div class="player-wrapper">
                    <div class="player-container">
                        <?php if (!empty($channel['stream_url'])): ?>
                            <iframe id="channelStreamPlayer"
                                    src="<?php echo htmlspecialchars($channel['stream_url']); ?>"
                                    allowfullscreen
                                    allow="autoplay; encrypted-media">
                                    <?php // Consider sandbox attributes if streams are from less trusted sources ?>
                            </iframe>
                        <?php else: ?>
                            <p class="info-message">URL do stream não disponível para este canal.</p>
                        <?php endif; ?>
                    </div>
                    <?php // Potential future spot for chat or related channel info ?>
                </div>
            <?php else: ?>
                <!-- This case should be caught by $error_message, but as a fallback -->
                <p class="error-message">Não foi possível carregar os detalhes do canal.</p>
            <?php endif; ?>

            <div class="back-link-container">
                <a href="index.php" class="back-link">Voltar para a Homepage</a>
            </div>
        </div>
    </main>

    <?php require_once 'templates/footer.php'; ?>
</body>
</html>
