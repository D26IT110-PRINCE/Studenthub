<?php
/**
 * Secure Logout Processor
 * Practical 10 & 14: Implements the 3-step secure session destruction with audit logging.
 */

session_start();

// Audit Log for Admin Logout (before destroying session)
if (isset($_SESSION["user_role"]) && $_SESSION["user_role"] === "admin" && isset($_SESSION["user_id"])) {
    require_once "api/db_connect_mysqli.php";
    $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
    $actionDesc = "Admin logout: {$_SESSION['user_email']}";
    $logStmt->bind_param("is", $_SESSION["user_id"], $actionDesc);
    $logStmt->execute();
    $logStmt->close();
    $conn->close();
}

// 1. Clear all session variables in memory
$_SESSION = [];

// 2. Delete the session cookie from the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        "",
        time() - 42000, // Expire in the past
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destroy the session file on the server
session_destroy();

// Redirect to login page
header("Location: login_user.html");
exit;
?>
