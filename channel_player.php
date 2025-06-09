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
    <!-- Re-use styles from match.php / index.php. Ideally, a shared CSS file. -->
    <style>
        /* Basic Reset & Sticky Footer (from previous steps) */
        * { box-sizing: border-box; }
        html { height: 100%; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #1a1a1a;
            color: #e0e0e0;
            display: flex;
            flex-direction: column;
            min-height: 100%;
        }
        .main-content {
            flex-grow: 1;
        }

        /* Header Styles (condensed from previous steps - assume these are complete and correct) */
        .site-header { background-color: #0d0d0d; padding: 10px 0; border-bottom: 3px solid #00ff00; color: #e0e0e0; }
        .header-container { max-width: 1200px; width: 90%; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo-area .logo-text { font-size: 2.2em; font-weight: bold; color: #fff; text-decoration: none; }
        .logo-area .logo-accent { color: #00ff00; }
        .main-navigation { flex-grow: 1; }
        .main-navigation ul { list-style: none; margin: 0; padding: 0; display: flex; margin-left: 10px; /* Reduced from 20px */ }
        .main-navigation li { margin-left: 10px; /* Reduced from 20px */ }
        .main-navigation a { text-decoration: none; color: #e0e0e0; font-weight: bold; padding: 5px 10px; border-radius: 4px; transition: background-color 0.3s, color 0.3s; }
        .main-navigation a:hover, .main-navigation a.active { color: #0d0d0d; background-color: #00ff00; }
        .main-navigation .league-nav-link { /* For hiding direct league links on small screens */ }
        .header-right-controls { display: flex; align-items: center; }
        .search-area { margin-right: 15px; }
        .search-area .search-form { display: flex; align-items: center; }
        .search-area input[type="search"] { padding: 8px 12px; border: 1px solid #00ff00; background-color: #2c2c2c; color: #e0e0e0; border-radius: 4px 0 0 4px; font-size: 0.85em; min-width: 120px; }
        .search-area input[type="search"]::placeholder { color: #888; }
        .search-area button[type="submit"] { padding: 8px 10px; background-color: #00ff00; color: #0d0d0d; border: 1px solid #00ff00; border-left: none; cursor: pointer; font-weight: bold; border-radius: 0 4px 4px 0; font-size: 0.85em; transition: background-color 0.3s; }
        .search-area button[type="submit"]:hover { background-color: #00cc00; }
        .leagues-menu { position: relative; }
        .leagues-menu-button { background: none; border: none; color: #00ff00; font-size: 1.6em; cursor: pointer; padding: 5px; line-height: 1; }
        .leagues-menu-button:hover { opacity: 0.8; }
        .leagues-dropdown-content { display: none; position: absolute; top: 100%; right: 0; background-color: #1a1a1a; border: 1px solid #00ff00; border-radius: 0 0 4px 4px; min-width: 200px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.3); z-index: 100; list-style: none; padding: 0; margin: 0; }
        .leagues-dropdown-content.show { display: block; }
        .leagues-dropdown-content li a { color: #e0e0e0; padding: 10px 15px; text-decoration: none; display: block; font-size: 0.95em; white-space: nowrap; }
        .leagues-dropdown-content li a:hover { background-color: #00ff00; color: #0d0d0d; }
        .admin-panel-link { display: inline-block; margin-left: 15px; padding: 5px 8px; background-color: #00b300; color: #ffffff; text-decoration: none; font-weight: bold; border-radius: 4px; font-size: 0.8em; transition: background-color 0.3s; }
        .admin-panel-link:hover { background-color: #009900; }

        /* Page Specific Styles for channel_player.php (similar to match.php) */
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #2c2c2c;
            border: 1px solid #00ff00;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 255, 0, 0.1);
        }
        .page-title-player { /* Specific title for player pages */
            color: #00ff00; /* Green for title */
            font-size: 2em;
            text-align: center;
            margin-bottom: 20px;
        }
        .player-wrapper { /* New wrapper for player and potential related content */
             /* Can add specific styling if needed, for now, just structure */
        }
        .player-container { /* Re-used from match.php */
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            border: 2px solid #00ff00;
            border-radius: 5px;
            overflow: hidden;
            background-color: #000;
        }
        .player-container iframe {
            display: block;
            width: 100%;
            border: none;
            aspect-ratio: 16 / 9;
        }
        .back-link-container { text-align: center; margin-top: 20px; }
        .back-link {
            display: inline-block;
            padding: 10px 20px;
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
        .error-message, .info-message { /* Re-used from match.php */
            text-align: center;
            font-size: 1.2em;
            color: #ffcc00;
            padding: 20px;
            background-color: #222;
            border: 1px solid #ffcc00;
            border-radius: 5px;
        }

        /* Responsive Header Adjustments (condensed) */
        @media (max-width: 767px) {
            .main-navigation .league-nav-link { display: none; }
            .logo-area .logo-text { font-size: 1.8em; }
            .search-area input[type="search"] { min-width: 120px; font-size: 0.85em; padding: 7px 10px; }
            .search-area button[type="submit"] { font-size: 0.85em; padding: 7px 10px; }
            .leagues-menu-button { font-size: 1.6em; }
            .admin-panel-link { font-size: 0.8em; padding: 5px 8px; }
            .header-container { width: 95%; }
            .main-navigation ul { margin-left: 10px; }
            .main-navigation li { margin-left: 10px; }
        }
        @media (max-width: 480px) {
            .logo-area .logo-text { font-size: 1.6em; }
            .search-area input[type="search"] { min-width: 80px; max-width: 120px; }
            .main-navigation { flex-grow: 0; }
        }
        /* Footer Styles (condensed) */
        .site-footer-main { background-color: #0d0d0d; color: #a0a0a0; padding: 20px 0; text-align: center; border-top: 2px solid #00ff00; font-size: 0.9em; margin-top: 30px; }
        .footer-container { max-width: 1200px; width: 90%; margin: 0 auto; }
        .cookie-consent-banner { display: none; position: fixed; bottom: 0; left: 0; width: 100%; background-color: rgba(10,10,10,0.95); color: #e0e0e0; padding: 15px 20px; z-index: 1000; text-align: center; border-top: 1px solid #00ff00; box-shadow: 0 -2px 10px rgba(0,0,0,0.5); }
        .cookie-consent-banner p { margin: 0 0 10px 0; font-size: 0.9em; display: inline; }
        .cookie-consent-banner a { color: #00ff00; text-decoration: underline; }
        #acceptCookieConsent { background-color: #00ff00; color: #0d0d0d; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-left: 15px; transition: background-color 0.3s; }
        #acceptCookieConsent:hover { background-color: #00cc00; }

    </style>
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
