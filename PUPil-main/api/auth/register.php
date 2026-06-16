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
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$data['username']]);
    
    if ($check_stmt->fetch()) {
        sendResponse(false, 'Username already taken', null, 409);
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get student role ID
    $role_sql = "SELECT id FROM dbo.roles WHERE role = 'student'";
    $role_stmt = $pdo->query($role_sql);
    $role_row = $role_stmt->fetch();
    $role_id = $role_row['id'];
    
    // Generate account code
    $account_code = 'STU_' . date('Ymd') . '_' . rand(1000, 9999);
    
    // Hash password
    $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Insert into users
    $user_sql = "INSERT INTO dbo.users (user_name, password_hash, role_id, account_code) VALUES (?, ?, ?, ?)";
    $user_stmt = $pdo->prepare($user_sql);
    $user_stmt->execute([
        $data['username'],
        $password_hash,
        $role_id,
        $account_code
    ]);
    
    // Get the generated user ID
    $user_id = $pdo->lastInsertId();
    
    // Insert into student_profiles
    $profile_sql = "INSERT INTO dbo.student_profiles 
                    (first_name, middle_name, last_name, suffix_name, 
                     course, year_level, section, phone_number, account_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $profile_stmt = $pdo->prepare($profile_sql);
    $profile_stmt->execute([
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
    $pdo->commit();
    
    // Return success response
    sendResponse(true, 'Registration successful', [
        'user_id' => $user_id,
        'username' => $data['username'],
        'account_code' => $account_code
    ], 201);
    
} catch (Exception $e) {
    // Roll back on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendResponse(false, 'Registration failed: ' . $e->getMessage(), null, 500);
}
