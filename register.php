<?php
session_start();
require_once 'config/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate full name
    $full_name = trim($_POST['full_name']);
    if (empty($full_name)) {
        $errors['full_name'] = "Full name is required";
    } elseif (!preg_match("/^[a-zA-Z ]{2,100}$/", $full_name)) {
        $errors['full_name'] = "Full name should only contain letters and spaces (2-100 characters)";
    }

    // Validate email
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }

    // Validate password
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password)) {
        $errors['password'] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters long";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
        $errors['password'] = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character";
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match";
    }

    // Check if email already exists
    if (empty($errors['email'])) {
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $errors['email'] = "Email already exists";
        }
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $verification_token = bin2hex(random_bytes(32));
        
        $sql = "INSERT INTO users (full_name, email, password, verification_token) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $full_name, $email, $hashed_password, $verification_token);
        
        if ($stmt->execute()) {
            $success = "Registration successful! Please check your email to verify your account.";
            // TODO: Implement email verification system
        } else {
            $errors['general'] = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - E Notes</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-book-reader"></i> E Notes
        </div>
        <div class="nav-links">
            <a href="index.html"><i class="fas fa-home"></i> Home</a>
            <div class="auth-buttons">
                <a href="login.php" class="login-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="register.php" class="register-btn"><i class="fas fa-user-plus"></i> Register</a>
            </div>
        </div>
    </nav>

    <div class="form-container">
        <div class="form-header">
            <h2>Register</h2>
        </div>
        <?php if (!empty($success)): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (isset($errors['general'])): ?>
            <div class="message error"><?php echo htmlspecialchars($errors['general']); ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="registerForm" novalidate>
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                <?php if (isset($errors['full_name'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['full_name']); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <div id="passwordStrength" class="password-strength"></div>
                <?php if (isset($errors['password'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['password']); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <?php if (isset($errors['confirm_password'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="submit-btn">Register</button>
        </form>
        <div class="form-footer">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 E Notes. All rights reserved.</p>
    </footer>

    <script>
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            let strength = 0;
            let message = '';

            // Length check
            if (password.length >= 8) strength++;
            // Uppercase check
            if (password.match(/[A-Z]/)) strength++;
            // Lowercase check
            if (password.match(/[a-z]/)) strength++;
            // Number check
            if (password.match(/[0-9]/)) strength++;
            // Special character check
            if (password.match(/[^A-Za-z0-9]/)) strength++;

            switch(strength) {
                case 0:
                case 1:
                case 2:
                    message = '<span class="strength-weak">Weak Password</span>';
                    break;
                case 3:
                case 4:
                    message = '<span class="strength-medium">Medium Password</span>';
                    break;
                case 5:
                    message = '<span class="strength-strong">Strong Password</span>';
                    break;
            }

            strengthDiv.innerHTML = message;
        });

        // Real-time password match validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
