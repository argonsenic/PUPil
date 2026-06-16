<?php
/**
 * Update Student API Endpoint
 * PUT /api/students/update.php?id={student_id}
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/auth.php';

// Only allow PUT requests
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendResponse(false, 'Method not allowed', null, 405);
}

requireRole('admin');

// Get student ID from URL parameter
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id <= 0) {
    sendResponse(false, 'Invalid student ID', null, 400);
}

$data = getJsonInput();

// Validate required fields
$required = ['first_name', 'last_name', 'course', 'year_level'];
$validation = validateRequired($data, $required);

if ($validation !== true) {
    sendResponse(false, 'Missing required fields: ' . implode(', ', $validation), null, 400);
}

// Additional validation
$errors = [];

if ($data['year_level'] < 1 || $data['year_level'] > 4) {
    $errors[] = 'Year level must be between 1 and 4';
}

if (!empty($errors)) {
    sendResponse(false, 'Validation failed', ['errors' => $errors], 400);
}

try {
    // Check if student exists
    $check_sql = "SELECT id FROM dbo.student_profiles WHERE id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$student_id]);
    
    if (!$check_stmt->fetch()) {
        sendResponse(false, 'Student not found', null, 404);
    }
    
    // Update student profile
    $sql = "UPDATE dbo.student_profiles 
            SET first_name = ?, middle_name = ?, last_name = ?, suffix_name = ?,
                course = ?, year_level = ?, section = ?, phone_number = ?
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['first_name'],
        $data['middle_name'] ?? '',
        $data['last_name'],
        $data['suffix_name'] ?? '',
        $data['course'],
        $data['year_level'],
        $data['section'] ?? '',
        $data['phone_number'] ?? '',
        $student_id
    ]);
    
    // Update username if provided
    if (isset($data['username']) && !empty($data['username'])) {
        if (strlen($data['username']) < 3) {
            sendResponse(false, 'Username must be at least 3 characters', null, 400);
        }
        
        // Get user_id from student profile
        $user_sql = "SELECT account_id FROM dbo.student_profiles WHERE id = ?";
        $user_stmt = $pdo->prepare($user_sql);
        $user_stmt->execute([$student_id]);
        $user_data = $user_stmt->fetch();
        $user_id = $user_data['account_id'];
        
        // Update username
        $update_user_sql = "UPDATE dbo.users SET user_name = ? WHERE id = ?";
        $update_user_stmt = $pdo->prepare($update_user_sql);
        $update_user_stmt->execute([$data['username'], $user_id]);
    }
    
    // Update password if provided
    if (isset($data['password']) && !empty($data['password'])) {
        if (strlen($data['password']) < 6) {
            sendResponse(false, 'Password must be at least 6 characters', null, 400);
        }
        
        // Get user_id from student profile
        $user_sql = "SELECT account_id FROM dbo.student_profiles WHERE id = ?";
        $user_stmt = $pdo->prepare($user_sql);
        $user_stmt->execute([$student_id]);
        $user_data = $user_stmt->fetch();
        $user_id = $user_data['account_id'];
        
        // Hash and update password
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $update_pass_sql = "UPDATE dbo.users SET password_hash = ? WHERE id = ?";
        $update_pass_stmt = $pdo->prepare($update_pass_sql);
        $update_pass_stmt->execute([$password_hash, $user_id]);
    }
    
    sendResponse(true, 'Student updated successfully');
    
} catch (Exception $e) {
    sendResponse(false, 'Failed to update student: ' . $e->getMessage(), null, 500);
}
