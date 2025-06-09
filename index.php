<?php
// Main page - List matches
require_once 'config.php'; // Database connection
// Define base path for channel logos for frontend display - relative to project root
define('FRONTEND_CHANNELS_LOGO_BASE_PATH', 'uploads/logos/channels/');
define('FRONTEND_MATCH_COVER_BASE_PATH', 'uploads/covers/matches/'); // New base path for match covers

// Fetch Leagues for Header
$header_leagues = [];
try {
    $stmt_header_leagues = $pdo->query("SELECT id, name FROM leagues ORDER BY name ASC");
    $header_leagues = $stmt_header_leagues->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silently fail for header leagues, or log error
}

$matches = [];
$error_message = '';

// Fetch TV Channels
$tv_channels = [];
try {
    $stmt_channels = $pdo->query("SELECT id, name, logo_filename, stream_url FROM tv_channels ORDER BY sort_order ASC, name ASC LIMIT 16"); // Limit to 16 for a 2x8 grid initially
    $tv_channels = $stmt_channels->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Optionally display an error for channels, or just don't show the section
    // For now, if fetching channels fails, the section just won't appear.
    // $error_message .= "<p>Erro ao buscar canais de TV: " . $e->getMessage() . "</p>";
}

// Fetch matches - UPDATED QUERY
try {
    // Fetch upcoming/ongoing matches, ordered by soonest first.
    // We can use NOW() for current server time. Consider a small offset if needed for "just started" games.
    // For example, to include games that started in the last 2 hours: DATE_SUB(NOW(), INTERVAL 2 HOUR)
    // For simplicity, we'll start with NOW() to show only future or very current games.
    $current_time_sql = "NOW()"; // Or specific timezone adjusted time if necessary
    $sql_matches = "SELECT id, team_home, team_away, match_time, description, league_id, cover_image_filename
                    FROM matches
                    WHERE match_time >= {$current_time_sql}
                    ORDER BY match_time ASC
                    LIMIT 30";
    $stmt_matches = $pdo->query($sql_matches);
    $matches = $stmt_matches->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if(empty($error_message)) { // Avoid overwriting other potential errors
        $error_message = "Erro ao buscar jogos: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jogos de Futebol Ao Vivo</title>
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
            flex-grow: 1; /* Allows this element to take up available space */
            /* flex-shrink: 0; /* Default, not strictly necessary here */
            /* flex-basis: auto; /* Default */
        }
        /* Ensure existing .container styles DO NOT have height or min-height that would conflict with flex-grow */
        /* The .site-footer-main will be a direct child of body and will be pushed down. */

        * { box-sizing: border-box; }

        /* New Header Styles - Common for index.php & match.php */
        .site-header {
            background-color: #0d0d0d; /* Darker metallic black */
            padding: 10px 0;
            border-bottom: 3px solid #00ff00; /* Green accent line */
            color: #e0e0e0;
        }
        .header-container {
            max-width: 1200px; /* Consistent with main content container */
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
            color: #00ff00; /* Green accent */
        }
        .main-navigation ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .main-navigation li {
            margin-left: 20px;
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
            background-color: #00ff00; /* Green accent */
        }
        .search-area .search-form {
            display: flex;
            align-items: center;
        }
        .search-area input[type="search"] {
            padding: 8px 12px;
            border: 1px solid #00ff00; /* Green border */
            background-color: #2c2c2c; /* Dark input background */
            color: #e0e0e0;
            border-radius: 4px 0 0 4px; /* Rounded left corners */
            font-size: 0.9em;
            min-width: 200px; /* Decent default width */
        }
        .search-area input[type="search"]::placeholder {
            color: #888;
        }
        .search-area button[type="submit"] {
            padding: 8px 15px;
            background-color: #00ff00; /* Green button */
            color: #0d0d0d; /* Dark text on green */
            border: 1px solid #00ff00;
            border-left: none; /* Avoid double border with input */
            cursor: pointer;
            font-weight: bold;
            border-radius: 0 4px 4px 0; /* Rounded right corners */
            font-size: 0.9em;
            transition: background-color 0.3s, color 0.3s;
        }
        .search-area button[type="submit"]:hover {
            background-color: #00cc00; /* Slightly darker green */
        }

        .header-container { /* Ensure this is flex and items are centered */
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .main-navigation { /* Adjust if it takes too much space or allow it to shrink */
            flex-grow: 1; /* Allows main nav to take space, pushing right controls */
        }
        .main-navigation ul { /* If only "Início" is left, this is fine */
             margin-left: 20px; /* Add some space from logo */
        }

        .header-right-controls {
            display: flex;
            align-items: center;
        }

        .search-area { /* Already styled, ensure it fits with the new menu */
            margin-right: 15px; /* Space between search and leagues menu */
        }

        .leagues-menu {
            position: relative; /* For dropdown positioning */
        }
        .leagues-menu-button {
            background: none;
            border: none;
            color: #00ff00; /* Green accent */
            font-size: 1.8em; /* Adjust size of ellipsis/icon */
            cursor: pointer;
            padding: 5px;
            line-height: 1; /* Ensure icon is centered */
        }
        .leagues-menu-button:hover {
            opacity: 0.8;
        }
        .leagues-dropdown-content {
            display: none; /* Hidden by default */
            position: absolute;
            top: 100%; /* Position below the button */
            right: 0; /* Align to the right of the button/menu container */
            background-color: #1a1a1a; /* Dark background for dropdown */
            border: 1px solid #00ff00; /* Green border */
            border-radius: 0 0 4px 4px; /* Rounded bottom corners */
            min-width: 200px; /* Minimum width */
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.3);
            z-index: 100; /* Ensure it's above other content */
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .leagues-dropdown-content.show {
            display: block; /* Show when .show class is added by JS */
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
            background-color: #00ff00; /* Green background on hover */
            color: #0d0d0d; /* Dark text on green */
        }

        /* Adjustments for existing styles if old header was very different */
        /* For example, the old header had h1, this one does not directly for the page title */
        /* Page specific titles (like "Jogos de Hoje" or Match Name) should now be in the main content area if needed */
        /* Example: a new .page-title class for h1s that were in the old header */
        .page-title {
            color: #00ff00; /* Green accent for page titles */
            text-align: center;
            font-size: 2.5em; /* Or match old header h1 size */
            margin-top: 20px;
            margin-bottom: 20px;
        }

        /* TV Channels Slider/Grid Styles */
        .tv-channels-slider {
            background-color: #111; /* Slightly different dark shade for this section */
            padding: 20px 0;
            margin-bottom: 30px;
            border-top: 2px solid #00ff00;
            border-bottom: 2px solid #00ff00;
        }
        .section-title { /* Can be reused for "Jogos de Hoje" if that h1 is styled differently */
            color: #00ff00;
            text-align: center;
            font-size: 2em;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .channels-grid {
            max-width: 1200px; /* Consistent with main content container */
            width: 90%;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); /* Responsive columns, aiming for ~8 wide */
            gap: 15px;
        }
        /* To strictly enforce max 8 columns on larger screens if auto-fill doesn't achieve it: */
        /* @media (min-width: 1200px) { .channels-grid { grid-template-columns: repeat(8, 1fr); } } */

        .channel-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center; /* Center content vertically */
            background-color: #2c2c2c;
            border: 1px solid #008000; /* Darker green border */
            border-radius: 8px;
            padding: 10px;
            text-decoration: none;
            color: #e0e0e0;
            transition: transform 0.2s ease, border-color 0.2s ease;
            height: 100px; /* Fixed height for items in a row */
            overflow: hidden; /* Hide overflow if name is too long */
        }
        .channel-item:hover {
            transform: translateY(-3px);
            border-color: #00ff00; /* Brighter green on hover */
        }
        .channel-logo {
            max-height: 50px; /* Adjust as needed */
            max-width: 100%;   /* Ensure logo fits */
            margin-bottom: 8px;
            object-fit: contain; /* Scale logo nicely */
        }
        .channel-name {
            font-size: 0.9em;
            text-align: center;
            display: block; /* Ensure it takes its own line */
            white-space: nowrap; /* Prevent name wrapping for now */
            overflow: hidden;
            text-overflow: ellipsis; /* Add ... if name is too long */
            width: 100%; /* Required for text-overflow */
        }
        .channel-name-placeholder { /* If no logo, show name more prominently */
            font-size: 1.1em;
            font-weight: bold;
            text-align: center;
            margin-bottom: 8px;
        }

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
            padding: 1.5em 0;
            text-align: center;
            border-bottom: 3px solid #00ff00; /* Green accent line */
        }
        header h1 {
            margin: 0;
            font-size: 2.5em;
        }
        .container {
            max-width: 1200px; /* Max width for very large screens */
            width: 90%; /* Responsive width */
            margin: 20px auto;
            overflow: hidden;
            padding: 20px;
        }
        /* Match Listing Grid Styles - Update existing .match-list and .match-list-item */
        .match-list {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px; /* Increased gap slightly */
        }
        .match-list-item {
            /* position: relative; /* Not strictly needed if <a> is the direct child doing the work */
            /* overflow: hidden; /* Keep this for image corners */
            /* display: flex; flex-direction: column; /* This should now be on .match-card-link */
            /* Remove background, border, shadow from here as <a> will take over */
            background-color: transparent; /* Or remove if not set */
            border: none; /* Or remove if not set */
            box-shadow: none; /* Or remove if not set */
        }

        .match-card-link { /* The new wrapper link */
            display: flex;
            flex-direction: column;
            background-color: #2c2c2c;
            border: 1px solid #008000;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 255, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            text-decoration: none; /* Remove underline from the whole card link */
            color: inherit; /* Inherit text color for content within */
            overflow: hidden; /* To make image corners conform */
            height: 100%; /* Make the link fill the li if li has fixed height or is part of a grid row */
        }
        .match-card-link:hover {
            transform: translateY(-4px);
            box-shadow: 0 7px 14px rgba(0, 255, 0, 0.2);
            border-color: #00ff00;
        }

        .match-cover-image { /* Existing style, should be fine */
            width: 100%;
            height: 160px;
            object-fit: cover;
        }
        .match-cover-image-placeholder { /* Optional: if no image, maintain space */
            width: 100%;
            height: 160px; /* Same height as image */
            background-color: #3a3a3a; /* Dark placeholder color */
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            /* content: "Sem Capa"; /* Can't use content on a div, use text or an SVG background */
        }


        .match-item-content { /* Existing style, should be fine */
            padding: 15px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            justify-content: space-between;
        }

        .match-title { /* New class for the title, formerly .match-link */
            color: #00dd00;
            font-size: 1.2em;
            font-weight: bold;
            margin: 0 0 8px 0; /* Remove default heading margins */
        }
        .match-card-link:hover .match-title { /* Optional: underline title on card hover */
            text-decoration: underline;
            color: #00ff00;
        }

        .match-time { /* Existing style, ensure color is not overridden by <a> if it was specific */
            font-size: 0.85em;
            color: #a0a0a0;
            margin-bottom: 8px;
        }
        .match-description { /* Existing style, ensure color is not overridden */
            font-size: 0.9em;
            color: #c0c0c0;
            line-height: 1.4;
            flex-grow: 1;
        }

        /* Responsive adjustments for match list */
        @media (max-width: 992px) {
            .match-list {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px; /* Adjust gap for tablets */
            }
            .match-title { /* Was .match-list-item .match-link */
                font-size: 1.3em;
            }
            .match-cover-image {
                height: 140px; /* Adjust cover height for tablets */
            }
        }
        @media (max-width: 576px) {
            .match-list {
                grid-template-columns: 1fr;
            }
            .match-title { /* Was .match-list-item .match-link */
                font-size: 1.4em;
            }
            /* .match-list-item content padding was 20px, now handled by .match-item-content */
            .match-cover-image {
                height: 180px; /* Adjust cover height for mobile - can be taller */
            }
        }
        .no-matches, .error-message {
            text-align: center;
            font-size: 1.2em;
            color: #ffcc00; /* Yellow for notices/errors */
            padding: 20px;
            background-color: #2c2c2c;
            border: 1px solid #ffcc00;
            border-radius: 5px;
        }

        /* Basic Footer Styles */
        .site-footer-main {
            background-color: #0d0d0d; /* Darker metallic black, similar to header */
            color: #a0a0a0; /* Light gray text */
            padding: 20px 0;
            text-align: center;
            border-top: 2px solid #00ff00; /* Green accent line */
            font-size: 0.9em;
            margin-top: 30px; /* Space above the footer */
        }
        .footer-container {
            max-width: 1200px;
            width: 90%;
            margin: 0 auto;
        }

        /* Cookie Consent Banner Styles */
        .cookie-consent-banner {
            display: none; /* Hidden by default, shown by JS */
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: rgba(10, 10, 10, 0.95); /* Very dark, slightly transparent */
            color: #e0e0e0;
            padding: 15px 20px;
            z-index: 1000; /* Ensure it's on top */
            text-align: center;
            border-top: 1px solid #00ff00; /* Green accent */
            box-shadow: 0 -2px 10px rgba(0,0,0,0.5);
        }
        .cookie-consent-banner p {
            margin: 0 0 10px 0;
            font-size: 0.9em;
            display: inline; /* Keep text and button on same line if space allows */
        }
        .cookie-consent-banner a {
            color: #00ff00; /* Green link */
            text-decoration: underline;
        }
        #acceptCookieConsent {
            background-color: #00ff00; /* Green button */
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

        .main-navigation a.active { /* Style for active menu links */
            color: #0d0d0d;
            background-color: #00ff00; /* Green accent */
            font-weight: bold; /* Ensure active link is prominent */
        }

        .admin-panel-link {
            display: inline-block;
            margin-left: 15px; /* Space from leagues dropdown or search */
            padding: 6px 12px;
            background-color: #00b300; /* Slightly different green or a distinct color */
            color: #ffffff;
            text-decoration: none;
            font-weight: bold;
            border-radius: 4px;
            font-size: 0.9em;
            transition: background-color 0.3s;
        }
        .admin-panel-link:hover {
            background-color: #009900; /* Darker shade on hover */
        }

        /* Header Responsiveness Adjustments */
        @media (max-width: 767px) {
            .main-navigation .league-nav-link { /* Hide direct league links */
                display: none;
            }

            .logo-area .logo-text {
                font-size: 1.8em; /* Slightly smaller logo text */
            }

            .search-area input[type="search"] {
                min-width: 120px; /* Allow search bar to shrink more */
                font-size: 0.85em;
                padding: 7px 10px;
            }
            .search-area button[type="submit"] {
                font-size: 0.85em;
                padding: 7px 10px;
            }

            .leagues-menu-button {
                font-size: 1.6em; /* Slightly smaller dropdown icon */
            }

            .admin-panel-link { /* If admin link is present */
                font-size: 0.8em;
                padding: 5px 8px;
            }

            .header-container {
                 width: 95%; /* More width for content on small screens */
            }
            .main-navigation ul {
                 margin-left: 10px; /* Reduce space from logo */
            }
             .main-navigation li { /* Reduce space between "Início" and next element if any */
                margin-left: 10px;
            }
        }

        @media (max-width: 480px) { /* Even smaller screens */
            .logo-area .logo-text {
                font-size: 1.6em;
            }
            /* Potentially hide search bar or make it an icon toggle on very small screens */
            /* For now, let it shrink */
            .search-area input[type="search"] {
                min-width: 80px;
                max-width: 120px; /* Prevent it from taking too much space if other items need it */
            }
             .main-navigation {
                flex-grow: 0; /* Allow it to not push other elements too much if space is tight */
            }
        }
    </style>
