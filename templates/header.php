<?php
// templates/header.php

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- BEGIN Activity Tracking ---
// Ensure $pdo is available (it should be, as including pages load config.php first)
// And ensure this tracking only happens for non-admin users if desired,
// or for all users accessing frontend pages. For now, track all frontend.
// Also, ensure session_id() returns a non-empty string.
if (isset($pdo) && function_exists('session_id') && session_id()) {
    try {
        $current_session_id = session_id();

        // Using NOW() directly in the query is fine for MySQL's current time.
        // The table's `last_activity` also has ON UPDATE CURRENT_TIMESTAMP,
        // so a simpler ON DUPLICATE KEY UPDATE session_id = session_id would also work.
        // But being explicit with last_activity = NOW() is clearer.
        $sql_track_activity = "INSERT INTO active_sessions (session_id, last_activity)
                               VALUES (:session_id, NOW())
                               ON DUPLICATE KEY UPDATE last_activity = NOW()";

        $stmt_track_activity = $pdo->prepare($sql_track_activity);
        $stmt_track_activity->bindParam(':session_id', $current_session_id, PDO::PARAM_STR);
        $stmt_track_activity->execute();

    } catch (PDOException $e) {
        // Log this error server-side, don't break page for user
        error_log("Error tracking session activity: " . $e->getMessage());
    }
}
// --- END Activity Tracking ---


// Assumes $header_leagues variable is passed or fetched by including script
if (!isset($header_leagues)) {
    $header_leagues = []; // Default if not provided by including page
}
$direct_nav_leagues = array_slice($header_leagues, 0, 3);
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <?php /* Dynamic title is set in each page like index.php, match.php etc. before including this header */ ?>
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <div class="logo-area">
                <a href="index.php" class="logo-text">Fut<span class="logo-accent">Online</span></a>
            </div>
            <nav class="main-navigation">
                <ul>
                    <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && !isset($_GET['league_id']) && !isset($_GET['query']) ? 'active' : ''; ?>">Início</a></li>
                    <?php foreach ($direct_nav_leagues as $league): ?>
                        <li class="league-nav-link">
                            <a href="index.php?league_id=<?php echo htmlspecialchars($league['id']); ?>"
                               class="<?php echo isset($_GET['league_id']) && $_GET['league_id'] == $league['id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($league['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <div class="header-right-controls">
                <div class="search-area">
                    <form action="search.php" method="GET" class="search-form">
                        <input type="search" name="query" placeholder="Buscar jogos..." aria-label="Buscar">
                        <button type="submit">Buscar</button>
                    </form>
                </div>

                <?php if (!empty($header_leagues)): ?>
                <div class="leagues-menu">
                    <button id="leagues-menu-toggle" class="leagues-menu-button" aria-expanded="false" aria-controls="leagues-dropdown" title="Mais Ligas">
                        &#x22EE;
                    </button>
                    <ul id="leagues-dropdown" class="leagues-dropdown-content">
                        <?php foreach ($header_leagues as $league): ?>
                            <li>
                                <a href="index.php?league_id=<?php echo htmlspecialchars($league['id']); ?>">
                                    <?php echo htmlspecialchars($league['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true): ?>
                    <a href="admin/index.php" class="admin-panel-link" title="Painel Admin">Painel</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <?php // The inline <script> for dropdown is kept here for now ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const leaguesMenuButton = document.getElementById('leagues-menu-toggle');
        const leaguesDropdown = document.getElementById('leagues-dropdown');

        if (leaguesMenuButton && leaguesDropdown) {
            leaguesMenuButton.addEventListener('click', function(event) {
                event.stopPropagation();
                const isExpanded = leaguesMenuButton.getAttribute('aria-expanded') === 'true';
                leaguesMenuButton.setAttribute('aria-expanded', !isExpanded);
                leaguesDropdown.classList.toggle('show');
            });
            document.addEventListener('click', function(event) {
                if (leaguesDropdown.classList.contains('show') && !leaguesMenuButton.contains(event.target) && !leaguesDropdown.contains(event.target)) {
                    leaguesDropdown.classList.remove('show');
                    leaguesMenuButton.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
    </script>
