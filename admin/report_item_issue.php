<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php'; // Para $pdo e credenciais DB

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// Verificação do método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método de requisição inválido. Use POST.';
    echo json_encode($response);
    exit;
}

// Validação dos dados de entrada
$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$item_type = isset($_POST['item_type']) ? $_POST['item_type'] : '';
$allowed_item_types = ['channel', 'match'];

if ($item_id <= 0) {
    $response['message'] = 'ID do item inválido.';
    echo json_encode($response);
    exit;
}

if (!in_array($item_type, $allowed_item_types)) {
    $response['message'] = 'Tipo de item inválido.';
    echo json_encode($response);
    exit;
}

// Obtenção do IP do usuário
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $user_ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
}
$user_ip = filter_var(explode(',', $user_ip)[0], FILTER_VALIDATE_IP);
if ($user_ip === false) {
    $user_ip = 'IP Inválido';
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    $response['message'] = 'Conexão com banco de dados não estabelecida corretamente.';
    echo json_encode($response);
    exit;
}

try {
    // Verifica se o item existe
    if ($item_type === 'channel') {
        $stmt_check_item = $pdo->prepare("SELECT id FROM tv_channels WHERE id = :item_id");
    } elseif ($item_type === 'match') {
        $stmt_check_item = $pdo->prepare("SELECT id FROM matches WHERE id = :item_id");
    }
    $stmt_check_item->bindParam(':item_id', $item_id, PDO::PARAM_INT);
    $stmt_check_item->execute();
    
    if ($stmt_check_item->rowCount() == 0) {
        $response['message'] = ucfirst($item_type) . ' não encontrado no banco de dados (ID: ' . $item_id . ').';
        echo json_encode($response);
        exit;
    }

    // Insere o reporte
    $sql = "INSERT INTO player_reports (item_id, item_type, user_ip, status) VALUES (:item_id, :item_type, :user_ip, 'new')";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
    $stmt->bindParam(':item_type', $item_type, PDO::PARAM_STR);
    $stmt->bindParam(':user_ip', $user_ip, PDO::PARAM_STR);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Reporte enviado com sucesso!';
    } else {
        $response['message'] = 'Erro ao salvar o reporte no banco de dados.';
    }

} catch (PDOException $e) {
    $response['message'] = 'Erro de banco de dados ao processar o reporte.';
} catch (Exception $e) {
    $response['message'] = 'Erro inesperado no servidor.';
}

echo json_encode($response);
?>
