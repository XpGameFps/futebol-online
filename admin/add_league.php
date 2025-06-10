<?php
// admin/add_league.php
require_once 'auth_check.php'; // Handles session_start()
require_once '../config.php';

define('LEAGUE_LOGO_UPLOAD_DIR', '../uploads/logos/leagues/');
define('MAX_FILE_SIZE', 1024 * 1024); // 1MB
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF token validation
    if (!function_exists('validate_csrf_token')) { // Should be loaded by auth_check.php
        require_once 'csrf_utils.php';
    }
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['form_error_message']['add_league'] = "Falha na verificação de segurança (CSRF). Por favor, tente novamente.";
        // Regenerate token for the form page if it were to be re-displayed from here,
        // but since we redirect, manage_leagues.php will generate a new one.
        header("Location: manage_leagues.php#add-league-form");
        exit;
    }

    $_SESSION['form_data']['add_league'] = $_POST;
    if (isset($_FILES['logo_file']) && !empty($_FILES['logo_file']['name'])) {
        $_SESSION['form_data']['add_league']['logo_filename_tmp'] = $_FILES['logo_file']['name'];
    }

    $name = trim($_POST["name"] ?? '');

    $logo_filename_to_save = null;
    $upload_error_message = '';

    if (empty($name)) {
        $_SESSION['form_error_message']['add_league'] = "O nome da liga não pode ser vazio.";
        header("Location: manage_leagues.php#add-league-form");
        exit;
    }

    // --- File Upload Handling ---
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['logo_file']['tmp_name'];
        $file_name = $_FILES['logo_file']['name'];
        $file_size = $_FILES['logo_file']['size'];
        $file_type = $_FILES['logo_file']['type'];
        $file_ext_array = explode('.', $file_name);
        $file_extension = strtolower(end($file_ext_array));

        if ($file_size > MAX_FILE_SIZE) { $upload_error_message = 'Arquivo muito grande. Máximo 1MB.'; }
        elseif (!in_array($file_type, $allowed_mime_types)) { $upload_error_message = 'Tipo de arquivo inválido. Apenas PNG, JPG, GIF.'; }
        else {
            // getimagesize check
            $image_info = @getimagesize($file_tmp_path);
            if ($image_info === false) {
                $upload_error_message = 'Arquivo inválido. Conteúdo não reconhecido como imagem.';
            } else {
                // Proceed with move_uploaded_file only if getimagesize passed
                $new_file_name = uniqid('league_', true) . '.' . $file_extension;
                $destination_path = LEAGUE_LOGO_UPLOAD_DIR . $new_file_name;
                if (!is_dir(LEAGUE_LOGO_UPLOAD_DIR)) { @mkdir(LEAGUE_LOGO_UPLOAD_DIR, 0755, true); }
                if (move_uploaded_file($file_tmp_path, $destination_path)) {
                    $logo_filename_to_save = $new_file_name;
                    // Clear tmp filename from session if upload was successful, as we have a saved one
                    if(isset($_SESSION['form_data']['add_league']['logo_filename_tmp'])) {
                        unset($_SESSION['form_data']['add_league']['logo_filename_tmp']);
                    }
                } else { $upload_error_message = 'Falha ao mover arquivo de logo.'; }
            }
        }
    } elseif (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['logo_file']['error'] != UPLOAD_ERR_OK) {
        $upload_error_message = 'Erro no upload do logo. Código: ' . $_FILES['logo_file']['error'];
    }

    if (!empty($upload_error_message)) {
        $_SESSION['form_error_message']['add_league'] = $upload_error_message;
        header("Location: manage_leagues.php#add-league-form");
        exit;
    }
    // --- End File Upload Handling ---

    try {
        $checkSql = "SELECT id FROM leagues WHERE name = :name";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->bindParam(":name", $name, PDO::PARAM_STR);
        $checkStmt->execute();
        if ($checkStmt->rowCount() > 0) {
            $_SESSION['form_error_message']['add_league'] = "Este nome de liga já existe. Por favor, escolha outro nome.";
            if ($logo_filename_to_save && file_exists(LEAGUE_LOGO_UPLOAD_DIR . $logo_filename_to_save)) {
                 @unlink(LEAGUE_LOGO_UPLOAD_DIR . $logo_filename_to_save);
                 // If name is duplicate, don't keep the uploaded logo, but keep tmp name in session for info
            }
            header("Location: manage_leagues.php#add-league-form");
            exit;
        }

        $sql = "INSERT INTO leagues (name, logo_filename) VALUES (:name, :logo_filename)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":name", $name, PDO::PARAM_STR);
        if ($logo_filename_to_save === null) { $stmt->bindValue(":logo_filename", null, PDO::PARAM_NULL); }
        else { $stmt->bindParam(":logo_filename", $logo_filename_to_save, PDO::PARAM_STR); }

        if ($stmt->execute()) {
            unset($_SESSION['form_data']['add_league']);
            unset($_SESSION['form_error_message']['add_league']);
            header("Location: manage_leagues.php?status=league_added");
        } else {
            $_SESSION['form_error_message']['add_league'] = "Erro ao adicionar liga no banco de dados.";
            if ($logo_filename_to_save && file_exists(LEAGUE_LOGO_UPLOAD_DIR . $logo_filename_to_save)) {
                 @unlink(LEAGUE_LOGO_UPLOAD_DIR . $logo_filename_to_save);
                 // Keep tmp filename in session as the overall operation failed
            }
            header("Location: manage_leagues.php#add-league-form");
        }
        exit;
    } catch (PDOException $e) {
        error_log("PDOException in " . __FILE__ . " (adding league): " . $e->getMessage());
        $_SESSION['form_error_message']['add_league'] = "Ocorreu um erro no banco de dados ao adicionar a liga. Por favor, tente novamente.";
        if ($logo_filename_to_save && file_exists(LEAGUE_LOGO_UPLOAD_DIR . $logo_filename_to_save)) {
             @unlink(LEAGUE_LOGO_UPLOAD_DIR . $logo_filename_to_save);
        }
        header("Location: manage_leagues.php#add-league-form");
        exit;
    }
} else {
    header("Location: manage_leagues.php");
    exit;
}
?>
