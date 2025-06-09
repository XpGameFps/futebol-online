<?php
require_once 'config.php'; // Corrected path
define('FRONTEND_MATCH_COVER_BASE_PATH', 'uploads/covers/matches/');

// Fetch Leagues for Header
$header_leagues = [];
try {
    // Ensure $pdo is available after config.php is correctly included
    if (isset($pdo)) {
        $stmt_header_leagues = $pdo->query("SELECT id, name FROM leagues ORDER BY name ASC");
        $header_leagues = $stmt_header_leagues->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Silently fail or log
}

$search_query = '';
$matches = [];
$search_message = '';

if (isset($_GET['query'])) {
    $search_query = trim($_GET['query']);

    if (!empty($search_query)) {
        try {
            $sql = "SELECT id, team_home, team_away, match_time, description, league_id, cover_image_filename
                    FROM matches
                    WHERE team_home LIKE :query
                       OR team_away LIKE :query
                       OR description LIKE :query_desc
                    ORDER BY match_time DESC";

            $stmt = $pdo->prepare($sql);
            $search_term_like = '%' . $search_query . '%';
            $stmt->bindParam(':query', $search_term_like, PDO::PARAM_STR);
            $stmt->bindParam(':query_desc', $search_term_like, PDO::PARAM_STR);

            $stmt->execute();
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($matches)) {
                $search_message = "Nenhum jogo encontrado para "<strong>" . htmlspecialchars($search_query) . "</strong>".";
            }
        } catch (PDOException $e) {
            $search_message = "Erro ao realizar a busca: " . $e->getMessage();
        }
    } else {
        $search_message = "Por favor, digite um termo para buscar.";
    }
} else {
    $search_message = "Digite algo na barra de busca para encontrar jogos.";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados da Busca por "<?php echo htmlspecialchars($search_query); ?>" - FutOnline</title>
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
        }

        /* Copied ALL styles from index.php's <style> tag here */
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        .site-header { background-color: #0d0d0d; padding: 10px 0; border-bottom: 3px solid #00ff00; color: #e0e0e0; }
        .header-container { max-width: 1200px; width: 90%; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo-area .logo-text { font-size: 2.2em; font-weight: bold; color: #fff; text-decoration: none; }
        .logo-area .logo-accent { color: #00ff00; }
        .main-navigation { flex-grow: 1; }
        .main-navigation ul { list-style: none; margin: 0; padding: 0; display: flex; margin-left: 20px; }
        .main-navigation li { margin-left: 20px; }
        .main-navigation a { text-decoration: none; color: #e0e0e0; font-weight: bold; padding: 5px 10px; border-radius: 4px; transition: background-color 0.3s, color 0.3s; }
        .main-navigation a:hover, .main-navigation a.active { color: #0d0d0d; background-color: #00ff00; }
        .header-right-controls { display: flex; align-items: center; }
        .search-area { margin-right: 15px; }
        .search-area .search-form { display: flex; align-items: center; }
        .search-area input[type="search"] { padding: 8px 12px; border: 1px solid #00ff00; background-color: #2c2c2c; color: #e0e0e0; border-radius: 4px 0 0 4px; font-size: 0.9em; min-width: 200px; }
        .search-area input[type="search"]::placeholder { color: #888; }
        .search-area button[type="submit"] { padding: 8px 15px; background-color: #00ff00; color: #0d0d0d; border: 1px solid #00ff00; border-left: none; cursor: pointer; font-weight: bold; border-radius: 0 4px 4px 0; font-size: 0.9em; transition: background-color 0.3s, color 0.3s; }
        .search-area button[type="submit"]:hover { background-color: #00cc00; }
        .leagues-menu { position: relative; }
        .leagues-menu-button { background: none; border: none; color: #00ff00; font-size: 1.8em; cursor: pointer; padding: 5px; line-height: 1; }
        .leagues-menu-button:hover { opacity: 0.8; }
        .leagues-dropdown-content { display: none; position: absolute; top: 100%; right: 0; background-color: #1a1a1a; border: 1px solid #00ff00; border-radius: 0 0 4px 4px; min-width: 200px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.3); z-index: 100; list-style: none; padding: 0; margin: 0; }
        .leagues-dropdown-content.show { display: block; }
        .leagues-dropdown-content li a { color: #e0e0e0; padding: 10px 15px; text-decoration: none; display: block; font-size: 0.95em; white-space: nowrap; }
        .leagues-dropdown-content li a:hover { background-color: #00ff00; color: #0d0d0d; }
        .container { max-width: 1200px; width: 90%; margin: 20px auto; overflow: visible; padding: 0 20px; }
        .page-title { color: #00ff00; text-align: center; font-size: 2.5em; margin-top: 20px; margin-bottom: 20px; }
        .section-title { color: #00ff00; text-align: center; font-size: 2em; margin-bottom: 20px; text-transform: uppercase; }
        .match-list { list-style: none; padding: 0; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
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
        .no-matches, .error-message, .search-info { text-align: center; font-size: 1.2em; color: #ffcc00; padding: 20px; background-color: #2c2c2c; border: 1px solid #ffcc00; border-radius: 5px; margin-top:20px; }
        .search-info { color: #e0e0e0; border-color: #00ff00; }
        @media (max-width: 992px) { .match-list { grid-template-columns: repeat(2, 1fr); gap: 15px; } .match-title { font-size: 1.3em; } .match-cover-image { height: 140px; } .match-cover-image-placeholder { height: 140px; } }
        @media (max-width: 576px) { .match-list { grid-template-columns: 1fr; } .match-title { font-size: 1.4em; } .match-cover-image { height: 180px; } .match-cover-image-placeholder { height: 180px; } }
        /* TV channels styles are intentionally omitted for search.php to keep it focused on match results */

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
    <?php require_once 'templates/header.php'; // Pass $header_leagues to it ?>
<main class="main-content">
    <div class="container">
        <h1 class="page-title">Resultados da Busca</h1>

        <?php if (!empty($search_query)): ?>
            <p class="search-info">Você buscou por: "<strong><?php echo htmlspecialchars($search_query); ?></strong>"</p>
        <?php endif; ?>

        <?php if (!empty($search_message)): ?>
            <p class="no-matches"><?php echo $search_message; /* HTML is already in the message or use htmlspecialchars if not */ ?></p>
        <?php elseif (!empty($matches)): ?>
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
