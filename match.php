<?php
// match.php
require_once 'config.php'; // Includes hexToRgba if added there

// Fetch Leagues for Header
$header_leagues = [];
if (isset($pdo)) {
    try {
        $stmt_header_leagues = $pdo->query("SELECT id, name FROM leagues ORDER BY name ASC");
        $header_leagues = $stmt_header_leagues->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { /* Silently fail or log */ }
}

$match_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$match = null;
$streams = [];
$page_specific_title = "Detalhes do Jogo";
$meta_description_content = "Veja os detalhes e como assistir ao jogo."; // Default
$meta_keywords_content = "futebol, jogo, ao vivo, online"; // Default
$error_message = '';

// Define base path for assets - these should ideally be in config.php or a global scope
if (!defined('FRONTEND_MATCH_COVER_BASE_PATH')) {
    define('FRONTEND_MATCH_COVER_BASE_PATH', 'uploads/covers/matches/');
}
if (!defined('TEAM_LOGO_BASE_PATH')) { // Assuming team logos are in uploads/logos/teams/ relative to root
    define('TEAM_LOGO_BASE_PATH', 'uploads/logos/teams/');
}


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
    } catch (Exception $e) { // Catching potential DateTime errors too
        $error_message = "Erro geral ao processar dados do jogo: ".$e->getMessage();
        $page_specific_title = "Erro";
    }
} else {
    $error_message = "ID do jogo inválido ou não especificado.";
    $page_specific_title = "ID do jogo inválido";
}
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
        /* Additional styles for match.php, can be moved to style.css later */
        .match-header-teams {
            display: flex;
            justify-content: space-around;
            align-items: center;
            margin-bottom: 20px; /* Increased margin */
            padding: 10px;
            /* background-color: #222; Slightly different background for this section */
            border-radius: 5px;
        }
        .match-header-team-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            flex-basis: 35%; /* Give some space for VS */
        }
        .match-header-team-info img.team-logo-md {
            max-height: 80px;
            max-width: 80px; /* Increased size */
            margin-bottom: 10px; /* Increased margin */
            object-fit: contain;
        }
        .match-header-team-info .team-name-md {
            font-size: 1.5em;
            font-weight: bold;
            color: #fff;
        }
        .match-header-vs {
            font-size: 1.8em; /* Increased size */
            font-weight: bold;
            color: #00ff00;
            flex-basis: 10%;
        }
        .match-page-cover-banner {
            width: 100%;
            max-height: 300px; /* Increased height */
            object-fit: cover;
            margin-bottom: 25px; /* Increased margin */
            border-radius: 5px;
        }
        .match-time-league { /* New class for combined time and league */
            text-align:center;
            font-size: 1.1em;
            margin-bottom:20px; /* Increased margin */
            color: #ccc;
        }
        .match-description-full {
            text-align:justify; /* Justify for better readability */
            margin-bottom:25px; /* Increased margin */
            line-height:1.7; /* Increased line height */
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
        margin-top: 15px; /* Ajuste a margem conforme necessário */
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
        text-align: center; /* Centralizar feedback */
    }
    .report-feedback.success {
        color: green;
    }
    .report-feedback.error {
        color: red;
    }
    </style>
</head>
<body>
    <?php require_once 'templates/header.php'; ?>
    <main class="main-content">
        <div class="container">
            <?php if (!empty($error_message)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php elseif ($match): ?>

                <?php if (!empty($match['cover_image_filename'])): ?>
                    <?php
                        $cover_image_path = '';
                        if ($match['cover_image_filename'] === 'default_match_cover.png') {
                            $cover_image_path = 'uploads/defaults/default_match_cover.png?t=1749759013';
                        } else {
                            $cover_image_path = FRONTEND_MATCH_COVER_BASE_PATH . htmlspecialchars($match['cover_image_filename']);
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
                                <img src="<?php echo TEAM_LOGO_BASE_PATH . htmlspecialchars($match['home_team_logo']); ?>"
                                     alt="<?php echo htmlspecialchars($match['home_team_name'] ?? 'Time da Casa'); ?>" class="team-logo-md">
                            <?php endif; ?>
                            <span class="team-name-md"><?php echo htmlspecialchars($match['home_team_name'] ?? 'Time da Casa'); ?></span>
                        </div>
                        <span class="match-header-vs">VS</span>
                        <div class="match-header-team-info">
                            <?php if (!empty($match['away_team_logo'])): ?>
                                <img src="<?php echo TEAM_LOGO_BASE_PATH . htmlspecialchars($match['away_team_logo']); ?>"
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

                <div class="player-container">
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

                <?php // <!-- ADICIONAR BOTÃO DE REPORTE AQUI --> ?>
                <?php if ($match && isset($match['id'])): // Garante que temos um ID de partida ?>
                <div class="report-button-container" style="text-align: center; margin-top: 20px; margin-bottom: 15px;">
                    <button id="reportMatchProblemBtn" class="report-issue-button" data-item-id="<?php echo htmlspecialchars($match['id']); ?>" data-item-type="match">
                        Reportar Problema no Jogo/Transmissão
                    </button>
                    <div id="reportMatchFeedback" class="report-feedback"></div>
                </div>
                <?php endif; ?>

            <?php else: // Este é o else para if ($match) ?>
                <p class="error-message">Detalhes do jogo não puderam ser carregados.</p>
            <?php endif; ?>

            <div class="back-link-container">
                <a href="index.php" class="back-link">Voltar para a Lista de Jogos</a>
            </div>
        </div>
    </main>

    <?php
    // Fetch Banners for Match Page
    $match_page_banners = [];
    if (isset($pdo)) {
        try {
            $stmt_mp_banners = $pdo->query("SELECT image_path, target_url, alt_text FROM banners WHERE is_active = 1 AND display_on_match_page = 1 ORDER BY RAND() LIMIT 4");
            $match_page_banners = $stmt_mp_banners->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PDOException fetching match page banners: " . $e->getMessage());
            // Silently fail or log the error, don't break the page
        }
    }

    if (!empty($match_page_banners)):
    ?>
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
                        player.src = streamUrl; // Use the captured streamUrl from the loop
                        streamButtons.forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');
                    }
                });
            });

            if (firstStreamUrl && player) {
                player.src = firstStreamUrl;
                if(streamButtons.length > 0) streamButtons[0].classList.add('active');
            } else if (player && streamButtons.length > 0 && !firstStreamUrl) {
                // Fallback if first button had no URL for some reason but others might
                for(let i=0; i < streamButtons.length; i++){
                    if(streamButtons[i].dataset.streamUrl){
                        player.src = streamButtons[i].dataset.streamUrl;
                        streamButtons[i].classList.add('active');
                        break;
                    }
                }
            }
        });

    // Script para o botão de reportar problema no jogo
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

            fetch('admin/report_item_issue.php', { // Usa o endpoint generalizado
                method: 'POST',
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
    // Fim do script para reportar problema
    </script>
</body>
</html>
