<?php
require_once __DIR__ . '/../FutOnline_config/config.php'; 

$header_leagues = [];
$tv_channels = [];
$site_default_cover_filename = null; $matches = [];
$home_banners = [];

$error_message = '';
$page_main_title = "PRÓXIMOS JOGOS"; $selected_league_id = null;
$selected_league_name = null;

$current_page_title = "Jogos de Futebol Ao Vivo - FutOnline";
$meta_description_content = "Acompanhe os jogos de futebol de hoje, resultados e transmissões ao vivo online.";
$meta_keywords_content = "futebol, jogos de hoje, ao vivo, online, resultados, transmissões";



if (isset($pdo)) {
        try {
        $stmt_header_leagues = $pdo->query("SELECT id, name FROM leagues ORDER BY name ASC");
        $header_leagues = $stmt_header_leagues->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("PDOException fetching header_leagues in index.php: " . $e->getMessage());
            }

        try {
        $stmt_channels = $pdo->query("SELECT id, name, logo_filename, stream_url FROM tv_channels ORDER BY sort_order ASC, name ASC LIMIT 16");
        $tv_channels = $stmt_channels->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("PDOException fetching tv_channels in index.php: " . $e->getMessage());
            }

        try {
        $stmt_default_cover = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'default_match_cover'");
        $stmt_default_cover->execute();
        $result_default_cover = $stmt_default_cover->fetch(PDO::FETCH_ASSOC);
        if ($result_default_cover && !empty($result_default_cover['setting_value'])) {
                        if (file_exists('uploads/defaults/' . $result_default_cover['setting_value'])) {
                $site_default_cover_filename = $result_default_cover['setting_value'];
            } else {
                error_log("Default cover '{$result_default_cover['setting_value']}' in settings but not found at 'uploads/defaults/{$result_default_cover['setting_value']}' from root index.php");
            }
        }
    } catch (PDOException $e) {
        error_log("PDOException fetching default_match_cover in root index.php: " . $e->getMessage());
    }

        if (isset($_GET['league_id']) && filter_var($_GET['league_id'], FILTER_VALIDATE_INT)) {
        $selected_league_id = (int)$_GET['league_id'];
        try {
            $stmt_league_name = $pdo->prepare("SELECT name FROM leagues WHERE id = :league_id");
            $stmt_league_name->bindParam(':league_id', $selected_league_id, PDO::PARAM_INT);
            $stmt_league_name->execute();
            $league_info = $stmt_league_name->fetch(PDO::FETCH_ASSOC);
            if ($league_info) {
                $selected_league_name = $league_info['name'];
                            } else {
                $error_message = "Liga não encontrada.";
                $selected_league_id = null;             }
        } catch (PDOException $e) {
            $error_message = "Erro ao buscar nome da liga: " . $e->getMessage();
            $selected_league_id = null;         }
    }

// Buscar configuração para exibir jogos passados na homepage
$show_past_matches_homepage = '0';
try {
    $stmt_setting = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'show_past_matches_homepage' LIMIT 1");
    $stmt_setting->execute();
    $result_setting = $stmt_setting->fetch(PDO::FETCH_ASSOC);
    if ($result_setting && $result_setting['setting_value'] === '1') {
        $show_past_matches_homepage = '1';
    }
} catch (PDOException $e) {
    error_log("PDOException fetching show_past_matches_homepage in index.php: " . $e->getMessage());
}

// Buscar configuração de limite de jogos na homepage
$homepage_matches_limit = 12;
try {
    $stmt_limit = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'homepage_matches_limit' LIMIT 1");
    $stmt_limit->execute();
    $result_limit = $stmt_limit->fetch(PDO::FETCH_ASSOC);
    if ($result_limit && is_numeric($result_limit['setting_value'])) {
        $homepage_matches_limit = (int)$result_limit['setting_value'];
    }
} catch (PDOException $e) {
    error_log("PDOException fetching homepage_matches_limit in index.php: " . $e->getMessage());
}

// Paginação
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $homepage_matches_limit;

try {
    $current_time_sql = "NOW()";
    if ($show_past_matches_homepage === '1') {
        $sql_matches = "SELECT
            m.id, m.match_time, m.description, m.league_id, m.cover_image_filename,
            m.meta_description, m.meta_keywords,
            ht.name AS home_team_name, ht.logo_filename AS home_team_logo, ht.primary_color_hex AS home_team_color,
            at.name AS away_team_name, at.logo_filename AS away_team_logo, at.primary_color_hex AS away_team_color,
            l.name as league_name,
            (m.match_time >= NOW()) AS is_future
        FROM matches m
        LEFT JOIN teams ht ON m.home_team_id = ht.id
        LEFT JOIN teams at ON m.away_team_id = at.id
        LEFT JOIN leagues l ON m.league_id = l.id";
        if ($selected_league_id !== null) {
            $sql_matches .= " WHERE m.league_id = :selected_league_id";
        }
        $sql_matches .= " ORDER BY is_future DESC, m.match_time ASC LIMIT :limit OFFSET :offset";
    } else {
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
        $sql_matches .= " ORDER BY m.match_time ASC LIMIT :limit OFFSET :offset";
    }
    $stmt_matches = $pdo->prepare($sql_matches);
    if ($selected_league_id !== null) {
        $stmt_matches->bindParam(':selected_league_id', $selected_league_id, PDO::PARAM_INT);
    }
    $stmt_matches->bindValue(':limit', $homepage_matches_limit, PDO::PARAM_INT);
    $stmt_matches->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt_matches->execute();
    $matches = $stmt_matches->fetchAll(PDO::FETCH_ASSOC);

    // Contar total de jogos para paginação
    $sql_count = ($show_past_matches_homepage === '1') ?
        "SELECT COUNT(*) FROM matches" :
        "SELECT COUNT(*) FROM matches WHERE match_time >= {$current_time_sql}";
    if ($selected_league_id !== null) {
        $sql_count .= ($show_past_matches_homepage === '1') ? " WHERE league_id = :selected_league_id" : " AND league_id = :selected_league_id";
    }
    $stmt_count = $pdo->prepare($sql_count);
    if ($selected_league_id !== null) {
        $stmt_count->bindParam(':selected_league_id', $selected_league_id, PDO::PARAM_INT);
    }
    $stmt_count->execute();
    $total_matches = (int)$stmt_count->fetchColumn();
    $total_pages = ceil($total_matches / $homepage_matches_limit);
} catch (PDOException $e) {
    if(empty($error_message)) { $error_message = "Erro ao buscar jogos: " . $e->getMessage(); }
    error_log("PDOException fetching matches in index.php: " . $e->getMessage());
}
    
        if ($selected_league_name) {
        $page_main_title = "Jogos da Liga: " . htmlspecialchars($selected_league_name);
        $current_page_title = htmlspecialchars($selected_league_name) . " - Jogos e Resultados - FutOnline";
        $meta_description_content = "Veja os próximos jogos e resultados da liga " . htmlspecialchars($selected_league_name) . ". Acompanhe as transmissões ao vivo.";
        $meta_keywords_content = htmlspecialchars($selected_league_name) . ", futebol, jogos de hoje, ao vivo, online, resultados, transmissões";
    } else {
                    }

        try {
                $stmt_banners = $pdo->query("SELECT image_path, target_url, alt_text, ad_type, ad_code FROM banners WHERE is_active = 1 AND display_on_homepage = 1 AND (ad_type = 'image' OR ad_type = 'banner_script' OR ad_type IS NULL) ORDER BY id ASC LIMIT 20");
        $potential_banners = $stmt_banners->fetchAll(PDO::FETCH_ASSOC);

        if ($potential_banners) {
            shuffle($potential_banners);             $home_banners = array_slice($potential_banners, 0, 4);         }
    } catch (PDOException $e) {
        error_log("PDOException fetching homepage banners in index.php: " . $e->getMessage());
            }

} else {
        $error_message = "Erro crítico: A conexão com o banco de dados não pôde ser estabelecida.";
        error_log("Critical error in index.php: \$pdo object not available after config.php inclusion.");
}


