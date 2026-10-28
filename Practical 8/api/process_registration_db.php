<?php
/**
 * Student Registration Processor - MySQL Database Version
 * Handles server-side validation, sanitization, and secure MySQL storage using PDO Prepared Statements
 *
 * 3-Step PDO Prepared Statement Process:
 * 1. prepare() - Parse and compile the SQL statement with named placeholders
 * 2. bindParam()/bindValue() - Bind parameters to placeholders
 * 3. execute() - Execute the prepared statement
 */

// Configuration
const REQUIRED_FIELDS = ['name', 'email', 'mobile', 'password', 'confirm-password', 'course', 'year', 'gender', 'terms'];

// Security: Prevent direct access
if (basename($_SERVER['SCRIPT_FILENAME']) !== 'process_registration_db.php') {
    http_response_code(403);
    exit('Direct access forbidden');
}

// Include database connection
require_once __DIR__ . '/db_connect.php';

/**
 * Start session for CSRF
 */
session_start();

/**
 * Generate CSRF token
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input for safe storage and display
 */
function sanitizeInput(string $input): string
{
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Validate name: letters and spaces only, minimum 2 characters
 */
function validateName(string $name): array
{
    $errors = [];
    $name = sanitizeInput($name);

    if (empty($name)) {
        $errors[] = 'Full name is required';
    } elseif (!preg_match('/^[A-Za-z\s]{2,}$/', $name)) {
        $errors[] = 'Name must contain only letters and spaces (minimum 2 characters)';
    }

    return [$name, $errors];
}

/**
 * Validate email format
 */
function validateEmail(string $email): array
{
    $errors = [];
    $email = sanitizeInput($email);

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    return [$email, $errors];
}

/**
 * Validate mobile: 10 digits, optionally prefixed with +91
 */
function validateMobile(string $mobile): array
{
    $errors = [];
    $mobile = sanitizeInput($mobile);

    if (empty($mobile)) {
        $errors[] = 'Mobile number is required';
    } elseif (!preg_match('/^(\+91)?[6-9]\d{9}$/', $mobile)) {
        $errors[] = 'Mobile must be a 10-digit Indian number (optionally starting with +91)';
    }

    return [$mobile, $errors];
}

/**
 * Validate password strength
 */
function validatePassword(string $password): array
{
    $errors = [];

    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    } elseif (!preg_match('/\d/', $password)) {
        $errors[] = 'Password must contain at least one number';
    } elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }

    return [$password, $errors];
}

/**
 * Validate confirm password matches
 */
function validateConfirmPassword(string $confirmPassword, string $password): array
{
    $errors = [];

    if (empty($confirmPassword)) {
        $errors[] = 'Please confirm your password';
    } elseif ($confirmPassword !== $password) {
        $errors[] = 'Passwords do not match';
    }

    return [$confirmPassword, $errors];
}

/**
 * Validate course selection
 */
function validateCourse(string $course): array
{
    $errors = [];
    $course = sanitizeInput($course);

    if (empty($course)) {
        $errors[] = 'Course is required';
    } elseif (!ctype_digit($course)) {
        $errors[] = 'Invalid course selected';
    }

    return [$course, $errors];
}

/**
 * Validate year selection
 */
function validateYear(string $year): array
{
    $errors = [];
    $allowedYears = ['1', '2', '3', '4'];
    $year = sanitizeInput($year);

    if (empty($year)) {
        $errors[] = 'Year is required';
    } elseif (!in_array($year, $allowedYears, true)) {
        $errors[] = 'Invalid year selected';
    }

    return [$year, $errors];
}

/**
 * Validate gender selection
 */
function validateGender(string $gender): array
{
    $errors = [];
    $allowedGenders = ['male', 'female', 'other'];
    $gender = sanitizeInput($gender);

    if (empty($gender)) {
        $errors[] = 'Gender is required';
    } elseif (!in_array($gender, $allowedGenders, true)) {
        $errors[] = 'Invalid gender selected';
    }

    return [$gender, $errors];
}

/**
 * Validate terms acceptance
 */
function validateTerms(string $terms): array
{
    $errors = [];
    $terms = sanitizeInput($terms);

    if ($terms !== 'on' && $terms !== '1' && $terms !== 'true') {
        $errors[] = 'You must accept the terms and conditions';
    }

    return [$terms, $errors];
}

