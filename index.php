<?php
// index.php
require_once 'config.php'; // Database connection
// Define base paths (existing)
define('FRONTEND_CHANNELS_LOGO_BASE_PATH', 'uploads/logos/channels/');
define('FRONTEND_MATCH_COVER_BASE_PATH', 'uploads/covers/matches/');

// Fetch Leagues for Header (existing)
$header_leagues = [];
if (isset($pdo)) {
    try {
        $stmt_header_leagues = $pdo->query("SELECT id, name FROM leagues ORDER BY name ASC");
        $header_leagues = $stmt_header_leagues->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { /* Silently fail or log */ }
}

// Fetch TV Channels (existing)
$tv_channels = [];
try {
    $stmt_channels = $pdo->query("SELECT id, name, logo_filename, stream_url FROM tv_channels ORDER BY sort_order ASC, name ASC LIMIT 16");
    $tv_channels = $stmt_channels->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* Silently fail or log */ }


// --- Match Fetching Logic with League Filter ---
$matches = [];
$error_message = '';
$page_main_title = "Jogos de Hoje"; // Default title
$selected_league_id = null;
$selected_league_name = null;

if (isset($_GET['league_id']) && filter_var($_GET['league_id'], FILTER_VALIDATE_INT)) {
    $selected_league_id = (int)$_GET['league_id'];

    // Fetch the selected league's name
    try {
        $stmt_league_name = $pdo->prepare("SELECT name FROM leagues WHERE id = :league_id");
        $stmt_league_name->bindParam(':league_id', $selected_league_id, PDO::PARAM_INT);
        $stmt_league_name->execute();
        $league_info = $stmt_league_name->fetch(PDO::FETCH_ASSOC);
        if ($league_info) {
            $selected_league_name = $league_info['name'];
            $page_main_title = "Jogos da Liga: " . htmlspecialchars($selected_league_name);
        } else {
            $error_message = "Liga não encontrada.";
            $selected_league_id = null; // Invalidate if league not found
        }
    } catch (PDOException $e) {
        $error_message = "Erro ao buscar nome da liga: " . $e->getMessage();
        $selected_league_id = null; // Invalidate on error
    }
}

try {
    $current_time_sql = "NOW()";
    // Ensure aliases are used consistently if table names are ambiguous (m for matches, l for leagues)
    $sql_matches = "SELECT m.id, m.team_home, m.team_away, m.match_time, m.description, m.league_id, m.cover_image_filename, l.name as league_name
                    FROM matches m
                    LEFT JOIN leagues l ON m.league_id = l.id
                    WHERE m.match_time >= {$current_time_sql}";

    if ($selected_league_id !== null) {
        $sql_matches .= " AND m.league_id = :selected_league_id";
    }

    $sql_matches .= " ORDER BY m.match_time ASC LIMIT 30";

    $stmt_matches = $pdo->prepare($sql_matches);

    if ($selected_league_id !== null) {
        $stmt_matches->bindParam(':selected_league_id', $selected_league_id, PDO::PARAM_INT);
    }

    $stmt_matches->execute();
    $matches = $stmt_matches->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    if(empty($error_message)) {
        $error_message = "Erro ao buscar jogos: " . $e->getMessage();
    }
}
// --- End Match Fetching Logic ---

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $selected_league_name ? htmlspecialchars($selected_league_name) . " - " : ""; ?>Jogos de Futebol - FutOnline</title>
    <style>
        /* Sticky Footer Styles */
        html {
            height: 100%;
        }
        body {
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex-grow: 1;
        }

        * { box-sizing: border-box; }

        /* New Header Styles - Common for index.php & match.php */
        .site-header {
            background-color: #0d0d0d;
            padding: 10px 0;
            border-bottom: 3px solid #00ff00;
            color: #e0e0e0;
        }
        .header-container {
            max-width: 1200px;
            width: 90%;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-area .logo-text {
            font-size: 2.2em;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
        }
        .logo-area .logo-accent {
            color: #00ff00;
        }
        .main-navigation {
            flex-grow: 1;
        }
        .main-navigation ul {
             list-style: none; margin: 0; padding: 0; display: flex; margin-left: 10px;
        }
        .main-navigation li {
            margin-left: 10px;
        }
        .main-navigation a {
            text-decoration: none;
            color: #e0e0e0;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s, color 0.3s;
        }
        .main-navigation a:hover, .main-navigation a.active {
            color: #0d0d0d;
            background-color: #00ff00;
        }
        .main-navigation .league-nav-link {
             /* display: none; by default, responsive CSS will handle this */
        }
        .header-right-controls {
            display: flex;
            align-items: center;
        }
        .search-area {
            margin-right: 15px;
        }
        .search-area .search-form {
            display: flex;
            align-items: center;
        }
        .search-area input[type="search"] {
            padding: 8px 12px;
            border: 1px solid #00ff00;
            background-color: #2c2c2c;
            color: #e0e0e0;
            border-radius: 4px 0 0 4px;
            font-size: 0.85em;
            min-width: 120px;
        }
        .search-area input[type="search"]::placeholder {
            color: #888;
        }
        .search-area button[type="submit"] {
            padding: 8px 10px; /* Adjusted to match input padding better */
            background-color: #00ff00;
            color: #0d0d0d;
            border: 1px solid #00ff00;
            border-left: none;
            cursor: pointer;
            font-weight: bold;
            border-radius: 0 4px 4px 0;
            font-size: 0.85em;
            transition: background-color 0.3s;
        }
        .search-area button[type="submit"]:hover {
            background-color: #00cc00;
        }
        .leagues-menu {
            position: relative;
        }
        .leagues-menu-button {
            background: none;
            border: none;
            color: #00ff00;
            font-size: 1.6em; /* Adjusted from responsive */
            cursor: pointer;
            padding: 5px;
            line-height: 1;
        }
        .leagues-menu-button:hover {
            opacity: 0.8;
        }
        .leagues-dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: #1a1a1a;
            border: 1px solid #00ff00;
            border-radius: 0 0 4px 4px;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.3);
            z-index: 100;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .leagues-dropdown-content.show {
            display: block;
        }
        .leagues-dropdown-content li a {
            color: #e0e0e0;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            font-size: 0.95em;
            white-space: nowrap;
        }
        .leagues-dropdown-content li a:hover {
            background-color: #00ff00;
            color: #0d0d0d;
        }
        .admin-panel-link {
            display: inline-block;
            margin-left: 15px;
            padding: 5px 8px; /* Adjusted from responsive */
            background-color: #00b300;
            color: #ffffff;
            text-decoration: none;
            font-weight: bold;
            border-radius: 4px;
            font-size: 0.8em; /* Adjusted from responsive */
            transition: background-color 0.3s;
        }
        .admin-panel-link:hover {
            background-color: #009900;
        }
        .page-title {
            color: #00ff00;
            text-align: center;
            font-size: 2.5em;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .league-title-display { /* Added this style */
            color: #00ff00;
            text-align: center;
            font-size: 1.8em;
            margin-top: 15px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        /* TV Channels Slider/Grid Styles */
        .tv-channels-slider {
            background-color: #111;
            padding: 20px 0;
            margin-bottom: 30px;
            border-top: 2px solid #00ff00;
            border-bottom: 2px solid #00ff00;
        }
        .section-title {
            color: #00ff00;
            text-align: center;
            font-size: 2em;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .channels-grid {
            max-width: 1200px;
            width: 90%;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
        }
        .channel-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #2c2c2c;
            border: 1px solid #008000;
            border-radius: 8px;
            padding: 10px;
            text-decoration: none;
            color: #e0e0e0;
            transition: transform 0.2s ease, border-color 0.2s ease;
            height: 100px;
            overflow: hidden;
        }
        .channel-item:hover {
            transform: translateY(-3px);
            border-color: #00ff00;
        }
        .channel-logo {
            max-height: 50px;
            max-width: 100%;
            margin-bottom: 8px;
            object-fit: contain;
        }
        .channel-name {
            font-size: 0.9em;
            text-align: center;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }
        .channel-name-placeholder {
            font-size: 1.1em;
            font-weight: bold;
            text-align: center;
            margin-bottom: 8px;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        /* header element styles are now part of .site-header */
        .container {
            max-width: 1200px;
            width: 90%;
            margin: 20px auto;
            overflow: hidden;
            padding: 20px;
        }
        /* Match Listing Grid Styles */
        .match-list {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .match-list-item {
            background-color: transparent;
            border: none;
            box-shadow: none;
        }
        .match-card-link {
            display: flex;
            flex-direction: column;
            background-color: #2c2c2c;
            border: 1px solid #008000;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 255, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            text-decoration: none;
            color: inherit;
            overflow: hidden;
            height: 100%;
        }
        .match-card-link:hover {
            transform: translateY(-4px);
            box-shadow: 0 7px 14px rgba(0, 255, 0, 0.2);
            border-color: #00ff00;
        }
        .match-cover-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }
        .match-cover-image-placeholder {
            width: 100%;
            height: 160px;
            background-color: #3a3a3a;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
        }
        .match-item-content {
            padding: 15px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            justify-content: space-between;
        }
        .match-title {
            color: #00dd00;
            font-size: 1.2em;
            font-weight: bold;
            margin: 0 0 8px 0;
        }
        .match-card-link:hover .match-title {
            text-decoration: underline;
            color: #00ff00;
        }
        .match-time {
            font-size: 0.85em;
            color: #a0a0a0;
            margin-bottom: 8px;
        }
        .match-description {
            font-size: 0.9em;
            color: #c0c0c0;
            line-height: 1.4;
            flex-grow: 1;
        }
        /* Responsive adjustments for match list */
        @media (max-width: 992px) {
            .match-list {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            .match-title {
                font-size: 1.3em;
            }
            .match-cover-image, .match-cover-image-placeholder {
                height: 140px;
            }
        }
        @media (max-width: 576px) {
            .match-list {
                grid-template-columns: 1fr;
            }
            .match-title {
                font-size: 1.4em;
            }
            .match-cover-image, .match-cover-image-placeholder {
                height: 180px;
            }
        }
        .no-matches, .error-message {
            text-align: center;
            font-size: 1.2em;
            color: #ffcc00;
            padding: 20px;
            background-color: #2c2c2c;
            border: 1px solid #ffcc00;
            border-radius: 5px;
        }

        /* Basic Footer Styles */
        .site-footer-main {
            background-color: #0d0d0d;
            color: #a0a0a0;
            padding: 20px 0;
            text-align: center;
            border-top: 2px solid #00ff00;
            font-size: 0.9em;
            margin-top: 30px;
        }
        .footer-container {
            max-width: 1200px;
            width: 90%;
            margin: 0 auto;
        }

        /* Cookie Consent Banner Styles */
        .cookie-consent-banner {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: rgba(10, 10, 10, 0.95);
            color: #e0e0e0;
            padding: 15px 20px;
            z-index: 1000;
            text-align: center;
            border-top: 1px solid #00ff00;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.5);
        }
        .cookie-consent-banner p {
            margin: 0 0 10px 0;
            font-size: 0.9em;
            display: inline;
        }
        .cookie-consent-banner a {
            color: #00ff00;
            text-decoration: underline;
        }
        #acceptCookieConsent {
            background-color: #00ff00;
            color: #0d0d0d;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-left: 15px;
            transition: background-color 0.3s;
        }
        #acceptCookieConsent:hover {
            background-color: #00cc00;
        }

        /* Header Responsiveness Adjustments */
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
    </style>
</head>
<body>
    <?php require_once 'templates/header.php'; // $header_leagues is available ?>
    <main class="main-content">
        <?php if (!empty($tv_channels)): ?>
        <section class="tv-channels-slider">
            <div class="container">
                <h2 class="section-title">Canais de TV</h2>
                <div class="channels-grid">
                    <?php foreach ($tv_channels as $channel): ?>
                        <a href="channel_player.php?id=<?php echo htmlspecialchars($channel['id']); ?>" class="channel-item" title="Assistir <?php echo htmlspecialchars($channel['name']); ?>">
                            <?php if (!empty($channel['logo_filename'])): ?>
                                <img src="<?php echo FRONTEND_CHANNELS_LOGO_BASE_PATH . htmlspecialchars($channel['logo_filename']); ?>" alt="<?php echo htmlspecialchars($channel['name']); ?>" class="channel-logo">
                            <?php else: ?>
                                <span class="channel-name-placeholder"><?php echo htmlspecialchars($channel['name']); ?></span>
                            <?php endif; ?>
                            <span class="channel-name"><?php echo htmlspecialchars($channel['name']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <div class="container">
            <h1 class="page-title"><?php echo htmlspecialchars($page_main_title); ?></h1>

            <?php if (!empty($error_message) && empty($matches)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php elseif (empty($matches)): ?>
                <?php if ($selected_league_name): ?>
                    <p class="no-matches">Nenhum jogo futuro encontrado para "<strong><?php echo htmlspecialchars($selected_league_name); ?></strong>".</p>
                <?php else: ?>
                    <p class="no-matches">Nenhum jogo programado no momento. Volte mais tarde!</p>
                <?php endif; ?>
            <?php else: ?>
                <ul class="match-list">
                    <?php foreach ($matches as $match): ?>
                        <li class="match-list-item">
                            <a class="match-card-link" href="match.php?id=<?php echo htmlspecialchars($match['id']); ?>">
                                <?php if (!empty($match['cover_image_filename'])): ?>
                                    <img src="<?php echo FRONTEND_MATCH_COVER_BASE_PATH . htmlspecialchars($match['cover_image_filename']); ?>"
                                         alt="Capa para <?php echo htmlspecialchars($match['team_home']); ?> vs <?php echo htmlspecialchars($match['team_away']); ?>"
                                         class="match-cover-image">
                                <?php else: ?>
                                    <div class="match-cover-image-placeholder"></div>
                                <?php endif; ?>
                                <div class="match-item-content">
                                    <h3 class="match-title">
                                        <?php echo htmlspecialchars($match['team_home']); ?> vs <?php echo htmlspecialchars($match['team_away']); ?>
                                    </h3>
                                    <p class="match-time">
                                        Horário: <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($match['match_time']))); ?>
                                    </p>
                                    <?php if (!empty($match['description'])): ?>
                                        <p class="match-description"><?php echo nl2br(htmlspecialchars($match['description'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
    <?php require_once 'templates/footer.php'; ?>
</body>
</html>
