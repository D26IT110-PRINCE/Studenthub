<?php
/**
 * Secure User Login Processor
 * Practical 10: Validate -> Find User -> password_verify -> regenerate ID -> store Session -> Redirect
 */

session_start();
require_once 'db_connect_mysqli.php';

// 1. Request Method Check
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login_user.html");
    exit;
}

// 2. Input Sanitization
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email === "" || $password === "") {
    header("Location: ../login_user.html?error=empty_fields");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../login_user.html?error=invalid_email");
    exit;
}

// 3. Find User in Database
$sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    // Generic error message to prevent user enumeration
    header("Location: ../login_user.html?error=invalid_credentials");
    exit;
}

$user = $result->fetch_assoc();

// 4. Verify Password using password_verify()
if (!password_verify($password, $user["password"])) {
    header("Location: ../login_user.html?error=invalid_credentials");
    exit;
}

// 5. Prevent Session Fixation
// Regenerate the session ID and delete the old session file
session_regenerate_id(true);

// 6. Store User Identity in Session
$_SESSION["user_id"] = $user["id"];
$_SESSION["user_name"] = $user["name"];
$_SESSION["user_email"] = $user["email"];
$_SESSION["user_role"] = $user["role"];
$_SESSION["last_activity"] = time();

$stmt->close();
$conn->close();

// 7. Role-Based Redirection
if ($user["role"] === "admin") {
    header("Location: ../admin_dashboard.php");
} else {
    header("Location: ../student_dashboard.php");
}
exit;
?>
