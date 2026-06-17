<?php
/**
 * Registration Handler
 * Allows new students to create an account
 */

require_once '../../config/database.php';
/** @var PDO $pdo */

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../../student/dashboard.php");
    exit();
}

$error = '';
$success = '';
$form_data = [];

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get and sanitize form data
    $form_data['username'] = trim($_POST['username'] ?? '');
    $form_data['password'] = $_POST['password'] ?? '';
    $form_data['confirm_password'] = $_POST['confirm_password'] ?? '';
    $form_data['first_name'] = trim($_POST['first_name'] ?? '');
    $form_data['middle_name'] = trim($_POST['middle_name'] ?? '');
    $form_data['last_name'] = trim($_POST['last_name'] ?? '');
    $form_data['suffix_name'] = trim($_POST['suffix_name'] ?? '');
    $form_data['course'] = trim($_POST['course'] ?? '');
    $form_data['year_level'] = (int)($_POST['year_level'] ?? 0);
    $form_data['section'] = trim($_POST['section'] ?? '');
    $form_data['phone_number'] = trim($_POST['phone_number'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($form_data['username'])) {
        $errors[] = "Username is required";
    } elseif (strlen($form_data['username']) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }
    
    if (empty($form_data['password'])) {
        $errors[] = "Password is required";
    } elseif (strlen($form_data['password']) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($form_data['password'] !== $form_data['confirm_password']) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($form_data['first_name'])) {
        $errors[] = "First name is required";
    }
    
    if (empty($form_data['last_name'])) {
        $errors[] = "Last name is required";
    }
    
    if (empty($form_data['course'])) {
        $errors[] = "Course is required";
    }
    
    if ($form_data['year_level'] < 1 || $form_data['year_level'] > 4) {
        $errors[] = "Valid year level (1-4) is required";
    }
    
    // Check if username exists
    // Check if username exists
    if (empty($errors)) {
        try {
            $check_sql = "SELECT id FROM dbo.users WHERE user_name = ?";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([$form_data['username']]);
            if ($check_stmt->fetch()) {
                $errors[] = "Username already taken";
            }
        } catch (Exception $e) {
            $errors[] = "Database validation error: " . $e->getMessage();
        }
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        
        try {
            // Start transaction securely with PDO
            $pdo->beginTransaction();
            
            // Get student role ID
            $role_sql = "SELECT id FROM dbo.roles WHERE role = 'student'";
            $role_stmt = $pdo->query($role_sql);
            $role_row = $role_stmt->fetch();
            $role_id = $role_row['id'];
            
            // Generate account code
            $account_code = 'STU_' . date('Ymd') . '_' . rand(1000, 9999);
            
            // Hash password
            $password_hash = password_hash($form_data['password'], PASSWORD_DEFAULT);
            
            // Insert into users
            $user_sql = "INSERT INTO dbo.users (user_name, password_hash, role_id, account_code) VALUES (?, ?, ?, ?)";
            $user_stmt = $pdo->prepare($user_sql);
            $user_stmt->execute([$form_data['username'], $password_hash, $role_id, $account_code]);
            
            // Get the generated user ID natively using PDO
            $user_id = $pdo->lastInsertId();
            
            // Insert into student_profiles
            $profile_sql = "INSERT INTO dbo.student_profiles 
                            (first_name, middle_name, last_name, suffix_name, 
                             course, year_level, section, phone_number, account_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $profile_stmt = $pdo->prepare($profile_sql);
            $profile_stmt->execute([
                $form_data['first_name'], 
                $form_data['middle_name'], 
                $form_data['last_name'],
                $form_data['suffix_name'], 
                $form_data['course'], 
                $form_data['year_level'],
                $form_data['section'], 
                $form_data['phone_number'], 
                $user_id
            ]);
            
            // Commit database changes
            $pdo->commit();
            
            $success = "Registration successful! You can now login.";
            $form_data = []; // Clear form
            
        } catch (Exception $e) {
            // Roll back database if anything fails
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Registration failed: " . $e->getMessage();
        }
        
    } else {
        $error = implode("<br>", $errors);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Attendance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .register-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 40px;
        }
        h2 { text-align: center; color: #333; margin-bottom: 10px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 30px; font-size: 14px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-group { flex: 1; margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #333; font-weight: 500; font-size: 14px; }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        input:focus, select:focus { outline: none; border-color: #667eea; }
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
            margin-top: 20px;
        }
        button:hover { transform: translateY(-2px); }
        .error { background: #fee; color: #c33; padding: 12px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #c33; }
        .success { background: #efe; color: #3c3; padding: 12px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #3c3; }
        .login-link { text-align: center; margin-top: 20px; color: #666; }
        .login-link a { color: #667eea; text-decoration: none; }
        hr { margin: 20px 0; border: none; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Create Account</h2>
        <div class="subtitle">Register as a new student</div>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <h3 style="margin-bottom: 15px; color: #667eea;">Account Information</h3>
            
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" required>
                </div>
            </div>
            
            <hr>
            
            <h3 style="margin-bottom: 15px; color: #667eea;">Personal Information</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" value="<?php echo htmlspecialchars($form_data['middle_name'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Suffix (Jr., Sr., III)</label>
                    <input type="text" name="suffix_name" value="<?php echo htmlspecialchars($form_data['suffix_name'] ?? ''); ?>">
                </div>
            </div>
            
            <hr>
            
            <h3 style="margin-bottom: 15px; color: #667eea;">Academic Information</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Course *</label>
                    <input type="text" name="course" value="<?php echo htmlspecialchars($form_data['course'] ?? ''); ?>" required placeholder="e.g., BS Information Technology">
                </div>
                <div class="form-group">
                    <label>Year Level *</label>
                    <select name="year_level" required>
                        <option value="">Select</option>
                        <option value="1" <?php echo (($form_data['year_level'] ?? '') == 1) ? 'selected' : ''; ?>>1st Year</option>
                        <option value="2" <?php echo (($form_data['year_level'] ?? '') == 2) ? 'selected' : ''; ?>>2nd Year</option>
                        <option value="3" <?php echo (($form_data['year_level'] ?? '') == 3) ? 'selected' : ''; ?>>3rd Year</option>
                        <option value="4" <?php echo (($form_data['year_level'] ?? '') == 4) ? 'selected' : ''; ?>>4th Year</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Section</label>
                    <input type="text" name="section" value="<?php echo htmlspecialchars($form_data['section'] ?? ''); ?>" placeholder="e.g., A, B, 1">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" value="<?php echo htmlspecialchars($form_data['phone_number'] ?? ''); ?>" placeholder="e.g., 09123456789">
                </div>
            </div>
            
            <button type="submit">Register</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="Login.php">Login here</a>
        </div>
    </div>
</body>
</html>
