<?php
/**
 * Delete Student API Endpoint
 * DELETE /api/students/delete.php?id={student_id}
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/auth.php';

// Only allow DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    sendResponse(false, 'Method not allowed', null, 405);
}

requireRole('admin');

// Get student ID from URL parameter
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id <= 0) {
    sendResponse(false, 'Invalid student ID', null, 400);
}

try {
    // Check if student exists
    $check_sql = "SELECT account_id FROM dbo.student_profiles WHERE id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$student_id]);
    $student_data = $check_stmt->fetch();
    
    if (!$student_data) {
        sendResponse(false, 'Student not found', null, 404);
    }
    
    $user_id = $student_data['account_id'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete attendance records
    $delete_attendance_sql = "DELETE FROM dbo.attendance_records WHERE student_id = ?";
    $delete_attendance_stmt = $pdo->prepare($delete_attendance_sql);
    $delete_attendance_stmt->execute([$student_id]);
    
    // Delete enrollment records
    $delete_enrollment_sql = "DELETE FROM dbo.enrollment_records WHERE student_id = ?";
    $delete_enrollment_stmt = $pdo->prepare($delete_enrollment_sql);
    $delete_enrollment_stmt->execute([$student_id]);
    
    // Delete student profile
    $delete_profile_sql = "DELETE FROM dbo.student_profiles WHERE id = ?";
    $delete_profile_stmt = $pdo->prepare($delete_profile_sql);
    $delete_profile_stmt->execute([$student_id]);
    
    // Delete user account
    $delete_user_sql = "DELETE FROM dbo.users WHERE id = ?";
    $delete_user_stmt = $pdo->prepare($delete_user_sql);
    $delete_user_stmt->execute([$user_id]);
    
    // Commit transaction
    $pdo->commit();
    
    sendResponse(true, 'Student deleted successfully');
    
} catch (Exception $e) {
    // Roll back on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendResponse(false, 'Failed to delete student: ' . $e->getMessage(), null, 500);
}
