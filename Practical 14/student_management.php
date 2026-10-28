<?php
/**
 * Student Management Module
 * Practical 11: Full CRUD with Search, Filter, and Pagination
 * Guarded by Admin Role and CSRF tokens.
 */

// 1. Guard Block: Admin only + Session Timeout
require_once "api/auth_check.php";
require_role("admin");

// 2. CSRF Token Generation
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// 3. Helper Functions
function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function redirectWithStatus(string $status): void {
    header("Location: student_management.php?status=" . rawurlencode($status));
    exit;
}

// 4. Database Connection
require_once "api/db_connect_mysqli.php";

$error = "";
$successMessage = "";
$editStudent = null;

// --- POST ACTIONS (Write Operations) ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF Verification
    $postedToken = $_POST["csrf_token"] ?? "";
    if (!is_string($postedToken) || !hash_equals($_SESSION["csrf_token"], $postedToken)) {
        http_response_code(403);
        die("Invalid request token. Refresh the page and try again.");
    }

    $action = $_POST["action"] ?? "";

    if ($action === "add" || $action === "update") {
        // Input Collection
        $id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT);
        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $mobile = trim($_POST["mobile"] ?? "");
        $courseId = filter_var($_POST["course_id"] ?? null, FILTER_VALIDATE_INT);
        $year = filter_var($_POST["year"] ?? null, FILTER_VALIDATE_INT);
        $gender = $_POST["gender"] ?? "";

        // Validation
        if ($name === "" || mb_strlen($name) > 100) {
            $error = "Enter a student name (maximum 100 characters).";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $error = "Enter a valid email address (maximum 150 characters).";
        } elseif ($mobile === "" || strlen($mobile) > 15) {
            $error = "Enter a mobile number (maximum 15 characters).";
        } elseif (!$courseId) {
            $error = "Select a valid course.";
        } elseif ($year === false || $year < 1 || $year > 10) {
            $error = "Enter a valid academic year (1-10).";
        } elseif (!in_array($gender, ["Male", "Female", "Other"], true)) {
            $error = "Select a valid gender.";
        } else {
            // Validated: Now check if course exists and get the name
            $cStmt = $conn->prepare("SELECT course_name FROM courses WHERE course_id = ?");
            $cStmt->bind_param("i", $courseId);
            $cStmt->execute();
            $courseData = $cStmt->get_result()->fetch_assoc();
            $cStmt->close();

            if (!$courseData) {
                $error = "The selected course is invalid.";
            } else {
                $courseName = $courseData["course_name"];

                if ($action === "add") {
                    $stmt = $conn->prepare("INSERT INTO students (name, email, mobile, year, gender, course_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssisi", $name, $email, $mobile, $year, $gender, $courseId);
                    if ($stmt->execute()) {
                        $studentId = $conn->insert_id;
                        // Audit Log
                        $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
                        $actionDesc = "Student added: $name ($email)";
                        $logStmt->bind_param("is", $_SESSION["user_id"], $actionDesc);
                        $logStmt->execute();
                        $logStmt->close();
                        $stmt->close();
                        redirectWithStatus("added");
                    }
                    $error = ($stmt->errno === 1062) ? "A record with that email already exists." : "Unable to add student.";
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare("UPDATE students SET name=?, email=?, mobile=?, year=?, gender=?, course_id=? WHERE id=?");
                    $stmt->bind_param("sssisi i", $name, $email, $mobile, $year, $gender, $courseId, $id);
                    if ($stmt->execute()) {
                        // Audit Log
                        $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
                        $actionDesc = "Student updated: $name ($email)";
                        $logStmt->bind_param("is", $_SESSION["user_id"], $actionDesc);
                        $logStmt->execute();
                        $logStmt->close();
                        $stmt->close();
                        redirectWithStatus("updated");
                    }
                    $error = "Unable to update student.";
                    $stmt->close();
                }
            }
        }
    } elseif ($action === "delete") {
        $delId = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT);
        if ($delId) {
            // Get student info before deletion for audit log
            $infoStmt = $conn->prepare("SELECT name, email FROM students WHERE id = ?");
            $infoStmt->bind_param("i", $delId);
            $infoStmt->execute();
            $studentInfo = $infoStmt->get_result()->fetch_assoc();
            $infoStmt->close();

            $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $stmt->bind_param("i", $delId);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                // Audit Log
                if ($studentInfo) {
                    $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
                    $actionDesc = "Student deleted: {$studentInfo['name']} ({$studentInfo['email']})";
                    $logStmt->bind_param("is", $_SESSION["user_id"], $actionDesc);
                    $logStmt->execute();
                    $logStmt->close();
                }
                $stmt->close();
                redirectWithStatus("deleted");
            }
            $error = "Student was not deleted. The record may no longer exist.";
            $stmt->close();
        }
    }
}

