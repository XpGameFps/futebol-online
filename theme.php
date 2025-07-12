<?php
// Gera CSS dinâmico com base nas cores do banco de dados
header('Content-Type: text/css; charset=UTF-8');
require_once __DIR__ . '/config.php';

$default_colors = [
    'theme_primary_color' => '#00ff00',
    'theme_secondary_color' => '#0d0d0d', 
    'theme_bg_color' => '#1a1a1a',
    'theme_text_color' => '#e0e0e0',
];
$colors = $default_colors;

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('theme_primary_color','theme_secondary_color','theme_bg_color','theme_text_color')");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['setting_value'])) {
                $colors[$row['setting_key']] = $row['setting_value'];
            }
        }
    } catch (PDOException $e) {}
}
?>
:root {
    --primary-color: <?php echo $colors['theme_primary_color']; ?>;
    --secondary-color: <?php echo $colors['theme_secondary_color']; ?>;
    --bg-color: <?php echo $colors['theme_bg_color']; ?>;
    --text-color: <?php echo $colors['theme_text_color']; ?>;
}

/* === ESTRUTURA GERAL === */
body {
    background-color: var(--bg-color) !important;
    color: var(--text-color) !important;
}

/* === HEADER === */
.site-header {
    background-color: var(--secondary-color) !important;
    border-bottom-color: var(--primary-color) !important;
    color: var(--text-color) !important;
}

.logo-area .logo-text {
    color: var(--text-color) !important;
}
.logo-area .logo-accent {
    color: var(--primary-color) !important;
}

.main-navigation a {
    color: var(--text-color) !important;
}
.main-navigation a:hover, .main-navigation a.active {
    color: var(--secondary-color) !important;
    background-color: var(--primary-color) !important;
}

.search-area input[type="search"] {
    border-color: var(--primary-color) !important;
    background-color: var(--secondary-color) !important;
    color: var(--text-color) !important;
}
.search-area button[type="submit"] {
    background-color: var(--primary-color) !important;
    color: var(--secondary-color) !important;
    border-color: var(--primary-color) !important;
}

.leagues-menu-button {
    color: var(--primary-color) !important;
}
.leagues-dropdown-content {
    background-color: var(--bg-color) !important;
    border-color: var(--primary-color) !important;
}
.leagues-dropdown-content li a {
    color: var(--text-color) !important;
}
.leagues-dropdown-content li a:hover {
    background-color: var(--primary-color) !important;
    color: var(--secondary-color) !important;
}

.admin-panel-link {
    background-color: var(--primary-color) !important;
    color: var(--secondary-color) !important;
}

/* === TÍTULOS === */
.page-title, .section-title {
    color: var(--primary-color) !important;
}

/* === CANAIS DE TV === */
.tv-channels-slider {
    background-color: var(--secondary-color) !important;
    border-top-color: var(--primary-color) !important;
    border-bottom-color: var(--primary-color) !important;
}

.channel-item {
    background-color: var(--secondary-color) !important;
    border-color: var(--primary-color) !important;
    color: var(--text-color) !important;
}

/* === FOOTER === */
.site-footer-main {
    background: var(--secondary-color) !important;
    color: var(--text-color) !important;
}

.footer-social a {
    transition: opacity 0.3s;
}
.footer-social a:hover {
    opacity: 0.7;
}

/* === BOTÕES GERAIS === */
.button, button, input[type=submit] {
    background: var(--primary-color) !important;
    color: var(--secondary-color) !important;
    border: 1px solid var(--primary-color) !important;
}
.button:hover, button:hover, input[type=submit]:hover {
    background: var(--text-color) !important;
    color: var(--bg-color) !important;
}

/* === LINKS === */
a {
    color: var(--primary-color) !important;
}
a:hover {
    opacity: 0.8;
}
