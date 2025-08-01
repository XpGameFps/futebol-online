<?php
require_once __DIR__ . '/../FutOnline_config/config.php'; $header_leagues = [];
if (isset($pdo)) {
    try {
        $stmt_header_leagues = $pdo->query("SELECT id, name FROM leagues ORDER BY name ASC");
        $header_leagues = $stmt_header_leagues->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {  }
}

$match_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$match = null;
$streams = [];
$page_specific_title = "Detalhes do Jogo";
$meta_description_content = "Veja os detalhes e como assistir ao jogo."; $meta_keywords_content = "futebol, jogo, ao vivo, online"; $error_message = '';


if ($match_id > 0) {
    try {
        $sql_match_details = "SELECT
                                m.id, m.match_time, m.description, m.league_id,
                                m.cover_image_filename, m.meta_description, m.meta_keywords,
                                ht.name AS home_team_name, ht.logo_filename AS home_team_logo,
                                ht.primary_color_hex AS home_team_color,
                                at.name AS away_team_name, at.logo_filename AS away_team_logo,
                                at.primary_color_hex AS away_team_color,
                                l.name as league_name
                            FROM matches m
                            LEFT JOIN teams ht ON m.home_team_id = ht.id
                            LEFT JOIN teams at ON m.away_team_id = at.id
                            LEFT JOIN leagues l ON m.league_id = l.id
                            WHERE m.id = :match_id";
        $stmt_match = $pdo->prepare($sql_match_details);
        $stmt_match->bindParam(':match_id', $match_id, PDO::PARAM_INT);
        $stmt_match->execute();
        $match = $stmt_match->fetch(PDO::FETCH_ASSOC);

        if ($match) {
            $home_team_display_name = $match['home_team_name'] ?? 'Time da Casa';
            $away_team_display_name = $match['away_team_name'] ?? 'Time Visitante';
            $page_specific_title = htmlspecialchars($home_team_display_name) . " vs " . htmlspecialchars($away_team_display_name);

            if (!empty($match['meta_description'])) {
                $meta_description_content = htmlspecialchars($match['meta_description']);
            } else {
                $meta_description_content = "Detalhes do jogo entre " . htmlspecialchars($home_team_display_name) . " e " . htmlspecialchars($away_team_display_name) . ". Opções para assistir ao vivo.";
            }
            if (!empty($match['meta_keywords'])) {
                $meta_keywords_content = htmlspecialchars($match['meta_keywords']);
            } else {
                 $meta_keywords_content = htmlspecialchars($home_team_display_name) . ", " . htmlspecialchars($away_team_display_name) . ", futebol, ao vivo, online";
            }

            $stmt_streams = $pdo->prepare("SELECT id, stream_url, stream_label FROM streams WHERE match_id = :match_id ORDER BY stream_label ASC");
            $stmt_streams->bindParam(':match_id', $match_id, PDO::PARAM_INT);
            $stmt_streams->execute();
            $streams = $stmt_streams->fetchAll(PDO::FETCH_ASSOC);

        } else {
            $error_message = "Jogo não encontrado.";
            $page_specific_title = "Jogo não encontrado";
        }
    } catch (PDOException $e) {
        $error_message = "Erro ao buscar detalhes do jogo: ".$e->getMessage();
        $page_specific_title = "Erro ao buscar jogo";
    } catch (Exception $e) {
        $error_message = "Erro geral ao processar dados do jogo: ".$e->getMessage();
        $page_specific_title = "Erro";
    }
} else {
    $error_message = "ID do jogo inválido ou não especificado.";
    $page_specific_title = "ID do jogo inválido";
}

$match_player_left_ad_code = null;
if (isset($pdo) && $match_id > 0) {     try {
        $stmt_left_ad = $pdo->prepare("SELECT ad_code, image_path, target_url, alt_text, ad_type FROM banners WHERE is_active = 1 AND display_match_player_left = 1 AND (ad_type = 'image' OR ad_type = 'banner_script') ORDER BY RAND() LIMIT 1");
        $stmt_left_ad->execute();
        $left_ad_banner = $stmt_left_ad->fetch(PDO::FETCH_ASSOC);
        if ($left_ad_banner) {
            if (($left_ad_banner['ad_type'] ?? 'image') === 'image' && !empty($left_ad_banner['image_path'])) {
                $alt = htmlspecialchars($left_ad_banner['alt_text'] ?? 'Banner');
                $target = htmlspecialchars($left_ad_banner['target_url'] ?? '#');
                $img_src = 'uploads/banners/' . htmlspecialchars($left_ad_banner['image_path']);
                $match_player_left_ad_code = "<a href='{$target}' target='_blank'><img src='{$img_src}' alt='{$alt}' style='width:160px; height:auto;'></a>";
            } elseif ($left_ad_banner['ad_type'] === 'banner_script' && !empty($left_ad_banner['ad_code'])) {
                $match_player_left_ad_code = $left_ad_banner['ad_code'];
            }
        }
    } catch (PDOException $e) {
        error_log("PDOException fetching left player ad for match page: " . $e->getMessage());
    }
}

$match_player_right_ad_code = null;
if (isset($pdo) && $match_id > 0) {     try {
        $stmt_right_ad = $pdo->prepare("SELECT ad_code, image_path, target_url, alt_text, ad_type FROM banners WHERE is_active = 1 AND display_match_player_right = 1 AND (ad_type = 'image' OR ad_type = 'banner_script') ORDER BY RAND() LIMIT 1");
        $stmt_right_ad->execute();
        $right_ad_banner = $stmt_right_ad->fetch(PDO::FETCH_ASSOC);
        if ($right_ad_banner) {
            if (($right_ad_banner['ad_type'] ?? 'image') === 'image' && !empty($right_ad_banner['image_path'])) {
                $alt = htmlspecialchars($right_ad_banner['alt_text'] ?? 'Banner');
                $target = htmlspecialchars($right_ad_banner['target_url'] ?? '#');
                $img_src = 'uploads/banners/' . htmlspecialchars($right_ad_banner['image_path']);
                $match_player_right_ad_code = "<a href='{$target}' target='_blank'><img src='{$img_src}' alt='{$alt}' style='width:160px; height:auto;'></a>";
            } elseif ($right_ad_banner['ad_type'] === 'banner_script' && !empty($right_ad_banner['ad_code'])) {
                $match_player_right_ad_code = $right_ad_banner['ad_code'];
            }
        }
    } catch (PDOException $e) {
        error_log("PDOException fetching right player ad for match page: " . $e->getMessage());
    }
}

require_once 'templates/header.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_specific_title; ?> - FutOnline</title>
    <?php if (!empty($meta_description_content)): ?><meta name="description" content="<?php echo $meta_description_content; ?>"><?php endif; ?>
    <?php if (!empty($meta_keywords_content)): ?><meta name="keywords" content="<?php echo $meta_keywords_content; ?>"><?php endif; ?>
    <link rel="stylesheet" href="css/style.css">
    <style>
        
        .match-header-teams {
            display: flex;
            justify-content: space-around;
            align-items: center;
            margin-bottom: 20px; 
            padding: 10px;
            
            border-radius: 5px;
        }
        .match-header-team-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            flex-basis: 35%; 
        }
        .match-header-team-info img.team-logo-md {
            max-height: 80px;
            max-width: 80px; 
            margin-bottom: 10px; 
            object-fit: contain;
        }
        .match-header-team-info .team-name-md {
            font-size: 1.5em;
            font-weight: bold;
            color: #fff;
        }
        .match-header-vs {
            font-size: 1.8em; 
            font-weight: bold;
            color: #00ff00;
            flex-basis: 10%;
        }
        .match-page-cover-banner {
            width: 100%;
            max-height: 300px; 
            object-fit: cover;
            margin-bottom: 25px; 
            border-radius: 5px;
        }
        .match-time-league { 
            text-align:center;
            font-size: 1.1em;
            margin-bottom:20px; 
            color: #ccc;
        }
        .match-description-full {
            text-align:justify; 
            margin-bottom:25px; 
            line-height:1.7; 
            background-color: rgba(0,0,0,0.1);
            padding: 15px;
            border-radius: 4px;
        }

    .report-issue-button {
        background-color: red;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        margin-top: 15px; 
        margin-bottom: 10px;
        display: block;
        width: fit-content;
        margin-left: auto;
        margin-right: auto;
    }
    .report-issue-button:hover {
        background-color: darkred;
    }
    .report-issue-button:disabled {
        background-color: #ccc;
        cursor: not-allowed;
    }
    .report-feedback {
        margin-top: 5px;
        font-size: 0.9em;
        text-align: center; 
    }
    .report-feedback.success {
        color: green;
    }
    .report-feedback.error {
        color: red;
    }

    
    .match-player-area-wrapper {
        display: flex;
        justify-content: space-between; 
        align-items: flex-start; 
        margin-bottom: 20px; 
    }

    .player-side-ad {
        width: 160px; 
        
        
        display: flex; 
        justify-content: center;
        align-items: center;
        overflow: hidden; 
    }

    .player-side-ad.left-ad {
        margin-right: 15px; 
    }

    .player-side-ad.right-ad {
        margin-left: 15px; 
    }

    .player-container-main {
        flex-grow: 1; 
        
    }

    
    .player-container-main iframe {
        width: 100%;
        min-height: 450px; 
        border: none; 
    }
    </style>
