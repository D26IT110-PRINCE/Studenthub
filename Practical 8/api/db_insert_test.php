<?php
/**
 * Database INSERT Test Script
 * Tests INSERT operations using PDO prepared statements with named placeholders
 * 3-Step Process: prepare() -> bindValue() -> execute()
 */

// Prevent direct access
if (basename($_SERVER['SCRIPT_FILENAME']) === 'db_insert_test.php') {
    define('TEST_MODE', true);
} else {
    http_response_code(403);
    exit('Direct access forbidden');
}

require_once __DIR__ . '/db_connect.php';

try {
    echo "<h2>Database INSERT Test</h2>\n";
    $pdo = getDbConnection();
    echo "<p><strong>Connection:</strong> Successful</p>\n";

    // =====================================================================
    // TEST 1: Insert a new course
    // =====================================================================
    echo "<h3>Test 1: Insert New Course</h3>\n";

    $newCourseName = 'PhD Computer Science';

    $sql = "INSERT INTO `courses` (`course_name`) VALUES (:course_name)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':course_name', $newCourseName, PDO::PARAM_STR);
    $result = $stmt->execute();

    if ($result) {
        $newCourseId = $pdo->lastInsertId();
        echo "<p><strong>Success!</strong> Course inserted with ID: " . $newCourseId . "</p>\n";
    } else {
        echo "<p><strong>Failed!</strong> Course insertion failed.</p>\n";
    }

    // =====================================================================
    // TEST 2: Insert a new student
    // =====================================================================
    echo "<h3>Test 2: Insert New Student</h3>\n";

    $studentData = [
        'name' => 'Test Student ' . time(),
        'email' => 'test.student' . time() . '@studenthub.edu',
        'mobile' => '+919999999999',
        'course_id' => 1, // B.Tech
        'year' => 1,
        'gender' => 'other',
    ];

    // Verify course exists first
    $courseStmt = $pdo->prepare("SELECT `course_id` FROM `courses` WHERE `course_id` = :course_id");
    $courseStmt->bindValue(':course_id', $studentData['course_id'], PDO::PARAM_INT);
    $courseStmt->execute();
    $course = $courseStmt->fetch();

    if ($course) {
        $sql = "
            INSERT INTO `students` (
                `name`, `email`, `mobile`, `course_id`, `year`, `gender`
            ) VALUES (
                :name, :email, :mobile, :course_id, :year, :gender
            )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':name', $studentData['name'], PDO::PARAM_STR);
        $stmt->bindValue(':email', $studentData['email'], PDO::PARAM_STR);
        $stmt->bindValue(':mobile', $studentData['mobile'], PDO::PARAM_STR);
        $stmt->bindValue(':course_id', $studentData['course_id'], PDO::PARAM_INT);
        $stmt->bindValue(':year', $studentData['year'], PDO::PARAM_INT);
        $stmt->bindValue(':gender', $studentData['gender'], PDO::PARAM_STR);

        $result = $stmt->execute();

        if ($result) {
            $newStudentId = $pdo->lastInsertId();
            echo "<p><strong>Success!</strong> Student inserted with ID: " . $newStudentId . "</p>\n";
            echo "<p>Email: " . htmlspecialchars($studentData['email']) . "</p>\n";
        } else {
            echo "<p><strong>Failed!</strong> Student insertion failed.</p>\n";
        }
    } else {
        echo "<p><strong>Failed!</strong> Course not found.</p>\n";
    }

    // =====================================================================
    // TEST 3: Insert event registration (many-to-many)
    // =====================================================================
    echo "<h3>Test 3: Insert Event Registration</h3>\n";

    // Get first student
    $studentStmt = $pdo->prepare("SELECT `id` FROM `students` LIMIT 1");
    $studentStmt->execute();
    $student = $studentStmt->fetch();

    // Get first event
    $eventStmt = $pdo->prepare("SELECT `event_id` FROM `events` LIMIT 1");
    $eventStmt->execute();
    $event = $eventStmt->fetch();

    if ($student && $event) {
        $sql = "
            INSERT INTO `registrations` (`student_id`, `event_id`)
            VALUES (:student_id, :event_id)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':student_id', $student['id'], PDO::PARAM_INT);
        $stmt->bindValue(':event_id', $event['event_id'], PDO::PARAM_INT);

        $result = $stmt->execute();

        if ($result) {
            $newRegId = $pdo->lastInsertId();
            echo "<p><strong>Success!</strong> Registration inserted with ID: " . $newRegId . "</p>\n";
            echo "<p>Student ID: " . $student['id'] . ", Event ID: " . $event['event_id'] . "</p>\n";
        } else {
            echo "<p><strong>Failed!</strong> Registration insertion failed (might be duplicate).</p>\n";
        }
    } else {
        echo "<p><strong>Skipped!</strong> No student or event available for registration test.</p>\n";
    }

    // =====================================================================
    // TEST 4: Insert a new event
    // =====================================================================
    echo "<h3>Test 4: Insert New Event</h3>\n";

    $sql = "
        INSERT INTO `events` (`title`, `event_date`, `location`, `category`)
        VALUES (:title, :event_date, :location, :category)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':title', 'Test Event ' . date('Y-m-d H:i:s'), PDO::PARAM_STR);
    $stmt->bindValue(':event_date', '2026-12-20', PDO::PARAM_STR);
    $stmt->bindValue(':location', 'Test Venue', PDO::PARAM_STR);
    $stmt->bindValue(':category', 'Test Category', PDO::PARAM_STR);
    $result = $stmt->execute();

    if ($result) {
        $newEventId = $pdo->lastInsertId();
        echo "<p><strong>Success!</strong> Event inserted with ID: " . $newEventId . "</p>\n";
    } else {
        echo "<p><strong>Failed!</strong> Event insertion failed.</p>\n";
    }

    echo "<h3>INSERT Tests Complete</h3>\n";

} catch (PDOException $e) {
    echo "<h2>Database Error</h2>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    error_log("DB Insert Test Error: " . $e->getMessage());
    exit(1);
}