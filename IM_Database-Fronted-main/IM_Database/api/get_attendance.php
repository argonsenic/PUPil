<?php
/**
 * Get Attendance Records
 * Retrieves all attendance records with timestamps
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/database_helper.php';

try {
    // Get all attendance records with subject and student information
    $query = "SELECT 
                ar.id,
                ar.student_number,
                ar.student_name,
                ar.submission_time,
                ar.status,
                s.subject_code,
                s.subject_name,
                qc.qr_code,
                qc.expires_at
              FROM attendance_records ar
              JOIN subjects s ON ar.subject_id = s.id
              JOIN qr_codes qc ON ar.qr_code_id = qc.id
              ORDER BY ar.submission_time DESC";
    
    $result = db_query($conn, $query);
    
    if ($result === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch attendance records']);
        exit;
    }
    
    $attendance = [];
    while ($row = db_fetch_assoc($result)) {
        $attendance[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'attendance' => $attendance,
        'count' => count($attendance)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
