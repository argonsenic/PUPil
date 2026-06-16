<?php
/**
 * Get Subjects API Endpoint
 * GET /api/attendance/subjects.php - Get subjects for attendance logging
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/auth.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, 'Method not allowed', null, 405);
}

requireAuth();

try {
    // Get subjects based on user role
    if ($_SESSION['role'] === 'instructor') {
        // Get instructor profile ID
        $instructor_profile_id = null;
        if (isset($_SESSION['profile']['id'])) {
            $instructor_profile_id = $_SESSION['profile']['id'];
        }
        
        if ($instructor_profile_id) {
            $sql = "SELECT id, subject_code, subject_name FROM dbo.subjects WHERE instructor_id = ? ORDER BY subject_code";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$instructor_profile_id]);
        } else {
            sendResponse(false, 'Instructor profile not found', null, 404);
        }
    } else {
        // Admin can see all subjects
        $sql = "SELECT id, subject_code, subject_name FROM dbo.subjects ORDER BY subject_code";
        $stmt = $pdo->query($sql);
    }
    
    $subjects = $stmt->fetchAll();
    
    sendResponse(true, 'Subjects retrieved successfully', $subjects);
    
} catch (Exception $e) {
    sendResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
