<?php
require_once 'config.php'; // Database connection
define('FRONTEND_CHANNELS_LOGO_BASE_PATH', 'uploads/logos/channels/'); // Not used on this page directly, but good for consistency if needed later
define('FRONTEND_MATCH_COVER_BASE_PATH', 'uploads/covers/matches/'); // Not used here

// Fetch Leagues for Header
$header_leagues = [];
if (isset($pdo)) {
    try {
        $stmt_header_leagues = $pdo->query("SELECT id, name FROM leagues ORDER BY name ASC");
        $header_leagues = $stmt_header_leagues->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Silently fail for header leagues or log
    }
}

$channel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$channel = null;
$error_message = '';
$page_specific_title = "Assistir Canal";
$meta_description_content = "Assista canais de TV ao vivo."; // Default
$meta_keywords_content = "tv, ao vivo, online, canal"; // Default


if ($channel_id > 0) {
    try {
        // UPDATED SQL to include SEO fields
        $stmt_channel = $pdo->prepare("SELECT id, name, stream_url, logo_filename, meta_description, meta_keywords FROM tv_channels WHERE id = :channel_id");
        $stmt_channel->bindParam(':channel_id', $channel_id, PDO::PARAM_INT);
        $stmt_channel->execute();
        $channel = $stmt_channel->fetch(PDO::FETCH_ASSOC);

        if ($channel) {
            $page_specific_title = "Assistir: " . htmlspecialchars($channel['name']);
            if (!empty($channel['meta_description'])) {
                $meta_description_content = htmlspecialchars($channel['meta_description']);
            } else { // Fallback meta description
                $meta_description_content = "Assista ao canal " . htmlspecialchars($channel['name']) . " ao vivo online.";
            }
            if (!empty($channel['meta_keywords'])) {
                $meta_keywords_content = htmlspecialchars($channel['meta_keywords']);
            } else { // Fallback meta keywords
                $meta_keywords_content = htmlspecialchars($channel['name']) . ", tv, ao vivo, online";
            }
        } else {
            $error_message = "Canal não encontrado.";
            $page_specific_title = "Canal não encontrado";
        }
    } catch (PDOException $e) {
        $error_message = "Erro ao buscar detalhes do canal: " . $e->getMessage();
        $page_specific_title = "Erro ao buscar canal";
    }
} else {
    $error_message = "ID do canal não especificado.";
    $page_specific_title = "ID do canal inválido";
}

// Fetch Player-Side Ads for TV Page
$tv_player_left_ad_code = null;
if (isset($pdo) && $channel_id > 0) { // Ensure $pdo is available and we have a channel context
    try {
        $stmt_left_ad_tv = $pdo->prepare("SELECT ad_code, image_path, target_url, alt_text, ad_type FROM banners WHERE is_active = 1 AND display_tv_player_left = 1 AND (ad_type = 'image' OR ad_type = 'banner_script') ORDER BY RAND() LIMIT 1");
        $stmt_left_ad_tv->execute();
        $left_ad_banner_tv = $stmt_left_ad_tv->fetch(PDO::FETCH_ASSOC);
        if ($left_ad_banner_tv) {
            if (($left_ad_banner_tv['ad_type'] ?? 'image') === 'image' && !empty($left_ad_banner_tv['image_path'])) {
                $alt = htmlspecialchars($left_ad_banner_tv['alt_text'] ?? 'Banner');
                $target = htmlspecialchars($left_ad_banner_tv['target_url'] ?? '#');
                $img_src = 'uploads/banners/' . htmlspecialchars($left_ad_banner_tv['image_path']);
                $tv_player_left_ad_code = "<a href='{$target}' target='_blank'><img src='{$img_src}' alt='{$alt}' style='width:160px; height:auto;'></a>";
            } elseif ($left_ad_banner_tv['ad_type'] === 'banner_script' && !empty($left_ad_banner_tv['ad_code'])) {
                $tv_player_left_ad_code = $left_ad_banner_tv['ad_code'];
            }
        }
    } catch (PDOException $e) {
        error_log("PDOException fetching left player ad for TV page: " . $e->getMessage());
    }
}

