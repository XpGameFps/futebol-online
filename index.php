<?php
// Main page - List matches
require_once 'config.php'; // Database connection
// Define base path for channel logos for frontend display - relative to project root
define('FRONTEND_CHANNELS_LOGO_BASE_PATH', 'uploads/logos/channels/');

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

try {
    // Fetch upcoming or recent matches, ordered by match time.
    // For simplicity, let's fetch all matches ordered by most recent first.
    // A more advanced query might filter for match_time >= NOW() for upcoming games.
    $stmt = $pdo->query("SELECT id, team_home, team_away, match_time, description FROM matches ORDER BY match_time DESC");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erro ao buscar jogos: " . $e->getMessage();
    // In a production environment, log this error instead of displaying to user directly.
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jogos de Futebol Ao Vivo</title>
    <style>
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
        /* Match Listing Grid Styles - Replaces previous .match-list and .match-list-item styles */
        .match-list {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* 3 columns */
            gap: 15px; /* Space between items */
        }
        .match-list-item {
            background-color: #2c2c2c;
            border: 1px solid #008000; /* Darker green border, brightens on hover */
            padding: 15px; /* Slightly reduced padding for smaller items */
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 255, 0, 0.05); /* More subtle shadow */
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            display: flex; /* Use flex to manage content height and alignment */
            flex-direction: column;
            justify-content: space-between; /* Pushes description down if content varies */
            min-height: 150px; /* Give items a minimum height, adjust as needed */
        }
        .match-list-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 255, 0, 0.15);
            border-color: #00ff00; /* Bright green border on hover */
        }
        .match-list-item .match-link { /* Changed from direct 'a' to a class for more specific targeting if needed */
            text-decoration: none;
            color: #00dd00; /* Slightly less bright green for default state */
            font-size: 1.3em; /* Adjusted font size for smaller cards */
            font-weight: bold;
            display: block; /* Make it block for better layout control */
            margin-bottom: 8px;
        }
        .match-list-item .match-link:hover {
            text-decoration: underline;
            color: #00ff00; /* Brighter green on hover */
        }
        .match-time {
            font-size: 0.9em; /* Adjusted font size */
            color: #a0a0a0;
            margin-bottom: 8px; /* Adjusted margin */
        }
        .match-description {
            font-size: 0.95em; /* Adjusted font size */
            color: #c0c0c0;
            line-height: 1.4;
            /* Optional: Limit lines for description if they become too long */
            /* display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; */
        }

        /* Responsive adjustments for match list */
        @media (max-width: 992px) { /* For tablets */
            .match-list {
                grid-template-columns: repeat(2, 1fr); /* 2 columns */
            }
            .match-list-item .match-link {
                font-size: 1.4em;
            }
        }
        @media (max-width: 576px) { /* For mobile phones */
            .match-list {
                grid-template-columns: 1fr; /* 1 column */
            }
            .match-list-item .match-link {
                font-size: 1.5em;
            }
            .match-list-item {
                padding: 20px; /* Restore more padding for single column items */
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
    <h1 class="page-title">Jogos de Hoje</h1> <!-- Add page title here -->
    <?php if (!empty($error_message)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php elseif (empty($matches)): ?>
            <p class="no-matches">Nenhum jogo programado no momento. Volte mais tarde!</p>
        <?php else: ?>
            <ul class="match-list">
                <?php foreach ($matches as $match): ?>
                    <li class="match-list-item">
                        <a class="match-link" href="match.php?id=<?php echo htmlspecialchars($match['id']); ?>">
                            <?php echo htmlspecialchars($match['team_home']); ?> vs <?php echo htmlspecialchars($match['team_away']); ?>
                        </a>
                        <p class="match-time">
                            Horário: <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($match['match_time']))); ?>
                        </p>
                        <?php if (!empty($match['description'])): ?>
                            <p class="match-description"><?php echo nl2br(htmlspecialchars($match['description'])); ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
