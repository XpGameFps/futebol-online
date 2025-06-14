<?php
require_once '../config.php';
require_once 'auth_check.php';
require_once 'csrf_utils.php';

$page_title = "Editar Banner";
$errors = [];
$banner_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$banner = null;
// Add ad_type and ad_code to input, current_image_path is already there.
$input = [
    'target_url' => '',
    'alt_text' => '',
    'is_active' => 1,
    'display_on_homepage' => 0,
    'display_on_match_page' => 0,
    'display_on_tv_page' => 0,
    'current_image_path' => '',
    'ad_type' => 'image', // Default, will be overwritten by fetched banner data
    'ad_code' => '',       // Default, will be overwritten
    'display_match_player_left' => 0, // Default
    'display_match_player_right' => 0, // Default
    'display_tv_player_left' => 0,    // Default
    'display_tv_player_right' => 0     // Default
];

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
    // Populate new fields from fetched banner data
    $input['ad_type'] = $banner['ad_type'] ?? 'image';
    $input['ad_code'] = $banner['ad_code'] ?? '';
    // Populate player-side ad locations from fetched banner data
    $input['display_match_player_left'] = $banner['display_match_player_left'] ?? 0;
    $input['display_match_player_right'] = $banner['display_match_player_right'] ?? 0;
    $input['display_tv_player_left'] = $banner['display_tv_player_left'] ?? 0;
    $input['display_tv_player_right'] = $banner['display_tv_player_right'] ?? 0;

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erro ao buscar banner: " . $e->getMessage();
    header("Location: manage_banners.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = "Falha na verificação CSRF. Por favor, tente novamente.";
    } else {
        $ad_type = trim($_POST['ad_type'] ?? $banner['ad_type']); // Get new ad_type
        $input['ad_type'] = $ad_type; // For repopulating form

        // Initialize variables for DB update
        $ad_code = $banner['ad_code']; // Keep old by default
        $target_url = $banner['target_url'];
        $alt_text = $banner['alt_text'];
        $image_path_db = $banner['image_path']; // Keep old image by default

        // Common fields
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $input['is_active'] = $is_active;
        $display_on_homepage = isset($_POST['display_on_homepage']) ? 1 : 0;
        $input['display_on_homepage'] = $display_on_homepage;
        $display_on_match_page = isset($_POST['display_on_match_page']) ? 1 : 0;
        $input['display_on_match_page'] = $display_on_match_page;
        $display_on_tv_page = isset($_POST['display_on_tv_page']) ? 1 : 0;
        $input['display_on_tv_page'] = $display_on_tv_page;

        // Player-side ad locations from POST
        $display_match_player_left = isset($_POST['display_match_player_left']) ? 1 : 0;
        $input['display_match_player_left'] = $display_match_player_left;
        $display_match_player_right = isset($_POST['display_match_player_right']) ? 1 : 0;
        $input['display_match_player_right'] = $display_match_player_right;
        $display_tv_player_left = isset($_POST['display_tv_player_left']) ? 1 : 0;
        $input['display_tv_player_left'] = $display_tv_player_left;
        $display_tv_player_right = isset($_POST['display_tv_player_right']) ? 1 : 0;
        $input['display_tv_player_right'] = $display_tv_player_right;

        $upload_dir = '../uploads/banners/';

        if ($ad_type === 'popup_script' || $ad_type === 'banner_script') {
            $ad_code = trim($_POST['ad_code'] ?? null);
            $input['ad_code'] = $ad_code; // For repopulating form

            if (empty($ad_code)) {
                $errors[] = "Código do Anúncio é obrigatório para o tipo de anúncio selecionado.";
            }

            // If switching from image to script, delete old image and clear image fields
            if ($banner['ad_type'] === 'image' && !empty($banner['image_path']) && file_exists($upload_dir . $banner['image_path'])) {
                unlink($upload_dir . $banner['image_path']);
            }
            $image_path_db = null;
            $target_url = '';
            $alt_text = '';
            $input['current_image_path'] = null; // Update for display
            $input['target_url'] = '';
            $input['alt_text'] = '';

        } else { // 'image' type
            $target_url = trim($_POST['target_url'] ?? '');
            $alt_text = trim($_POST['alt_text'] ?? '');
            $input['target_url'] = $target_url; // For repopulating form
            $input['alt_text'] = $alt_text; // For repopulating form
            $ad_code = null; // Clear ad_code if switching to image type
            $input['ad_code'] = '';


            if (empty($target_url) || !filter_var($target_url, FILTER_VALIDATE_URL)) {
                $errors[] = "URL Alvo é obrigatória e deve ser uma URL válida para anúncios de imagem.";
            }

            // Image upload validation (if a new image is provided for 'image' type)
            if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == UPLOAD_ERR_OK && !empty($_FILES['banner_image']['name'])) {
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
                        // Delete old image if a new one is successfully uploaded AND it's different from the old one
                        if (!empty($banner['image_path']) && $banner['image_path'] !== $unique_filename && file_exists($upload_dir . $banner['image_path'])) {
                            unlink($upload_dir . $banner['image_path']);
                        }
                        $image_path_db = $unique_filename; // Update to new filename
                        $input['current_image_path'] = $image_path_db; // Update for display
                    } else {
                        $errors[] = "Falha ao mover o arquivo enviado. Verifique as permissões do diretório.";
                    }
                }
            } elseif (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['banner_image']['error'] != UPLOAD_ERR_OK) {
                 $errors[] = "Erro no upload da imagem: Código " . $_FILES['banner_image']['error'];
            }
            // If no new image is uploaded and type is 'image', image_path_db remains $banner['image_path']
            // If switching from script to image and no new image is uploaded, $image_path_db would be null from banner, which needs to be handled (user must upload image)
            if ($ad_type === 'image' && empty($image_path_db) && empty($_FILES['banner_image']['name'])) {
                 $errors[] = "A imagem do banner é obrigatória para o tipo de anúncio 'Imagem' se nenhuma imagem existir.";
            }
        }

        if (empty($errors)) {
            try {
                $sql = "UPDATE banners SET
                            image_path = :image_path,
                            target_url = :target_url,
                            alt_text = :alt_text,
                            ad_type = :ad_type,
                            ad_code = :ad_code,
                            is_active = :is_active,
                            display_on_homepage = :display_on_homepage,
                            display_on_match_page = :display_on_match_page,
                            display_on_tv_page = :display_on_tv_page,
                            display_match_player_left = :display_match_player_left,
                            display_match_player_right = :display_match_player_right,
                            display_tv_player_left = :display_tv_player_left,
                            display_tv_player_right = :display_tv_player_right
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':image_path', $image_path_db, PDO::PARAM_STR);
                $stmt->bindParam(':target_url', $target_url, PDO::PARAM_STR);
                $stmt->bindParam(':alt_text', $alt_text, PDO::PARAM_STR);
                $stmt->bindParam(':ad_type', $ad_type, PDO::PARAM_STR);
                $stmt->bindParam(':ad_code', $ad_code, PDO::PARAM_STR);
                $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
                $stmt->bindParam(':display_on_homepage', $display_on_homepage, PDO::PARAM_INT);
                $stmt->bindParam(':display_on_match_page', $display_on_match_page, PDO::PARAM_INT);
                $stmt->bindParam(':display_on_tv_page', $display_on_tv_page, PDO::PARAM_INT);
                $stmt->bindParam(':display_match_player_left', $display_match_player_left, PDO::PARAM_INT);
                $stmt->bindParam(':display_match_player_right', $display_match_player_right, PDO::PARAM_INT);
                $stmt->bindParam(':display_tv_player_left', $display_tv_player_left, PDO::PARAM_INT);
                $stmt->bindParam(':display_tv_player_right', $display_tv_player_right, PDO::PARAM_INT);
                $stmt->bindParam(':id', $banner_id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    // Update $banner array with new values for consistency if page were re-rendered (though we redirect)
                    $banner['image_path'] = $image_path_db;
                    $banner['target_url'] = $target_url;
                    $banner['alt_text'] = $alt_text;
                    $banner['ad_type'] = $ad_type;
                    $banner['ad_code'] = $ad_code;
                    $banner['is_active'] = $is_active;
                    $banner['display_on_homepage'] = $display_on_homepage;
                    $banner['display_on_match_page'] = $display_on_match_page;
                    $banner['display_on_tv_page'] = $display_on_tv_page;
                    $banner['display_match_player_left'] = $display_match_player_left;
                    $banner['display_match_player_right'] = $display_match_player_right;
                    $banner['display_tv_player_left'] = $display_tv_player_left;
                    $banner['display_tv_player_right'] = $display_tv_player_right;

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
                    <label for="ad_type">Tipo de Anúncio</label>
                    <select class="form-control" id="ad_type" name="ad_type">
                        <option value="image" <?php echo ($input['ad_type'] === 'image') ? 'selected' : ''; ?>>Imagem</option>
                        <option value="popup_script" <?php echo ($input['ad_type'] === 'popup_script') ? 'selected' : ''; ?>>Script Pop-up</option>
                        <option value="banner_script" <?php echo ($input['ad_type'] === 'banner_script') ? 'selected' : ''; ?>>Script Banner</option>
                    </select>
                </div>

                <div id="image_fields_container">
                    <div class="form-group">
                        <label>Imagem Atual:</label><br>
                        <?php if (!empty($input['current_image_path'])): ?>
                            <img src="<?php echo '../uploads/banners/' . htmlspecialchars($input['current_image_path']); ?>" alt="<?php echo htmlspecialchars($input['alt_text'] ?? 'Banner Atual'); ?>" style="max-width: 200px; max-height: 100px; margin-bottom: 10px;">
                        <?php else: ?>
                            <p>Nenhuma imagem cadastrada para este banner.</p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="banner_image">Alterar Imagem (JPG, PNG, GIF, WEBP - Max 2MB)</label>
                        <input type="file" class="form-control-file" id="banner_image" name="banner_image">
                        <small class="form-text text-muted">Deixe em branco para manter a imagem atual, ou selecione uma nova se o tipo for 'Imagem'.</small>
                    </div>

                    <div class="form-group">
                        <label for="target_url">URL Alvo (link)</label>
                        <input type="url" class="form-control" id="target_url" name="target_url" value="<?php echo htmlspecialchars($input['target_url']); ?>" placeholder="https://exemplo.com">
                    </div>

                    <div class="form-group">
                        <label for="alt_text">Texto Alternativo (Alt Text)</label>
                        <input type="text" class="form-control" id="alt_text" name="alt_text" value="<?php echo htmlspecialchars($input['alt_text']); ?>" placeholder="Descrição da imagem para acessibilidade">
                    </div>
                </div>

                <div id="ad_code_container" style="display: none;">
                    <div class="form-group">
                        <label for="ad_code">Código do Anúncio</label>
                        <textarea class="form-control" id="ad_code" name="ad_code" rows="5" placeholder="Cole o script do anúncio aqui"><?php echo htmlspecialchars($input['ad_code']); ?></textarea>
                    </div>
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
                    <hr> <!-- Separator for new options -->
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="display_match_player_left" id="display_match_player_left" value="1" <?php echo (isset($input['display_match_player_left']) && $input['display_match_player_left'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="display_match_player_left">
                            Ao lado esquerdo do player (Jogo)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="display_match_player_right" id="display_match_player_right" value="1" <?php echo (isset($input['display_match_player_right']) && $input['display_match_player_right'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="display_match_player_right">
                            Ao lado direito do player (Jogo)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="display_tv_player_left" id="display_tv_player_left" value="1" <?php echo (isset($input['display_tv_player_left']) && $input['display_tv_player_left'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="display_tv_player_left">
                            Ao lado esquerdo do player (TV)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="display_tv_player_right" id="display_tv_player_right" value="1" <?php echo (isset($input['display_tv_player_right']) && $input['display_tv_player_right'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="display_tv_player_right">
                            Ao lado direito do player (TV)
                        </label>
                    </div>
                </fieldset>

                <button type="submit" class="btn btn-primary mt-3">Salvar Alterações</button>
                <a href="manage_banners.php" class="btn btn-secondary mt-3">Cancelar</a>
            </form>

        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const adTypeSelect = document.getElementById('ad_type');
    const imageFieldsContainer = document.getElementById('image_fields_container');
    const adCodeContainer = document.getElementById('ad_code_container');
    const bannerImageInput = document.getElementById('banner_image'); // Assuming ID of image input is 'banner_image'
    const targetUrlInput = document.getElementById('target_url'); // Assuming ID of target URL input is 'target_url'


    function toggleFields() {
        if (adTypeSelect.value === 'image') {
            imageFieldsContainer.style.display = '';
            adCodeContainer.style.display = 'none';
            // For editing, image is not strictly required if one already exists.
            // Target URL might still be considered required for image type.
            // targetUrlInput.required = true;
            // bannerImageInput.required = false; // Only required if no image exists, handled by server-side
        } else {
            imageFieldsContainer.style.display = 'none';
            adCodeContainer.style.display = '';
            // bannerImageInput.required = false;
            // targetUrlInput.required = false;
        }
    }

    if (adTypeSelect && imageFieldsContainer && adCodeContainer && bannerImageInput && targetUrlInput) {
        adTypeSelect.addEventListener('change', toggleFields);
        toggleFields(); // Initial call
    } else {
        console.error('One or more elements for ad type toggling not found in edit_banner.php.');
        if (!adTypeSelect) console.error('adTypeSelect not found');
        if (!imageFieldsContainer) console.error('imageFieldsContainer not found');
        if (!adCodeContainer) console.error('adCodeContainer not found');
        if (!bannerImageInput) console.error('bannerImageInput not found');
        if (!targetUrlInput) console.error('targetUrlInput not found');
    }
});
</script>

<?php include 'templates/footer.php'; ?>
