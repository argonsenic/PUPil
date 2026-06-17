<?php
/**
 * Delete Student Handler (Admin only)
 * Deletes a student and their associated user account
 */

require_once '../../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Authentication/Login.php");
    exit();
}

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id <= 0) {
    header("Location: AddStudent.php");
    exit();
}

// Get student user ID
$fetch_sql = "SELECT account_id FROM student_profiles WHERE id = ?";
$fetch_stmt = sqlsrv_query($conn, $fetch_sql, array($student_id));

if (!$fetch_stmt || !sqlsrv_has_rows($fetch_stmt)) {
    header("Location: AddStudent.php");
    exit();
}

$student = sqlsrv_fetch_array($fetch_stmt, SQLSRV_FETCH_ASSOC);
$user_id = $student['account_id'];
sqlsrv_free_stmt($fetch_stmt);

// Start transaction
sqlsrv_begin_transaction($conn);

$error = '';

try {
    // Delete student profile
    $delete_profile = "DELETE FROM student_profiles WHERE id = ?";
    $profile_stmt = sqlsrv_query($conn, $delete_profile, array($student_id));
    
    if (!$profile_stmt) {
        throw new Exception("Failed to delete student profile");
    }
    
    // Delete user account
    $delete_user = "DELETE FROM users WHERE id = ?";
    $user_stmt = sqlsrv_query($conn, $delete_user, array($user_id));
    
    if (!$user_stmt) {
        throw new Exception("Failed to delete user account");
    }
    
    sqlsrv_commit($conn);
    
} catch (Exception $e) {
    sqlsrv_rollback($conn);
    $error = $e->getMessage();
}

if (isset($profile_stmt)) sqlsrv_free_stmt($profile_stmt);
if (isset($user_stmt)) sqlsrv_free_stmt($user_stmt);

// Redirect back
if (empty($error)) {
    header("Location: AddStudent.php?deleted=1");
} else {
    header("Location: AddStudent.php?error=" . urlencode($error));
}
exit();
?>