<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user's note count
$user_id = $_SESSION['user_id'];
$sql = "SELECT COUNT(*) as note_count FROM notes WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$note_count = $result->fetch_assoc()['note_count'];

// Get recent notes
$sql = "SELECT * FROM notes WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_notes = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - E Notes</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .dashboard-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .stat-card {
            background: var(--primary-color);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .recent-notes {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .note-list {
            margin-top: 1rem;
        }
        
        .note-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .note-item:last-child {
            border-bottom: none;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .action-btn {
            display: block;
            padding: 1rem;
            background: var(--primary-color);
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            transition: transform 0.3s;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
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

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
            <div class="stats">
                <div class="stat-card">
                    <h3><?php echo $note_count; ?></h3>
                    <p>Total Notes</p>
                </div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="add_note.php" class="action-btn">
                <i class="fas fa-plus"></i> Add New Note
            </a>
            <a href="show_notes.php" class="action-btn">
                <i class="fas fa-list"></i> View All Notes
            </a>
        </div>

        <div class="recent-notes">
            <h3>Recent Notes</h3>
            <div class="note-list">
                <?php if ($recent_notes->num_rows > 0): ?>
                    <?php while ($note = $recent_notes->fetch_assoc()): ?>
                        <div class="note-item">
                            <h4><?php echo htmlspecialchars($note['title']); ?></h4>
                            <p><?php echo substr(htmlspecialchars($note['content']), 0, 100) . '...'; ?></p>
                            <small>Created: <?php echo date('M j, Y', strtotime($note['created_at'])); ?></small>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No notes yet. <a href="add_note.php">Add your first note</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 E Notes. All rights reserved.</p>
    </footer>
</body>
</html>
