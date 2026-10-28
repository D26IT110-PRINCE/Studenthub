<?php
/**
 * View Students - Database Version with INNER JOIN
 * Displays all student records with course names instead of foreign key IDs
 * Securely omits password hashes (since they are now removed from schema)
 *
 * Uses INNER JOIN to combine students and courses tables
 */

require_once __DIR__ . '/db_connect.php';

/**
 * Sanitize output for safe HTML display (XSS prevention)
 */
function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Get all students with course names using INNER JOIN
 *
 * @param PDO $pdo Database connection
 * @return array All students with course details
 */
function getStudentsWithCourses(PDO $pdo): array
{
    // INNER JOIN query - fetches student data with actual course names
    // Following strict schema: students(id, name, email, mobile, course_id, year, gender)
    $sql = "
        SELECT
            s.`id`,
            s.`name`,
            s.`email`,
            s.`mobile`,
            s.`year`,
            s.`gender`,
            c.`course_id`,
            c.`course_name`
        FROM `students` s
        INNER JOIN `courses` c ON s.`course_id` = c.`course_id`
        ORDER BY s.`id` DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll();
}

/**
 * Get year display name
 */
function getYearDisplay(string $year): string
{
    $years = [
        '1' => '1st Year',
        '2' => '2nd Year',
        '3' => '3rd Year',
        '4' => '4th Year'
    ];
    return $years[$year] ?? h($year);
}

try {
    $pdo = getDbConnection();
    $students = getStudentsWithCourses($pdo);
    $totalCount = count($students);

} catch (PDOException $e) {
    error_log("Database error in view_students_db: " . $e->getMessage());
    $students = [];
    $totalCount = 0;
    $dbError = 'Unable to load student data. Please try again later.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - View Students (Database)</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        .admin-title { margin: 0; }
        .stats-bar {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--card-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .stat-item { display: flex; flex-direction: column; }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: var(--primary-color); }
        .stat-label { font-size: 0.875rem; color: var(--text-muted); }
        .table-wrapper { overflow-x: auto; background: var(--card-bg); border-radius: 8px; border: 1px solid var(--border-color); }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th, td { padding: 0.875rem 1rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { background: var(--header-bg); font-weight: 600; color: var(--text-primary); position: sticky; top: 0; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--hover-bg); }
        .empty-state { text-align: center; padding: 3rem; color: var(--text-muted); }
        .badge {
            display: inline-block; padding: 0.25rem 0.5rem; border-radius: 4px;
            font-size: 0.75rem; font-weight: 600; text-transform: uppercase;
        }
        .back-link { display: inline-flex; align-items: center; gap: 0.5rem; color: var(--primary-color); text-decoration: none; font-weight: 500; }
        .back-link:hover { text-decoration: underline; }
        .error-message {
            background: var(--danger-light); color: var(--danger-dark); padding: 1rem;
            border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--danger-color);
        }
        @media (max-width: 768px) {
            .admin-container { padding: 1rem; }
            .stats-bar { flex-direction: column; gap: 1rem; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-top">
            <a class="brand" href="../index.html" aria-label="StudentHub home">
                <img src="../assets/studenthub-logo.png" alt="StudentHub logo" width="56" height="56">
                <h1>StudentHub Portal</h1>
            </a>
        </div>
        <button class="nav-toggle" type="button" aria-controls="site-nav" aria-expanded="false" aria-label="Open menu">☰</button>
        <nav id="site-nav" class="site-nav" aria-label="Main Navigation">
            <ul>
                <li><a href="../index.html">Home</a></li>
                <li><a href="../about.html">About</a></li>
                <li><a href="../register_db.php">Register (DB)</a></li>
                <li><a href="../login.html">Login</a></li>
                <li><a href="../events.html">Events</a></li>
                <li><a href="../faq.html">FAQ</a></li>
                <li><a href="../contact.html">Contact</a></li>
                <li><a href="../feedback.html">Feedback</a></li>
            </ul>
        </nav>
    </header>

    <main class="admin-container">
        <div class="admin-header">
            <h2 class="admin-title">Student Registrations (MySQL Database)</h2>
            <a href="../index.html" class="back-link">← Back to Home</a>
        </div>

        <?php if (isset($dbError)): ?>
            <div class="error-message">
                <strong>Database Error:</strong> <?php echo h($dbError); ?>
            </div>
        <?php endif; ?>

        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-value"><?php echo $totalCount; ?></span>
                <span class="stat-label">Total Students</span>
            </div>
        </div>

        <div class="table-wrapper">
            <?php if (empty($students)): ?>
                <div class="empty-state">
                    <p>No student records found.</p>
                    <p>Students will appear here after they submit the registration form.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Gender</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $index => $student): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo h($student['id']); ?></td>
                                <td><?php echo h($student['name']); ?></td>
                                <td><?php echo h($student['email']); ?></td>
                                <td><?php echo h($student['mobile']); ?></td>
                                <td><?php echo h($student['course_name']); ?></td>
                                <td><?php echo getYearDisplay($student['year']); ?></td>
                                <td><?php echo h(ucfirst($student['gender'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-brand">
                <img src="../assets/studenthub-logo.png" alt="" width="42" height="42">
                <div>
                    <h2>StudentHub Portal</h2>
                    <p>Your campus, connected.</p>
                </div>
            </div>
            <div class="footer-links">
                <h2>Explore</h2>
                <a href="../about.html">About StudentHub</a>
                <a href="../events.html">Campus events</a>
                <a href="../faq.html">Frequently asked questions</a>
            </div>
            <div class="footer-links">
                <h2>Get in touch</h2>
                <a href="../contact.html">Contact support</a>
                <a href="mailto:support@studenthub.edu">support@studenthub.edu</a>
                <a href="tel:+911234567890">+91 12345 67890</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 StudentHub Portal. All rights reserved.</p>
            <a href="../login.html">Student login</a>
        </div>
    </footer>

    <script src="../js/script.js"></script>
</body>
</html>