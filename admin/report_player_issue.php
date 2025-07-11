<?php
require_once __DIR__ . '/../../FutOnline_config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método de requisição inválido. Use POST.';
    echo json_encode($response);
    exit;
}

$channel_id = isset($_POST['channel_id']) ? (int)$_POST['channel_id'] : 0;
if ($channel_id <= 0) {
    $response['message'] = 'ID do canal inválido.';
    echo json_encode($response);
    exit;
}

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
    $response['message'] = 'Conexão com banco de dados não estabelecida corretamente. Verifique config.php e logs do servidor.';
    error_log('report_player_issue.php: $pdo não é uma instância de PDO ou não está definido após incluir config.php.');
    echo json_encode($response);
    exit;
}

try {
    $stmt_check_channel = $pdo->prepare("SELECT id FROM tv_channels WHERE id = :channel_id");
    $stmt_check_channel->bindParam(':channel_id', $channel_id, PDO::PARAM_INT);
    $stmt_check_channel->execute();
    if ($stmt_check_channel->rowCount() == 0) {
        $response['message'] = 'Canal não encontrado no banco de dados (ID: ' . $channel_id . ').';
        echo json_encode($response);
        exit;
    }
    $sql = "INSERT INTO player_reports (channel_id, user_ip, status) VALUES (:channel_id, :user_ip, 'new')";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':channel_id', $channel_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_ip', $user_ip, PDO::PARAM_STR);
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Reporte enviado com sucesso!';
    } else {
        $response['message'] = 'Erro ao salvar o reporte no banco de dados.';
    }
} catch (PDOException $e) {
    $response['message'] = 'Erro no banco de dados: ' . $e->getMessage();
}
echo json_encode($response);
?>

