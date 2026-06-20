<?php
/**
 * Login API Endpoint
 * POST /api/auth/login.php
 */

require_once __DIR__ . '/../config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Method not allowed', null, 405);
}

// Get JSON input
$data = getJsonInput();

// Validate required fields
$required = ['username', 'password'];
$validation = validateRequired($data, $required);

if ($validation !== true) {
    sendResponse(false, 'Missing required fields: ' . implode(', ', $validation), null, 400);
}

$username = trim($data['username']);
$password = $data['password'];

try {
    // Query user with role
    $sql = "SELECT 
                u.id, 
                u.user_name, 
                u.password_hash, 
                u.role_id, 
                u.account_code,
                r.role 
            FROM dbo.users u 
            INNER JOIN dbo.roles r ON u.role_id = r.id 
            WHERE u.user_name = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        sendResponse(false, 'Invalid credentials', null, 401);
    }
    
    // Verify password
    if (
    !password_verify($password, $user['password_hash']) &&
    $password !== $user['password_hash']
    ) {
    sendResponse(false, 'Invalid credentials', null, 401);
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['user_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['account_code'] = $user['account_code'];
    $_SESSION['logged_in'] = true;
    
    // Get profile data based on role
    $profile = null;
    $full_name = '';
    
    if ($user['role'] === 'student') {
        $profile_sql = "SELECT * FROM dbo.student_profiles WHERE account_id = ?";
        $p_stmt = $pdo->prepare($profile_sql);
        $p_stmt->execute([$user['id']]);
        $profile = $p_stmt->fetch();
        
        if ($profile) {
            $_SESSION['profile'] = $profile;
            $full_name = $profile['first_name'] . ' ' . $profile['last_name'];
        }
    } elseif ($user['role'] === 'instructor') {
        $profile_sql = "SELECT * FROM dbo.instructor_profiles WHERE account_id = ?";
        $p_stmt = $pdo->prepare($profile_sql);
        $p_stmt->execute([$user['id']]);
        $profile = $p_stmt->fetch();
        
        if ($profile) {
            $_SESSION['profile'] = $profile;
            $full_name = $profile['first_name'] . ' ' . $profile['last_name'];
        }
    } elseif ($user['role'] === 'admin') {
        $full_name = 'Administrator';
    }
    
    $_SESSION['full_name'] = $full_name;
    
    // Return success response
    sendResponse(true, 'Login successful', [
        'user_id' => $user['id'],
        'username' => $user['user_name'],
        'role' => $user['role'],
        'full_name' => $full_name,
        'profile' => $profile
    ]);
    
} catch (Exception $e) {
    sendResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
