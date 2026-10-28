<?php
/**
 * Admin Dashboard
 * Practical 10: Guarded protected page for users with 'admin' role.
 */

require_once "api/auth_check.php";
require_role("admin");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Control Panel | StudentHub</title>
  <link rel="stylesheet" href="css/style.css">
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
        <li><a href="student_management.php">Manage Students</a></li>
        <li><a href="event_management.php">Manage Events</a></li>
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
        <p><strong>Session ID:</strong> <code><?php echo session_id(); ?></code></p>
      </div>
      <div class="dashboard-actions">
        <a href="student_management.php" class="btn btn-primary">Manage Students</a>
        <a href="event_management.php" class="btn btn-primary">Manage Events</a>
        <a href="logout.php" class="btn btn-secondary">Secure Logout</a>
      </div>
    </section>

    <section class="info-grid">
      <article class="info-card">
        <h3>Admin Tools</h3>
        <ul class="admin-tools-list">
          <li><a href="manage-students.html">Verify Registrations</a></li>
          <li><a href="manage-events.html">Update Campus Calendar</a></li>
          <li><a href="feedback.html">Review Student Feedback</a></li>
        </ul>
      </article>
      <article class="info-card">
        <h3>System Security</h3>
        <p>RBAC Verification: <span class="status-ok">Active</span></p>
        <p>Session Timeout: 15 Minutes</p>
        <p>Encryption: AES-256 / Bcrypt</p>
      </article>
    </section>
  </main>

  <footer class="site-footer">
    <p>&copy; 2026 StudentHub Portal. Administrator Access Mode.</p>
  </footer>
  <script src="js/script.js"></script>
</body>
</html>
