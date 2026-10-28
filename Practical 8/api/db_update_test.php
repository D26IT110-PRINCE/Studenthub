<?php
/**
 * Database UPDATE Test Script
 * Tests UPDATE operations using PDO prepared statements with named placeholders
 * Demonstrates: UPDATE with WHERE clause, multiple fields, parameter binding
 */

// Prevent direct access
if (basename($_SERVER['SCRIPT_FILENAME']) === 'db_update_test.php') {
    define('TEST_MODE', true);
} else {
    http_response_code(403);
    exit('Direct access forbidden');
}

require_once __DIR__ . '/db_connect.php';

try {
    echo "<h2>Database UPDATE Test</h2>\n";
    $pdo = getDbConnection();
    echo "<p><strong>Connection:</strong> Successful</p>\n";

    // =====================================================================
    // TEST 1: Update single field (student year)
    // =====================================================================
    echo "<h3>Test 1: Update Single Field - Student Year</h3>\n";

    $studentId = 1;
    $newYear = 3;

    $beforeStmt = $pdo->prepare("SELECT `name`, `year` FROM `students` WHERE `id` = :id");
    $beforeStmt->bindValue(':id', $studentId, PDO::PARAM_INT);
    $beforeStmt->execute();
    $before = $beforeStmt->fetch();

    if ($before) {
        echo "<p><strong>Before:</strong> " . htmlspecialchars($before['name']) . " - Year " . htmlspecialchars($before['year']) . "</p>\n";

        $sql = "UPDATE `students` SET `year` = :year WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':year', $newYear, PDO::PARAM_INT);
        $stmt->bindValue(':id', $studentId, PDO::PARAM_INT);
        $result = $stmt->execute();

        $affected = $stmt->rowCount();

        if ($affected > 0) {
            echo "<p><strong>Success!</strong> $affected row(s) updated.</p>\n";

            $afterStmt = $pdo->prepare("SELECT `name`, `year` FROM `students` WHERE `id` = :id");
            $afterStmt->bindValue(':id', $studentId, PDO::PARAM_INT);
            $afterStmt->execute();
            $after = $afterStmt->fetch();
            echo "<p><strong>After:</strong> " . htmlspecialchars($after['name']) . " - Year " . htmlspecialchars($after['year']) . "</p>\n";
        } else {
            echo "<p><strong>No changes!</strong> Row not found or value unchanged.</p>\n";
        }
    } else {
        echo "<p><strong>Skipped!</strong> Student ID $studentId not found.</p>\n";
    }

    // =====================================================================
    // TEST 2: Update multiple fields
    // =====================================================================
    echo "<h3>Test 2: Update Multiple Fields</h3>\n";

    $studentId = 2;
    $updates = [
        'mobile' => '+918888888888',
        'gender' => 'female',
    ];

    $beforeStmt = $pdo->prepare("SELECT `name`, `mobile`, `gender` FROM `students` WHERE `id` = :id");
    $beforeStmt->bindValue(':id', $studentId, PDO::PARAM_INT);
    $beforeStmt->execute();
    $before = $beforeStmt->fetch();

    if ($before) {
        echo "<p><strong>Before:</strong> " . htmlspecialchars($before['name']) . "</p>\n";
        echo "<ul>\n";
        foreach ($updates as $field => $value) {
            echo "<li>$field: " . htmlspecialchars($before[$field] ?? 'N/A') . " -> " . htmlspecialchars($value) . "</li>\n";
        }
        echo "</ul>\n";

        $setParts = [];
        foreach ($updates as $field => $value) {
            $setParts[] = "`$field` = :$field";
        }

        $sql = "UPDATE `students` SET " . implode(', ', $setParts) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);

        foreach ($updates as $field => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(":$field", $value, $type);
        }
        $stmt->bindValue(':id', $studentId, PDO::PARAM_INT);

        $result = $stmt->execute();
        $affected = $stmt->rowCount();

        if ($affected > 0) {
            echo "<p><strong>Success!</strong> $affected row(s) updated.</p>\n";

            $afterStmt = $pdo->prepare("SELECT `name`, `mobile`, `gender` FROM `students` WHERE `id` = :id");
            $afterStmt->bindValue(':id', $studentId, PDO::PARAM_INT);
            $afterStmt->execute();
            $after = $afterStmt->fetch();
            echo "<p><strong>After:</strong></p>\n<ul>\n";
            foreach ($updates as $field => $value) {
                echo "<li>$field: " . htmlspecialchars($after[$field]) . "</li>\n";
            }
            echo "</ul>\n";
        } else {
            echo "<p><strong>No changes!</strong> Row not found or values unchanged.</p>\n";
        }
    } else {
        echo "<p><strong>Skipped!</strong> Student ID $studentId not found.</p>\n";
    }

    // =====================================================================
    // TEST 3: Update with transaction (atomic update)
    // =====================================================================
    echo "<h3>Test 3: Update with Transaction</h3>\n";

    $studentId1 = 3;
    $studentId2 = 4;

    try {
        $pdo->beginTransaction();

        $sql1 = "UPDATE `students` SET `year` = `year` + 1 WHERE `id` = :id1";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->bindValue(':id1', $studentId1, PDO::PARAM_INT);
        $stmt1->execute();

        $sql2 = "UPDATE `students` SET `year` = `year` + 1 WHERE `id` = :id2";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->bindValue(':id2', $studentId2, PDO::PARAM_INT);
        $stmt2->execute();

        $pdo->commit();

        echo "<p><strong>Transaction committed!</strong> Both students updated atomically.</p>\n";
        echo "<p>Student $studentId1 and $studentId2 year incremented by 1.</p>\n";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<p><strong>Transaction rolled back!</strong> Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }

    // =====================================================================
    // TEST 4: Update course name
    // =====================================================================
    echo "<h3>Test 4: Update Course Name</h3>\n";

    $courseId = 1;
    $newName = 'B.Tech (Updated)';

    $beforeStmt = $pdo->prepare("SELECT `course_id`, `course_name` FROM `courses` WHERE `course_id` = :id");
    $beforeStmt->bindValue(':id', $courseId, PDO::PARAM_INT);
    $beforeStmt->execute();
    $before = $beforeStmt->fetch();

    if ($before) {
        echo "<p><strong>Before:</strong> " . htmlspecialchars($before['course_name']) . "</p>\n";

        $sql = "UPDATE `courses` SET `course_name` = :name WHERE `course_id` = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':name', $newName, PDO::PARAM_STR);
        $stmt->bindValue(':id', $courseId, PDO::PARAM_INT);
        $result = $stmt->execute();

        $affected = $stmt->rowCount();

        if ($affected > 0) {
            echo "<p><strong>Success!</strong> $affected row(s) updated.</p>\n";

            $afterStmt = $pdo->prepare("SELECT `course_id`, `course_name` FROM `courses` WHERE `course_id` = :id");
            $afterStmt->bindValue(':id', $courseId, PDO::PARAM_INT);
            $afterStmt->execute();
            $after = $afterStmt->fetch();
            echo "<p><strong>After:</strong> " . htmlspecialchars($after['course_name']) . "</p>\n";
        }
    }

    // =====================================================================
    // TEST 5: Update event
    // =====================================================================
    echo "<h3>Test 5: Update Event</h3>\n";

    $eventId = 1;
    $newLocation = 'Updated Venue';

    $beforeStmt = $pdo->prepare("SELECT `event_id`, `title`, `location` FROM `events` WHERE `event_id` = :id");
    $beforeStmt->bindValue(':id', $eventId, PDO::PARAM_INT);
    $beforeStmt->execute();
    $before = $beforeStmt->fetch();

    if ($before) {
        echo "<p><strong>Before:</strong> " . htmlspecialchars($before['title']) . " - " . htmlspecialchars($before['location']) . "</p>\n";

        $sql = "UPDATE `events` SET `location` = :location WHERE `event_id` = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':location', $newLocation, PDO::PARAM_STR);
        $stmt->bindValue(':id', $eventId, PDO::PARAM_INT);
        $result = $stmt->execute();

        $affected = $stmt->rowCount();

        if ($affected > 0) {
            echo "<p><strong>Success!</strong> $affected row(s) updated.</p>\n";

            $afterStmt = $pdo->prepare("SELECT `event_id`, `title`, `location` FROM `events` WHERE `event_id` = :id");
            $afterStmt->bindValue(':id', $eventId, PDO::PARAM_INT);
            $afterStmt->execute();
            $after = $afterStmt->fetch();
            echo "<p><strong>After:</strong> " . htmlspecialchars($after['title']) . " - " . htmlspecialchars($after['location']) . "</p>\n";
        }
    }

    echo "<h3>UPDATE Tests Complete</h3>\n";

} catch (PDOException $e) {
    echo "<h2>Database Error</h2>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    error_log("DB Update Test Error: " . $e->getMessage());
    exit(1);
}