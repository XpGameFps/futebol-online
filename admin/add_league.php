<?php
session_start();
require_once '../config.php';

// Define target directory for league logos (relative to this script's location)
define('LEAGUE_LOGO_UPLOAD_DIR', '../uploads/logos/leagues/');
define('MAX_FILE_SIZE', 1024 * 1024); // 1MB
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"] ?? '');
    $logo_filename_to_save = null; // This will store the filename if upload is successful
    $upload_error_message = '';

    if (empty($name)) {
        header("Location: manage_leagues.php?status=league_add_error&reason=empty_name");
        exit;
    }

    // --- File Upload Handling ---
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['logo_file']['tmp_name'];
        $file_name = $_FILES['logo_file']['name'];
        $file_size = $_FILES['logo_file']['size'];
        $file_type = $_FILES['logo_file']['type']; // MIME type
        $file_ext_array = explode('.', $file_name);
        $file_extension = strtolower(end($file_ext_array));

        // Validate file size
        if ($file_size > MAX_FILE_SIZE) {
            $upload_error_message = 'Arquivo muito grande. Máximo 1MB.';
        }
        // Validate MIME type
        elseif (!in_array($file_type, $allowed_mime_types)) {
            $upload_error_message = 'Tipo de arquivo inválido. Apenas PNG, JPG, GIF são permitidos.';
        } else {
            // Create a unique filename to prevent overwriting
            $new_file_name = uniqid('league_', true) . '.' . $file_extension;
            $destination_path = LEAGUE_LOGO_UPLOAD_DIR . $new_file_name;

            // Create directory if it doesn't exist (basic check)
            if (!is_dir(LEAGUE_LOGO_UPLOAD_DIR)) {
                if (!mkdir(LEAGUE_LOGO_UPLOAD_DIR, 0755, true)) {
                    $upload_error_message = 'Falha ao criar diretório de uploads.';
                }
            }

            if (empty($upload_error_message) && move_uploaded_file($file_tmp_path, $destination_path)) {
                $logo_filename_to_save = $new_file_name;
            } else {
                if(empty($upload_error_message)) $upload_error_message = 'Falha ao mover arquivo upado.';
            }
        }
    } elseif (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['logo_file']['error'] != UPLOAD_ERR_OK) {
        // Handle other upload errors
        $upload_error_message = 'Erro no upload do arquivo. Código: ' . $_FILES['logo_file']['error'];
    }
    // --- End File Upload Handling ---

    // If there was an upload error, redirect back with the error message
    if (!empty($upload_error_message)) {
        header("Location: manage_leagues.php?status=league_add_error&reason=file_upload_error&err_msg=" . urlencode($upload_error_message));
        exit;
    }

    try {
        // Check for duplicate league name (already exists from previous step)
        $checkSql = "SELECT id FROM leagues WHERE name = :name";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->bindParam(":name", $name, PDO::PARAM_STR);
        $checkStmt->execute();
        if ($checkStmt->rowCount() > 0) {
             header("Location: manage_leagues.php?status=league_add_error&reason=league_name_exists");
             exit;
        }

        // Save to database
        // Assuming 'logo_filename' is the column name after update_schema_v3.sql
        $sql = "INSERT INTO leagues (name, logo_filename) VALUES (:name, :logo_filename)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":name", $name, PDO::PARAM_STR);

        if ($logo_filename_to_save === null) {
            $stmt->bindValue(":logo_filename", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":logo_filename", $logo_filename_to_save, PDO::PARAM_STR);
        }

        if ($stmt->execute()) {
            header("Location: manage_leagues.php?status=league_added");
        } else {
            // If DB insert fails after successful upload, ideally delete the uploaded file.
            // For simplicity, this step is omitted here but important for robust applications.
            if ($logo_filename_to_save && file_exists(LEAGUE_LOGO_UPLOAD_DIR . $logo_filename_to_save)) {
                // unlink(LEAGUE_LOGO_UPLOAD_DIR . $logo_filename_to_save); // Rollback file upload
            }
            header("Location: manage_leagues.php?status=league_add_error&reason=db_insert_failed");
        }
        exit;

    } catch (PDOException $e) {
        // Rollback file upload if PDO exception occurs
        if ($logo_filename_to_save && file_exists(LEAGUE_LOGO_UPLOAD_DIR . $logo_filename_to_save)) {
            // unlink(LEAGUE_LOGO_UPLOAD_DIR . $logo_filename_to_save);
        }
        if ($e->getCode() == '23000' && strpos($e->getMessage(), "Duplicate entry") !== false && strpos($e->getMessage(), "for key 'leagues.name'") !== false) {
             header("Location: manage_leagues.php?status=league_add_error&reason=league_name_exists");
        } else {
            header("Location: manage_leagues.php?status=league_add_error&reason=database_error_" . $e->getCode());
        }
        exit;
    }
} else {
    header("Location: manage_leagues.php");
    exit;
}
?>
