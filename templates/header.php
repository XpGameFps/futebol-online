<?php
// templates/header.php
// Assumes $header_leagues variable is passed or fetched by including script
// For simplicity, if $header_leagues is not set, initialize to empty array
if (!isset($header_leagues)) {
    $header_leagues = [];
}
?>
<header class="site-header">
    <div class="header-container">
        <div class="logo-area">
            <a href="index.php" class="logo-text">Fut<span class="logo-accent">Online</span></a>
            <!-- Or an image: <a href="index.php"><img src="<?php //echo $base_url; ?>images/logo.png" alt="Logo"></a> -->
        </div>
        <nav class="main-navigation">
            <ul>
                <li><a href="index.php">Início</a></li>
                <?php // Placeholder for direct nav links if any, or remove if all leagues in dropdown ?>
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
                <button id="leagues-menu-toggle" class="leagues-menu-button" aria-expanded="false" aria-controls="leagues-dropdown">
                    &#x22EE; <!-- Vertical ellipsis (⋮) or use an SVG/FontIcon -->
                    <?php /* &#x2630; Hamburger icon (☰) */ ?>
                </button>
                <ul id="leagues-dropdown" class="leagues-dropdown-content">
                    <?php foreach ($header_leagues as $league): ?>
                        <li>
                            <a href="index.php?league_id=<?php echo htmlspecialchars($league['id']); ?>">
                                <?php // Add logo here later if desired: <img src="path_to_league_logo" alt=""> ?>
                                <?php echo htmlspecialchars($league['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</header>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const leaguesMenuButton = document.getElementById('leagues-menu-toggle');
    const leaguesDropdown = document.getElementById('leagues-dropdown');

    if (leaguesMenuButton && leaguesDropdown) {
        leaguesMenuButton.addEventListener('click', function(event) {
            event.stopPropagation(); // Prevent click from immediately closing if bubbling
            const isExpanded = leaguesMenuButton.getAttribute('aria-expanded') === 'true';
            leaguesMenuButton.setAttribute('aria-expanded', !isExpanded);
            leaguesDropdown.classList.toggle('show');
        });

        // Optional: Close dropdown if clicking outside of it
        document.addEventListener('click', function(event) {
            if (leaguesDropdown.classList.contains('show') && !leaguesMenuButton.contains(event.target) && !leaguesDropdown.contains(event.target)) {
                leaguesDropdown.classList.remove('show');
                leaguesMenuButton.setAttribute('aria-expanded', 'false');
            }
        });
    }
});
</script>