</head>
<body>
    <main class="main-content">
        <div class="container">
            <?php if (!empty($error_message)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php elseif ($match): ?>

                <?php if (!empty($match['cover_image_filename'])): ?>
                    <?php
                        $cover_image_path = '';
                        if ($match['cover_image_filename'] === 'default_match_cover.png') {
                            $cover_image_path = WEB_DEFAULT_COVER_PATH . 'default_match_cover.png';
                        } else {
                            $cover_image_path = WEB_MATCH_COVER_PATH . htmlspecialchars($match['cover_image_filename']);
                        }
                    ?>
                    <img src="<?php echo $cover_image_path; ?>"
                         alt="Capa para <?php echo htmlspecialchars($match['home_team_name'] ?? ''); ?> vs <?php echo htmlspecialchars($match['away_team_name'] ?? ''); ?>"
                         class="match-page-cover-banner">
                <?php endif; ?>

                <div class="match-details">
                    <div class="match-header-teams">
                        <div class="match-header-team-info">
                            <?php if (!empty($match['home_team_logo'])): ?>
                                <img src="<?php echo WEB_TEAM_LOGO_PATH . htmlspecialchars($match['home_team_logo']); ?>"
                                     alt="<?php echo htmlspecialchars($match['home_team_name'] ?? 'Time da Casa'); ?>" class="team-logo-md">
                            <?php endif; ?>
                            <span class="team-name-md"><?php echo htmlspecialchars($match['home_team_name'] ?? 'Time da Casa'); ?></span>
                        </div>
                        <span class="match-header-vs">VS</span>
                        <div class="match-header-team-info">
                            <?php if (!empty($match['away_team_logo'])): ?>
                                <img src="<?php echo WEB_TEAM_LOGO_PATH . htmlspecialchars($match['away_team_logo']); ?>"
                                     alt="<?php echo htmlspecialchars($match['away_team_name'] ?? 'Time Visitante'); ?>" class="team-logo-md">
                            <?php endif; ?>
                            <span class="team-name-md"><?php echo htmlspecialchars($match['away_team_name'] ?? 'Time Visitante'); ?></span>
                        </div>
                    </div>

                    <p class="match-time-league">
                        Horário: <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($match['match_time']))); ?>
                        <?php if (!empty($match['league_name'])): ?>
                            | Liga: <?php echo htmlspecialchars($match['league_name']); ?>
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($match['description'])): ?>
                        <div class="match-description-full">
                            <?php echo nl2br(htmlspecialchars($match['description'])); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="match-player-area-wrapper">
                    <div class="player-side-ad left-ad">
                        <?php if (!empty($match_player_left_ad_code)): ?>
                            <?php echo $match_player_left_ad_code; ?>
                        <?php endif; ?>
                    </div>
                    <div class="player-container-main">                         <div class="player-container">
                            <iframe id="streamPlayer" src="about:blank" allowfullscreen allow="autoplay; encrypted-media"></iframe>
                        </div>

                        <?php if (!empty($streams)): ?>
                            <div class="stream-options">
                                <h3>Opções de Transmissão:</h3>
                                <ul>
                                    <?php foreach ($streams as $index => $stream): ?>
                                        <li><button class="stream-button" data-stream-url="<?php echo htmlspecialchars($stream['stream_url']); ?>"><?php echo htmlspecialchars($stream['stream_label']); ?></button></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <p class="info-message">Nenhuma opção de stream disponível para este jogo no momento.</p>
                        <?php endif; ?>
                    </div>                     <div class="player-side-ad right-ad">
                        <?php if (!empty($match_player_right_ad_code)): ?>
                            <?php echo $match_player_right_ad_code; ?>
                        <?php endif; ?>
                    </div>
                </div> 
                <?php if ($match && isset($match['id'])): ?>
                <div class="report-button-container" style="text-align: center; margin-top: 20px; margin-bottom: 15px;">
                    <button id="reportMatchProblemBtn" class="report-issue-button" data-item-id="<?php echo htmlspecialchars($match['id']); ?>" data-item-type="match">
                        Reportar Problema no Jogo/Transmissão
                    </button>
                    <div id="reportMatchFeedback" class="report-feedback"></div>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <p class="error-message">Detalhes do jogo não puderam ser carregados.</p>
            <?php endif; ?>

            <div class="back-link-container">
                <a href="index.php" class="back-link">Voltar para a Lista de Jogos</a>
            </div>
        </div>
    </main>

    <?php
        $match_page_banners = [];
    if (isset($pdo)) {
        try {
                        $stmt_mp_banners = $pdo->query("SELECT image_path, target_url, alt_text FROM banners WHERE is_active = 1 AND display_on_match_page = 1 AND (ad_type = 'image' OR ad_type IS NULL) ORDER BY RAND() LIMIT 4");
            $match_page_banners = $stmt_mp_banners->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PDOException fetching match page image banners: " . $e->getMessage());
                    }
    }

        $popup_ad_scripts = [];
    if (isset($pdo)) {
        try {
            $stmt_popup_ads = $pdo->prepare("SELECT ad_code FROM banners WHERE is_active = 1 AND ad_type = 'popup_script' AND display_on_match_page = 1");
            $stmt_popup_ads->execute();
            $popup_ad_scripts = $stmt_popup_ads->fetchAll(PDO::FETCH_COLUMN, 0);         } catch (PDOException $e) {
            error_log("PDOException fetching match page pop-up ads: " . $e->getMessage());
                    }
    }

    if (!empty($match_page_banners)):
    ?>
    <h4 class="publicidade-label">Publicidade</h4>
    <div class="banner-container">
        <?php foreach ($match_page_banners as $banner): ?>
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

    <?php
        if (!empty($popup_ad_scripts)) {
        foreach ($popup_ad_scripts as $script_code) {
            echo $script_code;         }
    }
    ?>

    <?php require_once 'templates/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const player = document.getElementById('streamPlayer');
            const streamButtons = document.querySelectorAll('.stream-button');
            let firstStreamUrl = null;

            streamButtons.forEach(function(button, index) {
                const streamUrl = button.dataset.streamUrl;
                if (index === 0 && streamUrl) {
                    firstStreamUrl = streamUrl;
                }
                button.addEventListener('click', function() {
                    if (player && streamUrl) {
                        player.src = streamUrl;                         streamButtons.forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');
                    }
                });
            });

            if (firstStreamUrl && player) {
                player.src = firstStreamUrl;
                if(streamButtons.length > 0) streamButtons[0].classList.add('active');
            } else if (player && streamButtons.length > 0 && !firstStreamUrl) {
                                for(let i=0; i < streamButtons.length; i++){
                    if(streamButtons[i].dataset.streamUrl){
                        player.src = streamButtons[i].dataset.streamUrl;
                        streamButtons[i].classList.add('active');
                        break;
                    }
                }
            }
        });

        const reportMatchButton = document.getElementById('reportMatchProblemBtn');
    const reportMatchFeedback = document.getElementById('reportMatchFeedback');

    if (reportMatchButton) {
        reportMatchButton.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const itemType = this.dataset.itemType;

            if (!itemId || !itemType) {
                if (reportMatchFeedback) {
                    reportMatchFeedback.textContent = 'Erro: ID ou Tipo do item não encontrado.';
                    reportMatchFeedback.className = 'report-feedback error';
                }
                return;
            }

            this.disabled = true;
            if (reportMatchFeedback) {
                reportMatchFeedback.textContent = 'Enviando reporte...';
                reportMatchFeedback.className = 'report-feedback';
            }

            const formData = new FormData();
            formData.append('item_id', itemId);
            formData.append('item_type', itemType);

            fetch('admin/report_item_issue.php', {                 method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (reportMatchFeedback) {
                    reportMatchFeedback.textContent = data.message || 'Reporte processado.';
                    if (data.success) {
                        reportMatchFeedback.className = 'report-feedback success';
                    } else {
                        reportMatchFeedback.className = 'report-feedback error';
                    }
                }
                setTimeout(() => {
                    reportMatchButton.disabled = false;
                    if (reportMatchFeedback && data.success) {
                        reportMatchFeedback.textContent = '';
                    }
                }, 5000);
            })
            .catch(error => {
                console.error('Erro ao reportar problema no jogo:', error);
                if (reportMatchFeedback) {
                    reportMatchFeedback.textContent = 'Erro ao enviar o reporte. Tente novamente mais tarde.';
                    reportMatchFeedback.className = 'report-feedback error';
                }
                setTimeout(() => {
                    reportMatchButton.disabled = false;
                }, 5000);
            });
        });
    }
        </script>
</body>
</html>
