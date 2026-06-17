<?php
/**
 * Database Configuration
 * SQL Server connection using PDO
 */

// Database configuration
define('DB_HOST', 'localhost\sqlexpress02'); // Your SQL Server instance name
define('DB_NAME', 'attendance_system');
define('DB_USER', 'pupil'); // Update with your SQL Server username
define('DB_PASS', 'pupil');   // Update with your SQL Server password

try {
    // Create PDO connection for SQL Server
    $dsn = "sqlsrv:Server=" . DB_HOST . ";Database=" . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    
    // Set PDO attributes
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch (PDOException $e) {
    // Log error and return JSON response for API calls
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed',
            'error' => $e->getMessage()
        ]);
        exit;
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}
