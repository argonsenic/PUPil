<?php
/**
 * Get Enrollment Handler
 * Displays enrollment records with filtering options
 */

require_once '../../config/database.php';

/** @var resource $conn */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('sqlsrv_query')) {
    die('SQL Server extension is not enabled.');
}

if (!defined('SQLSRV_FETCH_ASSOC')) {
    define('SQLSRV_FETCH_ASSOC', 2);
}

function sqlsrv_fetch_assoc($stmt) {
    if (!function_exists('sqlsrv_fetch_array')) {
        return false;
    }
    return call_user_func('sqlsrv_fetch_array', $stmt, SQLSRV_FETCH_ASSOC);
}

if (!function_exists('sqlsrv_has_rows')) {
    function sqlsrv_has_rows($stmt) {
        if (function_exists('sqlsrv_num_rows')) {
            $count = sqlsrv_num_rows($stmt);
            return $count !== false && $count > 0;
        }
        return false;
    }
}

if (!function_exists('sqlsrv_free_stmt')) {
    function sqlsrv_free_stmt($stmt) {
        return true;
    }
}

function sqlsrv_die_errors() {
    $message = 'Database query failed.';
    if (function_exists('sqlsrv_errors')) {
        $message = print_r(sqlsrv_errors(), true);
    }
    die($message);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Authentication/Login.php");
    exit();
}

// Get filter parameters
$student_filter = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$subject_filter = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$sql = "SELECT 
            er.id, er.enrollment_date, er.enrollment_code,
            sp.id AS student_id, sp.first_name AS student_first, sp.last_name AS student_last, 
            sp.course, sp.year_level, sp.section,
            s.id AS subject_id, s.subject_code, s.subject_name,
            ip.first_name AS instructor_first, ip.last_name AS instructor_last
        FROM enrollment_records er
        INNER JOIN student_profiles sp ON er.student_id = sp.id
        INNER JOIN subjects s ON er.subject_id = s.id
        LEFT JOIN instructor_profiles ip ON s.instructor_id = ip.id
        WHERE 1=1";

$params = [];

if ($student_filter > 0) {
    $sql .= " AND er.student_id = ?";
    $params[] = $student_filter;
}

if ($subject_filter > 0) {
    $sql .= " AND er.subject_id = ?";
    $params[] = $subject_filter;
}

if (!empty($date_from)) {
    $sql .= " AND CAST(er.enrollment_date AS DATE) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND CAST(er.enrollment_date AS DATE) <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY er.enrollment_date DESC";

$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    sqlsrv_die_errors();
}

// 2. Get student filter dropdown data
$students_sql = "SELECT id, first_name, last_name FROM dbo.student_profiles ORDER BY last_name";
$students_stmt = sqlsrv_query($conn, $students_sql);
if ($students_stmt === false) {
    sqlsrv_die_errors();
}

// 3. Get subject filter dropdown data
$subjects_sql = "SELECT id, subject_code, subject_name FROM dbo.subjects ORDER BY subject_code";
$subjects_stmt = sqlsrv_query($conn, $subjects_sql);
if ($subjects_stmt === false) {
    sqlsrv_die_errors();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Records - Attendance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; }
        .card-header h2 { font-size: 20px; }
        .card-body { padding: 20px; }
        .filters { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-group { flex: 1; min-width: 150px; }
        label { display: block; margin-bottom: 5px; color: #333; font-weight: 500; font-size: 12px; }
        select, input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        button { padding: 8px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 22px; }
        button:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        .clear-btn { background: #6c757d; margin-left: 10px; }
        .clear-btn:hover { background: #5a6268; }
        .back-btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Enrollment Records</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="filters">
                        <div class="filter-group">
                            <label>Student</label>
                            <select name="student_id">
                                <option value="">All Students</option>
                                <?php while ($s = sqlsrv_fetch_assoc($students_stmt)): ?>
                                    <option value="<?php echo $s['id']; ?>" <?php echo ($student_filter == $s['id']) ? 'selected' : ''; ?>>
                                        <?php echo $s['last_name'] . ', ' . $s['first_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Subject</label>
                            <select name="subject_id">
                                <option value="">All Subjects</option>
                                <?php while ($subj = sqlsrv_fetch_assoc($subjects_stmt)): ?>
                                    <option value="<?php echo $subj['id']; ?>" <?php echo ($subject_filter == $subj['id']) ? 'selected' : ''; ?>>
                                        <?php echo $subj['subject_code'] . ' - ' . $subj['subject_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Date From</label>
                            <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label>Date To</label>
                            <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit">Filter</button>
                            <a href="GetEnrollment.php" class="clear-btn" style="padding: 8px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">Clear</a>
                        </div>
                    </div>
                </form>
                
                <?php if (sqlsrv_has_rows($stmt)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Enrollment Code</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Year/Section</th>
                                <th>Subject</th>
                                <th>Instructor</th>
                                <th>Enrollment Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = sqlsrv_fetch_assoc($stmt)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['enrollment_code']); ?></td>
                                    <td><?php echo $row['student_last'] . ', ' . $row['student_first']; ?></td>
                                    <td><?php echo htmlspecialchars($row['course']); ?></td>
                                    <td><?php echo $row['year_level'] . ' - ' . ($row['section'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['subject_code']); ?> - <?php echo htmlspecialchars($row['subject_name']); ?></td>
                                    <td><?php echo ($row['instructor_first']) ? $row['instructor_last'] . ', ' . $row['instructor_first'] : 'Not Assigned'; ?></td>
                                    <td><?php echo $row['enrollment_date'] ? $row['enrollment_date']->format('Y-m-d H:i:s') : 'N/A'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No enrollment records found.</p>
                <?php endif; ?>
                
                <a href="EnrollStudent.php" class="back-btn">+ New Enrollment</a>
            </div>
        </div>
    </div>
</body>
</html>

<?php
if (isset($stmt)) sqlsrv_free_stmt($stmt);
if (isset($students_stmt)) sqlsrv_free_stmt($students_stmt);
if (isset($subjects_stmt)) sqlsrv_free_stmt($subjects_stmt);
?>