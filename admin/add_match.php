<?php
session_start();
require_once '../config.php'; // Database connection

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate presence of required fields
    if (empty(trim($_POST["team_home"])) || empty(trim($_POST["team_away"])) || empty(trim($_POST["match_time"]))) {
        header("Location: index.php?status=match_add_error&reason=missing_fields");
        exit;
    }

    $team_home = trim($_POST["team_home"]);
    $team_away = trim($_POST["team_away"]);
    $match_time = trim($_POST["match_time"]);
    $description = isset($_POST["description"]) ? trim($_POST["description"]) : null;

    // Handle league_id
    $league_id = null; // Default to null
    if (!empty($_POST["league_id"])) {
        $league_id_input = trim($_POST["league_id"]);
        if (filter_var($league_id_input, FILTER_VALIDATE_INT)) {
            $league_id = (int)$league_id_input;
        } else {
            // Optional: redirect with error if league_id is present but not an int
            // For now, we'll just treat it as NULL if invalid format and not empty
            // A more robust check would be to ensure league_id exists in leagues table
        }
    }

    try {
        $dt = new DateTime($match_time);
        $formatted_match_time = $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        header("Location: index.php?status=match_add_error&reason=invalid_date_format");
        exit;
    }

    try {
        // Added league_id to the SQL query and binding
        $sql = "INSERT INTO matches (team_home, team_away, match_time, description, league_id) VALUES (:team_home, :team_away, :match_time, :description, :league_id)";

        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":team_home", $team_home, PDO::PARAM_STR);
            $stmt->bindParam(":team_away", $team_away, PDO::PARAM_STR);
            $stmt->bindParam(":match_time", $formatted_match_time, PDO::PARAM_STR);
            $stmt->bindParam(":description", $description, PDO::PARAM_STR);

            // Bind league_id: if null, PDO handles it as SQL NULL
            if ($league_id === null) {
                $stmt->bindValue(":league_id", null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(":league_id", $league_id, PDO::PARAM_INT);
            }

            if ($stmt->execute()) {
                header("Location: index.php?status=match_added");
                exit;
            } else {
                header("Location: index.php?status=match_add_error&reason=execute_failed");
                exit;
            }
        }
        unset($stmt);
    } catch (PDOException $e) {
        // Check for foreign key constraint violation for league_id
        if (strpos($e->getMessage(), "FOREIGN KEY (`league_id`)") !== false) {
             header("Location: index.php?status=match_add_error&reason=invalid_league_id_fk");
        } else {
            // error_log("PDOException in add_match.php: " . $e->getMessage());
            header("Location: index.php?status=match_add_error&reason=pdo_exception_" . $e->getCode());
        }
        exit;
    }

    unset($pdo);
} else {
    header("Location: index.php");
    exit;
}
?>
