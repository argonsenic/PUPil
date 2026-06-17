<?php
/**
 * Get Subject Details Handler
 * Displays detailed information for a specific subject
 */

require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Authentication/Login.php");
    exit();
}

$subject_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($subject_id <= 0) {
    header("Location: AddSubject.php");
    exit();
}

// Fetch subject details
$sql = "SELECT 
            s.id, s.subject_code, s.subject_name, s.schedules,
            ip.id AS instructor_id, ip.first_name AS instructor_first, 
            ip.last_name AS instructor_last, ip.phone_number AS instructor_phone
        FROM subjects s
        LEFT JOIN instructor_profiles ip ON s.instructor_id = ip.id
        WHERE s.id = ?";

$stmt = sqlsrv_query($conn, $sql, array($subject_id));

if (!$stmt || !sqlsrv_has_rows($stmt)) {
    die("Subject not found");
}

$subject = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt);

// Get enrolled students
$students_sql = "SELECT 
                    sp.id, sp.first_name, sp.last_name, sp.course, sp.year_level,
                    er.enrollment_date, er.enrollment_code
                FROM enrollment_records er
                INNER JOIN student_profiles sp ON er.student_id = sp.id
                WHERE er.subject_id = ?
                ORDER BY sp.last_name";
$students_stmt = sqlsrv_query($conn, $students_sql, array($subject_id));

// Get attendance summary
$attendance_sql = "SELECT 
                        COUNT(*) AS total_records,
                        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) AS late_count,
                        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS absent_count
                    FROM attendance_records
                    WHERE subject_id = ?";
$attendance_stmt = sqlsrv_query($conn, $attendance_sql, array($subject_id));
$attendance_summary = sqlsrv_fetch_array($attendance_stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($attendance_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Details - Attendance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; }
        .card-header h2 { font-size: 20px; }
        .card-body { padding: 20px; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px; }
        .info-item { border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .info-label { font-weight: 600; color: #666; font-size: 12px; text-transform: uppercase; margin-bottom: 5px; }
        .info-value { font-size: 16px; color: #333; }
        .stats { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat-box { flex: 1; background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; }
        .stat-number { font-size: 28px; font-weight: bold; }
        .stat-label { color: #666; font-size: 12px; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        .back-btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Subject Information</h2>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Subject Code</div>
                        <div class="info-value"><?php echo htmlspecialchars($subject['subject_code']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Subject Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($subject['subject_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Schedule</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($subject['schedules'] ?? 'Not set')); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Instructor</div>
                        <div class="info-value">
                            <?php if ($subject['instructor_id']): ?>
                                <?php echo htmlspecialchars($subject['instructor_last'] . ', ' . $subject['instructor_first']); ?><br>
                                <small>Phone: <?php echo htmlspecialchars($subject['instructor_phone'] ?? 'N/A'); ?></small>
                            <?php else: ?>
                                Not Assigned
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Attendance Statistics</h2>
            </div>
            <div class="card-body">
                <div class="stats">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $attendance_summary['total_records'] ?? 0; ?></div>
                        <div class="stat-label">Total Records</div>
                    </div>
                    <div class="stat-box" style="background: #d4edda;">
                        <div class="stat-number" style="color: #155724;"><?php echo $attendance_summary['present_count'] ?? 0; ?></div>
                        <div class="stat-label">Present</div>
                    </div>
                    <div class="stat-box" style="background: #fff3cd;">
                        <div class="stat-number" style="color: #856404;"><?php echo $attendance_summary['late_count'] ?? 0; ?></div>
                        <div class="stat-label">Late</div>
                    </div>
                    <div class="stat-box" style="background: #f8d7da;">
                        <div class="stat-number" style="color: #721c24;"><?php echo $attendance_summary['absent_count'] ?? 0; ?></div>
                        <div class="stat-label">Absent</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Enrolled Students</h2>
            </div>
            <div class="card-body">
                <?php if (sqlsrv_has_rows($students_stmt)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th>Year Level</th>
                                <th>Enrollment Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($student = sqlsrv_fetch_array($students_stmt, SQLSRV_FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo $student['last_name'] . ', ' . $student['first_name']; ?></td>
                                    <td><?php echo htmlspecialchars($student['course']); ?></td>
                                    <td><?php echo $student['year_level']; ?> Year</td>
                                    <td><?php echo $student['enrollment_date'] ? $student['enrollment_date']->format('Y-m-d') : 'N/A'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No students enrolled in this subject.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <a href="AddSubject.php" class="back-btn">← Back to Subject List</a>
    </div>
</body>
</html>

<?php
if (isset($students_stmt)) sqlsrv_free_stmt($students_stmt);
?>