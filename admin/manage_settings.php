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
        $new_text = $_POST['cookie_banner_text'] ?? '';

        try {
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
        $current_cookie_banner_text = 'Este site utiliza cookies para melhorar a sua experiência. Ao continuar navegando, você concorda com o nosso uso de cookies.';
    }
} catch (PDOException $e) {
    $message .= '<p style="color:red;">Erro ao buscar configuração do banner de cookies: ' . $e->getMessage() . '</p>';
    $current_cookie_banner_text = 'Este site utiliza cookies para melhorar a sua experiência. Ao continuar navegando, você concorda com o nosso uso de cookies.';
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - Painel Admin</title>
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>
    <div class="container">
        <nav>
            <div>
                <a href="index.php">Painel Principal (Jogos)</a>
                <a href="manage_leagues.php">Gerenciar Ligas</a>
                <a href="manage_channels.php">Gerenciar Canais TV</a>
                <a href="manage_teams.php">Gerenciar Times</a>
                <a href="manage_saved_streams.php">Biblioteca de Streams</a>
                <a href="manage_item_reports.php">Reportes de Itens</a>
                <a href="manage_settings.php">Configurações</a>
            </div>
            <div class="nav-user-info">
                <span id="online-users-indicator" style="margin-right: 15px; color: #007bff; font-weight:bold;">
                    Online: <span id="online-users-count">--</span>
                </span>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const onlineUsersCountElement = document.getElementById('online-users-count');

    function fetchOnlineUsers() {
        if (!onlineUsersCountElement) return;

        fetch('get_online_users.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' . response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data && data.status === 'success') {
                    onlineUsersCountElement.textContent = data.online_count;
                } else {
                    onlineUsersCountElement.textContent = '--';
                }
            })
            .catch(error => {
                onlineUsersCountElement.textContent = 'Err';
                console.error('Fetch error for online users:', error);
            });
    }
    fetchOnlineUsers();
    setInterval(fetchOnlineUsers, 30000);
});
</script>
</body>
</html>
