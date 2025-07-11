<?php
require_once __DIR__ . '/../../FutOnline_config/config.php';
require_once 'auth_check.php';
require_once 'csrf_utils.php';

$page_title = "Adicionar Novo Banner";
$errors = [];
$input = [
    'target_url' => '',
    'alt_text' => '',
    'is_active' => 1,
    'display_on_homepage' => 0,
    'display_on_match_page' => 0,
    'display_on_tv_page' => 0,
    'ad_type' => 'image',     'ad_code' => '',
    'display_match_player_left' => 0,
    'display_match_player_right' => 0,
    'display_tv_player_left' => 0,
    'display_tv_player_right' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = "Falha na verificação CSRF. Por favor, tente novamente.";
    } else {
                $ad_type = trim($_POST['ad_type'] ?? 'image');
        $input['ad_type'] = $ad_type;

                $ad_code = null;
        $target_url = '';
        $alt_text = '';
        $image_path_db = null; 
                $is_active = isset($_POST['is_active']) ? 1 : 0;
        $input['is_active'] = $is_active;
        $display_on_homepage = isset($_POST['display_on_homepage']) ? 1 : 0;
        $input['display_on_homepage'] = $display_on_homepage;
        $display_on_match_page = isset($_POST['display_on_match_page']) ? 1 : 0;
        $input['display_on_match_page'] = $display_on_match_page;
        $display_on_tv_page = isset($_POST['display_on_tv_page']) ? 1 : 0;
        $input['display_on_tv_page'] = $display_on_tv_page;

                $display_match_player_left = isset($_POST['display_match_player_left']) ? 1 : 0;
        $input['display_match_player_left'] = $display_match_player_left;
        $display_match_player_right = isset($_POST['display_match_player_right']) ? 1 : 0;
        $input['display_match_player_right'] = $display_match_player_right;
        $display_tv_player_left = isset($_POST['display_tv_player_left']) ? 1 : 0;
        $input['display_tv_player_left'] = $display_tv_player_left;
        $display_tv_player_right = isset($_POST['display_tv_player_right']) ? 1 : 0;
        $input['display_tv_player_right'] = $display_tv_player_right;

        if ($ad_type === 'popup_script' || $ad_type === 'banner_script') {
            $ad_code = trim($_POST['ad_code'] ?? null);
            $input['ad_code'] = $ad_code;             if (empty($ad_code)) {
                $errors[] = "Código do Anúncio é obrigatório para o tipo de anúncio selecionado.";
            }
        } else {             $target_url = trim($_POST['target_url'] ?? '');
            $alt_text = trim($_POST['alt_text'] ?? '');
            $input['target_url'] = $target_url;             $input['alt_text'] = $alt_text; 
            if (empty($target_url) || !filter_var($target_url, FILTER_VALIDATE_URL)) {
                $errors[] = "URL Alvo é obrigatória e deve ser uma URL válida para anúncios de imagem.";
            }
                        if (empty($_FILES['banner_image']['name'])) {
                $errors[] = "A imagem do banner é obrigatória para anúncios de imagem.";
            }

            if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/banners/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = mime_content_type($_FILES['banner_image']['tmp_name']);

                if (!in_array($file_type, $allowed_types)) {
                    $errors[] = "Tipo de arquivo inválido. Apenas JPG, PNG, GIF, WEBP são permitidos.";
                } elseif ($_FILES['banner_image']['size'] > 2 * 1024 * 1024) {                     $errors[] = "O arquivo da imagem é muito grande. Máximo de 2MB.";
                } else {
                    $file_extension = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
                    $unique_filename = 'banner_' . uniqid() . '_' . time() . '.' . $file_extension;
                    $destination = $upload_dir . $unique_filename;

                    if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $destination)) {
                        $image_path_db = $unique_filename;                     } else {
                        $errors[] = "Falha ao mover o arquivo enviado. Verifique as permissões do diretório.";
                    }
                }
            } elseif (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] != UPLOAD_ERR_NO_FILE && $ad_type === 'image') {
                 $errors[] = "Erro no upload da imagem: " . $_FILES['banner_image']['error'];
            }
        }

        if (empty($errors)) {
            try {
                $sql = "INSERT INTO banners (image_path, target_url, alt_text, ad_type, ad_code, is_active,
                                           display_on_homepage, display_on_match_page, display_on_tv_page,
                                           display_match_player_left, display_match_player_right,
                                           display_tv_player_left, display_tv_player_right)
                        VALUES (:image_path, :target_url, :alt_text, :ad_type, :ad_code, :is_active,
                                :display_on_homepage, :display_on_match_page, :display_on_tv_page,
                                :display_match_player_left, :display_match_player_right,
                                :display_tv_player_left, :display_tv_player_right)";
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

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Banner adicionado com sucesso!";
                    header("Location: manage_banners.php");
                    exit;
                } else {
                    $errors[] = "Erro ao adicionar banner no banco de dados.";
                }
            } catch (PDOException $e) {
                $errors[] = "Erro no banco de dados: " . $e->getMessage();
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
        <main role="main" class="main-content col-md-9 ml-sm-auto col-lg-10 px-md-4">
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
                    <label for="ad_type">Tipo de Anúncio</label>
                    <select class="form-control" id="ad_type" name="ad_type">
                        <option value="image" <?php echo ($input['ad_type'] === 'image') ? 'selected' : ''; ?>>Imagem</option>
                        <option value="popup_script" <?php echo ($input['ad_type'] === 'popup_script') ? 'selected' : ''; ?>>Script Pop-up</option>
                        <option value="banner_script" <?php echo ($input['ad_type'] === 'banner_script') ? 'selected' : ''; ?>>Script Banner</option>
                    </select>
                </div>

                <div id="image_fields_container">
                    <div class="form-group">
                        <label for="banner_image">Imagem do Banner (JPG, PNG, GIF, WEBP - Max 2MB)</label>
                        <input type="file" class="form-control-file" id="banner_image" name="banner_image">
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
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo ($input['is_active'] == 1) ? 'checked' : ''; ?> >
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
                    <hr>                     <div class="form-check">
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

                <button type="submit" class="btn btn-primary mt-3">Adicionar Banner</button>
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
    const bannerImageInput = document.getElementById('banner_image');
    const targetUrlInput = document.getElementById('target_url');

    function toggleFields() {
        if (adTypeSelect.value === 'image') {
            imageFieldsContainer.style.display = '';
            adCodeContainer.style.display = 'none';
                        bannerImageInput.setAttribute('required', 'required');
            targetUrlInput.setAttribute('required', 'required');
        } else {
            imageFieldsContainer.style.display = 'none';
            adCodeContainer.style.display = '';
                        bannerImageInput.removeAttribute('required');
            targetUrlInput.removeAttribute('required');
        }
    }

    if (adTypeSelect && imageFieldsContainer && adCodeContainer && bannerImageInput && targetUrlInput) {
        adTypeSelect.addEventListener('change', toggleFields);
                toggleFields();
    } else {
        console.error('One or more elements for ad type toggling not found.');
                if (!adTypeSelect) console.error('ad_type select not found');
        if (!imageFieldsContainer) console.error('image_fields_container not found');
        if (!adCodeContainer) console.error('ad_code_container not found');
        if (!bannerImageInput) console.error('banner_image input not found');
        if (!targetUrlInput) console.error('target_url input not found');
    }
});
</script>

<?php include 'templates/footer.php'; ?>

