<?php
/**
 * Enrollment API Endpoint
 * GET /api/enrollment/index.php - Get enrollment records
 * POST /api/enrollment/index.php - Create new enrollment (admin only)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get enrollment records
    requireAuth();
    
    // Get filter parameters
    $student_filter = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
    $subject_filter = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
    
    try {
        $sql = "SELECT 
                    er.id, er.enrollment_date, er.enrollment_code,
                    sp.id AS student_id, sp.first_name AS student_first, sp.last_name AS student_last, 
                    sp.course, sp.year_level, sp.section,
                    s.id AS subject_id, s.subject_code, s.subject_name, s.schedule,
                    ip.first_name AS instructor_first, ip.last_name AS instructor_last
                FROM dbo.enrollment_records er
                INNER JOIN dbo.student_profiles sp ON er.student_id = sp.id
                INNER JOIN dbo.subjects s ON er.subject_id = s.id
                LEFT JOIN dbo.instructor_profiles ip ON s.instructor_id = ip.id
                WHERE 1=1";
        
        $params = [];
        
        if ($student_filter > 0) {
            $sql .= " AND er.student_id = ?";
            $params[] = $student_filter;
        }
        
        if ($subject_filter > 0) {
            $sql .= " AND er.subject_id = ?";
            $params[] = $subject_filter;
        }
        
        $sql .= " ORDER BY er.enrollment_date DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $enrollments = $stmt->fetchAll();
        
        sendResponse(true, 'Enrollment records retrieved successfully', $enrollments);
        
    } catch (Exception $e) {
        sendResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new enrollment (admin only)
    requireRole('admin');
    
    $data = getJsonInput();
    
    // Validate required fields
    $required = ['student_id', 'subject_id'];
    $validation = validateRequired($data, $required);
    
    if ($validation !== true) {
        sendResponse(false, 'Missing required fields: ' . implode(', ', $validation), null, 400);
    }
    
    try {
        // Check if enrollment already exists
        $check_sql = "SELECT id FROM dbo.enrollment_records WHERE student_id = ? AND subject_id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$data['student_id'], $data['subject_id']]);
        
        if ($check_stmt->fetch()) {
            sendResponse(false, 'Student already enrolled in this subject', null, 409);
        }
        
        // Generate enrollment code
        $enrollment_code = 'ENR_' . date('Ymd') . '_' . rand(1000, 9999);
        
        // Insert enrollment record
        $sql = "INSERT INTO dbo.enrollment_records (student_id, subject_id, enrollment_date, enrollment_code) 
                VALUES (?, ?, GETDATE(), ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data['student_id'], $data['subject_id'], $enrollment_code]);
        
        sendResponse(true, 'Enrollment created successfully', [
            'id' => $pdo->lastInsertId(),
            'enrollment_code' => $enrollment_code
        ], 201);
        
    } catch (Exception $e) {
        sendResponse(false, 'Failed to create enrollment: ' . $e->getMessage(), null, 500);
    }
    
} else {
    sendResponse(false, 'Method not allowed', null, 405);
}
