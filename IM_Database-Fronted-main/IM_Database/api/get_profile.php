<?php
/**
 * Get User Profile
 * Fetches profile data directly from database based on role
 */

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/database_helper.php';

try {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    $profile = null;
    
    if ($role === 'student') {
        $profile_sql = "SELECT * FROM student_profiles WHERE account_id = '" . pg_escape_string($conn, $user_id) . "'";
        $p_stmt = db_query($conn, $profile_sql);
        if ($p_stmt !== false) {
            $profile = db_fetch_assoc($p_stmt);
        }
    } elseif ($role === 'instructor') {
        $profile_sql = "SELECT * FROM instructor_profiles WHERE account_id = '" . pg_escape_string($conn, $user_id) . "'";
        $p_stmt = db_query($conn, $profile_sql);
        if ($p_stmt !== false) {
            $profile = db_fetch_assoc($p_stmt);
        }
    }
    
    echo json_encode([
        'success' => true,
        'profile' => $profile,
        'role' => $role,
        'account_code' => $_SESSION['account_code'],
        'username' => $_SESSION['username']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
