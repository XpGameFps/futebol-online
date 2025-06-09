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
