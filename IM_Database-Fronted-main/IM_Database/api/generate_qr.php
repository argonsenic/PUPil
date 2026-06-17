<?php
/**
 * Generate Dynamic QR Code
 * Creates a QR code with 24-hour expiration for a specific subject
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/database_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get subject ID from request
$data = json_decode(file_get_contents('php://input'), true);
$subject_id = isset($data['subject_id']) ? intval($data['subject_id']) : 0;

if ($subject_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid subject ID']);
    exit;
}

try {
    // Get subject information
    $subject_query = "SELECT id, subject_code, subject_name FROM subjects WHERE id = " . pg_escape_string($conn, $subject_id);
    $subject_result = db_query($conn, $subject_query);
    $subject = db_fetch_assoc($subject_result);

    if (!$subject) {
        echo json_encode(['success' => false, 'message' => 'Subject not found']);
        exit;
    }

    // Generate unique QR code
    $qr_code = 'ATT_' . date('YmdHis') . '_' . rand(1000, 9999) . '_SUBJ' . $subject_id;
    
    // Set expiration to 24 hours from now
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Insert QR code into database
    $insert_query = "INSERT INTO qr_codes (subject_id, qr_code, expires_at) VALUES (" . 
                    pg_escape_string($conn, $subject_id) . ", '" . 
                    pg_escape_string($conn, $qr_code) . "', '" . 
                    pg_escape_string($conn, $expires_at) . "') RETURNING id";
    
    $insert_result = db_query($conn, $insert_query);
    $qr_row = db_fetch_assoc($insert_result);
    
    if (!$qr_row) {
        echo json_encode(['success' => false, 'message' => 'Failed to generate QR code']);
        exit;
    }
    
    $qr_id = $qr_row['id'];
    
    // Generate QR code URL
    $attendance_url = "https://pup-attendance-system.onrender.com/attendance.html?qr=" . urlencode($qr_code) . "&subject=" . urlencode($subject['subject_code']);
    $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($attendance_url);
    
    echo json_encode([
        'success' => true,
        'qr_code' => $qr_code,
        'qr_url' => $qr_api_url,
        'attendance_url' => $attendance_url,
        'subject' => $subject,
        'expires_at' => $expires_at,
        'qr_id' => $qr_id
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
