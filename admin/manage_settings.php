<?php
require_once 'auth_check.php'; // Ensures admin is logged in
require_once '../config.php'; // Database connection

$page_title = "Configurações do Site";
$message = '';
$cookie_banner_text_key = 'cookie_banner_text';
$current_cookie_banner_text = '';

// Handle form submission to update settings
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['save_cookie_banner'])) {
        $new_text = $_POST['cookie_banner_text'] ?? ''; // Default to empty if not set

        try {
            // Use INSERT ... ON DUPLICATE KEY UPDATE to insert if not exists, or update if exists
            $sql = "INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':key', $cookie_banner_text_key, PDO::PARAM_STR);
            $stmt->bindParam(':value', $new_text, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $message = '<p style="color:green;">Texto do banner de cookies atualizado com sucesso!</p>';
            } else {
                $message = '<p style="color:red;">Erro ao atualizar o texto do banner de cookies.</p>';
            }
        } catch (PDOException $e) {
            $message = '<p style="color:red;">Erro de banco de dados: ' . $e->getMessage() . '</p>';
        }
    }
}

// Fetch current cookie banner text from database
try {
    $stmt_get = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = :key");
    $stmt_get->bindParam(':key', $cookie_banner_text_key, PDO::PARAM_STR);
    $stmt_get->execute();
    $result = $stmt_get->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $current_cookie_banner_text = $result['setting_value'];
    } else {
        // If not found, use a default text (could also be inserted as default in DB)
        $current_cookie_banner_text = 'Este site utiliza cookies para melhorar a sua experiência. Ao continuar navegando, você concorda com o nosso uso de cookies.';
    }
} catch (PDOException $e) {
    $message .= '<p style="color:red;">Erro ao buscar configuração do banner de cookies: ' . $e->getMessage() . '</p>';
    // Fallback to default text in case of DB error during fetch
    $current_cookie_banner_text = 'Este site utiliza cookies para melhorar a sua experiência. Ao continuar navegando, você concorda com o nosso uso de cookies.';
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - Painel Admin</title>
    <!-- Re-use admin styles (copy from admin/index.php or other admin pages) -->
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; padding:0; background-color: #f4f7f6; color: #333; }
        .container { width: 90%; max-width: 1000px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        nav { display: flex; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; justify-content: space-between; }
        nav a { margin-right: 15px; text-decoration: none; color: #007bff; font-weight: bold; }
        nav a:hover { text-decoration: underline; color: #0056b3; }
        .nav-user-info { font-size: 0.9em; color: #555; }
        .logout-link { color: #dc3545; font-weight: bold; text-decoration: none; }
        .logout-link:hover { text-decoration: underline; color: #c82333; }
        h1, h2 { color: #333; }
        h1 { text-align: center; margin-bottom:30px; }
        h2 { margin-top: 30px; border-bottom: 2px solid #007bff; padding-bottom: 5px; color: #007bff;}
        form div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 1em; min-height: 100px; }
        button[type="submit"] { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; transition: background-color 0.3s ease; }
        button[type="submit"]:hover { background-color: #0056b3; }
        .message { margin-bottom: 20px; }
        .message p { padding: 15px; border-radius: 5px; font-weight: bold; margin:0; }
        .message p[style*="color:green;"] { background-color: #d4edda; color: #155724 !important; border: 1px solid #c3e6cb; }
        .message p[style*="color:red;"] { background-color: #f8d7da; color: #721c24 !important; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <nav>
            <div>
                <a href="index.php">Painel Principal (Jogos)</a>
                <a href="manage_leagues.php">Gerenciar Ligas</a>
                <a href="manage_channels.php">Gerenciar Canais TV</a>
                <a href="manage_settings.php">Configurações</a>
            </div>
            <div class="nav-user-info">
                Usuário: <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?> |
                <a href="logout.php" class="logout-link">Logout</a>
            </div>
        </nav>
        <h1><?php echo htmlspecialchars($page_title); ?></h1>

        <?php if(!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <section id="cookie-banner-settings">
            <h2>Texto do Banner de Consentimento de Cookies</h2>
            <form action="manage_settings.php" method="POST">
                <div>
                    <label for="cookie_banner_text">Texto do Banner:</label>
                    <textarea id="cookie_banner_text" name="cookie_banner_text" rows="4"><?php echo htmlspecialchars($current_cookie_banner_text); ?></textarea>
                    <p style="font-size:0.8em; color:#555;">Este texto será exibido no banner de cookies no rodapé do site.</p>
                </div>
                <div>
                    <button type="submit" name="save_cookie_banner">Salvar Texto do Banner</button>
                </div>
            </form>
        </section>

        <?php // Add other settings sections here in the future if needed ?>

    </div>
</body>
</html>
