<?php
/**
 * Add Subject Handler (Admin only)
 * Allows admin to add new subjects/courses
 */

require_once '../../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Authentication/Login.php");
    exit();
}

$error = '';
$success = '';
$form_data = [];

// Get instructors for dropdown
$instructors_sql = "SELECT ip.id, ip.first_name, ip.last_name 
                    FROM instructor_profiles ip
                    ORDER BY ip.last_name";
$instructors_stmt = sqlsrv_query($conn, $instructors_sql);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $form_data['subject_code'] = strtoupper(trim($_POST['subject_code'] ?? ''));
    $form_data['subject_name'] = trim($_POST['subject_name'] ?? '');
    $form_data['schedules'] = trim($_POST['schedules'] ?? '');
    $form_data['instructor_id'] = isset($_POST['instructor_id']) && !empty($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : null;
    
    // Validation
    $errors = [];
    
    if (empty($form_data['subject_code'])) {
        $errors[] = "Subject code is required";
    }
    
    if (empty($form_data['subject_name'])) {
        $errors[] = "Subject name is required";
    }
    
    // Check if subject code exists
    if (empty($errors)) {
        $check_sql = "SELECT id FROM subjects WHERE subject_code = ?";
        $check_stmt = sqlsrv_query($conn, $check_sql, array($form_data['subject_code']));
        if ($check_stmt && sqlsrv_has_rows($check_stmt)) {
            $errors[] = "Subject code already exists";
        }
        sqlsrv_free_stmt($check_stmt);
    }
    
    if (empty($errors)) {
        
        // Insert subject
        $insert_sql = "INSERT INTO subjects (subject_code, subject_name, schedules, instructor_id) 
                       VALUES (?, ?, ?, ?)";
        $insert_params = array(
            $form_data['subject_code'], 
            $form_data['subject_name'], 
            $form_data['schedules'], 
            $form_data['instructor_id']
        );
        
        $insert_stmt = sqlsrv_query($conn, $insert_sql, $insert_params);
        
        if ($insert_stmt) {
            $success = "Subject added successfully!";
            $form_data = [];
        } else {
            $error = "Failed to add subject";
        }
        
        if (isset($insert_stmt)) sqlsrv_free_stmt($insert_stmt);
        
    } else {
        $error = implode("<br>", $errors);
    }
}

// Get existing subjects
$subjects_sql = "SELECT s.id, s.subject_code, s.subject_name, s.schedules,
                        ip.first_name, ip.last_name
                 FROM subjects s
                 LEFT JOIN instructor_profiles ip ON s.instructor_id = ip.id
                 ORDER BY s.subject_code";
$subjects_stmt = sqlsrv_query($conn, $subjects_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Subject - Attendance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; }
        .card-header h2 { font-size: 20px; }
        .card-body { padding: 20px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-group { flex: 1; }
        label { display: block; margin-bottom: 5px; color: #333; font-weight: 500; font-size: 14px; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #667eea; }
        button { background: #28a745; color: white; border: none; padding: 12px 30px; border-radius: 5px; font-size: 16px; cursor: pointer; }
        button:hover { background: #218838; }
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        .actions a { margin-right: 10px; text-decoration: none; padding: 5px 10px; border-radius: 3px; font-size: 12px; color: white; }
        .edit-btn { background: #ffc107; color: #333; }
        .view-btn { background: #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Add New Subject</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Subject Code *</label>
                            <input type="text" name="subject_code" value="<?php echo htmlspecialchars($form_data['subject_code'] ?? ''); ?>" required placeholder="e.g., CS101">
                        </div>
                        <div class="form-group">
                            <label>Subject Name *</label>
                            <input type="text" name="subject_name" value="<?php echo htmlspecialchars($form_data['subject_name'] ?? ''); ?>" required placeholder="e.g., Introduction to Programming">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Schedule</label>
                            <textarea name="schedules" rows="3" placeholder="e.g., Mon/Wed 10:00 AM - 12:00 PM, Room 101"><?php echo htmlspecialchars($form_data['schedules'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Assign Instructor</label>
                            <select name="instructor_id">
                                <option value="">-- Select Instructor (Optional) --</option>
                                <?php while ($instructor = sqlsrv_fetch_array($instructors_stmt, SQLSRV_FETCH_ASSOC)): ?>
                                    <option value="<?php echo $instructor['id']; ?>" <?php echo (($form_data['instructor_id'] ?? '') == $instructor['id']) ? 'selected' : ''; ?>>
                                        <?php echo $instructor['last_name'] . ', ' . $instructor['first_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit">Add Subject</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Subject List</h2>
            </div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Schedule</th>
                            <th>Instructor</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($subject = sqlsrv_fetch_array($subjects_stmt, SQLSRV_FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($subject['schedules'] ?? 'N/A'); ?></td>
                                <td><?php echo isset($subject['first_name']) ? $subject['last_name'] . ', ' . $subject['first_name'] : 'Not Assigned'; ?></td>
                                <td class="actions">
                                    <a href="GetSubject.php?id=<?php echo $subject['id']; ?>" class="view-btn">View</a>
                                    <a href="UpdateSubject.php?id=<?php echo $subject['id']; ?>" class="edit-btn">Edit</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

<?php
if (isset($instructors_stmt)) sqlsrv_free_stmt($instructors_stmt);
if (isset($subjects_stmt)) sqlsrv_free_stmt($subjects_stmt);
?>