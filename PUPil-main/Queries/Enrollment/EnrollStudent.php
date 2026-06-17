<?php
/**
 * Enroll Student Handler (Admin only)
 * Allows admin to enroll students in subjects
 */

require_once '../../config/database.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Authentication/Login.php");
    exit();
}

$error = '';
$success = '';

// Get all students
$students_sql = "SELECT id, first_name, last_name, course, year_level 
                 FROM student_profiles 
                 ORDER BY last_name";
$students = [];
if (isset($pdo) && $pdo instanceof PDO) {
    $students_stmt = $pdo->query($students_sql);
    if ($students_stmt instanceof PDOStatement) {
        $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} else {
    $error = 'Database connection not initialized.';
}

// Get all subjects
$subjects_sql = "SELECT id, subject_code, subject_name 
                 FROM subjects 
                 ORDER BY subject_code";
$subjects = [];
if (isset($pdo) && $pdo instanceof PDO) {
    $subjects_stmt = $pdo->query($subjects_sql);
    if ($subjects_stmt instanceof PDOStatement) {
        $subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($pdo) || !$pdo instanceof PDO) {
        $error = 'Database connection not initialized.';
    } else {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        
        if ($student_id <= 0) {
            $error = "Please select a student";
        } elseif ($subject_id <= 0) {
            $error = "Please select a subject";
        } else {
            
            try {
                // 1. Check if already enrolled using PDO
                $check_sql = "SELECT id FROM dbo.enrollment_records WHERE student_id = ? AND subject_id = ?";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([$student_id, $subject_id]);
                
                if ($check_stmt->fetch()) {
                    $error = "Student is already enrolled in this subject";
                } else {
                    // Generate enrollment code
                    $enrollment_code = 'ENR_' . date('Ymd') . '_' . rand(1000, 9999);
                    
                    // 2. Insert enrollment record securely
                    $insert_sql = "INSERT INTO dbo.enrollment_records (student_id, subject_id, enrollment_date, enrollment_code) 
                                   VALUES (?, ?, GETDATE(), ?)";
                    $insert_stmt = $pdo->prepare($insert_sql);
                    $success_insert = $insert_stmt->execute([$student_id, $subject_id, $enrollment_code]);
                    
                    if ($success_insert) {
                        $success = "Student enrolled successfully!";
                    } else {
                        $error = "Failed to enroll student";
                    }
                }
            } catch (Exception $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Get recent enrollments
$enrollments_sql = "SELECT 
                        er.id, er.enrollment_date, er.enrollment_code,
                        sp.first_name AS student_first, sp.last_name AS student_last, sp.course,
                        s.subject_code, s.subject_name
                    FROM enrollment_records er
                    INNER JOIN student_profiles sp ON er.student_id = sp.id
                    INNER JOIN subjects s ON er.subject_id = s.id
                    ORDER BY er.enrollment_date DESC";
$enrollments = [];
if (isset($pdo) && $pdo instanceof PDO) {
    $enrollments_stmt = $pdo->query($enrollments_sql);
    if ($enrollments_stmt instanceof PDOStatement) {
        $enrollments = $enrollments_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Student - Attendance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; }
        .card-header h2 { font-size: 20px; }
        .card-body { padding: 20px; }
        .form-row { display: flex; gap: 20px; }
        .form-group { flex: 1; }
        label { display: block; margin-bottom: 5px; color: #333; font-weight: 500; font-size: 14px; }
        select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        select:focus { outline: none; border-color: #667eea; }
        button { background: #28a745; color: white; border: none; padding: 12px 30px; border-radius: 5px; font-size: 16px; cursor: pointer; margin-top: 10px; }
        button:hover { background: #218838; }
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        .delete-btn { background: #dc3545; color: white; padding: 5px 10px; border-radius: 3px; text-decoration: none; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Enroll Student in Subject</h2>
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
                            <label>Select Student</label>
                            <select name="student_id" required>
                                <option value="">-- Select Student --</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' - ' . $student['course']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Select Subject</label>
                            <select name="subject_id" required>
                                <option value="">-- Select Subject --</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>">
                                        <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit">Enroll Student</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Enrollment Records</h2>
            </div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Enrollment Code</th>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Subject</th>
                            <th>Enrollment Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollments as $enroll): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($enroll['enrollment_code']); ?></td>
                                <td><?php echo htmlspecialchars($enroll['student_last'] . ', ' . $enroll['student_first']); ?></td>
                                <td><?php echo htmlspecialchars($enroll['course']); ?></td>
                                <td><?php echo htmlspecialchars($enroll['subject_code']) . ' - ' . htmlspecialchars($enroll['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($enroll['enrollment_date'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="DeleteEnrollment.php?id=<?php echo $enroll['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to remove this enrollment?')">Remove</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

<?php
unset($students_stmt, $subjects_stmt, $enrollments_stmt);
?>