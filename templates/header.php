<?php
// templates/header.php

// Set secure session cookie parameters for consistency
$cookie_params_frontend = [
    'lifetime' => 0, // Expires when browser closes
    'path' => '/', // Cookie available for the entire domain
    'domain' => '', // Let PHP handle the host effectively
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // Only send over HTTPS
    'httponly' => true, // Prevent JavaScript access to the session cookie
    'samesite' => 'Lax' // CSRF protection measure
];
// For PHP versions < 7.3, session_set_cookie_params must be called differently
if (PHP_VERSION_ID < 70300) {
    session_set_cookie_params(
        $cookie_params_frontend['lifetime'],
        $cookie_params_frontend['path'],
        $cookie_params_frontend['domain'],
        $cookie_params_frontend['secure'],
        $cookie_params_frontend['httponly']
    );
} else {
    session_set_cookie_params($cookie_params_frontend);
}

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

// --- BEGIN Max Concurrent Users Tracking ---
define('USER_ACTIVE_INTERVAL_MINUTES', 5);

$current_online_users = 0;
$max_concurrent_users_ever = 0;

if (isset($pdo)) {
    // Fetch Current Online Users
    try {
        $sql_current_online = "SELECT COUNT(*) as online_count FROM active_sessions WHERE last_activity >= DATE_SUB(NOW(), INTERVAL " . USER_ACTIVE_INTERVAL_MINUTES . " MINUTE)";
        $stmt_current_online = $pdo->query($sql_current_online);
        if ($stmt_current_online) {
            $result = $stmt_current_online->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $current_online_users = (int)$result['online_count'];
            }
        }
    } catch (PDOException $e) {
        error_log("Error fetching current online users: " . $e->getMessage());
    }

    // Fetch Max Concurrent Users from site_settings
    try {
        $sql_max_concurrent = "SELECT setting_value FROM site_settings WHERE setting_key = 'max_concurrent_users'";
        $stmt_max_concurrent = $pdo->query($sql_max_concurrent);
        if ($stmt_max_concurrent) {
            $result_max = $stmt_max_concurrent->fetch(PDO::FETCH_ASSOC);
            if ($result_max && $result_max['setting_value'] !== null) {
                $max_concurrent_users_ever = (int)$result_max['setting_value'];
            }
        }
    } catch (PDOException $e) {
        error_log("Error fetching max concurrent users from settings: " . $e->getMessage());
    }

    // Compare and Update Max Concurrent Users if current is higher
    if ($current_online_users > $max_concurrent_users_ever) {
        $max_concurrent_users_ever = $current_online_users; // Update PHP variable immediately

        try {
            $sql_update_max = "INSERT INTO site_settings (setting_key, setting_value)
                               VALUES ('max_concurrent_users', :max_users)
                               ON DUPLICATE KEY UPDATE setting_value = :max_users";
            $stmt_update_max = $pdo->prepare($sql_update_max);
            $stmt_update_max->bindParam(':max_users', $max_concurrent_users_ever, PDO::PARAM_INT);
            $stmt_update_max->execute();
        } catch (PDOException $e) {
            error_log("Error updating max concurrent users in settings: " . $e->getMessage());
            // If update fails, the PHP variable $max_concurrent_users_ever might be higher than DB
            // For this request, we'll use the higher value. On next request, it will re-evaluate.
        }
    }
} else {
    error_log("PDO object not available in header.php for concurrent user tracking.");
}

// Store these values globally for potential use in other template parts
$GLOBALS['current_online_users'] = $current_online_users;
$GLOBALS['max_concurrent_users_ever'] = $max_concurrent_users_ever;
// --- END Max Concurrent Users Tracking ---

// --- BEGIN Site Identity Settings ---
$site_name_from_db = 'FutOnline'; // Default
$site_logo_filename_from_db = null;
$site_display_format_from_db = 'text'; // Default 'text' or 'logo'

