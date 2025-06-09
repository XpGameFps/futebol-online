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
            $sql = "SELECT
                        m.id, m.match_time, m.description, m.league_id, m.cover_image_filename,
                        m.meta_description, m.meta_keywords,
                        ht.name AS home_team_name, ht.logo_filename AS home_team_logo, ht.primary_color_hex AS home_team_color,
                        at.name AS away_team_name, at.logo_filename AS away_team_logo, at.primary_color_hex AS away_team_color,
                        l.name as league_name
                    FROM matches m
                    LEFT JOIN teams ht ON m.home_team_id = ht.id
                    LEFT JOIN teams at ON m.away_team_id = at.id
                    LEFT JOIN leagues l ON m.league_id = l.id
                    WHERE (ht.name LIKE :query_team_home OR at.name LIKE :query_team_away OR m.description LIKE :query_desc)
                    ORDER BY m.match_time DESC"; // Search might need to look into joined team names now

            $stmt = $pdo->prepare($sql);
            $search_term_like = '%' . $search_query . '%';
            // Bind for new search logic targeting team names and description
            $stmt->bindParam(':query_team_home', $search_term_like, PDO::PARAM_STR);
            $stmt->bindParam(':query_team_away', $search_term_like, PDO::PARAM_STR);
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
    <title>Resultados da Busca por "<?php echo htmlspecialchars($search_query); ?>" - FutOnline</title>
    <?php // Style block removed, will be linked from header.php ?>
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
                    <li class="match-list-item-container">
                        <article>
                            <a class="match-card-link new-design" href="match.php?id=<?php echo htmlspecialchars($match['id']); ?>">

                                <div class="match-card-background-sections">
                                    <div class="match-card-bg-left"
                                         style="<?php echo !empty($match['home_team_color']) ? 'background-color: ' . hexToRgba($match['home_team_color'], 0.3) . ';' : ''; ?>">
                                    </div>
                                    <div class="match-card-bg-right"
                                         style="<?php echo !empty($match['away_team_color']) ? 'background-color: ' . hexToRgba($match['away_team_color'], 0.3) . ';' : ''; ?>">
                                    </div>
                                </div>

                                <?php if (!empty($match['cover_image_filename'])): ?>
                                    <img src="<?php echo FRONTEND_MATCH_COVER_BASE_PATH . htmlspecialchars($match['cover_image_filename']); ?>"
                                         alt="Background" class="match-card-main-bg-image">
                                <?php else: ?>
                                    <div class="match-card-main-bg-placeholder"></div>
                                <?php endif; ?>

                                <div class="match-card-overlay-content">
                                    <div class="teams-row">
                                        <div class="team-info home-team">
                                            <?php if (!empty($match['home_team_logo'])): ?>
                                                <img src="uploads/logos/teams/<?php echo htmlspecialchars($match['home_team_logo']); ?>" alt="<?php echo htmlspecialchars($match['home_team_name'] ?? 'Time da Casa'); ?>" class="team-logo">
                                            <?php endif; ?>
                                            <span class="team-name"><?php echo htmlspecialchars($match['home_team_name'] ?? 'Time da Casa'); ?></span>
                                        </div>
                                        <div class="match-versus">VS</div>
                                        <div class="team-info away-team">
                                            <?php if (!empty($match['away_team_logo'])): ?>
                                                <img src="uploads/logos/teams/<?php echo htmlspecialchars($match['away_team_logo']); ?>" alt="<?php echo htmlspecialchars($match['away_team_name'] ?? 'Time Visitante'); ?>" class="team-logo">
                                            <?php endif; ?>
                                            <span class="team-name"><?php echo htmlspecialchars($match['away_team_name'] ?? 'Time Visitante'); ?></span>
                                        </div>
                                    </div>
                                    <div class="match-card-time">
                                        <?php echo htmlspecialchars(date('d/m H:i', strtotime($match['match_time']))); ?>
                                    </div>
                                </div>
                            </a>
                        </article>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>
<?php require_once 'templates/footer.php'; ?>
</body>
</html>
