<?php
/**
 * Create Admin User
 * Creates admin user with username 'pupil' and password 'pupil'
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/database_helper.php';

if (!isset($conn) || $conn === false) {
    die("Database connection failed");
}

try {
    // Check if admin role exists
    $role_check = db_query($conn, "SELECT id FROM roles WHERE role = 'admin'");
    $role_row = db_fetch_assoc($role_check);
    
    if (!$role_row) {
        // Create admin role
        $insert_role = db_query($conn, "INSERT INTO roles (role) VALUES ('admin') RETURNING id");
        if ($insert_role === false) {
            throw new Exception('Failed to create admin role');
        }
        $role_row = db_fetch_assoc($insert_role);
    }
    
    $role_id = $role_row['id'];
    
    // Check if admin user exists
    $user_check = db_query($conn, "SELECT id FROM users WHERE user_name = 'pupil'");
    $existing_user = db_fetch_assoc($user_check);
    
    if ($existing_user) {
        echo "Admin user 'pupil' already exists.<br>";
    } else {
        // Create admin user
        $password_hash = password_hash('pupil', PASSWORD_DEFAULT);
        $account_code = 'ADM_' . date('Ymd') . '_0001';
        
        $insert_user = db_query($conn, 
            "INSERT INTO users (user_name, password_hash, role_id, account_code) VALUES ('" . pg_escape_string($conn, 'pupil') . "', '" . pg_escape_string($conn, $password_hash) . "', '" . pg_escape_string($conn, $role_id) . "', '" . pg_escape_string($conn, $account_code) . "') RETURNING id"
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
