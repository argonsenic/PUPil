<?php
/**
 * Submit Attendance
 * Handles attendance submission from QR code scanning
 */

// Suppress any HTML output and ensure only JSON is returned
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/database_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get attendance data
$data = json_decode(file_get_contents('php://input'), true);

$qr_code = isset($data['qr_code']) ? $data['qr_code'] : '';
$student_number = isset($data['student_number']) ? $data['student_number'] : '';
$student_name = isset($data['student_name']) ? $data['student_name'] : '';

if (empty($qr_code) || empty($student_number) || empty($student_name)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Check if QR code exists and is valid
    $qr_query = "SELECT qc.id, qc.subject_id, qc.expires_at, s.subject_code, s.subject_name 
                 FROM qr_codes qc 
                 JOIN subjects s ON qc.subject_id = s.id 
                 WHERE qc.qr_code = '" . pg_escape_string($conn, $qr_code) . "' 
                 AND qc.is_active = true 
                 AND qc.expires_at > NOW()";
    
    $qr_result = db_query($conn, $qr_query);
    
    if ($qr_result === false) {
        echo json_encode(['success' => false, 'message' => 'Database query failed: ' . db_error($conn)]);
        exit;
    }
    
    $qr_data = db_fetch_assoc($qr_result);
    
    if (!$qr_data) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired QR code']);
        exit;
    }
    
    // Check if student already submitted for this QR code
    $check_query = "SELECT id FROM attendance_records 
                    WHERE qr_code_id = " . pg_escape_string($conn, $qr_data['id']) . " 
                    AND student_number = '" . pg_escape_string($conn, $student_number) . "'";
    
    $check_result = db_query($conn, $check_query);
    
    if ($check_result === false) {
        echo json_encode(['success' => false, 'message' => 'Database check query failed: ' . db_error($conn)]);
        exit;
    }
    
    $existing = db_fetch_assoc($check_result);
    
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'Attendance already submitted for this QR code']);
        exit;
    }
    
    // Insert attendance record
    $insert_query = "INSERT INTO attendance_records 
                     (qr_code_id, student_id, subject_id, student_number, student_name, ip_address) 
                     VALUES (" . pg_escape_string($conn, $qr_data['id']) . ", 
                     0, " . pg_escape_string($conn, $qr_data['subject_id']) . ", 
                     '" . pg_escape_string($conn, $student_number) . "', 
                     '" . pg_escape_string($conn, $student_name) . "', 
                     '" . pg_escape_string($conn, $_SERVER['REMOTE_ADDR']) . "') RETURNING id";
    
    $insert_result = db_query($conn, $insert_query);
    
    if ($insert_result === false) {
        echo json_encode(['success' => false, 'message' => 'Database insert failed: ' . db_error($conn)]);
        exit;
    }
    
    $attendance_row = db_fetch_assoc($insert_result);
    
    if (!$attendance_row) {
        echo json_encode(['success' => false, 'message' => 'Failed to get attendance ID']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Attendance submitted successfully',
        'attendance_id' => $attendance_row['id'],
        'subject' => $qr_data['subject_name'],
        'subject_code' => $qr_data['subject_code'],
        'submission_time' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