$page_title = $current_page_title; $page_meta_description = $meta_description_content; $page_meta_keywords = $meta_keywords_content; 
require_once 'templates/header.php'; 
?>
<!DOCTYPE html>
<html lang="pt-br">
    <main class="main-content">
        <?php if (!empty($tv_channels)): ?>
        <section class="tv-channels-slider">
            <div class="container">
                <h2 class="section-title">Canais de TV</h2>
                <div class="channels-grid">
                    <?php foreach ($tv_channels as $channel): ?>
                        <a href="channel_player.php?id=<?php echo htmlspecialchars($channel['id']); ?>" class="channel-item" title="Assistir <?php echo htmlspecialchars($channel['name']); ?>">
                            <?php if (!empty($channel['logo_filename'])): ?>
                                <img src="<?php echo WEB_CHANNEL_LOGO_PATH . htmlspecialchars($channel['logo_filename']); ?>" alt="<?php echo htmlspecialchars($channel['name']); ?>" class="channel-logo">
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
                                $current_match_alt_text = "Capa do Jogo";
                                if (!empty($match['cover_image_filename'])) {
                                    if ($site_default_cover_filename && $match['cover_image_filename'] === $site_default_cover_filename) {
                                        $current_match_cover_src = WEB_DEFAULT_COVER_PATH . htmlspecialchars($site_default_cover_filename);
                                        $current_match_alt_text = "Capa Padrão do Jogo";
                                    } elseif (file_exists(ltrim(WEB_MATCH_COVER_PATH, '/') . $match['cover_image_filename'])) {
                                        $current_match_cover_src = WEB_MATCH_COVER_PATH . htmlspecialchars($match['cover_image_filename']);
                                        $current_match_alt_text = "Capa do Jogo: " . htmlspecialchars($match['home_team_name'] . " vs " . $match['away_team_name']);
                                    } elseif ($site_default_cover_filename) {
                                        $current_match_cover_src = WEB_DEFAULT_COVER_PATH . htmlspecialchars($site_default_cover_filename);
                                        $current_match_alt_text = "Capa Padrão do Jogo (Fallback)";
                                    }
                                } elseif ($site_default_cover_filename) {
                                    $current_match_cover_src = WEB_DEFAULT_COVER_PATH . htmlspecialchars($site_default_cover_filename);
                                    $current_match_alt_text = "Capa Padrão do Jogo";
                                }
                                if ($current_match_cover_src): ?>
                                    <img src="<?php echo $current_match_cover_src; ?>?t=<?php echo time(); ?>" alt="<?php echo htmlspecialchars($current_match_alt_text); ?>" class="match-card-main-bg-image">
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

    <?php if (!empty($home_banners)): ?>
        <h4 class="publicidade-label">Publicidade</h4>
        <div class="banner-container">
            <?php foreach ($home_banners as $banner): ?>
                <div class="banner-item">
                    <?php
                    $current_ad_type = $banner['ad_type'] ?? 'image';
                    if ($current_ad_type === 'image') :
                        if (!empty($banner['image_path']) && !empty($banner['target_url'])) :
                    ?>
                        <a href="<?php echo htmlspecialchars($banner['target_url']); ?>" target="_blank">
                            <img src="<?php echo WEB_BANNER_PATH . htmlspecialchars($banner['image_path']); ?>"
                                 alt="<?php echo htmlspecialchars($banner['alt_text'] ?? 'Banner'); ?>">
                        </a>
                    <?php
                        endif;
                    elseif ($current_ad_type === 'banner_script') :
                        if (!empty($banner['ad_code'])) :
                            echo $banner['ad_code'];
                        endif;
                    endif;
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php require_once 'templates/footer.php'; ?>
</body>
</html>
