<?php
session_start();
require_once 'config/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']) ? true : false;
    
    // Validate input
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }

    if (empty($password)) {
        $errors['password'] = "Password is required";
    }

    if (empty($errors)) {
        $sql = "SELECT * FROM users WHERE email = ? AND account_status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if account is locked due to too many attempts
            if ($user['login_attempts'] >= 5) {
                $last_attempt = new DateTime($user['last_login']);
                $now = new DateTime();
                $diff = $now->diff($last_attempt);
                
                if ($diff->i < 15) { // 15 minutes lockout
                    $errors['general'] = "Account is temporarily locked. Please try again later or reset your password.";
                } else {
                    // Reset login attempts after lockout period
                    $reset_sql = "UPDATE users SET login_attempts = 0 WHERE id = ?";
                    $reset_stmt = $conn->prepare($reset_sql);
                    $reset_stmt->bind_param("i", $user['id']);
                    $reset_stmt->execute();
                }
            }
            
            if (empty($errors) && password_verify($password, $user['password'])) {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                
                // Reset login attempts
                $reset_sql = "UPDATE users SET login_attempts = 0, last_login = NOW() WHERE id = ?";
                $reset_stmt = $conn->prepare($reset_sql);
                $reset_stmt->bind_param("i", $user['id']);
                $reset_stmt->execute();
                
                // Record login history
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                $history_sql = "INSERT INTO login_history (user_id, ip_address, user_agent, status) VALUES (?, ?, ?, 'success')";
                $history_stmt = $conn->prepare($history_sql);
                $history_stmt->bind_param("iss", $user['id'], $ip_address, $user_agent);
                $history_stmt->execute();
                
                // Handle remember me
                if ($remember_me) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                    
                    // Store token in database (you'd need to add a remember_token column to users table)
                    $token_sql = "UPDATE users SET remember_token = ? WHERE id = ?";
                    $token_stmt = $conn->prepare($token_sql);
                    $token_stmt->bind_param("si", $token, $user['id']);
                    $token_stmt->execute();
                }
                
                header("Location: dashboard.php");
                exit();
            } else {
                // Failed login attempt
                $attempts_sql = "UPDATE users SET login_attempts = login_attempts + 1, last_login = NOW() WHERE id = ?";
                $attempts_stmt = $conn->prepare($attempts_sql);
                $attempts_stmt->bind_param("i", $user['id']);
                $attempts_stmt->execute();
                
                // Record failed login
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                $history_sql = "INSERT INTO login_history (user_id, ip_address, user_agent, status) VALUES (?, ?, ?, 'failed')";
                $history_stmt = $conn->prepare($history_sql);
                $history_stmt->bind_param("iss", $user['id'], $ip_address, $user_agent);
                $history_stmt->execute();
                
                $errors['general'] = "Invalid email or password";
            }
        } else {
            $errors['general'] = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E Notes</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        .remember-me input {
            margin-right: 0.5rem;
        }
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
            <h2>Login</h2>
        </div>
        <?php if (isset($errors['general'])): ?>
            <div class="message error"><?php echo htmlspecialchars($errors['general']); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" novalidate>
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
                <?php if (isset($errors['password'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['password']); ?></div>
                <?php endif; ?>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember_me" name="remember_me">
                <label for="remember_me">Remember me</label>
            </div>

            <button type="submit" class="submit-btn">Login</button>
            
            <div class="form-links">
                <!-- <a href="forgot-password.php">Forgot Password?</a> -->
            </div>
        </form>
        
        <div class="form-footer">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 E Notes. All rights reserved.</p>
    </footer>

    <script>
        // Client-side validation
        document.querySelector('form').addEventListener('submit', function(e) {
            let hasError = false;
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            
            // Reset previous error messages
            document.querySelectorAll('.error-message').forEach(el => el.remove());
            
            // Email validation
            if (!email.value) {
                addError(email, 'Email is required');
                hasError = true;
            } else if (!isValidEmail(email.value)) {
                addError(email, 'Invalid email format');
                hasError = true;
            }
            
            // Password validation
            if (!password.value) {
                addError(password, 'Password is required');
                hasError = true;
            }
            
            if (hasError) {
                e.preventDefault();
            }
        });

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        function addError(element, message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = message;
            element.parentNode.appendChild(errorDiv);
        }
    </script>
</body>
</html>