/**
 * Check if course_id exists
 */
function courseExists(PDO $pdo, int $courseId): bool
{
    $sql = "SELECT 1 FROM `courses` WHERE `course_id` = :course_id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':course_id', $courseId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch() !== false;
}

/**
 * Check if email already exists in database
 */
function emailExists(PDO $pdo, string $email): bool
{
    $sql = "SELECT 1 FROM `students` WHERE `email` = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetch() !== false;
}

/**
 * Insert student registration into database
 * Now strictly following the provided schema: (id, name, email, mobile, course_id, year, gender)
 * Note: password_hash and terms_accepted were removed from schema, so they are removed from INSERT.
 */
function insertStudent(PDO $pdo, array $data): int|false
{
    $sql = "
        INSERT INTO `students` (
            `name`, `email`, `mobile`, `course_id`, `year`, `gender`
        ) VALUES (
            :name, :email, :mobile, :course_id, :year, :gender
        )
    ";
    $stmt = $pdo->prepare($sql);

    $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
    $stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
    $stmt->bindValue(':mobile', $data['mobile'], PDO::PARAM_STR);
    $stmt->bindValue(':course_id', $data['course_id'], PDO::PARAM_INT);
    $stmt->bindValue(':year', $data['year'], PDO::PARAM_INT);
    $stmt->bindValue(':gender', $data['gender'], PDO::PARAM_STR);

    $result = $stmt->execute();

    if ($result) {
        return (int)$pdo->lastInsertId();
    }

    return false;
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: ../register_db.php?error=invalid_method');
    exit;
}

// Verify CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($csrfToken)) {
    http_response_code(403);
    header('Location: ../register_db.php?error=csrf_invalid');
    exit;
}

// Collect and validate all fields
$allErrors = [];
$cleanData = [];

[$cleanData['name'], $errors] = validateName($_POST['name'] ?? '');
$allErrors = array_merge($allErrors, $errors);

[$cleanData['email'], $errors] = validateEmail($_POST['email'] ?? '');
$allErrors = array_merge($allErrors, $errors);

[$cleanData['mobile'], $errors] = validateMobile($_POST['mobile'] ?? '');
$allErrors = array_merge($allErrors, $errors);

[$cleanData['password'], $errors] = validatePassword($_POST['password'] ?? '');
$allErrors = array_merge($allErrors, $errors);

[$cleanData['confirm_password'], $errors] = validateConfirmPassword(
    $_POST['confirm-password'] ?? '',
    $cleanData['password']
);
$allErrors = array_merge($allErrors, $errors);

[$cleanData['course_id'], $errors] = validateCourse($_POST['course'] ?? '');
$allErrors = array_merge($allErrors, $errors);

[$cleanData['year'], $errors] = validateYear($_POST['year'] ?? '');
$allErrors = array_merge($allErrors, $errors);

[$cleanData['gender'], $errors] = validateGender($_POST['gender'] ?? '');
$allErrors = array_merge($allErrors, $errors);

[$cleanData['terms'], $errors] = validateTerms($_POST['terms'] ?? '');
$allErrors = array_merge($allErrors, $errors);

if (empty($allErrors)) {
    try {
        $pdo = getDbConnection();

        $courseId = (int)$cleanData['course_id'];
        if (!courseExists($pdo, $courseId)) {
            $allErrors[] = 'Invalid course selected';
        }

        if (empty($allErrors) && emailExists($pdo, $cleanData['email'])) {
            $allErrors[] = 'An account with this email already exists';
        }

        if (empty($allErrors)) {
            // Since password_hash is removed from schema, we no longer store it.
            // However, validation still happens to maintain the "practical" logic.

            $studentId = insertStudent($pdo, $cleanData);

            if ($studentId !== false) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: ../register_db.php?success=1');
                exit;
            } else {
                $allErrors[] = 'Registration failed. Please try again later.';
            }
        }

    } catch (PDOException $e) {
        error_log("Database error in registration: " . $e->getMessage());
        $allErrors[] = 'A database error occurred. Please try again later.';
    }
}

$errorString = urlencode(implode('|', $allErrors));
header('Location: ../register_db.php?errors=' . $errorString);
exit;