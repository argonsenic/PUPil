<?php
/**
 * Update Subject Handler (Admin only)
 * Allows admin to edit subject information
 */

require_once '../../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Authentication/Login.php");
    exit();
}

$subject_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0);

if ($subject_id <= 0) {
    header("Location: AddSubject.php");
    exit();
}

$error = '';
$success = '';

// Get instructors for dropdown
$instructors_sql = "SELECT id, first_name, last_name FROM instructor_profiles ORDER BY last_name";
$instructors_stmt = sqlsrv_query($conn, $instructors_sql);

// Fetch current subject data
$fetch_sql = "SELECT subject_code, subject_name, schedules, instructor_id FROM subjects WHERE id = ?";
$fetch_stmt = sqlsrv_query($conn, $fetch_sql, array($subject_id));

if (!$fetch_stmt || !sqlsrv_has_rows($fetch_stmt)) {
    die("Subject not found");
}

$current = sqlsrv_fetch_array($fetch_stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($fetch_stmt);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $subject_code = strtoupper(trim($_POST['subject_code'] ?? ''));
    $subject_name = trim($_POST['subject_name'] ?? '');
    $schedules = trim($_POST['schedules'] ?? '');
    $instructor_id = !empty($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : null;
    
    // Validation
    $errors = [];
    
    if (empty($subject_code)) $errors[] = "Subject code is required";
    if (empty($subject_name)) $errors[] = "Subject name is required";
    
    // Check if subject code exists (excluding current)
    if (empty($errors) && $subject_code !== $current['subject_code']) {
        $check_sql = "SELECT id FROM subjects WHERE subject_code = ?";
        $check_stmt = sqlsrv_query($conn, $check_sql, array($subject_code));
        if ($check_stmt && sqlsrv_has_rows($check_stmt)) {
            $errors[] = "Subject code already exists";
        }
        sqlsrv_free_stmt($check_stmt);
    }
    
    if (empty($errors)) {
        
        $update_sql = "UPDATE subjects 
                       SET subject_code = ?, subject_name = ?, schedules = ?, instructor_id = ?
                       WHERE id = ?";
        $update_params = array($subject_code, $subject_name, $schedules, $instructor_id, $subject_id);
        
        $update_stmt = sqlsrv_query($conn, $update_sql, $update_params);
        
        if ($update_stmt) {
            $success = "Subject updated successfully!";
            $current = ['subject_code' => $subject_code, 'subject_name' => $subject_name, 
                       'schedules' => $schedules, 'instructor_id' => $instructor_id];
        } else {
            $error = "Failed to update subject";
        }
        
        if (isset($update_stmt)) sqlsrv_free_stmt($update_stmt);
        
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
    <title>Update Subject - Attendance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; }
        .card-header h2 { font-size: 20px; }
        .card-body { padding: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #333; font-weight: 500; font-size: 14px; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #667eea; }
        button { background: #007bff; color: white; border: none; padding: 12px 30px; border-radius: 5px; font-size: 16px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        .back-btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Edit Subject</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                    
                    <div class="form-group">
                        <label>Subject Code *</label>
                        <input type="text" name="subject_code" value="<?php echo htmlspecialchars($current['subject_code']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Subject Name *</label>
                        <input type="text" name="subject_name" value="<?php echo htmlspecialchars($current['subject_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Schedule</label>
                        <textarea name="schedules" rows="3"><?php echo htmlspecialchars($current['schedules'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Assign Instructor</label>
                        <select name="instructor_id">
                            <option value="">-- Select Instructor --</option>
                            <?php while ($instructor = sqlsrv_fetch_array($instructors_stmt, SQLSRV_FETCH_ASSOC)): ?>
                                <option value="<?php echo $instructor['id']; ?>" <?php echo ($current['instructor_id'] == $instructor['id']) ? 'selected' : ''; ?>>
                                    <?php echo $instructor['last_name'] . ', ' . $instructor['first_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <button type="submit">Update Subject</button>
                </form>
                
                <a href="AddSubject.php" class="back-btn">← Back to Subject List</a>
            </div>
        </div>
    </div>
</body>
</html>

<?php
if (isset($instructors_stmt)) sqlsrv_free_stmt($instructors_stmt);
?>