</head>
<body>
<?php require_once 'templates/header.php'; ?>

<?php if (!empty($tv_channels)): ?>
<section class="tv-channels-slider">
    <h2 class="section-title">Canais de TV</h2>
    <div class="channels-grid">
        <?php foreach ($tv_channels as $channel): ?>
            <a href="<?php echo htmlspecialchars($channel['stream_url']); ?>" target="_blank" class="channel-item" title="Assistir <?php echo htmlspecialchars($channel['name']); ?>">
                <?php if (!empty($channel['logo_filename'])): ?>
                    <img src="<?php echo FRONTEND_CHANNELS_LOGO_BASE_PATH . htmlspecialchars($channel['logo_filename']); ?>"
                         alt="<?php echo htmlspecialchars($channel['name']); ?>" class="channel-logo">
                <?php else: ?>
                    <span class="channel-name-placeholder"><?php echo htmlspecialchars($channel['name']); ?></span>
                <?php endif; ?>
                <span class="channel-name"><?php echo htmlspecialchars($channel['name']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

    <div class="container"> <!-- Existing container -->
    <h1 class="page-title">Jogos de Hoje</h1>
<?php if (!empty($error_message) && empty($matches)): // Display error only if no matches and error exists ?>
    <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
<?php elseif (empty($matches)): ?>
    <p class="no-matches">Nenhum jogo programado no momento. Volte mais tarde!</p>
<?php else: ?>
    <ul class="match-list">
        <?php foreach ($matches as $match): ?>
            <li class="match-list-item">
                <?php if (!empty($match['cover_image_filename'])): ?>
                    <img src="<?php echo FRONTEND_MATCH_COVER_BASE_PATH . htmlspecialchars($match['cover_image_filename']); ?>"
                         alt="Capa para <?php echo htmlspecialchars($match['team_home']); ?> vs <?php echo htmlspecialchars($match['team_away']); ?>"
                         class="match-cover-image">
                <?php endif; ?>
                <div class="match-item-content">
                    <a class="match-link" href="match.php?id=<?php echo htmlspecialchars($match['id']); ?>">
                        <?php echo htmlspecialchars($match['team_home']); ?> vs <?php echo htmlspecialchars($match['team_away']); ?>
                    </a>
                    <p class="match-time">
                        Horário: <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($match['match_time']))); ?>
                    </p>
                    <?php if (!empty($match['description'])): ?>
                        <p class="match-description"><?php echo nl2br(htmlspecialchars($match['description'])); ?></p>
                    <?php endif; ?>
                    <?php // Placeholder for league display if $match['league_id'] is used later ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
</div><!-- end .container -->
<?php require_once 'templates/footer.php'; ?>
</body>
</html>
