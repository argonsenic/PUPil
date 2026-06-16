<?php
/**
 * Register API Endpoint
 * POST /api/auth/register.php
 */

require_once __DIR__ . '/../config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Method not allowed', null, 405);
}

// Get JSON input
$data = getJsonInput();

// Validate required fields
$required = ['username', 'password', 'first_name', 'last_name', 'course', 'year_level'];
$validation = validateRequired($data, $required);

if ($validation !== true) {
    sendResponse(false, 'Missing required fields: ' . implode(', ', $validation), null, 400);
}

// Additional validation
$errors = [];

if (strlen($data['username']) < 3) {
    $errors[] = 'Username must be at least 3 characters';
}

if (strlen($data['password']) < 6) {
    $errors[] = 'Password must be at least 6 characters';
}

if ($data['year_level'] < 1 || $data['year_level'] > 4) {
    $errors[] = 'Year level must be between 1 and 4';
}

if (!empty($errors)) {
    sendResponse(false, 'Validation failed', ['errors' => $errors], 400);
}

try {
    // Check if username exists
    $check_sql = "SELECT id FROM dbo.users WHERE user_name = ?";
    $check_stmt = executeQuery($check_sql, [$data['username']]);
    
    if (fetchOne($check_stmt)) {
        sendResponse(false, 'Username already taken', null, 409);
    }
    
    // Start transaction
    sqlsrv_begin_transaction($conn);
    
    // Get student role ID
    $role_sql = "SELECT id FROM dbo.roles WHERE role = 'student'";
    $role_stmt = executeQuery($role_sql);
    $role_row = fetchOne($role_stmt);
    $role_id = $role_row['id'];
    
    // Generate account code
    $account_code = 'STU_' . date('Ymd') . '_' . rand(1000, 9999);
    
    // Hash password
    $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Insert into users
    $user_sql = "INSERT INTO dbo.users (user_name, password_hash, role_id, account_code) VALUES (?, ?, ?, ?)";
    executeQuery($user_sql, [
        $data['username'],
        $password_hash,
        $role_id,
        $account_code
    ]);
    
    // Get the generated user ID
    $user_id_sql = "SELECT SCOPE_IDENTITY() as id";
    $user_id_stmt = executeQuery($user_id_sql);
    $user_id_row = fetchOne($user_id_stmt);
    $user_id = $user_id_row['id'];
    
    // Insert into student_profiles
    $profile_sql = "INSERT INTO dbo.student_profiles 
                    (first_name, middle_name, last_name, suffix_name, 
                     course, year_level, section, phone_number, account_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    executeQuery($profile_sql, [
        $data['first_name'],
        $data['middle_name'] ?? '',
        $data['last_name'],
        $data['suffix_name'] ?? '',
        $data['course'],
        $data['year_level'],
        $data['section'] ?? '',
        $data['phone_number'] ?? '',
        $user_id
    ]);
    
    // Commit transaction
    sqlsrv_commit($conn);
    
    // Return success response
    sendResponse(true, 'Registration successful', [
        'user_id' => $user_id,
        'username' => $data['username'],
        'account_code' => $account_code
    ], 201);
    
} catch (Exception $e) {
    // Roll back on error
    if (sqlsrv_in_transaction($conn)) {
        sqlsrv_rollback($conn);
    }
    sendResponse(false, 'Registration failed: ' . $e->getMessage(), null, 500);
}
