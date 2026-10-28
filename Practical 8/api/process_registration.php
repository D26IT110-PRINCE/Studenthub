<?php
/**
 * Student Registration Processor
 * Handles server-side validation, sanitization, and secure CSV storage
 */

// Configuration
const CSV_FILE = 'data/registrations.csv';
const REQUIRED_FIELDS = ['name', 'email', 'mobile', 'password', 'confirm-password', 'course', 'year', 'gender', 'terms'];
const MAX_FILE_SIZE = 1024 * 1024; // 1MB limit for CSV

// Security: Prevent direct access to data directory via web
if (basename($_SERVER['SCRIPT_FILENAME']) !== 'process_registration.php') {
    http_response_code(403);
    exit('Direct access forbidden');
}

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
    $allowedCourses = ['btech', 'mca', 'msc'];
    $course = sanitizeInput($course);

    if (empty($course)) {
        $errors[] = 'Course is required';
    } elseif (!in_array($course, $allowedCourses, true)) {
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
 * Ensure CSV file exists with headers
 */
function ensureCsvFileExists(): void
{
    $dir = dirname(CSV_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if (!file_exists(CSV_FILE)) {
        $headers = ['name', 'email', 'mobile', 'password_hash', 'course', 'year', 'gender', 'terms_accepted', 'registration_date'];
        $fp = fopen(CSV_FILE, 'w');
        if ($fp) {
            fputcsv($fp, $headers);
            fclose($fp);
            chmod(CSV_FILE, 0644);
        }
    }
}

/**
 * Write registration to CSV with file locking
 */
function writeRegistrationToCsv(array $data): bool
{
    ensureCsvFileExists();

    $fp = fopen(CSV_FILE, 'a');
    if (!$fp) {
        error_log("Failed to open CSV file for writing: " . CSV_FILE);
        return false;
    }

    // Exclusive lock for writing
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        error_log("Failed to acquire lock on CSV file");
        return false;
    }

    // Prepare row data
    $row = [
        $data['name'],
        $data['email'],
        $data['mobile'],
        $data['password_hash'],
        $data['course'],
        $data['year'],
        $data['gender'],
        $data['terms'] === 'on' ? 'Yes' : 'No',
        date('Y-m-d H:i:s')
    ];

    $result = fputcsv($fp, $row);

    // Release lock
    flock($fp, LOCK_UN);
    fclose($fp);

    return $result !== false;
}

/**
 * Check if email already exists in CSV
 */
function emailExists(string $email): bool
{
    if (!file_exists(CSV_FILE)) {
        return false;
    }

    $fp = fopen(CSV_FILE, 'r');
    if (!$fp) {
        return false;
    }

    // Shared lock for reading
    flock($fp, LOCK_SH);

    // Skip header
    fgetcsv($fp);

    while (($row = fgetcsv($fp)) !== false) {
        if (isset($row[1]) && strcasecmp($row[1], $email) === 0) {
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        }
    }

    flock($fp, LOCK_UN);
    fclose($fp);

    return false;
}

// Start session for CSRF
session_start();

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: ../register.html?error=invalid_method');
    exit;
}

// Verify CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($csrfToken)) {
    http_response_code(403);
    header('Location: ../register.html?error=csrf_invalid');
    exit;
}

// Collect and validate all fields
$allErrors = [];
$cleanData = [];

// Validate each field
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

[$cleanData['course'], $errors] = validateCourse($_POST['course'] ?? '');
$allErrors = array_merge($allErrors, $errors);

[$cleanData['year'], $errors] = validateYear($_POST['year'] ?? '');
$allErrors = array_merge($allErrors, $errors);

[$cleanData['gender'], $errors] = validateGender($_POST['gender'] ?? '');
$allErrors = array_merge($allErrors, $errors);

[$cleanData['terms'], $errors] = validateTerms($_POST['terms'] ?? '');
$allErrors = array_merge($allErrors, $errors);

// Check for duplicate email (only if email is valid)
if (empty($allErrors) && emailExists($cleanData['email'])) {
    $allErrors[] = 'An account with this email already exists';
}

// Hash password only if no errors so far
if (empty($allErrors)) {
    $cleanData['password_hash'] = password_hash($cleanData['password'], PASSWORD_DEFAULT);

    // Write to CSV
    if (writeRegistrationToCsv($cleanData)) {
        // Regenerate CSRF token for next submission
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Success redirect
        header('Location: register.html?success=1');
        exit;
    } else {
        $allErrors[] = 'Registration failed. Please try again later.';
    }
}

// On error, redirect back with errors
$errorString = urlencode(implode('|', $allErrors));
header('Location: ../register.html?errors=' . $errorString);
exit;