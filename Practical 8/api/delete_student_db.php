<?php
/**
 * Delete Student - Database Version
 * Processes student deletions via specific WHERE clauses using PDO prepared statements
 * Supports: hard delete (permanent), registration cancellation
 */

// Prevent direct access
if (basename($_SERVER['SCRIPT_FILENAME']) !== 'delete_student_db.php') {
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
 * Hard delete - permanently remove student and cascade related records
 * Uses foreign key CASCADE constraints
 *
 * @param PDO $pdo Database connection
 * @param int $studentId Student ID
 * @return array ['success' => bool, 'message' => string, 'affected' => int, 'cascaded' => array]
 */
function hardDeleteStudent(PDO $pdo, int $studentId): array
{
    // Get student info before deletion
    $checkStmt = $pdo->prepare("SELECT `id`, `name`, `email` FROM `students` WHERE `id` = :id");
    $checkStmt->bindValue(':id', $studentId, PDO::PARAM_INT);
    $checkStmt->execute();
    $student = $checkStmt->fetch();

    if (!$student) {
        return ['success' => false, 'message' => 'Student not found', 'affected' => 0, 'cascaded' => []];
    }

    // Count related records that will be cascade deleted
    $cascaded = [];

    // Check registrations
    $regStmt = $pdo->prepare("SELECT COUNT(*) FROM `registrations` WHERE `student_id` = :id");
    $regStmt->bindValue(':id', $studentId, PDO::PARAM_INT);
    $regStmt->execute();
    $cascaded['registrations'] = (int)$regStmt->fetchColumn();

    try {
        // Begin transaction for atomic operation
        $pdo->beginTransaction();

        // Delete student - CASCADE will handle registrations
        $sql = "DELETE FROM `students` WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $studentId, PDO::PARAM_INT);
        $result = $stmt->execute();

        $affected = $stmt->rowCount();

        // Commit transaction
        $pdo->commit();

        return [
            'success' => $result,
            'message' => $result
                ? "Student '{$student['name']}' permanently deleted"
                : 'Deletion failed',
            'affected' => $affected,
            'cascaded' => $cascaded
        ];

    } catch (Exception $e) {
        // Rollback on error
        $pdo->rollBack();
        return [
            'success' => false,
            'message' => 'Deletion failed: ' . $e->getMessage(),
            'affected' => 0,
            'cascaded' => []
        ];
    }
}

/**
 * Delete event registration for a student
 *
 * @param PDO $pdo Database connection
 * @param int $registrationId Registration ID
 * @param int $studentId Student ID (for authorization)
 * @return array ['success' => bool, 'message' => string]
 */
function deleteRegistration(PDO $pdo, int $registrationId, int $studentId): array
{
    // Verify registration belongs to student
    $checkStmt = $pdo->prepare("SELECT `reg_id` FROM `registrations` WHERE `reg_id` = :reg_id AND `student_id` = :stu_id");
    $checkStmt->bindValue(':reg_id', $registrationId, PDO::PARAM_INT);
    $checkStmt->bindValue(':stu_id', $studentId, PDO::PARAM_INT);
    $checkStmt->execute();

    if (!$checkStmt->fetch()) {
        return ['success' => false, 'message' => 'Registration not found or unauthorized'];
    }

    // Delete registration
    $sql = "DELETE FROM `registrations` WHERE `reg_id` = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $registrationId, PDO::PARAM_INT);
    $result = $stmt->execute();

    return [
        'success' => $result,
        'message' => $result ? 'Registration cancelled successfully' : 'Cancellation failed',
        'affected' => $stmt->rowCount()
    ];
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
    $registrationId = (int)($_POST['registration_id'] ?? 0);

    $response = ['success' => false, 'message' => 'Unknown action'];

    switch ($action) {
        case 'hard_delete':
            // Require confirmation
            $confirm = $_POST['confirm'] ?? '';
            if ($studentId > 0 && $confirm === 'DELETE') {
                $response = hardDeleteStudent($pdo, $studentId);
            } else {
                $response = ['success' => false, 'message' => 'Confirmation required: type "DELETE" to confirm'];
            }
            break;

        case 'delete_registration':
            if ($registrationId > 0 && $studentId > 0) {
                $response = deleteRegistration($pdo, $registrationId, $studentId);
            } else {
                $response = ['success' => false, 'message' => 'Invalid registration or student ID'];
            }
            break;

        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }

    // Redirect back with result
    $redirectUrl = '../view_students_db.php?' . http_build_query([
        'delete_result' => $response['success'] ? 'success' : 'error',
        'message' => $response['message']
    ]);

    header('Location: ' . $redirectUrl);
    exit;

} catch (PDOException $e) {
    error_log("Delete student error: " . $e->getMessage());
    header('Location: view_students_db.php?delete_result=error&message=Database error occurred');
    exit;
}