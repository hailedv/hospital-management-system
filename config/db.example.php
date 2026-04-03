<?php
// Copy this file to db.php and fill in your credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hospital_management_system');

session_start();

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Database connection failed. Please check config/db.php");
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database error. Please contact administrator.");
}

function sanitize_input($data) {
    global $conn;
    return $conn->real_escape_string(trim(stripslashes(htmlspecialchars($data))));
}

function check_login($required_role = null) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        header("Location: ../login.php");
        exit();
    }
    if ($required_role && $_SESSION['user_type'] !== $required_role) {
        header("Location: ../login.php");
        exit();
    }
}

function log_activity($action, $description = '') {
    global $conn;
    if (isset($_SESSION['user_type']) && isset($_SESSION['user_id'])) {
        $user_type = $_SESSION['user_type'];
        $user_id   = $_SESSION['user_id'];
        $ip        = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $check = $conn->query("SHOW TABLES LIKE 'activity_logs'");
        if ($check && $check->num_rows > 0) {
            $stmt = $conn->prepare("INSERT INTO activity_logs (user_type, user_id, action, description, ip_address) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sisss", $user_type, $user_id, $action, $description, $ip);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}
?>
