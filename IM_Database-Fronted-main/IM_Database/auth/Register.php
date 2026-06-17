<?php
/**
 * Registration Handler
 * Allows new students to create an account
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/database_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn) || $conn === false) {
    throw new Exception('Database connection not initialized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.html');
    exit();
}

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
    $form_data['student_number'] = trim($_POST['student_number'] ?? '');
    $form_data['phone_number'] = trim($_POST['phone_number'] ?? '');
    $form_data['section'] = '2-5'; // Fixed section for 2-5 year level
    
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
    
    if (empty($form_data['year_level']) || $form_data['year_level'] < 1 || $form_data['year_level'] > 4) {
        $errors[] = "Valid year level (1-4) is required";
    }

    if (empty($form_data['student_number'])) {
        $errors[] = "Student number is required";
    }
    // Student number format is flexible - just needs to be non-empty
    
    // Check if username exists (skip for now to isolate issue)
    // if (empty($errors)) {
    //     try {
    //         $check_sql = "SELECT id FROM dbo.users WHERE user_name = ?";
    //         $check_stmt = sqlsrv_query($conn, $check_sql, [$form_data['username']]);
    //         if ($check_stmt === false) {
    //             throw new Exception('Username check query failed');
    //         }
    //         $existing_user = sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC);
    //         if ($existing_user) {
    //             $errors[] = "Username already taken";
    //         }
    //     } catch (Exception $e) {
    //         $errors[] = "Database error: " . $e->getMessage();
    //     }
    // }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        
        try {
            $transactionStarted = false;
            // Start transaction with PostgreSQL
            if (!db_begin_transaction($conn)) {
                throw new Exception('Could not start transaction');
            }
            $transactionStarted = true;
            
            // Get student role ID
            $role_sql = "SELECT id FROM roles WHERE role = 'student'";
            $role_stmt = db_query($conn, $role_sql);
            if ($role_stmt === false) {
                throw new Exception('Failed to retrieve role information');
            }
            $role_row = db_fetch_assoc($role_stmt);
            if (!$role_row) {
                // Create the student role if it does not exist yet
                $insert_role_sql = "INSERT INTO roles (role) VALUES ('student') RETURNING id";
                $insert_role_stmt = db_query($conn, $insert_role_sql);
                if ($insert_role_stmt === false) {
                    throw new Exception('Failed to create missing student role');
                }
                $scope_row = db_fetch_assoc($insert_role_stmt);
                if (!$scope_row || empty($scope_row['id'])) {
                    throw new Exception('Student role creation returned no ID');
                }
                $role_id = $scope_row['id'];
            } else {
                $role_id = $role_row['id'];
            }
            
            // Generate account code
            $account_code = 'STU_' . date('Ymd') . '_' . rand(1000, 9999);
            
            // Hash password
            $password_hash = password_hash($form_data['password'], PASSWORD_DEFAULT);
            
            // Insert into users
            $user_sql = "INSERT INTO users (user_name, password_hash, role_id, account_code) VALUES ('" . pg_escape_string($conn, $form_data['username']) . "', '" . pg_escape_string($conn, $password_hash) . "', '" . pg_escape_string($conn, $role_id) . "', '" . pg_escape_string($conn, $account_code) . "') RETURNING id";
            $user_stmt = db_query($conn, $user_sql);
            if ($user_stmt === false) {
                throw new Exception('Failed to insert user');
            }
            
            // Get the generated user ID
            $id_row = db_fetch_assoc($user_stmt);
            if (!$id_row || empty($id_row['id'])) {
                throw new Exception('Failed to retrieve user ID');
            }
            $user_id = $id_row['id'];
            
            // Insert into student_profiles
            $profile_sql = "INSERT INTO student_profiles 
                            (first_name, middle_name, last_name, suffix_name, 
                             course, year_level, student_number, phone_number, account_id) 
                            VALUES ('" . pg_escape_string($conn, $form_data['first_name']) . "', '" . pg_escape_string($conn, $form_data['middle_name']) . "', '" . pg_escape_string($conn, $form_data['last_name']) . "', '" . pg_escape_string($conn, $form_data['suffix_name']) . "', '" . pg_escape_string($conn, $form_data['course']) . "', '" . pg_escape_string($conn, $form_data['year_level']) . "', '" . pg_escape_string($conn, $form_data['student_number']) . "', '" . pg_escape_string($conn, $form_data['phone_number']) . "', '" . pg_escape_string($conn, $user_id) . "')";
            $profile_stmt = db_query($conn, $profile_sql);
            if ($profile_stmt === false) {
                throw new Exception('Failed to insert student profile');
            }
            
            // Commit database changes
            if (!db_commit($conn)) {
                throw new Exception('Failed to commit transaction');
            }
            
            // Handle redirect after registration
            $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '../login.html';
            header('Location: ' . $redirect . '?registered=1');
            exit();
        } catch (Exception $e) {
            // Roll back database if anything fails
            if (!empty($transactionStarted) && db_rollback($conn) === false) {
                $error = "Registration failed and rollback failed: " . $e->getMessage();
            } else {
                $error = "Registration failed: " . $e->getMessage();
            }
            // Redirect back to registration page with error
            header('Location: ../register.html?error=' . urlencode($error));
            exit();
        }
        
    } else {
        $error = implode("<br>", $errors);
        // Redirect back to registration page with error
        header('Location: ../register.html?error=' . urlencode($error));
        exit();
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
            <div class="error" style="font-size: 16px; font-weight: bold; white-space: pre-wrap;">
                ❌ REGISTRATION ERROR:<br/><?php echo htmlspecialchars($error); ?>
            </div>
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
                    <label>Student Number *</label>
                    <input type="text" name="student_number" value="<?php echo htmlspecialchars($form_data['student_number'] ?? ''); ?>" required placeholder="e.g., 2023-10503-MN-0">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" value="<?php echo htmlspecialchars($form_data['phone_number'] ?? ''); ?>" placeholder="e.g., 09123456789">
                </div>
            </div>
            <input type="hidden" name="section" value="2-5" />
            
            <button type="submit">Register</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.html">Login here</a>
        </div>
    </div>
</body>
</html>
