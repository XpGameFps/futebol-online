<?php
require_once '../config.php';
require_once 'auth_check.php';
require_once 'csrf_utils.php';

$page_title = "Editar Banner";
$errors = [];
$banner_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$banner = null;
$input = ['target_url' => '', 'alt_text' => '', 'is_active' => 1, 'display_on_homepage' => 0, 'display_on_match_page' => 0, 'display_on_tv_page' => 0, 'current_image_path' => ''];

if ($banner_id <= 0) {
    $_SESSION['error_message'] = "ID de banner inválido.";
    header("Location: manage_banners.php");
    exit;
}

// Fetch existing banner data
try {
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = :id");
    $stmt->bindParam(':id', $banner_id, PDO::PARAM_INT);
    $stmt->execute();
    $banner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$banner) {
        $_SESSION['error_message'] = "Banner não encontrado.";
        header("Location: manage_banners.php");
        exit;
    }
    // Initialize input with fetched data
    $input['target_url'] = $banner['target_url'];
    $input['alt_text'] = $banner['alt_text'];
    $input['is_active'] = $banner['is_active'];
    $input['display_on_homepage'] = $banner['display_on_homepage'];
    $input['display_on_match_page'] = $banner['display_on_match_page'];
    $input['display_on_tv_page'] = $banner['display_on_tv_page'];
    $input['current_image_path'] = $banner['image_path'];

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erro ao buscar banner: " . $e->getMessage();
    header("Location: manage_banners.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = "Falha na verificação CSRF. Por favor, tente novamente.";
    } else {
        $input['target_url'] = trim($_POST['target_url'] ?? $banner['target_url']);
        $input['alt_text'] = trim($_POST['alt_text'] ?? $banner['alt_text']);
        $input['is_active'] = isset($_POST['is_active']) ? 1 : 0;
        $input['display_on_homepage'] = isset($_POST['display_on_homepage']) ? 1 : 0;
        $input['display_on_match_page'] = isset($_POST['display_on_match_page']) ? 1 : 0;
        $input['display_on_tv_page'] = isset($_POST['display_on_tv_page']) ? 1 : 0;
        // Keep current image path unless a new one is uploaded
        $image_path_db = $banner['image_path'];

        // Validate inputs
        if (empty($input['target_url']) || !filter_var($input['target_url'], FILTER_VALIDATE_URL)) {
            $errors[] = "URL Alvo é obrigatória e deve ser uma URL válida.";
        }

        // Image upload validation (if a new image is provided)
        $upload_dir = '../uploads/banners/';
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == UPLOAD_ERR_OK && !empty($_FILES['banner_image']['name'])) {
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true); // Should already exist from add_banner.php
            }
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = mime_content_type($_FILES['banner_image']['tmp_name']);

            if (!in_array($file_type, $allowed_types)) {
                $errors[] = "Tipo de arquivo inválido. Apenas JPG, PNG, GIF, WEBP são permitidos.";
            } elseif ($_FILES['banner_image']['size'] > 2 * 1024 * 1024) { // Max 2MB
                $errors[] = "O arquivo da imagem é muito grande. Máximo de 2MB.";
            } else {
                $file_extension = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
                $unique_filename = 'banner_' . uniqid() . '_' . time() . '.' . $file_extension;
                $destination = $upload_dir . $unique_filename;

                if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $destination)) {
                    // Delete old image if a new one is successfully uploaded
                    if ($banner['image_path'] && file_exists($upload_dir . $banner['image_path'])) {
                        unlink($upload_dir . $banner['image_path']);
                    }
                    $image_path_db = $unique_filename; // Update to new filename
                    $input['current_image_path'] = $image_path_db; // Update for display if form reloads
                } else {
                    $errors[] = "Falha ao mover o arquivo enviado. Verifique as permissões do diretório.";
                }
            }
        } elseif (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['banner_image']['error'] != UPLOAD_ERR_OK) {
             $errors[] = "Erro no upload da imagem: Código " . $_FILES['banner_image']['error'];
        }


        if (empty($errors)) {
            try {
                $sql = "UPDATE banners SET
                            image_path = :image_path,
                            target_url = :target_url,
                            alt_text = :alt_text,
                            is_active = :is_active,
                            display_on_homepage = :display_on_homepage,
                            display_on_match_page = :display_on_match_page,
                            display_on_tv_page = :display_on_tv_page
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':image_path', $image_path_db, PDO::PARAM_STR);
                $stmt->bindParam(':target_url', $input['target_url'], PDO::PARAM_STR);
                $stmt->bindParam(':alt_text', $input['alt_text'], PDO::PARAM_STR);
                $stmt->bindParam(':is_active', $input['is_active'], PDO::PARAM_INT);
                $stmt->bindParam(':display_on_homepage', $input['display_on_homepage'], PDO::PARAM_INT);
                $stmt->bindParam(':display_on_match_page', $input['display_on_match_page'], PDO::PARAM_INT);
                $stmt->bindParam(':display_on_tv_page', $input['display_on_tv_page'], PDO::PARAM_INT);
                $stmt->bindParam(':id', $banner_id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Banner atualizado com sucesso!";
                    header("Location: manage_banners.php");
                    exit;
                } else {
                    $errors[] = "Erro ao atualizar banner no banco de dados.";
                }
            } catch (PDOException $e) {
                $errors[] = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
}
?>

