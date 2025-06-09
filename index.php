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
    <title><?php echo $selected_league_name ? htmlspecialchars($selected_league_name) . " - " : ""; ?>Jogos de Futebol - FutOnline</title>
    <?php // Style block removed, will be linked from header.php ?>
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
