<?php
/**
 * Log Attendance Handler
 * Allows instructors to record student attendance
 */

require_once '../../config/database.php';
// Ensure session started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure PDO is available
if (!isset($pdo) || $pdo === null) {
    die("Database connection missing or not configured (\$pdo). Check config/database.php");
}

// Check if user is logged in and has instructor or admin role
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Authentication/Login.php");
    exit();
}

if ($_SESSION['role'] !== 'instructor' && $_SESSION['role'] !== 'admin') {
    die("Access denied. Only instructors and administrators can log attendance.");
}

$error = '';
$success = '';
$today_date = date('Y-m-d');

// Get instructor profile ID if instructor
$instructor_profile_id = null;
if ($_SESSION['role'] === 'instructor' && isset($_SESSION['profile']['id'])) {
    $instructor_profile_id = $_SESSION['profile']['id'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $student_id = (int)($_POST['student_id'] ?? 0);
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    $record_type = $_POST['record_type'] ?? '';
    $status = $_POST['status'] ?? '';
    
    // Validate inputs
    if ($student_id <= 0) {
        $error = "Please select a student";
    } elseif ($subject_id <= 0) {
        $error = "Please select a subject";
    } elseif (!in_array($record_type, ['Log-in', 'Log-out'])) {
        $error = "Invalid record type";
    } elseif (!in_array($status, ['Present', 'Late', 'Absent'])) {
        $error = "Invalid status";
    } else {
        
        // Check if already logged today
               try {
            // 1. Check if attendance already exists today using PDO
            $check_sql = "SELECT id FROM dbo.attendance_records 
                          WHERE student_id = ? AND subject_id = ? 
                          AND CAST(create_at AS DATE) = CAST(GETDATE() AS DATE)
                          AND record_type = ?";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([$student_id, $subject_id, $record_type]);
            
            if ($check_stmt->fetch()) {
                $error = ucfirst($record_type) . " already recorded for today";
            } else {
                // 2. Insert new attendance record securely
                $insert_sql = "INSERT INTO dbo.attendance_records 
                               (student_id, subject_id, record_type, status, create_at) 
                               VALUES (?, ?, ?, ?, GETDATE())";
                $insert_stmt = $pdo->prepare($insert_sql);
                $success_insert = $insert_stmt->execute([$student_id, $subject_id, $record_type, $status]);
                
                if ($success_insert) {
                    $success = "Attendance logged successfully!";
                } else {
                    $error = "Error logging attendance";
                }
            }
        } catch (Exception $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

try {
    // 3. Get subjects dropdown list based on user role
    if ($_SESSION['role'] === 'instructor') {
        $subjects_sql = "SELECT id, subject_code, subject_name FROM dbo.subjects WHERE instructor_id = ? ORDER BY subject_code";
        $subjects_stmt = $pdo->prepare($subjects_sql);
        $subjects_stmt->execute([$instructor_profile_id]);
    } else {
        $subjects_sql = "SELECT id, subject_code, subject_name FROM dbo.subjects ORDER BY subject_code";
        $subjects_stmt = $pdo->query($subjects_sql);
    }
    // Fetch subjects into a clean array for your frontend HTML loop
    $subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Get students list based on selected subject filter
    $selected_subject = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : (isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0);

    if ($selected_subject > 0) {
        $students_sql = "SELECT DISTINCT sp.id, sp.first_name, sp.last_name, sp.course 
                         FROM dbo.student_profiles sp
                         INNER JOIN dbo.enrollment_records er ON sp.id = er.student_id
                         WHERE er.subject_id = ?
                         ORDER BY sp.last_name";
        $students_stmt = $pdo->prepare($students_sql);
        $students_stmt->execute([$selected_subject]);
    } else {
        $students_sql = "SELECT id, first_name, last_name, course FROM dbo.student_profiles ORDER BY last_name";
        $students_stmt = $pdo->query($students_sql);
    }
    // Fetch students into a clean array for your frontend HTML loop
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Failed to fetch page selection lists: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Attendance - Attendance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 30px; }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .form-container { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        select:focus { outline: none; border-color: #667eea; }
        .form-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .form-row .form-group { flex: 1; margin-bottom: 0; }
        button { background: #28a745; color: white; border: none; padding: 12px 30px; border-radius: 5px; font-size: 16px; cursor: pointer; }
        button:hover { background: #218838; }
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        .back-link { display: inline-block; margin-top: 20px; color: #667eea; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Log Student Attendance</h1>
            <p>Record student check-in and check-out</p>
        </div>
        
        <div class="form-container">
            <?php if (!empty($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Select Subject</label>
                    <select name="subject_id" id="subject_id" required onchange="this.form.submit()">
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>" <?php echo ($selected_subject == $subject['id']) ? 'selected' : ''; ?>>
                                <?php echo $subject['subject_code'] . ' - ' . $subject['subject_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Select Student</label>
                    <select name="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>">
                                <?php echo $student['last_name'] . ', ' . $student['first_name'] . ' - ' . $student['course']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Record Type</label>
                        <select name="record_type" required>
                            <option value="">-- Select --</option>
                            <option value="Log-in">Log-in (Time In)</option>
                            <option value="Log-out">Log-out (Time Out)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="">-- Select --</option>
                            <option value="Present">Present</option>
                            <option value="Late">Late</option>
                            <option value="Absent">Absent</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit">Log Attendance</button>
            </form>
            
            <a href="GetAttendance.php" class="back-link">← View Attendance Records</a>
        </div>
    </div>
</body>
</html>

<?php
// Clean up PDO statements
if (isset($subjects_stmt)) {
    $subjects_stmt = null;
}
if (isset($students_stmt)) {
    $students_stmt = null;
}
?>