<?php include 'templates/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'templates/navigation.php'; ?>
        <main role="main" class="main-content col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $page_title; ?> (ID: <?php echo $banner_id; ?>)</h1>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="edit_banner.php?id=<?php echo $banner_id; ?>" method="POST" enctype="multipart/form-data">
                <?php echo generate_csrf_input(); ?>

                <div class="form-group">
                    <label>Imagem Atual:</label><br>
                    <?php if (!empty($input['current_image_path'])): ?>
                        <img src="<?php echo '../uploads/banners/' . htmlspecialchars($input['current_image_path']); ?>" alt="<?php echo htmlspecialchars($input['alt_text'] ?? 'Banner Atual'); ?>" style="max-width: 200px; max-height: 100px; margin-bottom: 10px;">
                    <?php else: ?>
                        <p>Nenhuma imagem cadastrada.</p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="banner_image">Alterar Imagem (JPG, PNG, GIF, WEBP - Max 2MB)</label>
                    <input type="file" class="form-control-file" id="banner_image" name="banner_image">
                    <small class="form-text text-muted">Deixe em branco para manter a imagem atual.</small>
                </div>

                <div class="form-group">
                    <label for="target_url">URL Alvo (link)</label>
                    <input type="url" class="form-control" id="target_url" name="target_url" value="<?php echo htmlspecialchars($input['target_url']); ?>" required placeholder="https://exemplo.com">
                </div>

                <div class="form-group">
                    <label for="alt_text">Texto Alternativo (Alt Text)</label>
                    <input type="text" class="form-control" id="alt_text" name="alt_text" value="<?php echo htmlspecialchars($input['alt_text']); ?>" placeholder="Descrição da imagem para acessibilidade">
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo ($input['is_active'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">
                            Ativo (visível no site)
                        </label>
                    </div>
                </div>

                <fieldset class="form-group border p-3">
                    <legend class="w-auto px-2 h6">Exibir em:</legend>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="display_on_homepage" name="display_on_homepage" value="1" <?php echo ($input['display_on_homepage'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="display_on_homepage">
                            Página Inicial
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="display_on_match_page" name="display_on_match_page" value="1" <?php echo ($input['display_on_match_page'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="display_on_match_page">
                            Página de Jogo
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="display_on_tv_page" name="display_on_tv_page" value="1" <?php echo ($input['display_on_tv_page'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="display_on_tv_page">
                            Página de TV Ao Vivo
                        </label>
                    </div>
                </fieldset>

                <button type="submit" class="btn btn-primary mt-3">Salvar Alterações</button>
                <a href="manage_banners.php" class="btn btn-secondary mt-3">Cancelar</a>
            </form>

        </main>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
