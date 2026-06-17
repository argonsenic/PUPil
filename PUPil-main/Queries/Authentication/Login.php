<?php
/**
 * Login Handler
 * Validates user credentials and redirects to appropriate dashboard
 */

// Start session at the very top so log-ins work
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. FIXED PATH: Points exactly to your Database folder configuration
require_once __DIR__ . '/../../Database/connection.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new Exception('Database connection not initialized');
}

// Initialize variables
$error = '';
$username = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on role
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../../admin/dashboard.php");
    } elseif ($_SESSION['role'] === 'instructor') {
        header("Location: ../../instructor/dashboard.php");
    } elseif ($_SESSION['role'] === 'student') {
        header("Location: ../../student/dashboard.php");
    }
    exit();
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get and sanitize form data
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        
        try {
            // 2. FIXED QUERY SYNTAX: Uses PDO to securely query dbo.users
            $sql = "SELECT 
                        u.id, 
                        u.user_name, 
                        u.password_hash, 
                        u.role_id, 
                        u.account_code,
                        r.role 
                    FROM dbo.users u 
                    INNER JOIN dbo.roles r ON u.role_id = r.id 
                    WHERE u.user_name = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Verify password
                if (password_verify($password, $user['password_hash'])) {
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['user_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['role_id'] = $user['role_id'];
                    $_SESSION['account_code'] = $user['account_code'];
                    $_SESSION['logged_in'] = true;
                    
                    // Get profile data based on role
                    if ($user['role'] === 'student') {
                        $profile_sql = "SELECT * FROM dbo.student_profiles WHERE account_id = ?";
                        $p_stmt = $pdo->prepare($profile_sql);
                        $p_stmt->execute([$user['id']]);
                        $profile = $p_stmt->fetch();
                        
                        if ($profile) {
                            $_SESSION['profile'] = $profile;
                            $_SESSION['full_name'] = $profile['first_name'] . ' ' . $profile['last_name'];
                        }
                        header("Location: ../../student/dashboard.php");
                        
                    } elseif ($user['role'] === 'instructor') {
                        $profile_sql = "SELECT * FROM dbo.instructor_profiles WHERE account_id = ?";
                        $p_stmt = $pdo->prepare($profile_sql);
                        $p_stmt->execute([$user['id']]);
                        $profile = $p_stmt->fetch();
                        
                        if ($profile) {
                            $_SESSION['profile'] = $profile;
                            $_SESSION['full_name'] = $profile['first_name'] . ' ' . $profile['last_name'];
                        }
                        header("Location: ../../instructor/dashboard.php");
                        
                    } elseif ($user['role'] === 'admin') {
                        $_SESSION['full_name'] = 'Administrator';
                        header("Location: ../../admin/dashboard.php");
                    }
                    exit();
                    
                } else {
                    $error = "Invalid password";
                }
            } else {
                $error = "Username not found";
            }
        } catch (Exception $e) {
            $error = "Database error occurred: " . $e->getMessage();
        }
    }
}
?>

<!-- Login HTML Form -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Attendance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        h2 { text-align: center; color: #333; margin-bottom: 10px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 30px; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        input:focus { outline: none; border-color: #667eea; }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover { transform: translateY(-2px); }
        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }
        .register-link { text-align: center; margin-top: 20px; color: #666; }
        .register-link a { color: #667eea; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Attendance System</h2>
        <div class="subtitle">Login to access your dashboard</div>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        
        <div class="register-link">
            Don't have an account? <a href="Register.php">Register here</a>
        </div>
    </div>
</body>
</html>
