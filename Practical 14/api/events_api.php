<?php
/**
 * Events JSON API
 * Practical 13: Asynchronous CRUD using JSON endpoints.
 * Handles GET (Read) and POST (Create, Update, Delete).
 */

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

    $sql = "SELECT * FROM events";

    if ($search !== '') {
        $sql .= " WHERE title LIKE ? OR location LIKE ? OR category LIKE ?";
        $stmt = $conn->prepare($sql);
        $like = "%$search%";
        $stmt->bind_param("sss", $like, $like, $like);
    } else {
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $events = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode(["success" => true, "data" => $events]);
    exit;
}

// --- POST REQUEST: Create, Update, Delete ---
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid JSON input."]);
        exit;
    }

    $action = $input['action'] ?? '';

    if ($action === 'add') {
        $title = trim($input['title'] ?? '');
        $date = $input['date'] ?? '';
        $location = trim($input['location'] ?? '');
        $category = trim($input['category'] ?? '');

        if (!$title || !$date || !$location || !$category) {
            echo json_encode(["success" => false, "message" => "All required fields must be filled."]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO events (title, event_date, location, category) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $title, $date, $location, $category);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Event added successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
        }
        $stmt->close();
    }
    elseif ($action === 'update') {
        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        $title = trim($input['title'] ?? '');
        $date = $input['date'] ?? '';
        $location = trim($input['location'] ?? '');
        $category = trim($input['category'] ?? '');

        if (!$id) {
            echo json_encode(["success" => false, "message" => "Invalid event ID."]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE events SET title=?, event_date=?, location=?, category=? WHERE event_id=?");
        $stmt->bind_param("ssssi", $title, $date, $location, $category, $id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Event updated successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
        }
        $stmt->close();
    }
    elseif ($action === 'delete') {
        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            echo json_encode(["success" => false, "message" => "Invalid event ID."]);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Event deleted successfully."]);
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
