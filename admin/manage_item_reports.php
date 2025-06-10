<?php
require_once 'auth_check.php'; // Autenticação do Admin
require_once '../config.php';   // Conexão PDO

if (!function_exists('generate_csrf_token')) {
    require_once 'csrf_utils.php';
}
$csrf_token = generate_csrf_token(); // Single token for all update forms on this page load

$message = '';
$error_message = '';

// Lógica para atualizar status do reporte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id']) && isset($_POST['new_status'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $error_message = "Falha na verificação de segurança (CSRF). Por favor, tente novamente.";
        $csrf_token = generate_csrf_token(true); // Regenerate for re-render
    } else {
        // CSRF PASSED - PROCEED WITH ORIGINAL LOGIC
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
                error_log("PDOException in " . __FILE__ . " (updating report status for report ID " . $report_id . "): " . $e->getMessage());
                $error_message = "Ocorreu um erro no banco de dados ao atualizar o status do reporte. Por favor, tente novamente.";
            }
        } else {
            // This else should only trigger if the data is invalid, *after* CSRF passes.
            // It should not overwrite a CSRF error message.
            if (empty($error_message)) {
                 $error_message = "Dados inválidos para atualização de status.";
            }
        }
    } // End CSRF else
} // End POST check

// Lógica para buscar reportes
$reports = [];
$filter_status = $_GET['filter_status'] ?? 'all';
$filter_item_type = $_GET['filter_item_type'] ?? 'all'; // 'all', 'channel', 'match'
$sort_order = $_GET['sort'] ?? 'newest';

// Base da Query SQL
$sql_reports = "SELECT
                    pr.id,
                    pr.report_timestamp,
                    pr.user_ip,
                    pr.status,
                    pr.item_id,
                    pr.item_type,
                    CASE
                        WHEN pr.item_type = 'channel' THEN tc.name
                        WHEN pr.item_type = 'match' THEN CONCAT(htm.name, ' vs ', atm.name)
                        ELSE 'N/A'
                    END AS item_name,
                    CASE
                        WHEN pr.item_type = 'channel' THEN tc.id
                        WHEN pr.item_type = 'match' THEN m.id
                        ELSE NULL
                    END AS actual_item_id_for_link
                FROM player_reports pr
                LEFT JOIN tv_channels tc ON pr.item_type = 'channel' AND pr.item_id = tc.id
                LEFT JOIN matches m ON pr.item_type = 'match' AND pr.item_id = m.id
                LEFT JOIN teams htm ON m.home_team_id = htm.id
                LEFT JOIN teams atm ON m.away_team_id = atm.id";

$where_clauses = [];
$params = [];

if (in_array($filter_status, ['new', 'viewed', 'resolved'])) {
    $where_clauses[] = "pr.status = :status_filter";
    $params[':status_filter'] = $filter_status;
}
if (in_array($filter_item_type, ['channel', 'match'])) {
    $where_clauses[] = "pr.item_type = :item_type_filter";
    $params[':item_type_filter'] = $filter_item_type;
}

if (!empty($where_clauses)) {
    $sql_reports .= " WHERE " . implode(" AND ", $where_clauses);
}

if ($sort_order === 'oldest') {
    $sql_reports .= " ORDER BY pr.report_timestamp ASC";
} else {
    $sql_reports .= " ORDER BY pr.report_timestamp DESC"; // Default to newest
}

try {
    $stmt_reports = $pdo->prepare($sql_reports);
    $stmt_reports->execute($params); // Passar os parâmetros para execute
    $reports = $stmt_reports->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("PDOException in " . __FILE__ . " (fetching reports list): " . $e->getMessage());
    $error_message = "Ocorreu um erro no banco de dados ao buscar os reportes. Por favor, tente novamente.";
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin - Gerenciar Reportes de Itens</title>
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        /* Estilos existentes + novos se necessário */
        .filter-form { margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px; }
        .filter-form label { margin-right: 5px; margin-left:10px; }
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
        .item-type-label {
            display: inline-block;
            padding: 0.2em 0.5em;
            font-size: 0.8em;
            border-radius: 0.25em;
            color: white;
        }
        .item-type-channel { background-color: #17a2b8; } /* Info blue */
        .item-type-match { background-color: #6f42c1; } /* Indigo */
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
                <a href="manage_item_reports.php" class="active-nav">Reportes de Itens</a> <!-- Link Atualizado -->
                <a href="manage_settings.php">Configurações</a>
            </div>
            <div class="nav-user-info">
                 Usuário: <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?> |
                <a href="logout.php" class="logout-link">Logout</a>
            </div>
        </nav>

        <h1>Gerenciar Reportes de Problemas (Canais e Jogos)</h1>

        <?php if (!empty($message)): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="GET" action="manage_item_reports.php" class="filter-form">
            <label for="filter_status">Status:</label>
            <select name="filter_status" id="filter_status">
                <option value="all" <?php echo ($filter_status === 'all') ? 'selected' : ''; ?>>Todos Status</option>
                <option value="new" <?php echo ($filter_status === 'new') ? 'selected' : ''; ?>>Novos</option>
                <option value="viewed" <?php echo ($filter_status === 'viewed') ? 'selected' : ''; ?>>Visualizados</option>
                <option value="resolved" <?php echo ($filter_status === 'resolved') ? 'selected' : ''; ?>>Resolvidos</option>
            </select>

            <label for="filter_item_type">Tipo de Item:</label>
            <select name="filter_item_type" id="filter_item_type">
                <option value="all" <?php echo ($filter_item_type === 'all') ? 'selected' : ''; ?>>Todos Tipos</option>
                <option value="channel" <?php echo ($filter_item_type === 'channel') ? 'selected' : ''; ?>>Canal</option>
                <option value="match" <?php echo ($filter_item_type === 'match') ? 'selected' : ''; ?>>Jogo</option>
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
                        <th>Tipo</th>
                        <th>Item Reportado (ID)</th>
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
                                <span class="item-type-label item-type-<?php echo htmlspecialchars($report['item_type']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($report['item_type'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $item_link = '#';
                                if ($report['item_type'] === 'channel' && $report['actual_item_id_for_link']) {
                                    $item_link = "../channel_player.php?id=" . htmlspecialchars($report['actual_item_id_for_link']);
                                } elseif ($report['item_type'] === 'match' && $report['actual_item_id_for_link']) {
                                    $item_link = "../match.php?id=" . htmlspecialchars($report['actual_item_id_for_link']);
                                }
                                ?>
                                <a href="<?php echo $item_link; ?>" target="_blank">
                                    <?php echo htmlspecialchars($report['item_name'] ?: 'Detalhes Indisponíveis'); ?> (ID: <?php echo htmlspecialchars($report['item_id']); ?>)
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars(date('d/m/Y H:i:s', strtotime($report['report_timestamp']))); ?></td>
                            <td><?php echo htmlspecialchars($report['user_ip'] ?? 'N/A'); ?></td>
                            <td class="status-<?php echo htmlspecialchars($report['status']); ?>">
                                <?php echo htmlspecialchars(ucfirst($report['status'])); ?>
                            </td>
                            <td class="action-buttons">
                                <form method="POST" action="manage_item_reports.php?filter_status=<?php echo htmlspecialchars($filter_status); ?>&filter_item_type=<?php echo htmlspecialchars($filter_item_type); ?>&sort=<?php echo htmlspecialchars($sort_order); ?>" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
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
