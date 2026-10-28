<?php
/**
 * View Registrations - Administrative Interface
 * Displays all student registrations from CSV with XSS protection
 */

// Configuration
const CSV_FILE = 'data/registrations.csv';

// Security: Simple admin check (in production, use proper authentication)
$isAdmin = isset($_GET['admin']) && $_GET['admin'] === 'true';
// For demo purposes, we'll allow access but in production this should be protected

/**
 * Sanitize output for safe HTML display (XSS prevention)
 */
function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Read all registrations from CSV with file locking
 */
function readRegistrations(): array
{
    $registrations = [];

    if (!file_exists(CSV_FILE)) {
        return $registrations;
    }

    $fp = fopen(CSV_FILE, 'r');
    if (!$fp) {
        return $registrations;
    }

    // Shared lock for reading
    if (!flock($fp, LOCK_SH)) {
        fclose($fp);
        return $registrations;
    }

    // Skip header row
    $headers = fgetcsv($fp);

    while (($row = fgetcsv($fp)) !== false) {
        // Ensure row has all expected columns
        if (count($row) >= 9) {
            $registrations[] = [
                'name' => $row[0],
                'email' => $row[1],
                'mobile' => $row[2],
                'password_hash' => $row[3],
                'course' => $row[4],
                'year' => $row[5],
                'gender' => $row[6],
                'terms_accepted' => $row[7],
                'registration_date' => $row[8]
            ];
        }
    }

    flock($fp, LOCK_UN);
    fclose($fp);

    // Sort by registration date descending (newest first)
    usort($registrations, function ($a, $b) {
        return strtotime($b['registration_date']) <=> strtotime($a['registration_date']);
    });

    return $registrations;
}

/**
 * Get course display name
 */
function getCourseDisplay(string $course): string
{
    $courses = [
        'btech' => 'B.Tech',
        'mca' => 'MCA',
        'msc' => 'M.Sc'
    ];
    return $courses[$course] ?? h($course);
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

$registrations = readRegistrations();
$totalCount = count($registrations);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - View Registrations</title>
    <link rel="stylesheet" href="css/style.css">
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
        .admin-title {
            margin: 0;
        }
        .stats-bar {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--card-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .stat-item {
            display: flex;
            flex-direction: column;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        .stat-label {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        .table-wrapper {
            overflow-x: auto;
            background: var(--card-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }
        th, td {
            padding: 0.875rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        th {
            background: var(--header-bg);
            font-weight: 600;
            color: var(--text-primary);
            position: sticky;
            top: 0;
        }
        tr:last-child td {
            border-bottom: none;
        }
        tr:hover td {
            background: var(--hover-bg);
        }
        .password-cell {
            font-family: monospace;
            font-size: 0.8rem;
            color: var(--text-muted);
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-btech { background: var(--primary-light); color: var(--primary-dark); }
        .badge-mca { background: var(--success-light); color: var(--success-dark); }
        .badge-msc { background: var(--warning-light); color: var(--warning-dark); }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }
            .stats-bar {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-top">
            <a class="brand" href="index.html" aria-label="StudentHub home">
                <img src="assets/studenthub-logo.png" alt="StudentHub logo" width="56" height="56">
                <h1>StudentHub Portal</h1>
            </a>
        </div>
        <button class="nav-toggle" type="button" aria-controls="site-nav" aria-expanded="false" aria-label="Open menu">☰</button>
        <nav id="site-nav" class="site-nav" aria-label="Main Navigation">
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="about.html">About</a></li>
                <li><a href="register.html">Register</a></li>
                <li><a href="login.html">Login</a></li>
                <li><a href="events.html">Events</a></li>
                <li><a href="faq.html">FAQ</a></li>
                <li><a href="contact.html">Contact</a></li>
                <li><a href="feedback.html">Feedback</a></li>
            </ul>
        </nav>
    </header>

    <main class="admin-container">
        <div class="admin-header">
            <h2 class="admin-title">Student Registrations</h2>
            <a href="index.html" class="back-link">← Back to Home</a>
        </div>

        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-value"><?php echo $totalCount; ?></span>
                <span class="stat-label">Total Registrations</span>
            </div>
            <div class="stat-item">
                <span class="stat-value">
                    <?php echo count(array_filter($registrations, fn($r) => $r['course'] === 'btech')); ?>
                </span>
                <span class="stat-label">B.Tech Students</span>
            </div>
            <div class="stat-item">
                <span class="stat-value">
                    <?php echo count(array_filter($registrations, fn($r) => $r['course'] === 'mca')); ?>
                </span>
                <span class="stat-label">MCA Students</span>
            </div>
            <div class="stat-item">
                <span class="stat-value">
                    <?php echo count(array_filter($registrations, fn($r) => $r['course'] === 'msc')); ?>
                </span>
                <span class="stat-label">M.Sc Students</span>
            </div>
        </div>

        <div class="table-wrapper">
            <?php if (empty($registrations)): ?>
                <div class="empty-state">
                    <p>No registrations found.</p>
                    <p>Registrations will appear here after students submit the registration form.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Gender</th>
                            <th>Terms</th>
                            <th>Password Hash</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $index => $reg): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo h($reg['name']); ?></td>
                                <td><?php echo h($reg['email']); ?></td>
                                <td><?php echo h($reg['mobile']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo h($reg['course']); ?>">
                                        <?php echo getCourseDisplay($reg['course']); ?>
                                    </span>
                                </td>
                                <td><?php echo getYearDisplay($reg['year']); ?></td>
                                <td><?php echo h(ucfirst($reg['gender'])); ?></td>
                                <td><?php echo h($reg['terms_accepted']); ?></td>
                                <td class="password-cell" title="Password is securely hashed">
                                    <?php echo h(substr($reg['password_hash'], 0, 30)) . '...'; ?>
                                </td>
                                <td><?php echo h($reg['registration_date']); ?></td>
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
                <img src="assets/studenthub-logo.png" alt="" width="42" height="42">
                <div>
                    <h2>StudentHub Portal</h2>
                    <p>Your campus, connected.</p>
                </div>
            </div>
            <div class="footer-links">
                <h2>Explore</h2>
                <a href="about.html">About StudentHub</a>
                <a href="events.html">Campus events</a>
                <a href="faq.html">Frequently asked questions</a>
            </div>
            <div class="footer-links">
                <h2>Get in touch</h2>
                <a href="contact.html">Contact support</a>
                <a href="mailto:support@studenthub.edu">support@studenthub.edu</a>
                <a href="tel:+911234567890">+91 12345 67890</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 StudentHub Portal. All rights reserved.</p>
            <a href="login.html">Student login</a>
        </div>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>