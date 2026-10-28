<?php
/**
 * Authentication Guard
 * Practical 10: Centralized check for session, timeout, and role access.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login_user.html");
    exit;
}

// 2. Session Timeout Check (15 minutes = 900 seconds)
$timeout = 900;
if (isset($_SESSION["last_activity"]) && (time() - $_SESSION["last_activity"] > $timeout)) {
    // Securely wipe session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), "", time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: login_user.html?timeout=1");
    exit;
}

// Update last activity time on every valid request
$_SESSION["last_activity"] = time();

/**
 * Role Verification Helper
 * Redirects user if they do not possess the required role.
 */
function require_role($role) {
    if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== $role) {
        // Redirect to their own dashboard based on their actual role
        $actual_role = $_SESSION["user_role"] ?? 'student';
        header("Location: " . $actual_role . "_dashboard.php");
        exit;
    }
}
?>
