<?php
// admin/csrf_utils.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function generate_csrf_token(bool $force_regenerate = false): string {
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }
    
    if (!$force_regenerate && !empty($_SESSION['csrf_tokens'])) {
        return end($_SESSION['csrf_tokens']);
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_tokens'][] = $token;
    
    // Mantém apenas os 5 tokens mais recentes
    if (count($_SESSION['csrf_tokens']) > 5) {
        array_shift($_SESSION['csrf_tokens']);
    }
    
    return $token;
}

function generate_csrf_input(): string {
    $token = generate_csrf_token(); // Use the existing function to get a token
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

function validate_csrf_token(?string $token_from_form): bool {
    if (empty($token_from_form) || !isset($_SESSION['csrf_tokens'])) {
        return false;
    }

    // Verifica contra todos os tokens válidos
    foreach ($_SESSION['csrf_tokens'] as $key => $session_token) {
        if (hash_equals($session_token, $token_from_form)) {
            // Remove apenas o token usado
            unset($_SESSION['csrf_tokens'][$key]);
            return true;
        }
    }

    return false;
}
?>