// --- GET ACTIONS (Read Operations) ---

// 1. Handle Edit Mode
$editId = filter_var($_GET["edit"] ?? null, FILTER_VALIDATE_INT);
if ($editId && $editId > 0) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editStudent = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}

// 2. Handle Search & Pagination
$search = trim((string)($_GET["q"] ?? ""));
$page = filter_var($_GET["page"] ?? 1, FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;
$perPage = 10;

$whereClause = "";
$params = [];
$types = "";

if ($search !== "") {
    $whereClause = " WHERE (s.name LIKE ? OR s.email LIKE ? OR c.course_name LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like);
    $types .= "sss";
}

// Count total matching records
$countSql = "SELECT COUNT(*) as total FROM students s INNER JOIN courses c ON s.course_id = c.course_id" . $whereClause;
$countStmt = $conn->prepare($countSql);
if ($types) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()["total"];
$countStmt->close();

$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Fetch the page of records
$sql = "SELECT s.id, s.name, s.email, s.mobile, c.course_name, s.year, s.gender
        FROM students s
        INNER JOIN courses c ON s.course_id = c.course_id" . $whereClause . " ORDER BY s.id DESC LIMIT ? OFFSET ?";
$listTypes = $types . "ii";
array_push($params, $perPage, $offset);

$listStmt = $conn->prepare($sql);
$listStmt->bind_param($listTypes, ...$params);
$listStmt->execute();
$students = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listStmt->close();

// 3. Load Courses for Dropdown
$courses = [];
$courseRes = $conn->query("SELECT course_id, course_name FROM courses ORDER BY course_name");
if ($courseRes) {
    while ($row = $courseRes->fetch_assoc()) {
        $courses[] = $row;
    }
    $courseRes->free();
}

// Status Messages
$statusMessages = [
    "added"   => "Student added successfully.",
    "updated" => "Student updated successfully.",
    "deleted" => "Student deleted successfully."
];
$status = (string)($_GET["status"] ?? "");
$successMessage = $statusMessages[$status] ?? "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | StudentHub Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .management-container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .form-card { background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .notice { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .notice-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .notice-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .student-table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: #fff; }
        .student-table th, .student-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .student-table th { background: #f8f9fa; font-weight: 600; }
        .btn-edit { color: #007bff; text-decoration: none; margin-right: 10px; }
        .btn-delete { color: #dc3545; border: none; background: none; cursor: pointer; font-weight: bold; }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 1rem; margin-top: 2rem; }
        .page-link { padding: 8px 16px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 4px; }
        .page-link.active { background: #007bff; color: #fff; border-color: #007bff; }
        .search-box { display: flex; gap: 1rem; margin-bottom: 1.5rem; align-items: center; }
        .search-box input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 300px; }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-top">
            <a class="brand" href="index.html">
                <img src="assets/studenthub-logo.png" alt="Logo" width="56" height="56">
                <h1>StudentHub Admin</h1>
            </a>
            <button class="theme-toggle" type="button">Change Theme</button>
        </div>
        <nav id="site-nav" class="site-nav">
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="management-container">
        <h2 class="page-title">Student Management</h2>

        <?php if ($successMessage): ?>
            <div class="notice notice-success" role="status"><?php echo h($successMessage); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="notice notice-error" role="alert"><?php echo h($error); ?></div>
        <?php endif; ?>

        <!-- Create/Update Form -->
        <section class="form-card">
            <h3><?php echo $editStudent ? "Edit Student Record" : "Add New Student"; ?></h3>
            <form method="POST" action="student_management.php">
                <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION["csrf_token"]); ?>">
                <input type="hidden" name="action" value="<?php echo $editStudent ? "update" : "add"; ?>">
                <?php if ($editStudent): ?>
                    <input type="hidden" name="id" value="<?php echo (int)$editStudent["id"]; ?>">
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <div>
                        <label>Full Name *</label>
                        <input type="text" name="name" value="<?php echo h($editStudent["name"] ?? ""); ?>" required>
                    </div>
                    <div>
                        <label>Email *</label>
                        <input type="email" name="email" value="<?php echo h($editStudent["email"] ?? ""); ?>" required>
                    </div>
                    <div>
                        <label>Mobile *</label>
                        <input type="text" name="mobile" value="<?php echo h($editStudent["mobile"] ?? ""); ?>" required>
                    </div>
                    <div>
                        <label>Course *</label>
                        <select name="course_id" required>
                            <option value="">Select course</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo (int)$c["course_id"]; ?>"
                                    <?php echo (isset($editStudent) && (int)$editStudent["course_id"] === (int)$c["course_id"]) ? "selected" : ""; ?>>
                                    <?php echo h($c["course_name"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Academic Year *</label>
                        <input type="number" name="year" min="1" max="10" value="<?php echo h($editStudent["year"] ?? ""); ?>" required>
                    </div>
                    <div>
                        <label>Gender *</label>
                        <select name="gender" required>
                            <option value="">Select gender</option>
                            <option value="Male" <?php echo (isset($editStudent) && $editStudent["gender"] === "Male") ? "selected" : ""; ?>>Male</option>
                            <option value="Female" <?php echo (isset($editStudent) && $editStudent["gender"] === "Female") ? "selected" : ""; ?>>Female</option>
                            <option value="Other" <?php echo (isset($editStudent) && $editStudent["gender"] === "Other") ? "selected" : ""; ?>>Other</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary"><?php echo $editStudent ? "Update Record" : "Add Student"; ?></button>
                    <?php if ($editStudent): ?>
                        <a href="student_management.php" style="margin-left: 1rem;">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- List & Search -->
        <section class="list-section">
            <div class="search-box">
                <form method="GET" action="student_management.php" style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="text" name="q" placeholder="Search by name, email or course..." value="<?php echo h($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="student_management.php" class="btn btn-secondary">Clear</a>
                </form>
                <p>Showing <?php echo count($students); ?> of <?php echo $totalRecords; ?> records</p>
            </div>

            <table class="student-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Gender</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="7" style="text-align: center;">No student records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): ?>
                        <tr>
                            <td><?php echo (int)$s["id"]; ?></td>
                            <td><?php echo h($s["name"]); ?></td>
                            <td><?php echo h($s["email"]); ?></td>
                            <td><?php echo h($s["course_name"]); ?></td>
                            <td><?php echo (int)$s["year"]; ?></td>
                            <td><?php echo h($s["gender"]); ?></td>
                            <td>
                                <a href="?edit=<?php echo (int)$s["id"]; ?>" class="btn-edit">Edit</a>
                                <form method="POST" action="student_management.php" style="display: inline;"
                                      onsubmit="return confirm('Are you sure you want to delete this student record?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION["csrf_token"]); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$s["id"]; ?>">
                                    <button type="submit" class="btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&q=<?php echo urlencode($search); ?>" class="page-link">&larr; Previous</a>
                <?php endif; ?>

                <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&q=<?php echo urlencode($search); ?>" class="page-link">Next &rarr;</a>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <script src="js/script.js"></script>
</body>
</html>
