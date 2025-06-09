<?php
// admin/add_match.php
require_once 'auth_check.php'; // Ensures admin is logged in
require_once '../config.php'; // Database connection

// Define target directory for match cover images
define('MATCH_COVER_UPLOAD_DIR', '../uploads/covers/matches/');
define('MAX_COVER_FILE_SIZE', 2 * 1024 * 1024); // 2MB
$allowed_cover_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate presence of required fields (team_home, team_away, match_time)
    if (empty(trim($_POST["team_home"])) || empty(trim($_POST["team_away"])) || empty(trim($_POST["match_time"]))) {
        header("Location: index.php?status=match_add_error&reason=missing_fields");
        exit;
    }

    $team_home = trim($_POST["team_home"]);
    $team_away = trim($_POST["team_away"]);
    $match_time = trim($_POST["match_time"]);
    $description = isset($_POST["description"]) ? trim($_POST["description"]) : null;
    $league_id = null;
    if (!empty($_POST["league_id"]) && filter_var(trim($_POST["league_id"]), FILTER_VALIDATE_INT)) {
        $league_id = (int)trim($_POST["league_id"]);
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
            $upload_error_message = 'Tipo de arquivo de capa inválido. Apenas PNG, JPG, GIF.';
        } else {
            $new_file_name = uniqid('match_cover_', true) . '.' . $file_extension;
            $destination_path = MATCH_COVER_UPLOAD_DIR . $new_file_name;

            if (!is_dir(MATCH_COVER_UPLOAD_DIR)) {
                if (!mkdir(MATCH_COVER_UPLOAD_DIR, 0755, true)) {
                    $upload_error_message = 'Falha ao criar diretório de uploads para capas de jogos.';
                }
            }

            if (empty($upload_error_message) && move_uploaded_file($file_tmp_path, $destination_path)) {
                $cover_image_filename_to_save = $new_file_name;
            } else {
                if(empty($upload_error_message)) $upload_error_message = 'Falha ao mover arquivo de capa upado.';
            }
        }
    } elseif (isset($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['cover_image_file']['error'] != UPLOAD_ERR_OK) {
        $upload_error_message = 'Erro no upload do arquivo de capa. Código: ' . $_FILES['cover_image_file']['error'];
    }
    // --- End Cover Image Upload Handling ---

    if (!empty($upload_error_message)) {
        header("Location: index.php?status=match_add_error&reason=file_upload_error&err_msg=" . urlencode($upload_error_message));
        exit;
    }

    try {
        $dt = new DateTime($match_time);
        $formatted_match_time = $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        header("Location: index.php?status=match_add_error&reason=invalid_date_format");
        exit;
    }

    try {
        // Added cover_image_filename to SQL
        $sql = "INSERT INTO matches (team_home, team_away, match_time, description, league_id, cover_image_filename) VALUES (:team_home, :team_away, :match_time, :description, :league_id, :cover_image_filename)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":team_home", $team_home, PDO::PARAM_STR);
        $stmt->bindParam(":team_away", $team_away, PDO::PARAM_STR);
        $stmt->bindParam(":match_time", $formatted_match_time, PDO::PARAM_STR);
        $stmt->bindParam(":description", $description, PDO::PARAM_STR);

        if ($league_id === null) { $stmt->bindValue(":league_id", null, PDO::PARAM_NULL); }
        else { $stmt->bindParam(":league_id", $league_id, PDO::PARAM_INT); }

        if ($cover_image_filename_to_save === null) { $stmt->bindValue(":cover_image_filename", null, PDO::PARAM_NULL); }
        else { $stmt->bindParam(":cover_image_filename", $cover_image_filename_to_save, PDO::PARAM_STR); }

        if ($stmt->execute()) {
            header("Location: index.php?status=match_added");
        } else {
            if ($cover_image_filename_to_save && file_exists(MATCH_COVER_UPLOAD_DIR . $cover_image_filename_to_save)) {
                // unlink(MATCH_COVER_UPLOAD_DIR . $cover_image_filename_to_save); // Rollback
            }
            header("Location: index.php?status=match_add_error&reason=db_insert_failed");
        }
        exit;
    } catch (PDOException $e) {
        if ($cover_image_filename_to_save && file_exists(MATCH_COVER_UPLOAD_DIR . $cover_image_filename_to_save)) {
            // unlink(MATCH_COVER_UPLOAD_DIR . $cover_image_filename_to_save); // Rollback
        }
        if (strpos($e->getMessage(), "FOREIGN KEY (`league_id`)") !== false) {
             header("Location: index.php?status=match_add_error&reason=invalid_league_id_fk");
        } else {
            header("Location: index.php?status=match_add_error&reason=database_error_" . $e->getCode());
        }
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
?>
