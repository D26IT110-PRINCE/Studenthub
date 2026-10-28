<?php
/**
 * Database Dynamic Dropdown Test - Course Selection
 * Intermediate Extension: Queries courses table to dynamically populate <select> dropdown
 * Demonstrates: Real-time data fetching for form elements, prepared statements for dropdown population
 */

// Prevent direct access
if (basename($_SERVER['SCRIPT_FILENAME']) === 'db_insert_course_test.php') {
    define('TEST_MODE', true);
} else {
    http_response_code(403);
    exit('Direct access forbidden');
}

require_once __DIR__ . '/db_connect.php';

try {
    echo "<h2>Dynamic Dropdown Test - Course Selection</h2>\n";
    $pdo = getDbConnection();
    echo "<p><strong>Connection:</strong> Successful</p>\n";

    // =====================================================================
    // TEST 1: Fetch all courses for dropdown
    // =====================================================================
    echo "<h3>Test 1: Fetch All Courses for Dropdown</h3>\n";

    $sql = "
        SELECT `course_id`, `course_name`
        FROM `courses`
        ORDER BY `course_name`
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $courses = $stmt->fetchAll();

    if (!empty($courses)) {
        echo "<p>Found " . count($courses) . " courses.</p>\n";

        // Display as HTML <select> dropdown
        echo "<h4>Generated HTML Select Element:</h4>\n";
        echo "<pre style='background: #f5f5f5; padding: 1rem; border-radius: 4px; overflow-x: auto;'>\n";
        echo htmlspecialchars('<select id="course" name="course" required>');
        echo "\n";
        echo htmlspecialchars('  <option value="">--Select Course--</option>');
        echo "\n";

        foreach ($courses as $course) {
            $option = '  <option value="' . $course['course_id'] . '">' . $course['course_name'] . '</option>';
            echo htmlspecialchars($option) . "\n";
        }

        echo htmlspecialchars('</select>');
        echo "\n";
        echo "</pre>\n";

        // Also show as table
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<thead><tr style='background: #f0f0f0;'><th>Course ID</th><th>Course Name</th></tr></thead>\n";
        echo "<tbody>\n";
        foreach ($courses as $course) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($course['course_id']) . "</td>";
            echo "<td>" . htmlspecialchars($course['course_name']) . "</td>";
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
    } else {
        echo "<p>No courses found.</p>\n";
    }

    // =====================================================================
    // TEST 2: JSON Endpoint for AJAX Dropdown Population
    // =====================================================================
    echo "<h3>Test 2: JSON Endpoint for AJAX Dropdown Population</h3>\n";

    header('Content-Type: application/json');

    $sql = "
        SELECT `course_id`, `course_name`
        FROM `courses`
        ORDER BY `course_name`
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $courses = $stmt->fetchAll();

    $response = [
        'success' => true,
        'count' => count($courses),
        'data' => $courses
    ];

    echo "<p><strong>JSON Response:</strong></p>\n";
    echo "<pre style='background: #f5f5f5; padding: 1rem; border-radius: 4px; overflow-x: auto;'>\n";
    echo htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "\n</pre>\n";

    // =====================================================================
    // TEST 3: JavaScript/HTML Integration Example
    // =====================================================================
    echo "<h3>Test 3: Complete HTML + JavaScript Integration</h3>\n";

    echo "<p>This example shows how to dynamically populate a dropdown using JavaScript fetch:</p>\n";
    echo "<pre style='background: #f5f5f5; padding: 1rem; border-radius: 4px; overflow-x: auto;'>\n";
    echo htmlspecialchars('<!-- HTML Form -->');
    echo "\n";
    echo htmlspecialchars('<form id="registration-form" action="process_registration_db.php" method="post">');
    echo "\n";
    echo htmlspecialchars('  <label for="course">Course *</label>');
    echo "\n";
    echo htmlspecialchars('  <select id="course" name="course" required>');
    echo "\n";
    echo htmlspecialchars('    <option value="">Loading courses...</option>');
    echo "\n";
    echo htmlspecialchars('  </select>');
    echo "\n";
    echo htmlspecialchars('</form>');
    echo "\n\n";

    echo htmlspecialchars('<!-- JavaScript -->');
    echo "\n";
    echo htmlspecialchars('<script>');
    echo "\n";
    echo htmlspecialchars('document.addEventListener("DOMContentLoaded", function() {');
    echo "\n";
    echo htmlspecialchars('  fetch("get_courses.php")  // PHP endpoint returning JSON');
    echo "\n";
    echo htmlspecialchars('    .then(response => response.json())');
    echo "\n";
    echo htmlspecialchars('    .then(data => {');
    echo "\n";
    echo htmlspecialchars('      const select = document.getElementById("course");');
    echo "\n";
    echo htmlspecialchars('      select.innerHTML = \'<option value="">--Select Course--</option>\';');
    echo "\n";
    echo htmlspecialchars('      data.data.forEach(course => {');
    echo "\n";
    echo htmlspecialchars('        const option = document.createElement("option");');
    echo "\n";
    echo htmlspecialchars('        option.value = course.course_id;');
    echo "\n";
    echo htmlspecialchars('        option.textContent = course.course_name;');
    echo "\n";
    echo htmlspecialchars('        select.appendChild(option);');
    echo "\n";
    echo htmlspecialchars('      });');
    echo "\n";
    echo htmlspecialchars('    })');
    echo "\n";
    echo htmlspecialchars('    .catch(error => {');
    echo "\n";
    echo htmlspecialchars('      console.error("Failed to load courses:", error);');
    echo "\n";
    echo htmlspecialchars('      select.innerHTML = \'<option value="">Error loading courses</option>\';');
    echo "\n";
    echo htmlspecialchars('    });');
    echo "\n";
    echo htmlspecialchars('});');
    echo "\n";
    echo htmlspecialchars('</script>');
    echo "\n";
    echo "</pre>\n";

    // =====================================================================
    // TEST 4: PHP Function for Reusable Dropdown Generation
    // =====================================================================
    echo "<h3>Test 4: Reusable PHP Function for Dropdown Generation</h3>\n";

    // Define the function
    function generateCourseDropdown(PDO $pdo, string $name = 'course', int $selectedValue = 0, bool $required = true): string
    {
        $sql = "
            SELECT `course_id`, `course_name`
            FROM `courses`
            ORDER BY `course_name`
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $courses = $stmt->fetchAll();

        $requiredAttr = $required ? ' required' : '';
        $html = "<select id=\"$name\" name=\"$name\"$requiredAttr>\n";
        $html .= "  <option value=\"\">--Select Course--</option>\n";

        foreach ($courses as $course) {
            $selected = ($course['course_id'] == $selectedValue) ? ' selected' : '';
            $html .= "  <option value=\"" . htmlspecialchars($course['course_id']) . "\"$selected>";
            $html .= htmlspecialchars($course['course_name']);
            $html .= "</option>\n";
        }

        $html .= "</select>";

        return $html;
    }

    // Use the function
    echo "<p><strong>Generated Dropdown (default):</strong></p>\n";
    echo generateCourseDropdown($pdo, 'course');

    echo "<br><br>\n";

    echo "<p><strong>Generated Dropdown (with Course ID 1 pre-selected):</strong></p>\n";
    echo generateCourseDropdown($pdo, 'course', 1);

    echo "<br><br>\n";

    echo "<p><strong>Generated Dropdown (optional):</strong></p>\n";
    echo generateCourseDropdown($pdo, 'course_opt', 0, false);

    echo "<h3>Dynamic Dropdown Tests Complete</h3>\n";

} catch (PDOException $e) {
    echo "<h2>Database Error</h2>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    error_log("DB Dropdown Test Error: " . $e->getMessage());
    exit(1);
}