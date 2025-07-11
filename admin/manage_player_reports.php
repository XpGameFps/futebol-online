<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../../FutOnline_config/config.php';

$message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id']) && isset($_POST['new_status'])) {
    $report_id = (int)$_POST['report_id'];
    $new_status = $_POST['new_status'];
    $allowed_statuses = ['new', 'viewed', 'resolved'];

    if (in_array($new_status, $allowed_statuses) && $report_id > 0) {
        try {
            $sql_update = "UPDATE player_reports SET status = :new_status WHERE id = :report_id";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->bindParam(':new_status', $new_status, PDO::PARAM_STR);
            $stmt_update->bindParam(':report_id', $report_id, PDO::PARAM_INT);

            if ($stmt_update->execute()) {
                $message = "Status do reporte #{$report_id} atualizado para '{$new_status}'.";
            } else {
                $error_message = "Erro ao atualizar status do reporte.";
            }
        } catch (PDOException $e) {
            $error_message = "Erro de banco de dados: " . $e->getMessage();
        }
    } else {
        $error_message = "Dados inválidos para atualização de status.";
    }
}

$reports = [];
$filter_status = $_GET['filter_status'] ?? 'all';
$sort_order = $_GET['sort'] ?? 'newest';
$sql_reports = "SELECT pr.id, pr.report_timestamp, pr.user_ip, pr.status, tc.name AS channel_name, tc.id AS channel_id
                FROM player_reports pr
                JOIN tv_channels tc ON pr.channel_id = tc.id";

$where_clauses = [];
if (in_array($filter_status, ['new', 'viewed', 'resolved'])) {
    $where_clauses[] = "pr.status = :status_filter";
}

if (!empty($where_clauses)) {
    $sql_reports .= " WHERE " . implode(" AND ", $where_clauses);
}

if ($sort_order === 'oldest') {
    $sql_reports .= " ORDER BY pr.report_timestamp ASC";
} else {
    $sql_reports .= " ORDER BY pr.report_timestamp DESC";
}

try {
    $stmt_reports = $pdo->prepare($sql_reports);
    if (in_array($filter_status, ['new', 'viewed', 'resolved'])) {
        $stmt_reports->bindParam(':status_filter', $filter_status, PDO::PARAM_STR);
    }
    $stmt_reports->execute();
    $reports = $stmt_reports->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erro ao buscar reportes: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin - Gerenciar Reportes de Player</title>
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .filter-form { margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px; }
        .filter-form label { margin-right: 10px; }
        .filter-form select, .filter-form button { padding: 8px 12px; border-radius: 3px; border: 1px solid #ddd; }
        .report-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .report-table th, .report-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .report-table th { background-color: #f2f2f2; }
        .report-table tr:nth-child(even) { background-color: #f9f9f9; }
        .status-form select { padding: 5px; }
        .status-new { color: #007bff; font-weight: bold; }
        .status-viewed { color: #ffc107; }
        .status-resolved { color: #28a745; }
        .action-buttons button { font-size: 0.9em; padding: 5px 10px; }
        .message { padding: 10px; margin-bottom:15px; border-radius:4px; }
        .message.success { background-color: #d4edda; color: #155724; border:1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border:1px solid #f5c6cb; }
    </style>
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
                <a href="manage_player_reports.php" class="active-nav">Reportes do Player</a>
                <a href="manage_settings.php">Configurações</a>
            </div>
            <div class="nav-user-info">
                 Usuário: <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?> |
                <a href="logout.php" class="logout-link">Logout</a>
            </div>
        </nav>

        <h1>Gerenciar Reportes de Problemas no Player</h1>

        <?php if (!empty($message)): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="GET" action="manage_player_reports.php" class="filter-form">
            <label for="filter_status">Filtrar por Status:</label>
            <select name="filter_status" id="filter_status">
                <option value="all" <?php echo ($filter_status === 'all') ? 'selected' : ''; ?>>Todos</option>
                <option value="new" <?php echo ($filter_status === 'new') ? 'selected' : ''; ?>>Novos</option>
                <option value="viewed" <?php echo ($filter_status === 'viewed') ? 'selected' : ''; ?>>Visualizados</option>
                <option value="resolved" <?php echo ($filter_status === 'resolved') ? 'selected' : ''; ?>>Resolvidos</option>
            </select>

            <label for="sort">Ordenar por:</label>
            <select name="sort" id="sort">
                <option value="newest" <?php echo ($sort_order === 'newest') ? 'selected' : ''; ?>>Mais Recentes</option>
                <option value="oldest" <?php echo ($sort_order === 'oldest') ? 'selected' : ''; ?>>Mais Antigos</option>
            </select>
            <button type="submit">Filtrar/Ordenar</button>
        </form>

        <?php if (empty($reports) && empty($error_message)): ?>
            <p>Nenhum reporte encontrado para os filtros selecionados.</p>
        <?php elseif (!empty($reports)): ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>ID Reporte</th>
                        <th>Canal (ID)</th>
                        <th>Data/Hora</th>
                        <th>IP Usuário</th>
                        <th>Status Atual</th>
                        <th>Mudar Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($report['id']); ?></td>
                            <td>
                                <a href="../channel_player.php?id=<?php echo htmlspecialchars($report['channel_id']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($report['channel_name'] ?? 'N/A'); ?> (ID: <?php echo htmlspecialchars($report['channel_id']); ?>)
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars(date('d/m/Y H:i:s', strtotime($report['report_timestamp']))); ?></td>
                            <td><?php echo htmlspecialchars($report['user_ip'] ?? 'N/A'); ?></td>
                            <td class="status-<?php echo htmlspecialchars($report['status']); ?>">
                                <?php echo htmlspecialchars(ucfirst($report['status'])); ?>
                            </td>
                            <td class="action-buttons">
                                <form method="POST" action="manage_player_reports.php?filter_status=<?php echo htmlspecialchars($filter_status); ?>&sort=<?php echo htmlspecialchars($sort_order); ?>" style="display:inline;">
                                    <input type="hidden" name="report_id" value="<?php echo htmlspecialchars($report['id']); ?>">
                                    <select name="new_status">
                                        <option value="new" <?php echo ($report['status'] === 'new') ? 'selected' : ''; ?>>Novo</option>
                                        <option value="viewed" <?php echo ($report['status'] === 'viewed') ? 'selected' : ''; ?>>Visualizado</option>
                                        <option value="resolved" <?php echo ($report['status'] === 'resolved') ? 'selected' : ''; ?>>Resolvido</option>
                                    </select>
                                    <button type="submit">Atualizar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>

