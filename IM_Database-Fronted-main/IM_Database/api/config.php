<?php
/**
 * API Configuration
 * Base configuration for REST API
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set headers for JSON API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

/**
 * Send JSON response
 * @param bool $success
 * @param string $message
 * @param mixed $data
 * @param int $statusCode
 */
function sendResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Get JSON input from request body
 * @return array
 */
function getJsonInput() {
    $json = file_get_contents('php://input');
    return json_decode($json, true) ?? [];
}

/**
 * Validate required fields
 * @param array $data
 * @param array $requiredFields
 * @return array|bool
 */
function validateRequired($data, $requiredFields) {
    $missing = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        return $missing;
    }
    
    return true;
}

/**
 * Execute SQL query using sqlsrv
 * @param string $sql
 * @param array $params
 * @return resource|false
 */
function executeQuery($sql, $params = []) {
  global $conn;
  $stmt = sqlsrv_query($conn, $sql, $params);
  if ($stmt === false) {
    throw new Exception(sqlsrv_errors()[0]['message']);
  }
  return $stmt;
}

/**
 * Fetch all rows from statement
 * @param resource $stmt
 * @return array
 */
function fetchAll($stmt) {
  $rows = [];
  while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $rows[] = $row;
  }
  return $rows;
}

/**
 * Fetch single row from statement
 * @param resource $stmt
 * @return array|false
 */
function fetchOne($stmt) {
  return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
}
