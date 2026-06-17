<?php
/**
 * Get Single Student API Endpoint
 * GET /api/students/get.php?id={student_id}
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/auth.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, 'Method not allowed', null, 405);
}

requireAuth();

// Get student ID from URL parameter
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id <= 0) {
    sendResponse(false, 'Invalid student ID', null, 400);
}

try {
    // Fetch student details
    $sql = "SELECT 
                sp.id, sp.first_name, sp.middle_name, sp.last_name, sp.suffix_name,
                sp.course, sp.year_level, sp.section, sp.phone_number,
                u.id AS user_id, u.user_name, u.account_code, u.role_id,
                r.role
            FROM dbo.student_profiles sp
            INNER JOIN dbo.users u ON sp.account_id = u.id
            INNER JOIN dbo.roles r ON u.role_id = r.id
            WHERE sp.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        sendResponse(false, 'Student not found', null, 404);
    }
    
    // Get enrollment records for this student
    $enroll_sql = "SELECT 
                    er.id, er.enrollment_date, er.enrollment_code,
                    s.id AS subject_id, s.subject_code, s.subject_name,
                    ip.first_name AS instructor_first, ip.last_name AS instructor_last
                FROM dbo.enrollment_records er
                INNER JOIN dbo.subjects s ON er.subject_id = s.id
                LEFT JOIN dbo.instructor_profiles ip ON s.instructor_id = ip.id
                WHERE er.student_id = ?
                ORDER BY er.enrollment_date DESC";
    
    $enroll_stmt = $pdo->prepare($enroll_sql);
    $enroll_stmt->execute([$student_id]);
    $enrollments = $enroll_stmt->fetchAll();
    
    // Get attendance records
    $attendance_sql = "SELECT 
                        ar.id, ar.record_type, ar.status, ar.create_at,
                        s.subject_code, s.subject_name
                    FROM dbo.attendance_records ar
                    INNER JOIN dbo.subjects s ON ar.subject_id = s.id
                    WHERE ar.student_id = ?
                    ORDER BY ar.create_at DESC";
    
    $attendance_stmt = $pdo->prepare($attendance_sql);
    $attendance_stmt->execute([$student_id]);
    $attendance = $attendance_stmt->fetchAll();
    
    // Combine all data
    $student_data = [
        'profile' => $student,
        'enrollments' => $enrollments,
        'attendance' => $attendance
    ];
    
    sendResponse(true, 'Student retrieved successfully', $student_data);
    
} catch (Exception $e) {
    sendResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
