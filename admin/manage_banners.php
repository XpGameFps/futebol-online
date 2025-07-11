<?php
require_once __DIR__ . '/../../FutOnline_config/config.php';
require_once 'auth_check.php';
require_once 'csrf_utils.php'; 
$admin_base_path = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'])));
if ($admin_base_path === '/') $admin_base_path = '';

define('ADMIN_WEB_BANNER_PATH', $admin_base_path . '/uploads/banners/');

$stmt = $pdo->query("SELECT * FROM banners ORDER BY created_at DESC");
$banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Gerenciar Banners";
?>
<?php include 'templates/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'templates/navigation.php'; ?>
        <main role="main" class="main-content col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $page_title; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add_banner.php" class="btn btn-sm btn-outline-primary">
                        <span data-feather="plus-circle"></span>
                        Adicionar Novo Banner
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Conteúdo do Banner/Anúncio</th>
                            <th>Tipo de Anúncio</th>
                            <th>URL Alvo</th>
                            <th>Texto Alt</th>
                            <th>Ativo</th>
                            <th>Homepage</th>
                            <th>Pág. Jogo</th>
                            <th>Pág. TV</th>
                            <th>Player Jogo (E/D)</th>
                            <th>Player TV (E/D)</th>
                            <th>Criado Em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($banners)): ?>
                            <tr>
                                <td colspan="13" class="text-center">Nenhum banner encontrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php
                                $adTypeDisplayNames = [
                                    'image' => 'Imagem',
                                    'popup_script' => 'Script Pop-up',
                                    'banner_script' => 'Script Banner'
                                ];
                            ?>
                            <?php foreach ($banners as $banner): ?>
                                <?php $current_ad_type = $banner['ad_type'] ?? 'image'; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($banner['id']); ?></td>
                                    <td>
                                        <?php if ($current_ad_type === 'image'): ?>
                                            <?php if (!empty($banner['image_path'])): ?>
                                                <img src="<?php echo ADMIN_WEB_BANNER_PATH . htmlspecialchars($banner['image_path']); ?>" alt="<?php echo htmlspecialchars($banner['alt_text'] ?? 'Banner'); ?>" class="banner-list-thumbnail">
                                            <?php else: ?>
                                                Sem imagem
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <code title="<?php echo htmlspecialchars($banner['ad_code']); ?>">
                                                <?php echo htmlspecialchars(substr($banner['ad_code'] ?? '', 0, 50)) . (strlen($banner['ad_code'] ?? '') > 50 ? '...' : ''); ?>
                                            </code>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($adTypeDisplayNames[$current_ad_type] ?? ucfirst($current_ad_type)); ?></td>
                                    <td>
                                        <?php if ($current_ad_type === 'image' && !empty($banner['target_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($banner['target_url']); ?>" target="_blank"><?php echo htmlspecialchars(substr($banner['target_url'], 0, 30) . (strlen($banner['target_url']) > 30 ? '...' : '')); ?></a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($current_ad_type === 'image'): ?>
                                            <?php echo htmlspecialchars($banner['alt_text'] ?? 'N/A'); ?>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form action="actions/toggle_banner_status.php" method="POST" style="display: inline;">
                                            <?php echo generate_csrf_input(); ?>
                                            <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $banner['is_active']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $banner['is_active'] ? 'btn-success' : 'btn-warning'; ?>">
                                                <?php echo $banner['is_active'] ? 'Sim' : 'Não'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td><?php echo $banner['display_on_homepage'] ? 'Sim' : 'Não'; ?></td>
                                    <td><?php echo $banner['display_on_match_page'] ? 'Sim' : 'Não'; ?></td>
                                    <td><?php echo $banner['display_on_tv_page'] ? 'Sim' : 'Não'; ?></td>
                                    <td>
                                        <?php echo ($banner['display_match_player_left'] ?? 0) ? 'Sim' : 'Não'; ?> /
                                        <?php echo ($banner['display_match_player_right'] ?? 0) ? 'Sim' : 'Não'; ?>
                                    </td>
                                    <td>
                                        <?php echo ($banner['display_tv_player_left'] ?? 0) ? 'Sim' : 'Não'; ?> /
                                        <?php echo ($banner['display_tv_player_right'] ?? 0) ? 'Sim' : 'Não'; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($banner['created_at']))); ?></td>
                                    <td>
                                        <a href="edit_banner.php?id=<?php echo $banner['id']; ?>" class="btn btn-sm btn-info">
                                            <span data-feather="edit"></span> Editar
                                        </a>
                                        <form action="actions/delete_banner.php" method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este banner? Esta ação não pode ser desfeita.');">
                                            <?php echo generate_csrf_input(); ?>
                                            <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                            <input type="hidden" name="image_path" value="<?php echo htmlspecialchars($banner['image_path']); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <span data-feather="trash-2"></span> Excluir
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

