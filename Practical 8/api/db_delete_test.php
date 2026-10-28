<?php
/**
 * Database DELETE Test Script
 * Tests DELETE operations using PDO prepared statements with named placeholders
 * Demonstrates: DELETE with WHERE clause, transactions, CASCADE deletes
 */

// Prevent direct access
if (basename($_SERVER['SCRIPT_FILENAME']) === 'db_delete_test.php') {
    define('TEST_MODE', true);
} else {
    http_response_code(403);
    exit('Direct access forbidden');
}

require_once __DIR__ . '/db_connect.php';

try {
    echo "<h2>Database DELETE Test</h2>\n";
    $pdo = getDbConnection();
    echo "<p><strong>Connection:</strong> Successful</p>\n";

    // =====================================================================
    // TEST 1: Hard Delete with WHERE clause - Remove Test Course
    // =====================================================================
    echo "<h3>Test 1: Hard Delete - Remove Test Course</h3>\n";

    // Find the test course we inserted earlier
    $courseStmt = $pdo->prepare("SELECT `course_id`, `course_name` FROM `courses` WHERE `course_name` = 'PhD Computer Science'");
    $courseStmt->execute();
    $course = $courseStmt->fetch();

    if ($course) {
        echo "<p><strong>Deleting:</strong> " . htmlspecialchars($course['course_name']) . " (ID: " . $course['course_id'] . ")</p>\n";

        // Check if any students reference this course (ON DELETE RESTRICT)
        $studentCheck = $pdo->prepare("SELECT COUNT(*) FROM `students` WHERE `course_id` = :id");
        $studentCheck->bindValue(':id', $course['course_id'], PDO::PARAM_INT);
        $studentCheck->execute();
        $studentCount = $studentCheck->fetchColumn();

        if ($studentCount > 0) {
            echo "<p><strong>Cannot delete!</strong> $studentCount student(s) reference this course. Foreign key constraint (ON DELETE RESTRICT) prevents deletion.</p>\n";
        } else {
            $sql = "DELETE FROM `courses` WHERE `course_id` = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', $course['course_id'], PDO::PARAM_INT);
            $result = $stmt->execute();

            $affected = $stmt->rowCount();

            if ($affected > 0) {
                echo "<p><strong>Success!</strong> Course permanently deleted. $affected row(s) affected.</p>\n";
            } else {
                echo "<p><strong>Failed!</strong> Course not deleted.</p>\n";
            }
        }
    } else {
        echo "<p><strong>Skipped!</strong> Test course not found (may have been deleted already).</p>\n";
    }

    // =====================================================================
    // TEST 2: Hard Delete Student with Transaction
    // =====================================================================
    echo "<h3>Test 2: Hard Delete Student with Transaction (CASCADE registrations)</h3>\n";

    // Insert a test student first
    $testEmail = 'delete_test_' . time() . '@studenthub.edu';

    $insertSql = "
        INSERT INTO `students` (`name`, `email`, `mobile`, `course_id`, `year`, `gender`)
        VALUES ('Delete Test User', :email, '+917777777777', 1, 1, 'other')
    ";

    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->bindValue(':email', $testEmail, PDO::PARAM_STR);
    $insertStmt->execute();
    $testStudentId = $pdo->lastInsertId();

    echo "<p>Inserted test student with ID: $testStudentId</p>\n";

    // Also insert a registration for this student to test CASCADE
    $eventStmt = $pdo->prepare("SELECT `event_id` FROM `events` LIMIT 1");
    $eventStmt->execute();
    $event = $eventStmt->fetch();

    if ($event) {
        $regSql = "INSERT INTO `registrations` (`student_id`, `event_id`) VALUES (:student_id, :event_id)";
        $regStmt = $pdo->prepare($regSql);
        $regStmt->bindValue(':student_id', $testStudentId, PDO::PARAM_INT);
        $regStmt->bindValue(':event_id', $event['event_id'], PDO::PARAM_INT);
        $regStmt->execute();
        echo "<p>Inserted test registration for student $testStudentId</p>\n";
    }

    try {
        $pdo->beginTransaction();

        $deleteSql = "DELETE FROM `students` WHERE `id` = :id";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->bindValue(':id', $testStudentId, PDO::PARAM_INT);
        $deleteStmt->execute();

        $affected = $deleteStmt->rowCount();

        $pdo->commit();

        echo "<p><strong>Transaction committed!</strong> $affected row(s) deleted atomically.</p>\n";
        echo "<p>CASCADE DELETE: Associated registrations also removed.</p>\n";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<p><strong>Transaction rolled back!</strong> Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }

    // =====================================================================
    // TEST 3: CASCADE Delete Demonstration - Event with Registrations
    // =====================================================================
    echo "<h3>Test 3: CASCADE Delete - Event with Registrations</h3>\n";

    $regStmt = $pdo->prepare("
        SELECT e.`event_id`, e.`title`, COUNT(r.`reg_id`) AS `reg_count`
        FROM `events` e
        INNER JOIN `registrations` r ON e.`event_id` = r.`event_id`
        GROUP BY e.`event_id`, e.`title`
        ORDER BY `reg_count` DESC
        LIMIT 1
    ");
    $regStmt->execute();
    $eventWithRegs = $regStmt->fetch();

    if ($eventWithRegs) {
        echo "<p><strong>Event:</strong> " . htmlspecialchars($eventWithRegs['title']) . " (ID: " . $eventWithRegs['event_id'] . ")</p>\n";
        echo "<p><strong>Registrations:</strong> " . $eventWithRegs['reg_count'] . "</p>\n";
        echo "<p><strong>Action:</strong> Deleting this event will CASCADE delete their registrations (FK: ON DELETE CASCADE).</p>\n";

        /*
        $sql = "DELETE FROM `events` WHERE `event_id` = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $eventWithRegs['event_id'], PDO::PARAM_INT);
        $stmt->execute();
        echo "<p>Deleted event and " . $stmt->rowCount() . " cascaded registrations.</p>\n";
        */
        echo "<p><em>Actual delete commented out to preserve test data. Uncomment code to test cascade.</em></p>\n";
    } else {
        echo "<p>No events with registrations found.</p>\n";
    }

    // =====================================================================
    // TEST 4: Delete Event Registration (Junction Table)
    // =====================================================================
    echo "<h3>Test 4: Delete Event Registration (Junction Table</h3>\n";

    $regStmt = $pdo->prepare("
        SELECT r.`reg_id`, s.`name`, e.`title`
        FROM `registrations` r
        INNER JOIN `students` s ON r.`student_id` = s.`id`
        INNER JOIN `events` e ON r.`event_id` = e.`event_id`
        LIMIT 1
    ");
    $regStmt->execute();
    $registration = $regStmt->fetch();

    if ($registration) {
        echo "<p><strong>Registration:</strong> " . htmlspecialchars($registration['name']) . " -> " . htmlspecialchars($registration['title']) . "</p>\n";
        echo "<p><strong>Registration ID:</strong> " . $registration['reg_id'] . "</p>\n";

        $sql = "DELETE FROM `registrations` WHERE `reg_id` = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $registration['reg_id'], PDO::PARAM_INT);
        $result = $stmt->execute();

        $affected = $stmt->rowCount();

        if ($affected > 0) {
            echo "<p><strong>Success!</strong> Registration deleted. $affected row(s) affected.</p>\n";
        } else {
            echo "<p><strong>Failed!</strong> Registration not deleted.</p>\n";
        }
    } else {
        echo "<p>No registrations found.</p>\n";
    }

    // =====================================================================
    // TEST 5: Bulk Delete with IN clause
    // =====================================================================
    echo "<h3>Test 5: Bulk Delete with IN Clause (Parameterized)</h3>\n";

    $testEmails = [];
    for ($i = 0; $i < 3; $i++) {
        $email = 'bulk_delete_' . time() . '_' . $i . '@studenthub.edu';
        $testEmails[] = $email;

        $insertSql = "
            INSERT INTO `students` (`name`, `email`, `mobile`, `course_id`, `year`, `gender`)
            VALUES ('Bulk Delete Test', :email, '+916666666666', 1, 1, 'other')
        ";

        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->bindValue(':email', $email, PDO::PARAM_STR);
        $insertStmt->execute();
    }

    echo "<p>Inserted " . count($testEmails) . " test students for bulk delete.</p>\n";

    $placeholders = implode(',', array_fill(0, count($testEmails), '?'));
    $sql = "DELETE FROM `students` WHERE `email` IN ($placeholders)";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($testEmails);
    $affected = $stmt->rowCount();

    echo "<p><strong>Bulk delete result:</strong> $affected row(s) deleted using IN clause.</p>\n";

    // =====================================================================
    // TEST 6: Delete Test Event
    // =====================================================================
    echo "<h3>Test 6: Delete Test Event</h3>\n";

    $eventStmt = $pdo->prepare("SELECT `event_id`, `title` FROM `events` WHERE `title` LIKE 'Test Event%'");
    $eventStmt->execute();
    $testEvents = $eventStmt->fetchAll();

    if (!empty($testEvents)) {
        $eventIds = array_column($testEvents, 'event_id');
        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));

        $sql = "DELETE FROM `events` WHERE `event_id` IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($eventIds);
        $affected = $stmt->rowCount();

        echo "<p><strong>Test events deleted:</strong> $affected row(s).</p>\n";
    } else {
        echo "<p>No test events to clean up.</p>\n";
    }

    echo "<h3>DELETE Tests Complete</h3>\n";

} catch (PDOException $e) {
    echo "<h2>Database Error</h2>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    error_log("DB Delete Test Error: " . $e->getMessage());
    exit(1);
}