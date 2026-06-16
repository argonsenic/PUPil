<?php
/**
 * Get Students by Subject API Endpoint
 * GET /api/attendance/students.php?subject_id={subject_id}
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/auth.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, 'Method not allowed', null, 405);
}

requireAuth();

// Get subject_id from URL parameter
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

try {
    if ($subject_id > 0) {
        // Get students enrolled in specific subject
        $sql = "SELECT DISTINCT sp.id, sp.first_name, sp.last_name, sp.course 
                 FROM dbo.student_profiles sp
                 INNER JOIN dbo.enrollment_records er ON sp.id = er.student_id
                 WHERE er.subject_id = ?
                 ORDER BY sp.last_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$subject_id]);
    } else {
        // Get all students
        $sql = "SELECT id, first_name, last_name, course FROM dbo.student_profiles ORDER BY last_name";
        $stmt = $pdo->query($sql);
    }
    
    $students = $stmt->fetchAll();
    
    sendResponse(true, 'Students retrieved successfully', $students);
    
} catch (Exception $e) {
    sendResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
