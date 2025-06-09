<?php
session_start();
// If already logged in, redirect to admin index
if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
    header("Location: index.php");
    exit;
}

$login_error = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == '1') {
        $login_error = '<p style="color:red;">Usuário ou senha inválidos.</p>';
    } elseif ($_GET['error'] == '2') {
        $login_error = '<p style="color:red;">Por favor, faça login para acessar.</p>';
    }
} elseif (isset($_GET['status']) && $_GET['status'] == 'logged_out') {
    $login_error = '<p style="color:green;">Você foi desconectado com sucesso.</p>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Painel Admin</title>
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <h1>Painel Administrativo</h1>
        <?php if (!empty($login_error)): ?>
            <div class="error-message"><?php echo $login_error; ?></div>
        <?php endif; ?>
        <form action="process_login.php" method="POST">
            <div>
                <label for="username">Usuário:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div>
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div>
                <button type="submit">Entrar</button>
            </div>
        </form>
    </div>
</body>
</html>
