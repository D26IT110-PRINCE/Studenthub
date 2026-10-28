<?php
/**
 * Database JOIN Test Script
 * Tests INNER JOIN queries between students and courses tables
 * Demonstrates fetching course names instead of foreign key IDs
 */

// Prevent direct access
if (basename($_SERVER['SCRIPT_FILENAME']) === 'db_join_test.php') {
    define('TEST_MODE', true);
} else {
    http_response_code(403);
    exit('Direct access forbidden');
}

// Include database connection
require_once __DIR__ . '/db_connect.php';

try {
    echo "<h2>Database JOIN Test</h2>\n";

    $pdo = getDbConnection();
    echo "<p><strong>Connection:</strong> Successful</p>\n";

    // =====================================================================
    // TEST 1: Basic INNER JOIN - Students with Course Names
    // =====================================================================
    echo "<h3>Test 1: INNER JOIN - Students with Course Names</h3>\n";

    $sql1 = "
        SELECT
            s.`id`,
            s.`name`,
            s.`email`,
            s.`year`,
            c.`course_id`,
            c.`course_name`
        FROM `students` s
        INNER JOIN `courses` c ON s.`course_id` = c.`course_id`
        ORDER BY s.`id` DESC
    ";

    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute();
    $students = $stmt1->fetchAll();

    if (!empty($students)) {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<thead><tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Year</th><th>Course ID</th><th>Course Name</th></tr></thead>\n";
        echo "<tbody>\n";

        foreach ($students as $student) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['id']) . "</td>";
            echo "<td>" . htmlspecialchars($student['name']) . "</td>";
            echo "<td>" . htmlspecialchars($student['email']) . "</td>";
            echo "<td>" . htmlspecialchars($student['year']) . "</td>";
            echo "<td>" . htmlspecialchars($student['course_id']) . "</td>";
            echo "<td>" . htmlspecialchars($student['course_name']) . "</td>";
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
        echo "<p>Total records: " . count($students) . "</p>\n";
    } else {
        echo "<p>No students found.</p>\n";
    }

    // =====================================================================
    // TEST 2: LEFT JOIN - Show all courses even if no students
    // =====================================================================
    echo "<h3>Test 2: LEFT JOIN - All Courses with Student Count</h3>\n";

    $sql2 = "
        SELECT
            c.`course_id`,
            c.`course_name`,
            COUNT(s.`id`) AS `student_count`
        FROM `courses` c
        LEFT JOIN `students` s ON c.`course_id` = s.`course_id`
        GROUP BY c.`course_id`, c.`course_name`
        ORDER BY c.`course_id`
    ";

    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute();
    $courses = $stmt2->fetchAll();

    if (!empty($courses)) {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<thead><tr style='background: #f0f0f0;'><th>Course ID</th><th>Course Name</th><th>Student Count</th></tr></thead>\n";
        echo "<tbody>\n";

        foreach ($courses as $course) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($course['course_id']) . "</td>";
            echo "<td>" . htmlspecialchars($course['course_name']) . "</td>";
            echo "<td>" . htmlspecialchars($course['student_count']) . "</td>";
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
    } else {
        echo "<p>No courses found.</p>\n";
    }

    // =====================================================================
    // TEST 3: Multi-table JOIN - Students, Courses, and Registrations
    // =====================================================================
    echo "<h3>Test 3: Multi-table JOIN - Students, Courses, and Event Registrations</h3>\n";

    $sql3 = "
        SELECT
            s.`id`,
            s.`name`,
            c.`course_name`,
            e.`title`,
            r.`registered_on`
        FROM `students` s
        INNER JOIN `courses` c ON s.`course_id` = c.`course_id`
        INNER JOIN `registrations` r ON s.`id` = r.`student_id`
        INNER JOIN `events` e ON r.`event_id` = e.`event_id`
        ORDER BY r.`registered_on` DESC
        LIMIT 10
    ";

    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute();
    $registrations = $stmt3->fetchAll();

    if (!empty($registrations)) {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<thead><tr style='background: #f0f0f0;'><th>Student ID</th><th>Name</th><th>Course</th><th>Event</th><th>Reg. Date</th></tr></thead>\n";
        echo "<tbody>\n";

        foreach ($registrations as $reg) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($reg['id']) . "</td>";
            echo "<td>" . htmlspecialchars($reg['name']) . "</td>";
            echo "<td>" . htmlspecialchars($reg['course_name']) . "</td>";
            echo "<td>" . htmlspecialchars($reg['title']) . "</td>";
            echo "<td>" . htmlspecialchars($reg['registered_on']) . "</td>";
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
    } else {
        echo "<p>No event registrations found.</p>\n";
    }

    // =====================================================================
    // TEST 4: Prepared Statement with Parameters in JOIN
    // =====================================================================
    echo "<h3>Test 4: Prepared Statement with Parameters - Students by Course</h3>\n";

    $targetCourseId = 1; // B.Tech

    $sql4 = "
        SELECT
            s.`id`,
            s.`name`,
            s.`email`,
            s.`mobile`,
            s.`year`,
            s.`gender`,
            c.`course_name`
        FROM `students` s
        INNER JOIN `courses` c ON s.`course_id` = c.`course_id`
        WHERE c.`course_id` = :course_id
        ORDER BY s.`name`
    ";

    $stmt4 = $pdo->prepare($sql4);
    $stmt4->bindValue(':course_id', $targetCourseId, PDO::PARAM_INT);
    $stmt4->execute();
    $btechStudents = $stmt4->fetchAll();

    if (!empty($btechStudents)) {
        echo "<p><strong>Course ID:</strong> 1 (B.Tech) - filtered via prepared statement parameter</p>\n";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<thead><tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Mobile</th><th>Year</th><th>Gender</th></tr></thead>\n";
        echo "<tbody>\n";

        foreach ($btechStudents as $student) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['id']) . "</td>";
            echo "<td>" . htmlspecialchars($student['name']) . "</td>";
            echo "<td>" . htmlspecialchars($student['email']) . "</td>";
            echo "<td>" . htmlspecialchars($student['mobile']) . "</td>";
            echo "<td>" . htmlspecialchars($student['year']) . "</td>";
            echo "<td>" . htmlspecialchars($student['gender']) . "</td>";
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
        echo "<p>Total B.Tech students: " . count($btechStudents) . "</p>\n";
    } else {
        echo "<p>No B.Tech students found.</p>\n";
    }

    echo "<h3>Test Complete</h3>\n";
    echo "<p>All JOIN tests completed successfully!</p>\n";

} catch (PDOException $e) {
    echo "<h2>Database Error</h2>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>Code:</strong> " . htmlspecialchars($e->getCode()) . "</p>\n";
    error_log("DB JOIN Test Error: " . $e->getMessage());
    exit(1);
}