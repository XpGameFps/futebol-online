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
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h1 { text-align: center; color: #333; margin-bottom: 20px; }
        form div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 1em; }
        button[type="submit"] { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; width: 100%; transition: background-color 0.3s ease; }
        button[type="submit"]:hover { background-color: #0056b3; }
        .error-message { text-align:center; margin-bottom:15px; }
    </style>
</head>
<body>
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
