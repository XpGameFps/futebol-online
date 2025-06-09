<?php
session_start();
require_once '../config.php'; // Database connection

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate presence of required fields
    if (empty(trim($_POST["team_home"])) || empty(trim($_POST["team_away"])) || empty(trim($_POST["match_time"]))) {
        // Redirect back with an error message or handle error appropriately
        header("Location: index.php?status=match_add_error&reason=missing_fields");
        exit;
    }

    $team_home = trim($_POST["team_home"]);
    $team_away = trim($_POST["team_away"]);
    $match_time = trim($_POST["match_time"]);
    $description = isset($_POST["description"]) ? trim($_POST["description"]) : null;

    // Basic validation for datetime format (more robust validation can be added)
    // PHP's strtotime can be forgiving, but for database it's better to be strict
    // For HTML5 datetime-local, the format should be 'Y-m-d\TH:i'
    try {
        $dt = new DateTime($match_time);
        $formatted_match_time = $dt->format('Y-m-d H:i:s'); // Format for MySQL DATETIME
    } catch (Exception $e) {
        header("Location: index.php?status=match_add_error&reason=invalid_date_format");
        exit;
    }

    try {
        $sql = "INSERT INTO matches (team_home, team_away, match_time, description) VALUES (:team_home, :team_away, :match_time, :description)";

        if ($stmt = $pdo->prepare($sql)) {
            // Bind parameters
            $stmt->bindParam(":team_home", $team_home, PDO::PARAM_STR);
            $stmt->bindParam(":team_away", $team_away, PDO::PARAM_STR);
            $stmt->bindParam(":match_time", $formatted_match_time, PDO::PARAM_STR);
            $stmt->bindParam(":description", $description, PDO::PARAM_STR);

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Redirect to admin page with success message
                header("Location: index.php?status=match_added");
                exit;
            } else {
                // Log error and redirect
                // error_log("Error executing statement: " . implode(":", $stmt->errorInfo()));
                header("Location: index.php?status=match_add_error&reason=execute_failed");
                exit;
            }
        }
        unset($stmt); // Close statement
    } catch (PDOException $e) {
        // Log detailed error for admin, show generic message to user
        // error_log("PDOException in add_match.php: " . $e->getMessage());
        header("Location: index.php?status=match_add_error&reason=pdo_exception");
        exit;
    }

    unset($pdo); // Close connection
} else {
    // If not a POST request, redirect to admin page or show error
    header("Location: index.php");
    exit;
}
?>
