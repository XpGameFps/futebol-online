<?php
// admin/add_match.php
require_once 'auth_check.php'; // Handles session_start()
require_once '../config.php';

define('MATCH_COVER_UPLOAD_DIR', '../uploads/covers/matches/');
define('MAX_COVER_FILE_SIZE', 2 * 1024 * 1024); // 2MB
$allowed_cover_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Store all POST data in session immediately for repopulation
    $_SESSION['form_data']['add_match'] = $_POST;
    // Store original filename if a file was attempted, for user info
    if (isset($_FILES['cover_image_file']) && !empty($_FILES['cover_image_file']['name'])) {
        $_SESSION['form_data']['add_match']['cover_image_filename_tmp'] = $_FILES['cover_image_file']['name'];
    }


    $team_home = trim($_POST["team_home"] ?? '');
    $team_away = trim($_POST["team_away"] ?? '');
    $match_time_input = trim($_POST["match_time"] ?? '');
    $description = trim($_POST["description"] ?? null);
    $league_id = null;
    if (!empty($_POST["league_id"]) && filter_var(trim($_POST["league_id"]), FILTER_VALIDATE_INT)) {
        $league_id = (int)trim($_POST["league_id"]);
    }
    $meta_description = trim($_POST['meta_description'] ?? null);
    $meta_keywords = trim($_POST['meta_keywords'] ?? null);

    // Basic field validation
    if (empty($team_home) || empty($team_away) || empty($match_time_input)) {
        $_SESSION['form_error_message']['add_match'] = "Times e data/hora da partida são obrigatórios.";
        header("Location: index.php#add-match-form"); // Redirect back to form anchor
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
        $file_name = $_FILES['cover_image_file']['name']; // Original name
        $file_size = $_FILES['cover_image_file']['size'];
        $file_type = $_FILES['cover_image_file']['type'];
        $file_ext_array = explode('.', $file_name);
        $file_extension = strtolower(end($file_ext_array));

        if ($file_size > MAX_COVER_FILE_SIZE) {
            $upload_error_message = 'Arquivo de capa muito grande. Máximo 2MB.';
        } elseif (!in_array($file_type, $allowed_cover_mime_types)) {
            $upload_error_message = 'Tipo de arquivo de capa inválido. Apenas PNG, JPG, GIF.';
        } else {
            $new_file_name = uniqid('match_cover_', true) . '.' . $file_extension;
            $destination_path = MATCH_COVER_UPLOAD_DIR . $new_file_name;
            if (!is_dir(MATCH_COVER_UPLOAD_DIR)) { @mkdir(MATCH_COVER_UPLOAD_DIR, 0755, true); }
            if (move_uploaded_file($file_tmp_path, $destination_path)) {
                $cover_image_filename_to_save = $new_file_name;
                 // Clear tmp filename from session if upload was successful
                if(isset($_SESSION['form_data']['add_match']['cover_image_filename_tmp'])) {
                    unset($_SESSION['form_data']['add_match']['cover_image_filename_tmp']);
                }
            } else { $upload_error_message = 'Falha ao mover arquivo de capa upado.'; }
        }
    } elseif (isset($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['cover_image_file']['error'] != UPLOAD_ERR_OK) {
        $upload_error_message = 'Erro no upload do arquivo de capa. Código: ' . $_FILES['cover_image_file']['error'];
    }

    if (!empty($upload_error_message)) {
        $_SESSION['form_error_message']['add_match'] = $upload_error_message;
        // Note: $_SESSION['form_data']['add_match'] still holds other POST data for repopulation
        header("Location: index.php#add-match-form");
        exit;
    }
    // --- End Cover Image Upload Handling ---

    try {
        $sql = "INSERT INTO matches (team_home, team_away, match_time, description, league_id, cover_image_filename, meta_description, meta_keywords)
                VALUES (:team_home, :team_away, :match_time, :description, :league_id, :cover_image_filename, :meta_description, :meta_keywords)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":team_home", $team_home, PDO::PARAM_STR);
        $stmt->bindParam(":team_away", $team_away, PDO::PARAM_STR);
        $stmt->bindParam(":match_time", $formatted_match_time_for_db, PDO::PARAM_STR);

        if ($description === null) { $stmt->bindValue(":description", null, PDO::PARAM_NULL); } else { $stmt->bindParam(":description", $description, PDO::PARAM_STR); }
        if ($league_id === null) { $stmt->bindValue(":league_id", null, PDO::PARAM_NULL); } else { $stmt->bindParam(":league_id", $league_id, PDO::PARAM_INT); }
        if ($cover_image_filename_to_save === null) { $stmt->bindValue(":cover_image_filename", null, PDO::PARAM_NULL); } else { $stmt->bindParam(":cover_image_filename", $cover_image_filename_to_save, PDO::PARAM_STR); }
        if ($meta_description === null) { $stmt->bindValue(":meta_description", null, PDO::PARAM_NULL); } else { $stmt->bindParam(":meta_description", $meta_description, PDO::PARAM_STR); }
        if ($meta_keywords === null) { $stmt->bindValue(":meta_keywords", null, PDO::PARAM_NULL); } else { $stmt->bindParam(":meta_keywords", $meta_keywords, PDO::PARAM_STR); }

        if ($stmt->execute()) {
            $new_match_id = $pdo->lastInsertId();
            unset($_SESSION['form_data']['add_match']);
            unset($_SESSION['form_error_message']['add_match']);
            header("Location: index.php?status=match_added#match-" . $new_match_id);
            exit;
        } else {
            $_SESSION['form_error_message']['add_match'] = "Erro ao adicionar jogo no banco de dados.";
            if ($cover_image_filename_to_save && file_exists(MATCH_COVER_UPLOAD_DIR . $cover_image_filename_to_save)) {
                 @unlink(MATCH_COVER_UPLOAD_DIR . $cover_image_filename_to_save);
                 // Keep tmp filename in session as the upload effectively failed from user's POV
            }
        }
    } catch (PDOException $e) {
        $_SESSION['form_error_message']['add_match'] = "Erro de BD: " . $e->getMessage();
        if ($cover_image_filename_to_save && file_exists(MATCH_COVER_UPLOAD_DIR . $cover_image_filename_to_save)) {
             @unlink(MATCH_COVER_UPLOAD_DIR . $cover_image_filename_to_save);
        }
        if (strpos($e->getMessage(), "FOREIGN KEY (`league_id`)") !== false) {
             $_SESSION['form_error_message']['add_match'] = "Erro: ID da liga inválido.";
        }
    }
    header("Location: index.php#add-match-form");
    exit;

} else {
    // Clear any stale form data if accessed via GET directly
    if(isset($_SESSION['form_data']['add_match'])) unset($_SESSION['form_data']['add_match']);
    if(isset($_SESSION['form_error_message']['add_match'])) unset($_SESSION['form_error_message']['add_match']);
    header("Location: index.php");
    exit;
}
?>
