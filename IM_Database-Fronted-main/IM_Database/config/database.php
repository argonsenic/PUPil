<?php
/**
 * Database Configuration
 * PostgreSQL connection using pg_* functions
 */

// Database configuration - use environment variables for Render.com
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('DB_PORT') ?: '5432';
$db_name = getenv('DB_NAME') ?: 'attendance_system';
$db_user = getenv('DB_USER') ?: 'postgres';
$db_password = getenv('DB_PASSWORD') ?: '';

// Create database connection string
$conn_string = "host=$db_host port=$db_port dbname=$db_name user=$db_user password=$db_password";

// Establish connection
$conn = pg_connect($conn_string);

if ($conn === false) {
    $error_message = "Database connection failed: Unable to connect to PostgreSQL server.";
    
    // Return JSON response for API calls
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed',
            'error' => $error_message
        ]);
        exit;
    } else {
        die($error_message);
    }
}
