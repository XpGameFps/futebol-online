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

// Fetch Site Default Cover Filename
$site_default_cover_filename = null;
try {
    $stmt_default_cover = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'default_match_cover'");
    $stmt_default_cover->execute();
    $result_default_cover = $stmt_default_cover->fetch(PDO::FETCH_ASSOC);
    if ($result_default_cover && !empty($result_default_cover['setting_value'])) {
        // Filesystem check relative to index.php
        if (file_exists('uploads/defaults/' . $result_default_cover['setting_value'])) {
            $site_default_cover_filename = $result_default_cover['setting_value'];
        } else {
            error_log("Default cover '{$result_default_cover['setting_value']}' in settings but not found at 'uploads/defaults/{$result_default_cover['setting_value']}' from root index.php");
        }
    }
} catch (PDOException $e) {
    error_log("PDOException fetching default_match_cover in root index.php: " . $e->getMessage());
}

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
    $sql_matches = "SELECT
                       m.id, m.match_time, m.description, m.league_id, m.cover_image_filename,
                       m.meta_description, m.meta_keywords,
                       ht.name AS home_team_name, ht.logo_filename AS home_team_logo, ht.primary_color_hex AS home_team_color,
                       at.name AS away_team_name, at.logo_filename AS away_team_logo, at.primary_color_hex AS away_team_color,
                       l.name as league_name
                   FROM matches m
                   LEFT JOIN teams ht ON m.home_team_id = ht.id
                   LEFT JOIN teams at ON m.away_team_id = at.id
                   LEFT JOIN leagues l ON m.league_id = l.id
                   WHERE m.match_time >= {$current_time_sql}";

    if ($selected_league_id !== null) {
        $sql_matches .= " AND m.league_id = :selected_league_id";
    }
    $sql_matches .= " ORDER BY m.match_time ASC LIMIT 12"; // Changed to 12

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

$current_page_title = "Jogos de Futebol Ao Vivo - FutOnline";
if ($selected_league_name) { // If a league is selected, update title and metas
    $page_main_title = "Jogos da Liga: " . htmlspecialchars($selected_league_name); // This was already being set
    $current_page_title = htmlspecialchars($selected_league_name) . " - Jogos e Resultados - FutOnline";
    $meta_description_content = "Veja os próximos jogos e resultados da liga " . htmlspecialchars($selected_league_name) . ". Acompanhe as transmissões ao vivo.";
    $meta_keywords_content = htmlspecialchars($selected_league_name) . ", futebol, jogos de hoje, ao vivo, online, resultados, transmissões";
} else { // Default for homepage
    $meta_description_content = "Acompanhe os jogos de futebol de hoje, resultados e transmissões ao vivo online.";
    $meta_keywords_content = "futebol, jogos de hoje, ao vivo, online, resultados, transmissões";
}

// Prepare variables for header.php
$page_title = $current_page_title;
$page_meta_description = $meta_description_content;
$page_meta_keywords = $meta_keywords_content;
// $header_leagues is already fetched and available to header.php
?>
<!DOCTYPE html>
<html lang="pt-br">
<?php require_once 'templates/header.php'; ?>
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

                                <?php
                                $current_match_cover_src = null;
                                $current_match_alt_text = "Capa do Jogo"; // Default alt

                                // Path for default covers, relative to web root
                                $web_default_cover_path = '/uploads/defaults/';
                                // Path for specific match covers, relative to web root
                                $web_specific_match_cover_path = '/uploads/covers/matches/';
                                // Filesystem path for checking default cover existence (relative to index.php)
                                $fs_default_cover_path = 'uploads/defaults/'; // Used by $site_default_cover_filename logic already

                                // Check if the site_default_cover_filename was successfully fetched and the file exists
                                // $site_default_cover_filename is already checked for file existence when it's populated.
                                // So, if it's not null here, the file exists.
                                $actual_site_default_cover_filename = $site_default_cover_filename;

                                if (!empty($match['cover_image_filename'])) {
                                    // Case 1: The match's cover filename IS the site's default cover filename.
                                    if ($actual_site_default_cover_filename && $match['cover_image_filename'] === $actual_site_default_cover_filename) {
                                        $current_match_cover_src = $web_default_cover_path . htmlspecialchars($actual_site_default_cover_filename);
                                        $current_match_alt_text = "Capa Padrão do Jogo";
                                    }
                                    // Case 2: The match has a specific cover filename (legacy).
                                    else {
                                        // Check if this specific file exists in the specific directory
                                        if (file_exists('uploads/covers/matches/' . $match['cover_image_filename'])) {
                                             $current_match_cover_src = $web_specific_match_cover_path . htmlspecialchars($match['cover_image_filename']);
                                             $current_match_alt_text = "Capa do Jogo: " . htmlspecialchars($match['home_team_name'] . " vs " . $match['away_team_name']);
                                        } else {
                                            // Specific file listed in DB but not found, try to fallback to default if available
                                            if ($actual_site_default_cover_filename) {
                                                $current_match_cover_src = $web_default_cover_path . htmlspecialchars($actual_site_default_cover_filename);
                                                $current_match_alt_text = "Capa Padrão do Jogo";
                                            }
                                            // If no default either, src remains null, placeholder will show.
                                        }
                                    }
                                }
                                // Case 3: Match has no cover_image_filename, so use site default if available.
                                elseif ($actual_site_default_cover_filename) {
                                    $current_match_cover_src = $web_default_cover_path . htmlspecialchars($actual_site_default_cover_filename);
                                    $current_match_alt_text = "Capa Padrão do Jogo";
                                }

                                if ($current_match_cover_src): ?>
                                    <img src="<?php echo $current_match_cover_src; ?>?t=<?php echo time(); // Cache buster ?>"
                                         alt="<?php echo htmlspecialchars($current_match_alt_text); ?>" class="match-card-main-bg-image">
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

    <?php
    // Fetch Banners for Homepage
    $home_banners = [];
    if (isset($pdo)) {
        try {
            $stmt_banners = $pdo->query("SELECT image_path, target_url, alt_text FROM banners WHERE is_active = 1 AND display_on_homepage = 1 ORDER BY RAND() LIMIT 4");
            $home_banners = $stmt_banners->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PDOException fetching homepage banners: " . $e->getMessage());
            // Silently fail or log the error, don't break the page
        }
    }

    if (!empty($home_banners)):
    ?>
    <div class="banner-container">
        <?php foreach ($home_banners as $banner): ?>
            <div class="banner-item">
                <a href="<?php echo htmlspecialchars($banner['target_url']); ?>" target="_blank">
                    <img src="uploads/banners/<?php echo htmlspecialchars($banner['image_path']); ?>"
                         alt="<?php echo htmlspecialchars($banner['alt_text'] ?? 'Banner'); ?>">
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    endif;
    ?>

    <?php require_once 'templates/footer.php'; ?>
</body>
</html>
