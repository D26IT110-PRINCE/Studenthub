<?php
/**
 * Students JSON API
 * Practical 13: Asynchronous CRUD using JSON endpoints.
 * Handles GET (Read) and POST (Create, Update, Delete).
 */

// Set response header to JSON for all requests
header('Content-Type: application/json; charset=utf-8');

// 1. Security Guard: Admin only
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "admin") {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized access. Admin role required."]);
    exit;
}

require_once 'db_connect_mysqli.php';

$method = $_SERVER['REQUEST_METHOD'];

// --- GET REQUEST: Read (List) ---
if ($method === 'GET') {
    $search = trim($_GET['q'] ?? '');

    $sql = "SELECT s.id, s.name, s.email, s.mobile, c.course_name, s.year, s.gender
            FROM students s
            INNER JOIN courses c ON s.course_id = c.course_id";

    if ($search !== '') {
        $sql .= " WHERE (s.name LIKE ? OR s.email LIKE ? OR c.course_name LIKE ?)";
        $stmt = $conn->prepare($sql);
        $like = "%$search%";
        $stmt->bind_param("sss", $like, $like, $like);
    } else {
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $students = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode(["success" => true, "data" => $students]);
    exit;
}

// --- POST REQUEST: Create, Update, Delete ---
if ($method === 'POST') {
    // PHP doesn't populate $_POST for JSON bodies, so we read the raw input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid JSON input."]);
        exit;
    }

    $action = $input['action'] ?? '';

    if ($action === 'add') {
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $mobile = trim($input['mobile'] ?? '');
        $courseId = filter_var($input['course_id'] ?? null, FILTER_VALIDATE_INT);
        $year = filter_var($input['year'] ?? null, FILTER_VALIDATE_INT);
        $gender = $input['gender'] ?? '';

        if (!$name || !$email || !$courseId || !$year) {
            echo json_encode(["success" => false, "message" => "All required fields must be filled."]);
            exit;
        }

        // Get course name for the 'course' column in students table
        $cStmt = $conn->prepare("SELECT course_name FROM courses WHERE course_id = ?");
        $cStmt->bind_param("i", $courseId);
        $cStmt->execute();
        $courseName = $cStmt->get_result()->fetch_assoc()['course_name'] ?? '';
        $cStmt->close();

        $stmt = $conn->prepare("INSERT INTO students (name, email, mobile, course, year, gender, course_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssisi", $name, $email, $mobile, $courseName, $year, $gender, $courseId);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Student added successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
        }
        $stmt->close();
    }
    elseif ($action === 'update') {
        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $mobile = trim($input['mobile'] ?? '');
        $courseId = filter_var($input['course_id'] ?? null, FILTER_VALIDATE_INT);
        $year = filter_var($input['year'] ?? null, FILTER_VALIDATE_INT);
        $gender = $input['gender'] ?? '';

        if (!$id) {
            echo json_encode(["success" => false, "message" => "Invalid student ID."]);
            exit;
        }

        $cStmt = $conn->prepare("SELECT course_name FROM courses WHERE course_id = ?");
        $cStmt->bind_param("i", $courseId);
        $cStmt->execute();
        $courseName = $cStmt->get_result()->fetch_assoc()['course_name'] ?? '';
        $cStmt->close();

        $stmt = $conn->prepare("UPDATE students SET name=?, email=?, mobile=?, course=?, year=?, gender=?, course_id=? WHERE id=?");
        $stmt->bind_param("ssssisii", $name, $email, $mobile, $courseName, $year, $gender, $courseId, $id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Student updated successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
        }
        $stmt->close();
    }
    elseif ($action === 'delete') {
        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            echo json_encode(["success" => false, "message" => "Invalid student ID."]);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Student deleted successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
        }
        $stmt->close();
    }
    else {
        echo json_encode(["success" => false, "message" => "Invalid action specified."]);
    }
}

$conn->close();
?>
