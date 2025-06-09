<?php
require_once '../config.php'; // Para $pdo e credenciais DB

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método de requisição inválido. Use POST.';
    echo json_encode($response);
    exit;
}

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

// Tenta obter o IP do usuário de forma mais robusta
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $user_ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
}
// Limpar e validar o IP
$user_ip = filter_var(explode(',', $user_ip)[0], FILTER_VALIDATE_IP);
if ($user_ip === false) {
    $user_ip = 'IP Inválido';
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    $response['message'] = 'Conexão com banco de dados não estabelecida corretamente.';
    error_log('report_item_issue.php: $pdo não é uma instância de PDO ou não está definido.');
    echo json_encode($response);
    exit;
}

try {
    // Opcional: Validar se o item (canal ou jogo) existe
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
        error_log('report_item_issue.php: Falha ao executar insert. Erro: ' . implode(":", $stmt->errorInfo()));
    }

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'SQLSTATE[HY000] [2002]') !== false || strpos($e->getMessage(), 'SQLSTATE[HY000] [2003]') !== false) {
        $response['message'] = 'Erro de conexão com o banco de dados. Verifique as configurações.';
    } else {
        $response['message'] = 'Erro de banco de dados ao processar o reporte.';
    }
    error_log('report_item_issue.php: PDOException: ' . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'Erro inesperado no servidor.';
    error_log('report_item_issue.php: General Exception: ' . $e->getMessage());
}

echo json_encode($response);
?>
