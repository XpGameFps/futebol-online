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

?>
<footer class="site-footer-main">
    <div class="footer-container">
        <p>&copy; <?php echo date("Y"); ?> FutOnline. Todos os direitos reservados.</p>
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
