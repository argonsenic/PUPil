<?php
/**
 * Create Admin User
 * Creates admin user with username 'pupil' and password 'pupil'
 */

require_once __DIR__ . '/config/database.php';

if (!isset($conn) || $conn === false) {
    die("Database connection failed");
}

try {
    // Check if admin role exists
    $role_check = sqlsrv_query($conn, "SELECT id FROM dbo.roles WHERE role = 'admin'");
    $role_row = sqlsrv_fetch_array($role_check, SQLSRV_FETCH_ASSOC);
    
    if (!$role_row) {
        // Create admin role
        $insert_role = sqlsrv_query($conn, "INSERT INTO dbo.roles (role) VALUES ('admin')");
        if ($insert_role === false) {
            throw new Exception('Failed to create admin role');
        }
        $role_check = sqlsrv_query($conn, "SELECT id FROM dbo.roles WHERE role = 'admin'");
        $role_row = sqlsrv_fetch_array($role_check, SQLSRV_FETCH_ASSOC);
    }
    
    $role_id = $role_row['id'];
    
    // Check if admin user exists
    $user_check = sqlsrv_query($conn, "SELECT id FROM dbo.users WHERE user_name = 'pupil'");
    $existing_user = sqlsrv_fetch_array($user_check, SQLSRV_FETCH_ASSOC);
    
    if ($existing_user) {
        echo "Admin user 'pupil' already exists.<br>";
    } else {
        // Create admin user
        $password_hash = password_hash('pupil', PASSWORD_DEFAULT);
        $account_code = 'ADM_' . date('Ymd') . '_0001';
        
        $insert_user = sqlsrv_query($conn, 
            "INSERT INTO dbo.users (user_name, password_hash, role_id, account_code) VALUES (?, ?, ?, ?)",
            ['pupil', $password_hash, $role_id, $account_code]
        );
        
        if ($insert_user === false) {
            throw new Exception('Failed to create admin user');
        }
        
        echo "Admin user created successfully!<br>";
        echo "Username: pupil<br>";
        echo "Password: pupil<br>";
    }
    
    echo "<a href='login.html'>Go to Login</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
