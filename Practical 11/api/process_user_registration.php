<?php
/**
 * Secure User Registration Processor (MySQLi)
 * Practical 9: Implements Validate -> Duplicate Check -> Hash -> Insert
 */

session_start();
require_once 'db_connect_mysqli.php';

// 1. POST check
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Invalid request method.");
}

// 2. CSRF Protection
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    exit("Invalid form submission (CSRF token mismatch).");
}

// 3. Read & Trim input (with null coalescing)
$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

$errors = [];

// 4. Server-side Validation
if (empty($name) || empty($email) || empty($password)) {
    $errors[] = "All fields are required.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
}

if (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters long.";
}

if ($password !== $confirm) {
    $errors[] = "Passwords do not match.";
}

// 5. Duplicate Email Check (SELECT before INSERT)
if (empty($errors)) {
    $check_sql = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $errors[] = "This email is already registered. Please try logging in.";
    }
    $check_stmt->close();
}

// 6. Process Registration
if (empty($errors)) {
    try {
        // a. Hashing (Bcrypt via PASSWORD_DEFAULT)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // b. Secure Insert using Prepared Statement
        $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $email, $hashed_password);

        if ($stmt->execute()) {
            $userId = $conn->insert_id;

            // c. Audit Log (Advanced Extension)
            $log_sql = "INSERT INTO audit_logs (user_id, action) VALUES (?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            $action = "User registered successfully";
            $log_stmt->bind_param("is", $userId, $action);
            $log_stmt->execute();
            $log_stmt->close();

            // Regenerate CSRF token after success
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            header("Location: ../register_user.php?success=1");
            exit;
        } else {
            $errors[] = "Database error: Could not complete registration.";
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log("Registration Error: " . $e->getMessage());
        $errors[] = "A system error occurred. Please try again later.";
    }
}

// 7. Respond with Errors
if (!empty($errors)) {
    $error_string = urlencode(implode('|', $errors));
    header("Location: ../register_user.php?errors=" . $error_string);
    exit;
}

$conn->close();
?>
