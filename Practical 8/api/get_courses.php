<?php
/**
 * Get Courses API Endpoint
 * Returns courses as JSON for dynamic dropdown population
 * Used by JavaScript fetch() in registration forms
 */

// Prevent direct access
if (basename($_SERVER['SCRIPT_FILENAME']) !== 'get_courses.php') {
    http_response_code(403);
    exit('Direct access forbidden');
}

require_once __DIR__ . '/db_connect.php';

try {
    // Set JSON header
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');

    // Only allow GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed. Use GET.'
        ]);
        exit;
    }

    $pdo = getDbConnection();

    $sql = "
        SELECT `course_id`, `course_name`
        FROM `courses`
        ORDER BY `course_name`
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $courses = $stmt->fetchAll();

    // Return JSON response
    echo json_encode([
        'success' => true,
        'count' => count($courses),
        'data' => $courses
    ], JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Get courses API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}