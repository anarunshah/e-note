<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Delete note if requested
if (isset($_POST['delete_note'])) {
    $note_id = filter_var($_POST['note_id'], FILTER_SANITIZE_NUMBER_INT);
    $user_id = $_SESSION['user_id'];
    
    $sql = "DELETE FROM notes WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $note_id, $user_id);
    
    if ($stmt->execute()) {
        $success = "Note deleted successfully!";
    } else {
        $error = "Failed to delete note.";
    }
}

// Fetch user's notes
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM notes WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notes - E Notes</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .notes-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .notes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .note-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .note-card h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .note-content {
            margin-bottom: 1rem;
            color: var(--text-color);
        }
        
        .note-meta {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }
        
        .note-attachment {
            margin: 1rem 0;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .note-attachment a {
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .note-attachment a:hover {
            text-decoration: underline;
        }
        
        .note-actions {
            display: flex;
            gap: 1rem;
        }
        
        .edit-btn, .delete-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s;
        }
        
        .edit-btn {
            background: var(--primary-color);
            color: white;
        }
        
        .delete-btn {
            background: #dc3545;
            color: white;
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

    <div class="notes-container">
        <h2>My Notes</h2>
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="notes-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($note = $result->fetch_assoc()): ?>
                    <div class="note-card">
                        <h3><?php echo htmlspecialchars($note['title']); ?></h3>
                        <div class="note-content">
                            <?php echo nl2br(htmlspecialchars($note['content'])); ?>
                        </div>
                        <?php if ($note['attachment']): ?>
                        <div class="note-attachment">
                            <a href="<?php echo htmlspecialchars($note['attachment']); ?>" target="_blank">
                                <i class="fas fa-paperclip"></i>
                                <?php echo basename($note['attachment']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        <div class="note-meta">
                            Created: <?php echo date('M j, Y', strtotime($note['created_at'])); ?>
                        </div>
                        <div class="note-actions">
                            <a href="edit_note.php?id=<?php echo $note['id']; ?>" class="edit-btn">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form action="show_notes.php" method="POST" style="display: inline;">
                                <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                <button type="submit" name="delete_note" class="delete-btn" 
                                        onclick="return confirm('Are you sure you want to delete this note?')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No notes found. <a href="add_note.php">Add your first note</a></p>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 E Notes. All rights reserved.</p>
    </footer>
</body>
</html>
