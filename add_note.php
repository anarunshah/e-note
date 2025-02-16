<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $content = filter_var($_POST['content'], FILTER_SANITIZE_STRING);
    $user_id = $_SESSION['user_id'];
    
    // Handle file upload
    $attachment_path = null;
    $original_filename = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) {
            if (!@mkdir($upload_dir, 0777, true)) {
                $error = "Failed to create upload directory. Please contact administrator.";
            }
        }
        
        // Validate file size (max 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($_FILES['attachment']['size'] > $max_size) {
            $error = "File is too large. Maximum size allowed is 5MB.";
        } else {
            // Get file information
            $file_info = pathinfo($_FILES['attachment']['name']);
            $file_ext = strtolower($file_info['extension']);
            $original_filename = $_FILES['attachment']['name'];
            
            // Allowed file types with their MIME types
            $allowed_types = array(
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'txt' => 'text/plain',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png'
            );
            
            if (array_key_exists($file_ext, $allowed_types)) {
                // Validate MIME type
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $_FILES['attachment']['tmp_name']);
                finfo_close($finfo);
                
                if ($mime_type === $allowed_types[$file_ext]) {
                    // Generate secure filename
                    $unique_id = uniqid('', true);
                    $file_name = $unique_id . '_' . time() . '.' . $file_ext;
                    $target_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_path)) {
                        $attachment_path = $target_path;
                    } else {
                        $error = "Failed to upload file. Please try again.";
                    }
                } else {
                    $error = "Invalid file type detected. Please upload a valid file.";
                }
            } else {
                $error = "Invalid file type. Allowed types: PDF, DOC, DOCX, TXT, JPG, JPEG, PNG";
            }
        }
    }
    
    if (!isset($error)) {
        $sql = "INSERT INTO notes (user_id, title, content, attachment, original_filename) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $user_id, $title, $content, $attachment_path, $original_filename);
        
        if ($stmt->execute()) {
            $success = "Note added successfully!";
        } else {
            $error = "Failed to add note. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Note - E Notes</title>
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
        <h2>Add New Note</h2>
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form action="add_note.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="content">Content</label>
                <textarea id="content" name="content" required></textarea>
            </div>
            <div class="form-group">
                <label for="attachment">Attachment (Optional)</label>
                <input type="file" id="attachment" name="attachment">
                <small>Allowed file types: PDF, DOC, DOCX, TXT, JPG, JPEG, PNG</small>
            </div>
            <button type="submit" class="submit-btn">Save Note</button>
        </form>
    </div>

    <footer>
        <p>&copy; 2024 E Notes. All rights reserved.</p>
    </footer>
</body>
</html>
