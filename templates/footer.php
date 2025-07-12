<?php

if (!isset($pdo) && file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

$cookie_banner_text_from_db = '';
$default_cookie_banner_text = 'Este site utiliza cookies para melhorar a sua experiência. Ao continuar navegando, você concorda com o nosso uso de cookies.';

if (isset($pdo)) {
    try {
        $cookie_banner_text_key = 'cookie_banner_text';
        $stmt_get_text = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = :key");
        $stmt_get_text->bindParam(':key', $cookie_banner_text_key, PDO::PARAM_STR);
        $stmt_get_text->execute();
        $result = $stmt_get_text->fetch(PDO::FETCH_ASSOC);
        if ($result && !empty($result['setting_value'])) {
            $cookie_banner_text_from_db = $result['setting_value'];
        } else {
            $cookie_banner_text_from_db = $default_cookie_banner_text;
        }
    } catch (PDOException $e) {
                        $cookie_banner_text_from_db = $default_cookie_banner_text;
    }
} else {
        $cookie_banner_text_from_db = $default_cookie_banner_text;
}

// Carregar links sociais do banco
$social_links = [
    'facebook' => '',
    'instagram' => '',
    'twitter' => '',
    'youtube' => ''
];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('social_facebook','social_instagram','social_twitter','social_youtube')");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            switch ($row['setting_key']) {
                case 'social_facebook': $social_links['facebook'] = $row['setting_value']; break;
                case 'social_instagram': $social_links['instagram'] = $row['setting_value']; break;
                case 'social_twitter': $social_links['twitter'] = $row['setting_value']; break;
                case 'social_youtube': $social_links['youtube'] = $row['setting_value']; break;
            }
        }
    } catch (PDOException $e) {}
}
?>
<footer class="site-footer-main">
    <div class="footer-container">
        <p>&copy; <?php echo date("Y"); ?> FutOnline. Todos os direitos reservados.</p>
        <div class="footer-social">
            <?php if ($social_links['facebook']): ?>
                <a href="<?php echo htmlspecialchars($social_links['facebook']); ?>" target="_blank" rel="noopener" title="Facebook"><img src="uploads/site/facebook.svg" alt="Facebook" style="height:24px;"></a>
            <?php endif; ?>
            <?php if ($social_links['instagram']): ?>
                <a href="<?php echo htmlspecialchars($social_links['instagram']); ?>" target="_blank" rel="noopener" title="Instagram"><img src="uploads/site/instagram.svg" alt="Instagram" style="height:24px;"></a>
            <?php endif; ?>
            <?php if ($social_links['twitter']): ?>
                <a href="<?php echo htmlspecialchars($social_links['twitter']); ?>" target="_blank" rel="noopener" title="Twitter"><img src="uploads/site/twitter.svg" alt="Twitter" style="height:24px;"></a>
            <?php endif; ?>
            <?php if ($social_links['youtube']): ?>
                <a href="<?php echo htmlspecialchars($social_links['youtube']); ?>" target="_blank" rel="noopener" title="YouTube"><img src="uploads/site/youtube.svg" alt="YouTube" style="height:24px;"></a>
            <?php endif; ?>
        </div>
    </div>
</footer>

<div id="cookieConsentBanner" class="cookie-consent-banner">
    <p><?php echo htmlspecialchars($cookie_banner_text_from_db); ?></p>
    <button id="acceptCookieConsent">Entendi!</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const consentBanner = document.getElementById('cookieConsentBanner');
    const acceptButton = document.getElementById('acceptCookieConsent');

        if (!localStorage.getItem('cookieConsentGiven')) {
        if (consentBanner) {
            consentBanner.style.display = 'block';
        }
    }

    if (acceptButton) {
        acceptButton.addEventListener('click', function() {
            localStorage.setItem('cookieConsentGiven', 'true');
            if (consentBanner) {
                consentBanner.style.display = 'none';
            }
        });
    }
});
</script>
