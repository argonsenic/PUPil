<?php
/**
 * Get Student Details Handler
 * Displays detailed information for a specific student
 */

require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Authentication/Login.php");
    exit();
}

// Get student ID from URL
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id <= 0) {
    header("Location: AddStudent.php");
    exit();
}

// Fetch student details
$sql = "SELECT 
            sp.id, sp.first_name, sp.middle_name, sp.last_name, sp.suffix_name,
            sp.course, sp.year_level, sp.section, sp.phone_number,
            u.id AS user_id, u.user_name, u.account_code, u.role_id,
            r.role
        FROM student_profiles sp
        INNER JOIN users u ON sp.account_id = u.id
        INNER JOIN roles r ON u.role_id = r.id
        WHERE sp.id = ?";

$stmt = sqlsrv_query($conn, $sql, array($student_id));

if (!$stmt || !sqlsrv_has_rows($stmt)) {
    die("Student not found");
}

$student = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt);

// Get enrollment records for this student
$enroll_sql = "SELECT 
                    er.id, er.enrollment_date, er.enrollment_code,
                    s.id AS subject_id, s.subject_code, s.subject_name,
                    ip.first_name AS instructor_first, ip.last_name AS instructor_last
                FROM enrollment_records er
                INNER JOIN subjects s ON er.subject_id = s.id
                LEFT JOIN instructor_profiles ip ON s.instructor_id = ip.id
                WHERE er.student_id = ?
                ORDER BY er.enrollment_date DESC";

$enroll_stmt = sqlsrv_query($conn, $enroll_sql, array($student_id));

// Get attendance records
$attendance_sql = "SELECT 
                        ar.id, ar.record_type, ar.status, ar.create_at,
                        s.subject_code, s.subject_name
                    FROM attendance_records ar
                    INNER JOIN subjects s ON ar.subject_id = s.id
                    WHERE ar.student_id = ?
                    ORDER BY ar.create_at DESC";

$attendance_stmt = sqlsrv_query($conn, $attendance_sql, array($student_id));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details - Attendance System</title>
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
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 12px; }
        .badge-present { background: #d4edda; color: #155724; }
        .badge-late { background: #fff3cd; color: #856404; }
        .badge-absent { background: #f8d7da; color: #721c24; }
        .back-btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; }
        .back-btn:hover { background: #5a6268; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Student Profile</h2>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Student ID / Account Code</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['account_code']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Username</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['user_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value">
                            <?php 
                            $full_name = $student['first_name'] . ' ';
                            if (!empty($student['middle_name'])) $full_name .= $student['middle_name'] . ' ';
                            $full_name .= $student['last_name'];
                            if (!empty($student['suffix_name'])) $full_name .= ' ' . $student['suffix_name'];
                            echo htmlspecialchars($full_name);
                            ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Course</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['course']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Year Level</div>
                        <div class="info-value"><?php echo $student['year_level']; ?> Year</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Section</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['section'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['phone_number'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Role</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['role']); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Enrolled Subjects</h2>
            </div>
            <div class="card-body">
                <?php if (sqlsrv_has_rows($enroll_stmt)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Instructor</th>
                                <th>Enrollment Date</th>
                                <th>Enrollment Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($enroll = sqlsrv_fetch_array($enroll_stmt, SQLSRV_FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($enroll['subject_code']); ?></td>
                                    <td><?php echo htmlspecialchars($enroll['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($enroll['instructor_first'] . ' ' . $enroll['instructor_last']); ?></td>
                                    <td><?php echo $enroll['enrollment_date'] ? $enroll['enrollment_date']->format('Y-m-d') : 'N/A'; ?></td>
                                    <td><?php echo htmlspecialchars($enroll['enrollment_code'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No enrolled subjects found.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Attendance History</h2>
            </div>
            <div class="card-body">
                <?php if (sqlsrv_has_rows($attendance_stmt)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Subject</th>
                                <th>Record Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($att = sqlsrv_fetch_array($attendance_stmt, SQLSRV_FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo $att['create_at'] ? $att['create_at']->format('Y-m-d H:i:s') : 'N/A'; ?></td>
                                    <td><?php echo htmlspecialchars($att['subject_code']); ?></td>
                                    <td><?php echo htmlspecialchars($att['record_type']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($att['status']); ?>">
                                            <?php echo htmlspecialchars($att['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No attendance records found.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <a href="AddStudent.php" class="back-btn">← Back to Student List</a>
    </div>
</body>
</html>

<?php
if (isset($enroll_stmt)) sqlsrv_free_stmt($enroll_stmt);
if (isset($attendance_stmt)) sqlsrv_free_stmt($attendance_stmt);
?>