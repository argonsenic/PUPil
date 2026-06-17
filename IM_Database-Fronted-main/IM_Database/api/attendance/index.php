<?php
/**
 * Attendance API Endpoint
 * GET /api/attendance/index.php - Get attendance records
 * POST /api/attendance/index.php - Log attendance (instructor/admin only)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get attendance records
    requireAuth();
    
    // Get query parameters
    $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
    $subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    try {
        $sql = "SELECT 
                    ar.id, ar.student_id, ar.subject_id, ar.record_type, ar.status, ar.create_at,
                    sp.first_name, sp.last_name, sp.course,
                    s.subject_code, s.subject_name
                FROM dbo.attendance_records ar
                INNER JOIN dbo.student_profiles sp ON ar.student_id = sp.id
                INNER JOIN dbo.subjects s ON ar.subject_id = s.id
                WHERE CAST(ar.create_at AS DATE) = ?";
        
        $params = [$date];
        
        if ($student_id > 0) {
            $sql .= " AND ar.student_id = ?";
            $params[] = $student_id;
        }
        
        if ($subject_id > 0) {
            $sql .= " AND ar.subject_id = ?";
            $params[] = $subject_id;
        }
        
        $sql .= " ORDER BY ar.create_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $attendance = $stmt->fetchAll();
        
        sendResponse(true, 'Attendance records retrieved successfully', $attendance);
        
    } catch (Exception $e) {
        sendResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log attendance (instructor/admin only)
    requireAnyRole(['instructor', 'admin']);
    
    $data = getJsonInput();
    
    // Validate required fields
    $required = ['student_id', 'subject_id', 'record_type', 'status'];
    $validation = validateRequired($data, $required);
    
    if ($validation !== true) {
        sendResponse(false, 'Missing required fields: ' . implode(', ', $validation), null, 400);
    }
    
    // Validate record type and status
    if (!in_array($data['record_type'], ['Log-in', 'Log-out'])) {
        sendResponse(false, 'Invalid record type. Must be Log-in or Log-out', null, 400);
    }
    
    if (!in_array($data['status'], ['Present', 'Late', 'Absent'])) {
        sendResponse(false, 'Invalid status. Must be Present, Late, or Absent', null, 400);
    }
    
    try {
        // Check if attendance already exists today
        $check_sql = "SELECT id FROM dbo.attendance_records 
                      WHERE student_id = ? AND subject_id = ? 
                      AND CAST(create_at AS DATE) = CAST(GETDATE() AS DATE)
                      AND record_type = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$data['student_id'], $data['subject_id'], $data['record_type']]);
        
        if ($check_stmt->fetch()) {
            sendResponse(false, ucfirst($data['record_type']) . ' already recorded for today', null, 409);
        }
        
        // Insert new attendance record
        $insert_sql = "INSERT INTO dbo.attendance_records 
                       (student_id, subject_id, record_type, status, create_at) 
                       VALUES (?, ?, ?, ?, GETDATE())";
        $insert_stmt = $pdo->prepare($insert_sql);
        $insert_stmt->execute([
            $data['student_id'],
            $data['subject_id'],
            $data['record_type'],
            $data['status']
        ]);
        
        sendResponse(true, 'Attendance logged successfully', [
            'id' => $pdo->lastInsertId()
        ], 201);
        
    } catch (Exception $e) {
        sendResponse(false, 'Failed to log attendance: ' . $e->getMessage(), null, 500);
    }
    
} else {
    sendResponse(false, 'Method not allowed', null, 405);
}
