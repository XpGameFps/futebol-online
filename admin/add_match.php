<?php
// admin/add_match.php
require_once 'auth_check.php'; // Handles session_start()
require_once '../config.php';

// Ensure csrf_utils.php is loaded (auth_check.php should have already included it)
if (!function_exists('validate_csrf_token')) {
    // Fallback if somehow not loaded, though auth_check.php should handle this.
    require_once 'csrf_utils.php';
}

define('MATCH_COVER_UPLOAD_DIR', '../uploads/covers/matches/');
define('MAX_COVER_FILE_SIZE', 2 * 1024 * 1024); // 2MB
$allowed_cover_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['form_error_message']['add_match'] = "Falha na verificação CSRF. Por favor, tente novamente.";
        // It's good practice to redirect to a neutral page or the form page itself.
        // Avoid redirecting to index.php?status=... as it might imply a partial success/failure of an operation.
        header("Location: index.php#add-match-form");
        exit;
    }
    $_SESSION['form_data']['add_match'] = $_POST;
    if (isset($_FILES['cover_image_file']) && !empty($_FILES['cover_image_file']['name'])) {
        $_SESSION['form_data']['add_match']['cover_image_filename_tmp'] = $_FILES['cover_image_file']['name'];
    }

    $home_team_id = trim($_POST["home_team_id"] ?? '');
    $away_team_id = trim($_POST["away_team_id"] ?? '');
    $match_time_input = trim($_POST["match_time"] ?? '');
    $description = trim($_POST["description"] ?? null);
    $league_id_val = null; // Renamed to avoid conflict with $league_id from potential future includes
    if (!empty($_POST["league_id"]) && filter_var(trim($_POST["league_id"]), FILTER_VALIDATE_INT)) {
        $league_id_val = (int)trim($_POST["league_id"]);
    }
    $meta_description_val = trim($_POST['meta_description'] ?? null); // Renamed
    $meta_keywords_val = trim($_POST['meta_keywords'] ?? null);   // Renamed

    // Validation for new team ID fields
    if (empty($home_team_id) || !filter_var($home_team_id, FILTER_VALIDATE_INT)) {
        $_SESSION['form_error_message']['add_match'] = "Time da casa é obrigatório.";
        header("Location: index.php#add-match-form"); exit;
    }
    if (empty($away_team_id) || !filter_var($away_team_id, FILTER_VALIDATE_INT)) {
        $_SESSION['form_error_message']['add_match'] = "Time visitante é obrigatório.";
        header("Location: index.php#add-match-form"); exit;
    }
    if ($home_team_id === $away_team_id) {
        $_SESSION['form_error_message']['add_match'] = "Time da casa e visitante não podem ser o mesmo.";
        header("Location: index.php#add-match-form"); exit;
    }
    // Basic field validation for match_time (already existed)
    if (empty($match_time_input)) {
        $_SESSION['form_error_message']['add_match'] = "Data/hora da partida é obrigatória.";
        header("Location: index.php#add-match-form");
        exit;
    }

    try {
        $dt = new DateTime($match_time_input);
        $formatted_match_time_for_db = $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        $_SESSION['form_error_message']['add_match'] = "Formato de data/hora inválido.";
        header("Location: index.php#add-match-form");
        exit;
    }

    $cover_image_filename_to_save = null;
    $upload_error_message = '';
    // --- Cover Image Upload Handling ---
    if (isset($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['cover_image_file']['tmp_name'];
        $file_name = $_FILES['cover_image_file']['name'];
        $file_size = $_FILES['cover_image_file']['size'];
        $file_type = $_FILES['cover_image_file']['type'];
        $file_ext_array = explode('.', $file_name);
        $file_extension = strtolower(end($file_ext_array));

        if ($file_size > MAX_COVER_FILE_SIZE) {
            $upload_error_message = 'Arquivo de capa muito grande. Máximo 2MB.';
        } elseif (!in_array($file_type, $allowed_cover_mime_types)) {
            // Check browser-provided MIME type first
            $upload_error_message = 'Tipo de arquivo de capa inválido (MIME). Apenas PNG, JPG, GIF.';
        } else {
            // Verify if it's actually an image using getimagesize
            $image_info = @getimagesize($file_tmp_path);
            if ($image_info === false) {
                $upload_error_message = 'Arquivo de capa inválido. Conteúdo não reconhecido como imagem.';
            } else {
                // Optional: could further check $image_info[2] if specific image types (beyond MIME) are required.
                // Example: $allowed_image_types = [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG];
                // if (!in_array($image_info[2], $allowed_image_types)) {
                //    $upload_error_message = 'Formato de imagem não suportado após verificação de conteúdo.';
                // } else {

                // Proceed with generating new filename and moving the file
                $new_file_name = uniqid('match_cover_', true) . '.' . $file_extension;
                $destination_path = MATCH_COVER_UPLOAD_DIR . $new_file_name;

                if (!is_dir(MATCH_COVER_UPLOAD_DIR)) {
                    if (!@mkdir(MATCH_COVER_UPLOAD_DIR, 0755, true)) {
                        $upload_error_message = 'Falha ao criar diretório de upload.';
                        // Log this server-side error
                        error_log("Failed to create upload directory: " . MATCH_COVER_UPLOAD_DIR);
                    }
                }

                if (empty($upload_error_message)) { // Check if directory creation was successful
                    if (move_uploaded_file($file_tmp_path, $destination_path)) {
                        $cover_image_filename_to_save = $new_file_name;
                        if (isset($_SESSION['form_data']['add_match']['cover_image_filename_tmp'])) {
                            unset($_SESSION['form_data']['add_match']['cover_image_filename_tmp']);
                        }
                    } else {
                        $upload_error_message = 'Falha ao mover arquivo de capa upado.';
                        // Log this server-side error
                        error_log("Failed to move uploaded file to: " . $destination_path);
                    }
                }
                // } // End of optional stricter image type check
            }
        }
    } elseif (isset($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['cover_image_file']['error'] != UPLOAD_ERR_OK) {
        $upload_error_message = 'Erro no upload do arquivo de capa. Código: ' . $_FILES['cover_image_file']['error'];
    }

    if (!empty($upload_error_message)) {
        $_SESSION['form_error_message']['add_match'] = $upload_error_message;
        header("Location: index.php#add-match-form");
        exit;
    }
    // --- End Cover Image Upload Handling ---

    // If all prior validations passed (including those that exit on error)
    try {
        // This SQL will be updated in the next step to remove team_home, team_away text columns
        // For now, we prepare it to use IDs, but also need to provide dummy text values
        // OR, we assume the table is already altered for this step's purpose.
        // Let's assume the table will be altered and write the SQL for that.
        $sql = "INSERT INTO matches (home_team_id, away_team_id, match_time, description, league_id, cover_image_filename, meta_description, meta_keywords)
                VALUES (:home_team_id, :away_team_id, :match_time, :description, :league_id, :cover_image_filename, :meta_description, :meta_keywords)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":home_team_id", $home_team_id, PDO::PARAM_INT);
        $stmt->bindParam(":away_team_id", $away_team_id, PDO::PARAM_INT);
        $stmt->bindParam(":match_time", $formatted_match_time_for_db, PDO::PARAM_STR);

        if ($description === null) { $stmt->bindValue(":description", null, PDO::PARAM_NULL); } else { $stmt->bindParam(":description", $description, PDO::PARAM_STR); }
        if ($league_id_val === null) { $stmt->bindValue(":league_id", null, PDO::PARAM_NULL); } else { $stmt->bindParam(":league_id", $league_id_val, PDO::PARAM_INT); }
        if ($cover_image_filename_to_save === null) { $stmt->bindValue(":cover_image_filename", null, PDO::PARAM_NULL); } else { $stmt->bindParam(":cover_image_filename", $cover_image_filename_to_save, PDO::PARAM_STR); }
        if ($meta_description_val === null) { $stmt->bindValue(":meta_description", null, PDO::PARAM_NULL); } else { $stmt->bindParam(":meta_description", $meta_description_val, PDO::PARAM_STR); }
        if ($meta_keywords_val === null) { $stmt->bindValue(":meta_keywords", null, PDO::PARAM_NULL); } else { $stmt->bindParam(":meta_keywords", $meta_keywords_val, PDO::PARAM_STR); }

        if ($stmt->execute()) {
            $new_match_id = $pdo->lastInsertId();
            unset($_SESSION['form_data']['add_match']);
            unset($_SESSION['form_error_message']['add_match']);
            header("Location: index.php?status=match_added#match-" . $new_match_id);
            exit;
        } else {
            $_SESSION['form_error_message']['add_match'] = "Erro ao adicionar jogo (DB). Verifique se a tabela 'matches' foi atualizada para usar IDs de times.";
            if ($cover_image_filename_to_save && file_exists(MATCH_COVER_UPLOAD_DIR . $cover_image_filename_to_save)) {
                 @unlink(MATCH_COVER_UPLOAD_DIR . $cover_image_filename_to_save);
            }
        }
    } catch (PDOException $e) {
        // Check for foreign key constraint violation for team_ids if table is altered
        if (strpos($e->getMessage(), "FOREIGN KEY (`home_team_id`)") !== false || strpos($e->getMessage(), "FOREIGN KEY (`away_team_id`)") !== false) {
             $_SESSION['form_error_message']['add_match'] = "Erro: ID do time da casa ou visitante inválido.";
        } elseif (strpos($e->getMessage(), "FOREIGN KEY (`league_id`)") !== false) {
             $_SESSION['form_error_message']['add_match'] = "Erro: ID da liga inválido.";
        } else {
            error_log("PDOException in " . __FILE__ . " (add_match): " . $e->getMessage());
            $_SESSION['form_error_message']['add_match'] = "Ocorreu um erro no banco de dados ao adicionar o jogo. Por favor, tente novamente.";
        }
        if ($cover_image_filename_to_save && file_exists(MATCH_COVER_UPLOAD_DIR . $cover_image_filename_to_save)) {
             @unlink(MATCH_COVER_UPLOAD_DIR . $cover_image_filename_to_save);
        }
    }
    header("Location: index.php#add-match-form");
    exit;

} else {
    if(isset($_SESSION['form_data']['add_match'])) unset($_SESSION['form_data']['add_match']);
    if(isset($_SESSION['form_error_message']['add_match'])) unset($_SESSION['form_error_message']['add_match']);
    header("Location: index.php");
    exit;
}
?>
