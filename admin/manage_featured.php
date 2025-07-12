<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../../FutOnline_config/config.php';

$matches = $pdo->query("SELECT id, description FROM matches ORDER BY match_time DESC")->fetchAll(PDO::FETCH_ASSOC);
$leagues = $pdo->query("SELECT id, name FROM leagues ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_type = $_POST['item_type'];
    $item_id = !empty($_POST['item_id']) ? (int)$_POST['item_id'] : null;
    $custom_title = trim($_POST['custom_title'] ?? '');
    $custom_url = trim($_POST['custom_url'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $active = isset($_POST['active']) ? 1 : 0;
    $custom_image = null;

    if ($item_type === 'custom' && isset($_FILES['custom_image']) && $_FILES['custom_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['custom_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('featured_', true) . '.' . $ext;
        if (!is_dir('../uploads/featured/')) {
            mkdir('../uploads/featured/', 0755, true);
        }
        move_uploaded_file($_FILES['custom_image']['tmp_name'], '../uploads/featured/' . $filename);
        $custom_image = $filename;
    }

    $stmt = $pdo->prepare("INSERT INTO featured_items (item_type, item_id, custom_title, custom_image, custom_url, sort_order, active) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$item_type, $item_id, $custom_title, $custom_image, $custom_url, $sort_order, $active]);
    header("Location: manage_featured.php");
    exit;
}

$featured = $pdo->query("SELECT * FROM featured_items ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>Destaques/Carrossel</h2>
<form method="POST" enctype="multipart/form-data">
    <label>Tipo:</label>
    <select name="item_type" required>
        <option value="match">Jogo</option>
        <option value="league">Liga</option>
        <option value="custom">Personalizado</option>
    </select>
    <label>ID do item (para jogo/liga):</label>
    <select name="item_id">
        <option value="">Selecione...</option>
        <?php foreach ($matches as $m): ?>
            <option value="<?php echo $m['id']; ?>">Jogo: <?php echo htmlspecialchars($m['description']); ?></option>
        <?php endforeach; ?>
        <?php foreach ($leagues as $l): ?>
            <option value="<?php echo $l['id']; ?>">Liga: <?php echo htmlspecialchars($l['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <label>Título personalizado:</label>
    <input type="text" name="custom_title">
    <label>Imagem personalizada:</label>
    <input type="file" name="custom_image">
    <label>URL personalizada:</label>
    <input type="url" name="custom_url">
    <label>Ordem:</label>
    <input type="number" name="sort_order" value="0">
    <label>Ativo:</label>
    <input type="checkbox" name="active" checked>
    <button type="submit">Adicionar Destaque</button>
</form>
<ul>
    <?php foreach ($featured as $f): ?>
        <li>
            <?php echo htmlspecialchars($f['item_type']); ?> 
            <?php echo htmlspecialchars($f['custom_title'] ?: $f['item_id']); ?> 
            <?php if ($f['active']): ?>[Ativo]<?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
