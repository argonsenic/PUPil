<?php
/**
 * Update Student Handler (Admin only)
 * Allows admin to edit student information
 */

require_once '../../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Authentication/Login.php");
    exit();
}

// Get student ID
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0);

if ($student_id <= 0) {
    header("Location: AddStudent.php");
    exit();
}

$error = '';
$success = '';
$form_data = [];

// Fetch current student data
$fetch_sql = "SELECT 
                    sp.id, sp.first_name, sp.middle_name, sp.last_name, sp.suffix_name,
                    sp.course, sp.year_level, sp.section, sp.phone_number,
                    u.id AS user_id, u.user_name
                FROM student_profiles sp
                INNER JOIN users u ON sp.account_id = u.id
                WHERE sp.id = ?";

$fetch_stmt = sqlsrv_query($conn, $fetch_sql, array($student_id));

if (!$fetch_stmt || !sqlsrv_has_rows($fetch_stmt)) {
    die("Student not found");
}

$current = sqlsrv_fetch_array($fetch_stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($fetch_stmt);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $form_data['first_name'] = trim($_POST['first_name'] ?? '');
    $form_data['middle_name'] = trim($_POST['middle_name'] ?? '');
    $form_data['last_name'] = trim($_POST['last_name'] ?? '');
    $form_data['suffix_name'] = trim($_POST['suffix_name'] ?? '');
    $form_data['course'] = trim($_POST['course'] ?? '');
    $form_data['year_level'] = (int)($_POST['year_level'] ?? 0);
    $form_data['section'] = trim($_POST['section'] ?? '');
    $form_data['phone_number'] = trim($_POST['phone_number'] ?? '');
    $new_password = $_POST['password'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($form_data['first_name'])) $errors[] = "First name is required";
    if (empty($form_data['last_name'])) $errors[] = "Last name is required";
    if (empty($form_data['course'])) $errors[] = "Course is required";
    if ($form_data['year_level'] < 1 || $form_data['year_level'] > 4) $errors[] = "Valid year level required";
    
    if (empty($errors)) {
        
        sqlsrv_begin_transaction($conn);
        
        try {
            // Update student profile
            $update_sql = "UPDATE student_profiles 
                           SET first_name = ?, middle_name = ?, last_name = ?, suffix_name = ?,
                               course = ?, year_level = ?, section = ?, phone_number = ?
                           WHERE id = ?";
            
            $update_params = array(
                $form_data['first_name'], $form_data['middle_name'], $form_data['last_name'],
                $form_data['suffix_name'], $form_data['course'], $form_data['year_level'],
                $form_data['section'], $form_data['phone_number'], $student_id
            );
            
            $update_stmt = sqlsrv_query($conn, $update_sql, $update_params);
            
            if (!$update_stmt) {
                throw new Exception("Failed to update profile");
            }
            
            // Update password if provided
            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    throw new Exception("Password must be at least 6 characters");
                }
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $pass_sql = "UPDATE users SET password_hash = ? WHERE id = ?";
                $pass_stmt = sqlsrv_query($conn, $pass_sql, array($password_hash, $current['user_id']));
                if (!$pass_stmt) {
                    throw new Exception("Failed to update password");
                }
                sqlsrv_free_stmt($pass_stmt);
            }
            
            sqlsrv_commit($conn);
            $success = "Student information updated successfully!";
            
            // Refresh current data
            $current['first_name'] = $form_data['first_name'];
            $current['middle_name'] = $form_data['middle_name'];
            $current['last_name'] = $form_data['last_name'];
            $current['suffix_name'] = $form_data['suffix_name'];
            $current['course'] = $form_data['course'];
            $current['year_level'] = $form_data['year_level'];
            $current['section'] = $form_data['section'];
            $current['phone_number'] = $form_data['phone_number'];
            
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            $error = $e->getMessage();
        }
        
        if (isset($update_stmt)) sqlsrv_free_stmt($update_stmt);
        
    } else {
        $error = implode("<br>", $errors);
    }
} else {
    // Pre-populate form with current data
    $form_data = $current;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student - Attendance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; }
        .card-header h2 { font-size: 20px; }
        .card-body { padding: 20px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-group { flex: 1; }
        label { display: block; margin-bottom: 5px; color: #333; font-weight: 500; font-size: 14px; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: #667eea; }
        button { background: #007bff; color: white; border: none; padding: 12px 30px; border-radius: 5px; font-size: 16px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        .info-note { background: #e7f3ff; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; color: #004085; }
        .back-btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Edit Student Information</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="info-note">
                    <strong>Student ID:</strong> <?php echo htmlspecialchars($current['account_code'] ?? 'N/A'); ?><br>
                    <strong>Username:</strong> <?php echo htmlspecialchars($current['user_name']); ?>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" name="middle_name" value="<?php echo htmlspecialchars($form_data['middle_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Suffix</label>
                            <input type="text" name="suffix_name" value="<?php echo htmlspecialchars($form_data['suffix_name'] ?? ''); ?>" placeholder="Jr., Sr., III">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Course *</label>
                            <input type="text" name="course" value="<?php echo htmlspecialchars($form_data['course'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Year Level *</label>
                            <select name="year_level" required>
                                <option value="1" <?php echo (($form_data['year_level'] ?? 0) == 1) ? 'selected' : ''; ?>>1st Year</option>
                                <option value="2" <?php echo (($form_data['year_level'] ?? 0) == 2) ? 'selected' : ''; ?>>2nd Year</option>
                                <option value="3" <?php echo (($form_data['year_level'] ?? 0) == 3) ? 'selected' : ''; ?>>3rd Year</option>
                                <option value="4" <?php echo (($form_data['year_level'] ?? 0) == 4) ? 'selected' : ''; ?>>4th Year</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Section</label>
                            <input type="text" name="section" value="<?php echo htmlspecialchars($form_data['section'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($form_data['phone_number'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>New Password (leave blank to keep current)</label>
                            <input type="password" name="password" placeholder="Enter new password">
                        </div>
                    </div>
                    
                    <button type="submit">Update Student</button>
                </form>
                
                <a href="AddStudent.php" class="back-btn">← Back to Student List</a>
            </div>
        </div>
    </div>
</body>
</html>