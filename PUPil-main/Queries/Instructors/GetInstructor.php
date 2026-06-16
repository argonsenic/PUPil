<?php
/**
 * Get Instructor Details Handler
 * Displays detailed information for a specific instructor
 */

require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Authentication/Login.php");
    exit();
}

$instructor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($instructor_id <= 0) {
    header("Location: AddInstructor.php");
    exit();
}

// Fetch instructor details
$sql = "SELECT 
            ip.id, ip.first_name, ip.middle_name, ip.last_name, ip.suffix_name, ip.phone_number,
            u.id AS user_id, u.user_name, u.account_code
        FROM instructor_profiles ip
        INNER JOIN users u ON ip.account_id = u.id
        WHERE ip.id = ?";

$stmt = sqlsrv_query($conn, $sql, array($instructor_id));

if (!$stmt || !sqlsrv_has_rows($stmt)) {
    die("Instructor not found");
}

$instructor = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt);

// Get subjects taught by this instructor
$subjects_sql = "SELECT id, subject_code, subject_name, schedules 
                 FROM subjects 
                 WHERE instructor_id = ?
                 ORDER BY subject_code";
$subjects_stmt = sqlsrv_query($conn, $subjects_sql, array($instructor_id));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Details - Attendance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
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
        .back-btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Instructor Profile</h2>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Instructor ID / Account Code</div>
                        <div class="info-value"><?php echo htmlspecialchars($instructor['account_code']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Username</div>
                        <div class="info-value"><?php echo htmlspecialchars($instructor['user_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value">
                            <?php 
                            $full_name = $instructor['first_name'] . ' ';
                            if (!empty($instructor['middle_name'])) $full_name .= $instructor['middle_name'] . ' ';
                            $full_name .= $instructor['last_name'];
                            if (!empty($instructor['suffix_name'])) $full_name .= ' ' . $instructor['suffix_name'];
                            echo htmlspecialchars($full_name);
                            ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($instructor['phone_number'] ?? 'N/A'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Assigned Subjects</h2>
            </div>
            <div class="card-body">
                <?php if (sqlsrv_has_rows($subjects_stmt)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Schedule</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($subject = sqlsrv_fetch_array($subjects_stmt, SQLSRV_FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['schedules'] ?? 'Not set'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No subjects assigned to this instructor.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <a href="AddInstructor.php" class="back-btn">← Back to Instructor List</a>
    </div>
</body>
</html>

<?php
if (isset($subjects_stmt)) sqlsrv_free_stmt($subjects_stmt);
?>