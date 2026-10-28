<?php
/**
 * Admin Dashboard
 * Practical 14: RBAC, Dynamic Stats, and Audit Monitoring
 */

require_once "api/auth_check.php";
require_role("admin");
require_once "api/db_connect_mysqli.php";

// 1. Fetch Dynamic Statistics
$stats = ['users' => 0, 'students' => 0, 'events' => 0];

$resUsers = $conn->query("SELECT COUNT(*) as total FROM users");
if ($resUsers) $stats['users'] = $resUsers->fetch_assoc()['total'];

$resStudents = $conn->query("SELECT COUNT(*) as total FROM students");
if ($resStudents) $stats['students'] = $resStudents->fetch_assoc()['total'];

$resEvents = $conn->query("SELECT COUNT(*) as total FROM events");
if ($resEvents) $stats['events'] = $resEvents->fetch_assoc()['total'];

// 2. Fetch Latest Registrations
$regSql = "SELECT r.reg_id, s.name as student_name, e.title as event_title, r.registered_on
           FROM registrations r
           INNER JOIN students s ON r.student_id = s.id
           INNER JOIN events e ON r.event_id = e.event_id
           ORDER BY r.registered_on DESC LIMIT 5";
$regResult = $conn->query($regSql);

// 3. Fetch Recent Audit Logs
$auditSql = "SELECT al.log_id, al.action, al.created_at, u.name as admin_name, u.email as admin_email
             FROM audit_logs al
             LEFT JOIN users u ON al.user_id = u.id
             ORDER BY al.created_at DESC LIMIT 10";
$auditResult = $conn->query($auditSql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Control Panel | StudentHub</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
    .stat-card { background: var(--surface); padding: 1.5rem; border-radius: 1rem; border: 1px solid var(--border); box-shadow: var(--shadow); text-align: center; }
    .stat-card h3 { color: var(--muted); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
    .stat-card .value { font-size: 2.5rem; font-weight: 800; color: var(--navy); }
    .recent-table { width: 100%; border-collapse: collapse; background: var(--surface); border-radius: 1rem; overflow: hidden; border: 1px solid var(--border); }
    .recent-table th, .recent-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border); }
    .recent-table th { background: rgba(23,105,224,0.05); color: var(--navy); font-weight: 700; }
    .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
    .audit-table td { font-size: 0.9rem; }
    .audit-table .action-cell { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  </style>
</head>
<body class="admin-theme">
  <header class="site-header">
    <div class="header-top">
      <a class="brand" href="index.html" aria-label="StudentHub home">
        <img src="assets/studenthub-logo.png" alt="StudentHub logo" width="56" height="56">
        <h1>StudentHub Admin</h1>
      </a>
      <button class="theme-toggle" type="button">Change Theme</button>
    </div>
    <button class="nav-toggle" type="button" aria-controls="site-nav" aria-expanded="false" aria-label="Open menu">☰</button>
    <nav id="site-nav" class="site-nav" aria-label="Main Navigation">
      <ul>
        <li><a href="index.html">Home</a></li>
        <li><a href="admin_dashboard.php" aria-current="page">Admin Panel</a></li>
        <?php if ($_SESSION["user_role"] === "admin"): ?>
          <li><a href="student_management.php">Manage Students</a></li>
          <li><a href="event_management.php">Manage Events</a></li>
        <?php endif; ?>
        <li><a href="logout.php" class="logout-link">Logout</a></li>
      </ul>
    </nav>
  </header>

  <main class="dashboard-container">
    <section class="welcome-card admin-card">
      <h1>Administrator Panel</h1>
      <p>Welcome back, <strong class="admin-name"><?php echo htmlspecialchars($_SESSION["user_name"]); ?></strong></p>
      <div class="user-info">
        <p><strong>Admin Email:</strong> <?php echo htmlspecialchars($_SESSION["user_email"]); ?></p>
        <p><strong>Privilege Level:</strong> <span class="badge badge-admin">Super Admin</span></p>
      </div>
    </section>

    <section class="stats-grid">
      <div class="stat-card">
        <h3>Total Users</h3>
        <div class="value"><?php echo $stats['users']; ?></div>
      </div>
      <div class="stat-card">
        <h3>Students</h3>
        <div class="value"><?php echo $stats['students']; ?></div>
      </div>
      <div class="stat-card">
        <h3>Campus Events</h3>
        <div class="value"><?php echo $stats['events']; ?></div>
      </div>
    </section>

    <section class="info-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
      <article class="info-card">
        <h3>Latest Registrations</h3>
        <table class="recent-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Event</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($regResult && $regResult->num_rows > 0): ?>
              <?php while($row = $regResult->fetch_assoc()): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                  <td><?php echo htmlspecialchars($row['event_title']); ?></td>
                  <td><?php echo htmlspecialchars($row['registered_on']); ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="3" style="text-align:center;">No recent registrations found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </article>

      <article class="info-card">
        <h3>Recent Admin Activity (Audit Log)</h3>
        <table class="recent-table audit-table">
          <thead>
            <tr>
              <th>Action</th>
              <th>Admin</th>
              <th>Timestamp</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($auditResult && $auditResult->num_rows > 0): ?>
              <?php while($row = $auditResult->fetch_assoc()): ?>
                <tr>
                  <td class="action-cell"><?php echo htmlspecialchars($row['action']); ?></td>
                  <td><?php echo htmlspecialchars($row['admin_name'] ?? 'System'); ?></td>
                  <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="3" style="text-align:center;">No audit entries found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </article>
    </section>

    <section class="info-card" style="margin-top: 2rem;">
      <h3>Quick Actions</h3>
      <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="student_management.php" class="btn btn-primary">Manage Students</a>
        <a href="event_management.php" class="btn btn-primary">Manage Events</a>
        <a href="ajax_crud_demo.html" class="btn btn-secondary">AJAX CRUD Demo</a>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <p>&copy; 2026 StudentHub Portal. Administrator Access Mode.</p>
  </footer>
  <script src="js/script.js"></script>
</body>
</html>
