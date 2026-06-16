<?php
/**
 * Database Configuration
 * SQL Server connection using sqlsrv_* functions
 */

// Database configuration
define('DB_HOST', 'localhost\sqlexpress02'); // Your SQL Server instance name
define('DB_NAME', 'attendance_system');

// Create database connection using Windows Authentication
$connectionInfo = array(
    "Database" => DB_NAME,
    "UID" => "",
    "PWD" => ""
);

$conn = sqlsrv_connect(DB_HOST, $connectionInfo);

if ($conn === false) {
    $errors = sqlsrv_errors();
    $error_message = "Database connection failed: ";
    if ($errors) {
        foreach ($errors as $error) {
            $error_message .= $error['message'] . " ";
        }
    }
    
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
