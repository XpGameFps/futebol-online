<?php
require_once '../config.php';
require_once 'auth_check.php';
require_once 'utils/csrf_utils.php';

$page_title = "Adicionar Novo Banner";
$errors = [];
$input = ['target_url' => '', 'alt_text' => '', 'is_active' => 1, 'display_on_homepage' => 0, 'display_on_match_page' => 0, 'display_on_tv_page' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = "Falha na verificação CSRF. Por favor, tente novamente.";
    } else {
        $input['target_url'] = trim($_POST['target_url'] ?? '');
        $input['alt_text'] = trim($_POST['alt_text'] ?? '');
        $input['is_active'] = isset($_POST['is_active']) ? 1 : 0;
        $input['display_on_homepage'] = isset($_POST['display_on_homepage']) ? 1 : 0;
        $input['display_on_match_page'] = isset($_POST['display_on_match_page']) ? 1 : 0;
        $input['display_on_tv_page'] = isset($_POST['display_on_tv_page']) ? 1 : 0;

        // Validate inputs
        if (empty($input['target_url']) || !filter_var($input['target_url'], FILTER_VALIDATE_URL)) {
            $errors[] = "URL Alvo é obrigatória e deve ser uma URL válida.";
        }
        if (empty($_FILES['banner_image']['name'])) {
            $errors[] = "A imagem do banner é obrigatória.";
        }

        // Image upload validation
        $image_path_db = null;
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/banners/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
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
                    $image_path_db = $unique_filename; // Store only filename
                } else {
                    $errors[] = "Falha ao mover o arquivo enviado. Verifique as permissões do diretório.";
                }
            }
        } elseif (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] != UPLOAD_ERR_NO_FILE) {
            $errors[] = "Erro no upload da imagem: " . $_FILES['banner_image']['error'];
        }


        if (empty($errors)) {
            try {
                $sql = "INSERT INTO banners (image_path, target_url, alt_text, is_active, display_on_homepage, display_on_match_page, display_on_tv_page)
                        VALUES (:image_path, :target_url, :alt_text, :is_active, :display_on_homepage, :display_on_match_page, :display_on_tv_page)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':image_path', $image_path_db, PDO::PARAM_STR);
                $stmt->bindParam(':target_url', $input['target_url'], PDO::PARAM_STR);
                $stmt->bindParam(':alt_text', $input['alt_text'], PDO::PARAM_STR);
                $stmt->bindParam(':is_active', $input['is_active'], PDO::PARAM_INT);
                $stmt->bindParam(':display_on_homepage', $input['display_on_homepage'], PDO::PARAM_INT);
                $stmt->bindParam(':display_on_match_page', $input['display_on_match_page'], PDO::PARAM_INT);
                $stmt->bindParam(':display_on_tv_page', $input['display_on_tv_page'], PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Banner adicionado com sucesso!";
                    header("Location: manage_banners.php");
                    exit;
                } else {
                    $errors[] = "Erro ao adicionar banner no banco de dados.";
                }
            } catch (PDOException $e) {
                $errors[] = "Erro no banco de dados: " . $e->getMessage();
                // Potentially delete uploaded file if DB insert fails
                if ($image_path_db && file_exists($upload_dir . $image_path_db)) {
                    unlink($upload_dir . $image_path_db);
                }
            }
        }
    }
}
?>

<?php include 'templates/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'templates/navigation.php'; ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $page_title; ?></h1>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="add_banner.php" method="POST" enctype="multipart/form-data">
                <?php echo generate_csrf_input(); ?>

                <div class="form-group">
                    <label for="banner_image">Imagem do Banner (JPG, PNG, GIF, WEBP - Max 2MB)</label>
                    <input type="file" class="form-control-file" id="banner_image" name="banner_image" required>
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

                <button type="submit" class="btn btn-primary mt-3">Adicionar Banner</button>
                <a href="manage_banners.php" class="btn btn-secondary mt-3">Cancelar</a>
            </form>

        </main>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
