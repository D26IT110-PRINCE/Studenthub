<?php
/**
 * Student Dashboard
 * Practical 10: Guarded protected page for users with 'student' role.
 */

require_once "api/auth_check.php";
require_role("student");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard | StudentHub</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <header class="site-header">
    <div class="header-top">
      <a class="brand" href="index.html" aria-label="StudentHub home">
        <img src="assets/studenthub-logo.png" alt="StudentHub logo" width="56" height="56">
        <h1>StudentHub Portal</h1>
      </a>
      <button class="theme-toggle" type="button">Change Theme</button>
    </div>
    <button class="nav-toggle" type="button" aria-controls="site-nav" aria-expanded="false" aria-label="Open menu">☰</button>
    <nav id="site-nav" class="site-nav" aria-label="Main Navigation">
      <ul>
        <li><a href="index.html">Home</a></li>
        <li><a href="about.html">About</a></li>
        <li><a href="student_dashboard.php" aria-current="page">Dashboard</a></li>
        <li><a href="events.html">Events</a></li>
        <li><a href="faq.html">FAQ</a></li>
        <li><a href="logout.php" class="logout-link">Logout</a></li>
      </ul>
    </nav>
  </header>

  <main class="dashboard-container">
    <section class="welcome-card">
      <h1>Welcome, <?php echo htmlspecialchars($_SESSION["user_name"]); ?>!</h1>
      <p>You are logged in as a <strong>Student</strong>.</p>
      <div class="user-info">
        <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION["user_email"]); ?></p>
        <p><strong>Session ID:</strong> <code><?php echo session_id(); ?></code></p>
      </div>
      <div class="dashboard-actions">
        <a href="events.html" class="btn btn-primary">Explore Events</a>
        <a href="logout.php" class="btn btn-secondary">Secure Logout</a>
      </div>
    </section>

    <section class="info-grid">
      <article class="info-card">
        <h3>Quick Access</h3>
        <ul>
          <li><a href="faq.html">Campus FAQs</a></li>
          <li><a href="contact.html">Support Center</a></li>
          <li><a href="feedback.html">Give Feedback</a></li>
        </ul>
      </article>
      <article class="info-card">
        <h3>Security Status</h3>
        <p>Your session is active and encrypted.</p>
        <p>Automatic timeout in 15 minutes of inactivity.</p>
      </article>
    </section>
  </main>

  <footer>
    <p>&copy; 2026 StudentHub Portal. Student Access Mode.</p>
  </footer>
  <script src="js/script.js"></script>
</body>
</html>
