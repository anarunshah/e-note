<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'enotes_db';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create users table with enhanced fields
$users_table = "CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255),
    account_status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(100),
    reset_token VARCHAR(100),
    reset_token_expiry DATETIME,
    last_login DATETIME,
    login_attempts INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Create notes table
$notes_table = "CREATE TABLE IF NOT EXISTS notes (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    attachment VARCHAR(255),
    original_filename VARCHAR(255),
    category VARCHAR(50),
    tags TEXT,
    is_pinned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (!$conn->query($notes_table)) {
    die("Error creating notes table: " . $conn->error);
}

// Add attachment column if it doesn't exist
$check_attachment = "SHOW COLUMNS FROM notes LIKE 'attachment'";
$result = $conn->query($check_attachment);
if ($result->num_rows == 0) {
    $add_attachment = "ALTER TABLE notes ADD COLUMN attachment VARCHAR(255) AFTER content";
    if (!$conn->query($add_attachment)) {
        die("Error adding attachment column: " . $conn->error);
    }
}

// Add original_filename column if it doesn't exist
$check_filename = "SHOW COLUMNS FROM notes LIKE 'original_filename'";
$result = $conn->query($check_filename);
if ($result->num_rows == 0) {
    $add_filename = "ALTER TABLE notes ADD COLUMN original_filename VARCHAR(255) AFTER attachment";
    if (!$conn->query($add_filename)) {
        die("Error adding original_filename column: " . $conn->error);
    }
}

// Create login_history table for security
$login_history_table = "CREATE TABLE IF NOT EXISTS login_history (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('success', 'failed') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

$conn->query($users_table);
$conn->query($login_history_table);
