<?php
/**
 * Event Management Module
 * Practical 12: CRUD with Poster Upload and File Validation
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
    header("Location: event_management.php?status=" . rawurlencode($status));
    exit;
}

// 4. Database Connection
require_once "api/db_connect_mysqli.php";

$error = "";
$successMessage = "";
$editEvent = null;

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
        $title = trim($_POST["title"] ?? "");
        $date = $_POST["date"] ?? "";
        $location = trim($_POST["location"] ?? "");
        $category = trim($_POST["category"] ?? "");

        // Validation
        if ($title === "" || mb_strlen($title) > 100) {
            $error = "Enter a valid event title (max 100 chars).";
        } elseif ($date === "") {
            $error = "Please select a valid date.";
        } elseif ($location === "" || mb_strlen($location) > 150) {
            $error = "Enter a valid location (max 150 chars).";
        } elseif ($category === "") {
            $error = "Please select a category.";
        } else {
            // --- FILE UPLOAD VALIDATION ---
            $posterPath = null;
            if (isset($_FILES["poster"]) && $_FILES["poster"]["error"] === UPLOAD_ERR_OK) {
                $file = $_FILES["poster"];
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                $maxSize = 2 * 1024 * 1024; // 2MB

                if (!in_array($file["type"], $allowedTypes)) {
                    $error = "Invalid file type. Only JPG, PNG, and WebP are allowed.";
                } elseif ($file["size"] > $maxSize) {
                    $error = "File too large. Maximum size is 2MB.";
                } else {
                    // Sanitize filename and move to uploads folder
                    $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
                    $newFileName = "event_" . time() . "_" . uniqid() . "." . $ext;
                    $targetPath = "uploads/posters/" . $newFileName;

                    if (move_uploaded_file($file["tmp_name"], $targetPath)) {
                        $posterPath = $targetPath;
                        // If updating, delete old poster from server
                        if ($action === "update" && !empty($editEvent["poster_path"])) {
                            if (file_exists($editEvent["poster_path"])) {
                                unlink($editEvent["poster_path"]);
                            }
                        }
                    } else {
                        $error = "Failed to upload poster image.";
                    }
                }
            }

            if (!$error) {
                if ($action === "add") {
                    $stmt = $conn->prepare("INSERT INTO events (title, date, location, category, poster_path) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $title, $date, $location, $category, $posterPath);
                    if ($stmt->execute()) {
                        $stmt->close();
                        redirectWithStatus("added");
                    }
                    $error = "Unable to add event.";
                    $stmt->close();
                } else {
                    // If no new poster was uploaded, keep the old one
                    $finalPoster = $posterPath ?? $editEvent["poster_path"];
                    $stmt = $conn->prepare("UPDATE events SET title=?, date=?, location=?, category=?, poster_path=? WHERE id=?");
                    $stmt->bind_param("ssssii", $title, $date, $location, $category, $finalPoster, $id);
                    if ($stmt->execute()) {
                        $stmt->close();
                        redirectWithStatus("updated");
                    }
                    $error = "Unable to update event.";
                    $stmt->close();
                }
            }
        }
    } elseif ($action === "delete") {
        $delId = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT);
        if ($delId) {
            // First, find and delete the poster file from server
            $stmt = $conn->prepare("SELECT poster_path FROM events WHERE id = ?");
            $stmt->bind_param("i", $delId);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            if ($res && $res["poster_path"] && file_exists($res["poster_path"])) {
                unlink($res["poster_path"]);
            }
            $stmt->close();

            // Now delete the database record
            $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
            $stmt->bind_param("i", $delId);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $stmt->close();
                redirectWithStatus("deleted");
            }
            $error = "Event was not deleted.";
            $stmt->close();
        }
    }
}

// --- GET ACTIONS ---

// 1. Handle Edit Mode
$editId = filter_var($_GET["edit"] ?? null, FILTER_VALIDATE_INT);
if ($editId && $editId > 0) {
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editEvent = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}

// 2. Fetch Events List
$sql = "SELECT * FROM events ORDER BY date ASC";
$result = $conn->query($sql);
$events = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    $result->free();
}

// Status Messages
$statusMessages = [
    "added"   => "Event created successfully.",
    "updated" => "Event updated successfully.",
    "deleted" => "Event deleted successfully."
];
$status = (string)($_GET["status"] ?? "");
$successMessage = $statusMessages[$status] ?? "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events | StudentHub Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .management-container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .form-card { background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .notice { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .notice-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .notice-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .event-table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: #fff; }
        .event-table th, .event-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .event-table th { background: #f8f9fa; font-weight: 600; }
        .poster-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
        .btn-edit { color: #007bff; text-decoration: none; margin-right: 10px; }
        .btn-delete { color: #dc3545; border: none; background: none; cursor: pointer; font-weight: bold; }
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
        <h2 class="page-title">Event Management</h2>

        <?php if ($successMessage): ?>
            <div class="notice notice-success" role="status"><?php echo h($successMessage); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="notice notice-error" role="alert"><?php echo h($error); ?></div>
        <?php endif; ?>

        <!-- Create/Update Form -->
        <section class="form-card">
            <h3><?php echo $editEvent ? "Edit Event" : "Add New Event"; ?></h3>
            <form method="POST" action="event_management.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION["csrf_token"]); ?>">
                <input type="hidden" name="action" value="<?php echo $editEvent ? "update" : "add"; ?>">
                <?php if ($editEvent): ?>
                    <input type="hidden" name="id" value="<?php echo (int)$editEvent["id"]; ?>">
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <div>
                        <label>Event Title *</label>
                        <input type="text" name="title" value="<?php echo h($editEvent["title"] ?? ""); ?>" required>
                    </div>
                    <div>
                        <label>Date *</label>
                        <input type="date" name="date" value="<?php echo h($editEvent["date"] ?? ""); ?>" required>
                    </div>
                    <div>
                        <label>Location *</label>
                        <input type="text" name="location" value="<?php echo h($editEvent["location"] ?? ""); ?>" required>
                    </div>
                    <div>
                        <label>Category *</label>
                        <select name="category" required>
                            <option value="">Select category</option>
                            <option value="Technical" <?php echo (isset($editEvent) && $editEvent["category"] === "Technical") ? "selected" : ""; ?>>Technical</option>
                            <option value="Cultural" <?php echo (isset($editEvent) && $editEvent["category"] === "Cultural") ? "selected" : ""; ?>>Cultural</option>
                            <option value="Sports" <?php echo (isset($editEvent) && $editEvent["category"] === "Sports") ? "selected" : ""; ?>>Sports</option>
                            <option value="Academic" <?php echo (isset($editEvent) && $editEvent["category"] === "Academic") ? "selected" : ""; ?>>Academic</option>
                        </select>
                    </div>
                    <div>
                        <label>Event Poster *</label>
                        <input type="file" name="poster" accept="image/jpeg,image/jpg,image/png,image/webp">
                        <?php if ($editEvent && $editEvent["poster_path"]): ?>
                            <div style="margin-top: 5px;">
                                <img src="<?php echo h($editEvent["poster_path"]); ?>" width="50" alt="Current poster">
                                <small>(Leave blank to keep current image)</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary"><?php echo $editEvent ? "Update Event" : "Create Event"; ?></button>
                    <?php if ($editEvent): ?>
                        <a href="event_management.php" style="margin-left: 1rem;">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- Event List -->
        <section class="list-section">
            <table class="event-table">
                <thead>
                    <tr>
                        <th>Poster</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                        <tr><td colspan="6" style="text-align: center;">No events found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($events as $e): ?>
                        <tr>
                            <td>
                                <?php if ($e["poster_path"]): ?>
                                    <img src="<?php echo h($e["poster_path"]); ?>" class="poster-thumb" alt="Poster">
                                <?php else: ?>
                                    <div class="poster-thumb" style="background: #eee; display: flex; align-items: center; justify-content: center; font-size: 10px;">No Image</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo h($e["title"]); ?></td>
                            <td><?php echo h($e["date"]); ?></td>
                            <td><?php echo h($e["location"]); ?></td>
                            <td><?php echo h($e["category"]); ?></td>
                            <td>
                                <a href="?edit=<?php echo (int)$e["id"]; ?>" class="btn-edit">Edit</a>
                                <form method="POST" action="event_management.php" style="display: inline;"
                                      onsubmit="return confirm('Are you sure you want to delete this event?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION["csrf_token"]); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$e["id"]; ?>">
                                    <button type="submit" class="btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
    <script src="js/script.js"></script>
</body>
</html>
