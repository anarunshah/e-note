<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if note ID is provided
if (!isset($_GET['id'])) {
    header("Location: show_notes.php");
    exit();
}

$note_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
$user_id = $_SESSION['user_id'];

// Fetch note details
$sql = "SELECT * FROM notes WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $note_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: show_notes.php");
    exit();
}

$note = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $content = filter_var($_POST['content'], FILTER_SANITIZE_STRING);
    
    $update_sql = "UPDATE notes SET title = ?, content = ? WHERE id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssii", $title, $content, $note_id, $user_id);
    
    if ($update_stmt->execute()) {
        $success = "Note updated successfully!";
        // Refresh note data
        $stmt->execute();
        $result = $stmt->get_result();
        $note = $result->fetch_assoc();
    } else {
        $error = "Failed to update note. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Note - E Notes</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .note-form {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .note-form textarea {
            width: 100%;
            min-height: 300px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-book-reader"></i> E Notes
        </div>
        <div class="nav-links">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="add_note.php"><i class="fas fa-plus"></i> Add Note</a>
            <a href="show_notes.php"><i class="fas fa-list"></i> Show Notes</a>
            <a href="logout.php" class="login-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="note-form">
        <h2>Edit Note</h2>
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form action="edit_note.php?id=<?php echo $note_id; ?>" method="POST">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($note['title']); ?>" required>
            </div>
            <div class="form-group">
                <label for="content">Content</label>
                <textarea id="content" name="content" required><?php echo htmlspecialchars($note['content']); ?></textarea>
            </div>
            <button type="submit" class="submit-btn">Update Note</button>
            <a href="show_notes.php" class="btn" style="display: inline-block; margin-left: 1rem; color: var(--primary-color);">Cancel</a>
        </form>
    </div>

    <footer>
        <p>&copy; 2024 E Notes. All rights reserved.</p>
    </footer>
</body>
</html>
