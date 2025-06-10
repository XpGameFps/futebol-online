<?php
// admin/csrf_utils.php
if (session_status() == PHP_SESSION_NONE) {
    // It's generally expected that session_start() is called before this,
    // but as a fallback for direct script access or testing.
    session_start();
}

/**
 * Generates a CSRF token, stores it in the session, and returns it.
 * If a token already exists in the session, it returns that token
 * unless $force_regenerate is true.
 *
 * @param bool $force_regenerate If true, a new token will be generated even if one exists.
 * @return string The CSRF token.
 */
function generate_csrf_token(bool $force_regenerate = false): string {
    if (!$force_regenerate && isset($_SESSION['csrf_token'])) {
        return $_SESSION['csrf_token'];
    }
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}

/**
 * Validates the submitted CSRF token against the one stored in the session.
 * To prevent replay attacks, the token in the session is cleared after successful validation.
 *
 * @param string|null $token_from_form The token submitted with the form.
 * @return bool True if the token is valid, false otherwise.
 */
function validate_csrf_token(?string $token_from_form): bool {
    if (empty($token_from_form)) {
        return false;
    }

    if (!isset($_SESSION['csrf_token'])) {
        // No token in session, so validation fails
        return false;
    }

    $session_token = $_SESSION['csrf_token'];

    if (hash_equals($session_token, $token_from_form)) {
        // Token matches. Clear it to prevent reuse for this session's token.
        // For some scenarios, you might want to regenerate it instead of just unsetting,
        // especially if the user is expected to perform multiple protected actions
        // on the same page load without reloading. For simplicity here, we unset.
        // A more robust approach for multi-action pages might involve multiple tokens
        // or AJAX-refreshed tokens.
        unset($_SESSION['csrf_token']);
        return true;
    }

    return false;
}
?>