if (isset($pdo)) {
    try {
        $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM site_settings
                                      WHERE setting_key IN (
                                          'site_name', 'site_logo_filename', 'site_display_format',
                                          'seo_homepage_title', 'seo_homepage_description', 'seo_homepage_keywords'
                                      )");
        $site_settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

        $site_name_from_db = $site_settings['site_name'] ?? 'FutOnline';
        $site_logo_filename_from_db = $site_settings['site_logo_filename'] ?? null;
        $site_display_format_from_db = $site_settings['site_display_format'] ?? 'text';
        $seo_homepage_title_from_db = $site_settings['seo_homepage_title'] ?? null;
        $seo_homepage_description_from_db = $site_settings['seo_homepage_description'] ?? '';
        $seo_homepage_keywords_from_db = $site_settings['seo_homepage_keywords'] ?? '';

    } catch (PDOException $e) {
        error_log("Error fetching site identity and SEO settings: " . $e->getMessage());
        // Defaults will be used
        // Ensure these are initialized even on error to prevent undefined variable issues later
        $site_name_from_db = $site_name_from_db ?? 'FutOnline'; // Keep existing if somehow set before error
        $site_logo_filename_from_db = $site_logo_filename_from_db ?? null;
        $site_display_format_from_db = $site_display_format_from_db ?? 'text';
        $seo_homepage_title_from_db = null;
        $seo_homepage_description_from_db = '';
        $seo_homepage_keywords_from_db = '';
    }
} else {
    // Case where $pdo is not set, ensure defaults for all needed variables
    $site_name_from_db = 'FutOnline';
    $site_logo_filename_from_db = null;
    $site_display_format_from_db = 'text';
    $seo_homepage_title_from_db = null;
    $seo_homepage_description_from_db = '';
    $seo_homepage_keywords_from_db = '';
}
// --- END Site Identity Settings ---

// Assumes $header_leagues variable is passed or fetched by including script
if (!isset($header_leagues)) {
    $header_leagues = []; // Default if not provided by including page
}
$direct_nav_leagues = array_slice($header_leagues, 0, 3);

// Determine if current page is Homepage
$is_homepage = (basename($_SERVER['PHP_SELF']) == 'index.php' &&
                empty($_GET['league_id']) &&
                empty($_GET['match_id']) && // Assuming match_id could be a param on index.php for some views
                empty($_GET['channel_id']) && // Assuming channel_id might also be used
                empty($_GET['query'])); // Check for search query

// Initialize variables for title, meta description, and keywords
// $page_title is usually set by the including page (e.g., index.php, match.php)
$page_title_output = $page_title ?? $site_name_from_db; // Fallback to site name if $page_title not set
$meta_description_output = '';
$meta_keywords_output = '';

if ($is_homepage) {
    $page_title_output = !empty($seo_homepage_title_from_db) ? $seo_homepage_title_from_db : $site_name_from_db;
    if (!empty($seo_homepage_description_from_db)) {
        $meta_description_output = $seo_homepage_description_from_db;
    }
    if (!empty($seo_homepage_keywords_from_db)) {
        $meta_keywords_output = $seo_homepage_keywords_from_db;
    }
} else {
    // For other pages, use $page_title if set by the page, otherwise fallback to site name (already handled by $page_title_output initialization)
    // Allow other pages to define their own meta description and keywords
    if (isset($page_meta_description) && !empty($page_meta_description)) {
        $meta_description_output = $page_meta_description;
    }
    if (isset($page_meta_keywords) && !empty($page_meta_keywords)) {
        $meta_keywords_output = $page_meta_keywords;
    }
}
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title_output); ?></title>
    <?php if (!empty($meta_description_output)): ?>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description_output); ?>">
    <?php endif; ?>
    <?php if (!empty($meta_keywords_output)): ?>
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords_output); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <div class="logo-area">
                <?php
                $effective_site_name = $site_name_from_db ?? 'FutOnline';
                $effective_display_format = $site_display_format_from_db ?? 'text';
                $logo_full_path = (!empty($site_logo_filename_from_db)) ? 'uploads/site/' . htmlspecialchars($site_logo_filename_from_db) : '';
                // Check if file exists relative to the root of the project.
                // This header is included by files in the root (e.g. index.php, match.php).
                $logo_file_exists = (!empty($logo_full_path) && file_exists($logo_full_path));
                ?>
                <a href="index.php" class="<?php echo ($effective_display_format === 'logo' && $logo_file_exists) ? 'logo-image-link' : 'logo-text'; ?>">
                    <?php if ($effective_display_format === 'logo' && $logo_file_exists): ?>
                        <img src="<?php echo $logo_full_path; ?>" alt="<?php echo htmlspecialchars($effective_site_name); ?>" style="max-height: 55px; width: auto; vertical-align: middle;">
                    <?php else: ?>
                        <?php
                        if ($effective_site_name === 'FutOnline') {
                            echo 'Fut<span class="logo-accent">Online</span>';
                        } else {
                            echo htmlspecialchars($effective_site_name);
                        }
                        ?>
                    <?php endif; ?>
                </a>
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
