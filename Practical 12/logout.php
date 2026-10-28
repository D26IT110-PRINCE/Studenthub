<?php
/**
 * Secure Logout Processor
 * Practical 10: Implements the 3-step secure session destruction.
 */

session_start();

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
