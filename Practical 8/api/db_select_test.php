<?php
/**
 * Database SELECT Test Script
 * Tests various SELECT operations using PDO prepared statements
 * Demonstrates: fetchAll, fetch, fetchColumn, WHERE clauses, ORDER BY, LIMIT
 */

// Prevent direct access
if (basename($_SERVER['SCRIPT_FILENAME']) === 'db_select_test.php') {
    define('TEST_MODE', true);
} else {
    http_response_code(403);
    exit('Direct access forbidden');
}

require_once __DIR__ . '/db_connect.php';

try {
    echo "<h2>Database SELECT Test</h2>\n";
    $pdo = getDbConnection();
    echo "<p><strong>Connection:</strong> Successful</p>\n";

    // =====================================================================
    // TEST 1: fetchAll() - Get all students
    // =====================================================================
    echo "<h3>Test 1: fetchAll() - All Students</h3>\n";

    $sql = "
        SELECT `id`, `name`, `email`, `year`, `gender`, `course_id`
        FROM `students`
        ORDER BY `id` DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $students = $stmt->fetchAll();

    if (!empty($students)) {
        echo "<p>Total students: " . count($students) . "</p>\n";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<thead><tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Year</th><th>Gender</th><th>Course ID</th></tr></thead>\n";
        echo "<tbody>\n";
        foreach ($students as $student) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['id']) . "</td>";
            echo "<td>" . htmlspecialchars($student['name']) . "</td>";
            echo "<td>" . htmlspecialchars($student['email']) . "</td>";
            echo "<td>" . htmlspecialchars($student['year']) . "</td>";
            echo "<td>" . htmlspecialchars($student['gender']) . "</td>";
            echo "<td>" . htmlspecialchars($student['course_id']) . "</td>";
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
    } else {
        echo "<p>No students found.</p>\n";
    }

    // =====================================================================
    // TEST 2: fetch() - Get single student by ID
    // =====================================================================
    echo "<h3>Test 2: fetch() - Single Student by ID</h3>\n";

    $targetId = 1;

    $sql = "
        SELECT `id`, `name`, `email`, `mobile`, `year`, `gender`, `course_id`
        FROM `students`
        WHERE `id` = :id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $targetId, PDO::PARAM_INT);
    $stmt->execute();
    $student = $stmt->fetch();

    if ($student) {
        echo "<p><strong>Student Found:</strong></p>\n";
        echo "<ul>\n";
        foreach ($student as $key => $value) {
            echo "<li><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value) . "</li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "<p>No student found with ID: $targetId</p>\n";
    }

    // =====================================================================
    // TEST 3: fetchColumn() - Get single value (COUNT)
    // =====================================================================
    echo "<h3>Test 3: fetchColumn() - Count Students by Course</h3>\n";

    $courseId = 1;

    $sql = "
        SELECT COUNT(*)
        FROM `students`
        WHERE `course_id` = :course_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':course_id', $courseId, PDO::PARAM_INT);
    $stmt->execute();
    $count = $stmt->fetchColumn();

    echo "<p>Number of students in Course ID $courseId: <strong>$count</strong></p>\n";

    // =====================================================================
    // TEST 4: SELECT with multiple parameters
    // =====================================================================
    echo "<h3>Test 4: SELECT with Multiple Parameters</h3>\n";

    $year = 2;
    $gender = 'female';

    $sql = "
        SELECT `id`, `name`, `email`, `course_id`
        FROM `students`
        WHERE `year` = :year
        AND `gender` = :gender
        ORDER BY `name`
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':year', $year, PDO::PARAM_INT);
    $stmt->bindValue(':gender', $gender, PDO::PARAM_STR);
    $stmt->execute();
    $students = $stmt->fetchAll();

    if (!empty($students)) {
        echo "<p>Female students in Year 2: " . count($students) . "</p>\n";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<thead><tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Course ID</th></tr></thead>\n";
        echo "<tbody>\n";
        foreach ($students as $student) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['id']) . "</td>";
            echo "<td>" . htmlspecialchars($student['name']) . "</td>";
            echo "<td>" . htmlspecialchars($student['email']) . "</td>";
            echo "<td>" . htmlspecialchars($student['course_id']) . "</td>";
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
    } else {
        echo "<p>No female students in Year 2 found.</p>\n";
    }

    // =====================================================================
    // TEST 5: SELECT with LIKE for search
    // =====================================================================
    echo "<h3>Test 5: SELECT with LIKE - Search Students</h3>\n";

    $searchTerm = '%rahul%';

    $sql = "
        SELECT `id`, `name`, `email`
        FROM `students`
        WHERE `name` LIKE :search_name OR `email` LIKE :search_email
        ORDER BY `name`
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':search_name', $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(':search_email', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    $students = $stmt->fetchAll();

    if (!empty($students)) {
        echo "<p>Search results for 'rahul': " . count($students) . "</p>\n";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<thead><tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th></tr></thead>\n";
        echo "<tbody>\n";
        foreach ($students as $student) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['id']) . "</td>";
            echo "<td>" . htmlspecialchars($student['name']) . "</td>";
            echo "<td>" . htmlspecialchars($student['email']) . "</td>";
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
    } else {
        echo "<p>No matches found for 'rahul'.</p>\n";
    }

    // =====================================================================
    // TEST 6: SELECT with LIMIT and OFFSET (pagination)
    // =====================================================================
    echo "<h3>Test 6: SELECT with LIMIT and OFFSET (Pagination)</h3>\n";

    $page = 1;
    $perPage = 2;
    $offset = ($page - 1) * $perPage;

    $sql = "
        SELECT `id`, `name`, `email`
        FROM `students`
        ORDER BY `id`
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll();

    if (!empty($students)) {
        echo "<p>Page $page (showing $perPage per page):</p>\n";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<thead><tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th></tr></thead>\n";
        echo "<tbody>\n";
        foreach ($students as $student) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['id']) . "</td>";
            echo "<td>" . htmlspecialchars($student['name']) . "</td>";
            echo "<td>" . htmlspecialchars($student['email']) . "</td>";
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
    }

    // =====================================================================
    // TEST 7: SELECT with JOIN - Students with Course Names
    // =====================================================================
    echo "<h3>Test 7: SELECT with INNER JOIN - Students with Course Names</h3>\n";

    $sql = "
        SELECT
            s.`id`,
            s.`name`,
            s.`email`,
            c.`course_id`,
            c.`course_name`
        FROM `students` s
        INNER JOIN `courses` c ON s.`course_id` = c.`course_id`
        ORDER BY s.`id`
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $students = $stmt->fetchAll();

    if (!empty($students)) {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<thead><tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Course ID</th><th>Course Name</th></tr></thead>\n";
        echo "<tbody>\n";
        foreach ($students as $student) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['id']) . "</td>";
            echo "<td>" . htmlspecialchars($student['name']) . "</td>";
            echo "<td>" . htmlspecialchars($student['email']) . "</td>";
            echo "<td>" . htmlspecialchars($student['course_id']) . "</td>";
            echo "<td>" . htmlspecialchars($student['course_name']) . "</td>";
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
    }

    echo "<h3>SELECT Tests Complete</h3>\n";

} catch (PDOException $e) {
    echo "<h2>Database Error</h2>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    error_log("DB Select Test Error: " . $e->getMessage());
    exit(1);
}