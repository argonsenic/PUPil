<?php
/**
 * Get Attendance Handler
 * Displays attendance records with filtering options
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Database connection failed.");
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Authentication/Login.php");
    exit();
}

// Get filter parameters
$student_filter = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$subject_filter = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$record_type_filter = isset($_GET['record_type']) ? $_GET['record_type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$sql = "SELECT 
            ar.id,
            ar.record_type,
            ar.status,
            ar.create_at AS datetime,
            sp.first_name,
            sp.last_name,
            sp.course,
            sp.year_level,
            s.subject_code,
            s.subject_name
        FROM attendance_records ar
        INNER JOIN student_profiles sp ON ar.student_id = sp.id
        INNER JOIN subjects s ON ar.subject_id = s.id
        WHERE 1=1";

$params = [];

if ($student_filter > 0) {
    $sql .= " AND ar.student_id = ?";
    $params[] = $student_filter;
}

if ($subject_filter > 0) {
    $sql .= " AND ar.subject_id = ?";
    $params[] = $subject_filter;
}

if (!empty($record_type_filter)) {
    $sql .= " AND ar.record_type = ?";
    $params[] = $record_type_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND ar.status = ?";
    $params[] = $status_filter;
}

if (!empty($date_from)) {
    $sql .= " AND CAST(ar.create_at AS DATE) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND CAST(ar.create_at AS DATE) <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY ar.create_at DESC";

// 1. Run your main query with variables/parameters
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

try {
    // 2. Get student filter dropdown data
    $students_sql = "SELECT id, first_name, last_name FROM dbo.student_profiles ORDER BY last_name";
    $students_stmt = $pdo->query($students_sql);
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC); // Loops over $students in HTML

    // 3. Get subject filter dropdown data
    $subjects_sql = "SELECT id, subject_code, subject_name FROM dbo.subjects ORDER BY subject_code";
    $subjects_stmt = $pdo->query($subjects_sql);
    $subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC); // Loops over $subjects in HTML

} catch (Exception $e) {
    die("Dropdown filtering failed to load: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width