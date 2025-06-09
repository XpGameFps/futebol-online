<?php
// templates/footer.php
?>
<footer class="site-footer-main">
    <div class="footer-container">
        <p>&copy; <?php echo date("Y"); ?> FutOnline. Todos os direitos reservados.</p>
        <?php // Link para política de privacidade/cookies pode ser adicionado aqui ?>
    </div>
</footer>

<div id="cookieConsentBanner" class="cookie-consent-banner">
    <p>Este site utiliza cookies para melhorar a sua experiência. Ao continuar navegando, você concorda com o nosso uso de cookies.
        <?php // <a href="/politica-de-cookies.php">Saiba mais</a> ?>
    </p>
    <button id="acceptCookieConsent">Entendi!</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const consentBanner = document.getElementById('cookieConsentBanner');
    const acceptButton = document.getElementById('acceptCookieConsent');

    // Check if consent was already given
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
