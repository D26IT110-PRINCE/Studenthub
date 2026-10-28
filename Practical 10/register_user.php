<?php
/**
 * Student User Registration Form
 * Practical 9: Secure Registration using MySQLi
 * Generates CSRF token server-side to match validation in process_user_registration.php
 */
session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Handle error/success messages from redirect
$errors = [];
$success = false;
if (isset($_GET['errors'])) {
    $errors = explode('|', urldecode($_GET['errors']));
}
if (isset($_GET['success']) && $_GET['success'] === '1') {
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
  <title>StudentHub - Secure Registration</title>
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
        <li><a href="register_user.php" aria-current="page">Register</a></li>
        <li><a href="login.html">Login</a></li>
        <li><a href="events.html">Events</a></li>
        <li><a href="faq.html">FAQ</a></li>
        <li><a href="contact.html">Contact</a></li>
        <li><a href="feedback.html">Feedback</a></li>
      </ul>
    </nav>
  </header>

  <main class="form-page">
    <section class="form-card" aria-labelledby="register-title">
      <h2 id="register-title">Secure User Registration</h2>
      <p class="form-note">Practical 9: Implementing secure registration with MySQLi prepared statements and password hashing.</p>

      <?php if ($success): ?>
        <div class="success-message" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #c3e6cb;">
          <strong>Success!</strong> Your account has been created securely.
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #f5c6cb;">
          <strong>Registration failed:</strong>
          <ul style="margin: 0.5rem 0 0 1.5rem;">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form class="portal-form validation-form" id="registration-form" action="api/process_user_registration.php" method="post" novalidate>
        <input type="hidden" name="csrf_token" id="csrf-token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

        <label for="name">Full Name <span aria-hidden="true">*</span></label>
        <input type="text" id="name" name="name" autocomplete="name" aria-describedby="name-error" required>
        <p class="field-error" id="name-error" aria-live="polite"></p>

        <label for="email">Email <span aria-hidden="true">*</span></label>
        <input type="email" id="email" name="email" autocomplete="email" aria-describedby="email-error" required>
        <p class="field-error" id="email-error" aria-live="polite"></p>

        <label for="password">Password <span aria-hidden="true">*</span></label>
        <input type="password" id="password" name="password" autocomplete="new-password" aria-describedby="password-help password-error password-strength" required>
        <p class="field-help" id="password-help">Use 8+ characters with uppercase, lowercase, a number, and a symbol.</p>
        <div class="password-meter" aria-hidden="true"><span id="strength-bar"></span></div>
        <p class="strength-text" id="password-strength" aria-live="polite">Password strength: not set</p>
        <p class="field-error" id="password-error" aria-live="polite"></p>

        <label for="confirm-password">Confirm Password <span aria-hidden="true">*</span></label>
        <input type="password" id="confirm-password" name="confirm_password" autocomplete="new-password" aria-describedby="confirm-password-error" required>
        <p class="field-error" id="confirm-password-error" aria-live="polite"></p>

        <button type="submit">Create Account</button>
        <p class="form-status" id="form-status" aria-live="polite"></p>
      </form>
    </section>
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
