<?php
// templates/header.php

// Start session if not already started, to check for admin login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Assumes $header_leagues variable is passed or fetched by including script
// If $header_leagues is not set, initialize to empty array
if (!isset($header_leagues)) {
    $header_leagues = [];
}

// Determine a few leagues to show directly in the nav, if available
$direct_nav_leagues = array_slice($header_leagues, 0, 3); // Get up to the first 3 leagues
?>
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

                <?php // Add placeholders if less than 3 leagues were taken for direct nav
                $placeholders_needed = 3 - count($direct_nav_leagues);
                if (count($direct_nav_leagues) == 0 && $placeholders_needed > 0) {
                    // This is a placeholder, real link to a leagues page could be here
                    // For now, it can also trigger the dropdown if all leagues are there.
                    // Or simply be a non-functional placeholder if the dropdown handles all leagues.
                    // Given the dropdown exists for "more", let's ensure at least a few links or placeholders.
                }
                // Example placeholders if needed:
                // for ($i = 0; $i < $placeholders_needed; $i++) {
                //     // echo '<li><a href="#">Placeholder ' . ($i+1) . '</a></li>';
                // }
                ?>
            </ul>
        </nav>
        <div class="header-right-controls">
            <div class="search-area">
                <form action="search.php" method="GET" class="search-form">
                    <input type="search" name="query" placeholder="Buscar jogos..." aria-label="Buscar">
                    <button type="submit">Buscar</button>
                </form>
            </div>

            <?php if (!empty($header_leagues)): // Show dropdown if there are any leagues ?>
            <div class="leagues-menu">
                <button id="leagues-menu-toggle" class="leagues-menu-button" aria-expanded="false" aria-controls="leagues-dropdown" title="Mais Ligas">
                    &#x22EE;
                </button>
                <ul id="leagues-dropdown" class="leagues-dropdown-content">
                    <?php foreach ($header_leagues as $league): // List ALL leagues in dropdown ?>
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
<!-- Existing JavaScript for leagues dropdown -->
<script>
// ... (JavaScript for leagues dropdown remains the same) ...
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