$tv_player_right_ad_code = null;
if (isset($pdo) && $channel_id > 0) { // Ensure $pdo is available and we have a channel context
    try {
        $stmt_right_ad_tv = $pdo->prepare("SELECT ad_code, image_path, target_url, alt_text, ad_type FROM banners WHERE is_active = 1 AND display_tv_player_right = 1 AND (ad_type = 'image' OR ad_type = 'banner_script') ORDER BY RAND() LIMIT 1");
        $stmt_right_ad_tv->execute();
        $right_ad_banner_tv = $stmt_right_ad_tv->fetch(PDO::FETCH_ASSOC);
        if ($right_ad_banner_tv) {
            if (($right_ad_banner_tv['ad_type'] ?? 'image') === 'image' && !empty($right_ad_banner_tv['image_path'])) {
                $alt = htmlspecialchars($right_ad_banner_tv['alt_text'] ?? 'Banner');
                $target = htmlspecialchars($right_ad_banner_tv['target_url'] ?? '#');
                $img_src = 'uploads/banners/' . htmlspecialchars($right_ad_banner_tv['image_path']);
                $tv_player_right_ad_code = "<a href='{$target}' target='_blank'><img src='{$img_src}' alt='{$alt}' style='width:160px; height:auto;'></a>";
            } elseif ($right_ad_banner_tv['ad_type'] === 'banner_script' && !empty($right_ad_banner_tv['ad_code'])) {
                $tv_player_right_ad_code = $right_ad_banner_tv['ad_code'];
            }
        }
    } catch (PDOException $e) {
        error_log("PDOException fetching right player ad for TV page: ". $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_specific_title; ?> - FutOnline</title>
    <?php if (!empty($meta_description_content)): ?>
        <meta name="description" content="<?php echo $meta_description_content; ?>">
    <?php endif; ?>
    <?php if (!empty($meta_keywords_content)): ?>
        <meta name="keywords" content="<?php echo $meta_keywords_content; ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="css/style.css">
    <style>
    .report-issue-button {
        background-color: red;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        margin-top: 10px;
        display: block; /* Para ocupar a largura e facilitar o posicionamento */
        width: fit-content; /* Para ajustar ao conteúdo */
        margin-left: auto; /* Para alinhar à direita se o container permitir */
        margin-right: auto; /* Para centralizar se o container permitir */
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
    }
    .report-feedback.success {
        color: green;
    }
    .report-feedback.error {
        color: red;
    }

    /* Styles for Player-Side Ads (similar to match.php) */
    .tv-player-area-wrapper { /* Specific wrapper for TV page if needed, or reuse .match-player-area-wrapper if identical */
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
    }

    .player-side-ad { /* Shared class */
        width: 160px;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
        /* min-height: 300px; /* Consider if a minimum height is universally desired */
        /* border: 1px solid #333; /* Optional: for visualizing */
    }

    .player-side-ad.left-ad { /* Shared class */
        margin-right: 15px;
    }

    .player-side-ad.right-ad { /* Shared class */
        margin-left: 15px;
    }

    .player-container-main { /* Shared class */
        flex-grow: 1;
    }

    .player-container-main iframe { /* Shared class */
        width: 100%;
        min-height: 450px; /* Adjust as needed */
        border: none;
    }
    </style>
</head>
<body>
    <?php require_once 'templates/header.php'; // $header_leagues is available here ?>

    <main class="main-content">
        <div class="container">
            <?php if (!empty($error_message)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php elseif ($channel): ?>
                <h1 class="page-title-player"><?php echo htmlspecialchars($channel['name']); ?></h1>

                <div class="tv-player-area-wrapper">
                    <div class="player-side-ad left-ad">
                        <?php if (!empty($tv_player_left_ad_code)): ?>
                            <?php echo $tv_player_left_ad_code; ?>
                        <?php endif; ?>
                    </div>
                    <div class="player-container-main">
                        <?php if (!empty($channel['stream_url'])): ?>
                            <iframe id="channelStreamPlayer"
                                    src="<?php echo htmlspecialchars($channel['stream_url']); ?>"
                                    allowfullscreen
                                    allow="autoplay; encrypted-media">
                                    <?php // Consider sandbox attributes if streams are from less trusted sources ?>
                            </iframe>
                        <?php else: ?>
                            <p class="info-message">URL do stream não disponível para este canal.</p>
                        <?php endif; ?>
                    </div>
                    <div class="player-side-ad right-ad">
                        <?php if (!empty($tv_player_right_ad_code)): ?>
                            <?php echo $tv_player_right_ad_code; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($channel && isset($channel['id'])): ?>
                <div class="report-button-container" style="text-align: center; margin-bottom: 15px;"> <!-- Container para centralizar -->
                    <button id="reportProblemBtn" class="report-issue-button" data-item-id="<?php echo htmlspecialchars($channel['id']); ?>" data-item-type="channel">
                        Reportar Problema no Player
                    </button>
                    <div id="reportFeedback" class="report-feedback"></div>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- This case should be caught by $error_message, but as a fallback -->
                <p class="error-message">Não foi possível carregar os detalhes do canal.</p>
            <?php endif; ?>

            <div class="back-link-container">
                <a href="index.php" class="back-link">Voltar para a Homepage</a>
            </div>
        </div>
    </main>

    <?php
    // Fetch Banners for TV Page
    $tv_page_banners = [];
    if (isset($pdo)) {
        try {
            // Refined query for image banners
            $stmt_tv_banners = $pdo->query("SELECT image_path, target_url, alt_text FROM banners WHERE is_active = 1 AND display_on_tv_page = 1 AND (ad_type = 'image' OR ad_type IS NULL) ORDER BY RAND() LIMIT 4");
            $tv_page_banners = $stmt_tv_banners->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PDOException fetching TV page image banners: " . $e->getMessage());
            // Silently fail or log the error, don't break the page
        }
    }

    // Fetch Pop-up Ad Scripts for TV Page
    $popup_ad_scripts_tv = [];
    if (isset($pdo)) {
        try {
            $stmt_popup_ads_tv = $pdo->prepare("SELECT ad_code FROM banners WHERE is_active = 1 AND ad_type = 'popup_script' AND display_on_tv_page = 1");
            $stmt_popup_ads_tv->execute();
            $popup_ad_scripts_tv = $stmt_popup_ads_tv->fetchAll(PDO::FETCH_COLUMN, 0); // Fetch only the ad_code column
        } catch (PDOException $e) {
            error_log("PDOException fetching TV page pop-up ads: " . $e->getMessage());
            // Silently fail or log the error, don't break the page
        }
    }

    if (!empty($tv_page_banners)):
    ?>
    <h4 class="publicidade-label">Publicidade</h4>
    <div class="banner-container">
        <?php foreach ($tv_page_banners as $banner): ?>
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
    // Echo Pop-up Ad Scripts if any exist for TV page
    if (!empty($popup_ad_scripts_tv)) {
        foreach ($popup_ad_scripts_tv as $script_code) {
            echo $script_code; // Output the raw ad code
        }
    }
    ?>

    <?php require_once 'templates/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const reportButton = document.getElementById('reportProblemBtn');
        const reportFeedback = document.getElementById('reportFeedback');

        if (reportButton) {
            reportButton.addEventListener('click', function() {
                const itemId = this.dataset.itemId;
                const itemType = this.dataset.itemType;

                if (!itemId || !itemType) {
                    if (reportFeedback) {
                        reportFeedback.textContent = 'Erro: ID ou Tipo do item não encontrado.';
                        reportFeedback.className = 'report-feedback error';
                    }
                    return;
                }

                this.disabled = true;
                if (reportFeedback) {
                    reportFeedback.textContent = 'Enviando reporte...';
                    reportFeedback.className = 'report-feedback';
                }

                const formData = new FormData();
                formData.append('item_id', itemId);
                formData.append('item_type', itemType);

                fetch('admin/report_item_issue.php', { // Endpoint atualizado
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (reportFeedback) {
                        reportFeedback.textContent = data.message || 'Reporte processado.';
                        if (data.success) {
                            reportFeedback.className = 'report-feedback success';
                        } else {
                            reportFeedback.className = 'report-feedback error';
                        }
                    }
                    // Re-abilitar o botão após um tempo, mesmo se falhar, para permitir nova tentativa
                    // Ou manter desabilitado se o sucesso for o critério.
                    // Por agora, reabilitar após 5 segundos
                    setTimeout(() => {
                        reportButton.disabled = false;
                        if (reportFeedback && data.success) { // Limpa a mensagem de sucesso após um tempo
                             reportFeedback.textContent = '';
                        }
                    }, 5000);
                })
                .catch(error => {
                    console.error('Erro ao reportar problema:', error);
                    if (reportFeedback) {
                        reportFeedback.textContent = 'Erro ao enviar o reporte. Tente novamente mais tarde.';
                        reportFeedback.className = 'report-feedback error';
                    }
                    setTimeout(() => {
                        reportButton.disabled = false;
                    }, 5000);
                });
            });
        }
    });
    </script>
</body>
</html>
