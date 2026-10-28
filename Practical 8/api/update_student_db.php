<?php
/**
 * Update Student - Database Version
 * Processes student updates via specific WHERE clauses using PDO prepared statements
 * Handles: profile updates, year changes, contact info updates
 */

// Prevent direct access
if (basename($_SERVER['SCRIPT_FILENAME']) !== 'update_student_db.php') {
    http_response_code(403);
    exit('Direct access forbidden');
}

require_once __DIR__ . '/db_connect.php';

// Start session for CSRF
session_start();

/**
 * Validate CSRF token
 */
function validateCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input
 */
function sanitizeInput(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Update student record using prepared statement
 *
 * @param PDO $pdo Database connection
 * @param int $studentId Student ID to update
 * @param array $updates Fields to update
 * @return array ['success' => bool, 'message' => string, 'affected' => int]
 */
function updateStudent(PDO $pdo, int $studentId, array $updates): array
{
    // Validate student exists
    $checkStmt = $pdo->prepare("SELECT `id` FROM `students` WHERE `id` = :id");
    $checkStmt->bindValue(':id', $studentId, PDO::PARAM_INT);
    $checkStmt->execute();

    if (!$checkStmt->fetch()) {
        return ['success' => false, 'message' => 'Student not found', 'affected' => 0];
    }

    // Build dynamic UPDATE query with named placeholders
    $allowedFields = ['name', 'email', 'mobile', 'year', 'gender', 'course_id'];
    $setParts = [];
    foreach ($updates as $field => $value) {
        // Validate field name to prevent injection
        if (!in_array($field, $allowedFields, true)) {
            continue;
        }
        $setParts[] = "`$field` = :$field";
    }

    if (empty($setParts)) {
        return ['success' => false, 'message' => 'No valid fields to update', 'affected' => 0];
    }

    $sql = "UPDATE `students` SET " . implode(', ', $setParts) . " WHERE `id` = :id";
    $stmt = $pdo->prepare($sql);

    // Bind all values with appropriate types
    foreach ($updates as $field => $value) {
        if (!in_array($field, $allowedFields, true)) {
            continue;
        }
        $type = (is_int($value) || $field === 'year' || $field === 'course_id')
            ? PDO::PARAM_INT
            : PDO::PARAM_STR;
        $stmt->bindValue(":$field", $value, $type);
    }
    $stmt->bindValue(':id', $studentId, PDO::PARAM_INT);

    $result = $stmt->execute();
    $affected = $stmt->rowCount();

    return [
        'success' => $result,
        'message' => $affected > 0 ? 'Student updated successfully' : 'No changes made',
        'affected' => $affected
    ];
}

/**
 * Check if course exists
 */
function courseExists(PDO $pdo, int $courseId): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM `courses` WHERE `course_id` = :id LIMIT 1");
    $stmt->bindValue(':id', $courseId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch() !== false;
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: ../view_students_db.php?error=invalid_method');
    exit;
}

// Verify CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($csrfToken)) {
    http_response_code(403);
    header('Location: ../view_students_db.php?error=csrf_invalid');
    exit;
}

try {
    $pdo = getDbConnection();

    $action = $_POST['action'] ?? '';
    $studentId = (int)($_POST['student_id'] ?? 0);

    if ($studentId <= 0) {
        header('Location: view_students_db.php?error=invalid_student_id');
        exit;
    }

    $response = ['success' => false, 'message' => 'Unknown action'];

    switch ($action) {
        case 'update_profile':
            $updates = [];

            if (!empty($_POST['name'])) {
                $updates['name'] = sanitizeInput($_POST['name']);
            }

            if (!empty($_POST['email'])) {
                $email = sanitizeInput($_POST['email']);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $checkStmt = $pdo->prepare("SELECT `id` FROM `students` WHERE `email` = :email AND `id` != :id");
                    $checkStmt->bindValue(':email', $email, PDO::PARAM_STR);
                    $checkStmt->bindValue(':id', $studentId, PDO::PARAM_INT);
                    $checkStmt->execute();

                    if ($checkStmt->fetch()) {
                        $response = ['success' => false, 'message' => 'Email already in use'];
                    } else {
                        $updates['email'] = $email;
                    }
                } else {
                    $response = ['success' => false, 'message' => 'Invalid email format'];
                }
            }

            if (!empty($_POST['mobile'])) {
                $mobile = sanitizeInput($_POST['mobile']);
                if (preg_match('/^(\+91)?[6-9]\d{9}$/', $mobile)) {
                    $updates['mobile'] = $mobile;
                } else {
                    $response = ['success' => false, 'message' => 'Invalid mobile format'];
                }
            }

            if (isset($_POST['year']) && in_array($_POST['year'], ['1', '2', '3', '4'], true)) {
                $updates['year'] = (int)$_POST['year'];
            }

            if (isset($_POST['gender']) && in_array($_POST['gender'], ['male', 'female', 'other'], true)) {
                $updates['gender'] = sanitizeInput($_POST['gender']);
            }

            if (isset($_POST['course']) && ctype_digit($_POST['course'])) {
                $courseId = (int)$_POST['course'];
                if (courseExists($pdo, $courseId)) {
                    $updates['course_id'] = $courseId;
                } else {
                    $response = ['success' => false, 'message' => 'Invalid course selected'];
                }
            }

            if (!empty($updates) && !isset($response['success'])) {
                $response = updateStudent($pdo, $studentId, $updates);
            }
            break;

        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }

    $redirectUrl = '../view_students_db.php?' . http_build_query([
        'update_result' => $response['success'] ? 'success' : 'error',
        'message' => $response['message']
    ]);

    header('Location: ' . $redirectUrl);
    exit;

} catch (PDOException $e) {
    error_log("Update student error: " . $e->getMessage());
    header('Location: view_students_db.php?update_result=error&message=Database error occurred');
    exit;
}