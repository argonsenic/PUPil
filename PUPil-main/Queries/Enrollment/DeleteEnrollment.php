<?php
/**
 * Delete Enrollment Handler (Admin only)
 * Removes a student's enrollment from a subject
 */

// 1. FIXED PATH: Goes out of Queries/Enrollment/ and into your Database folder
$pdo = null;
$connectionInclude = require_once __DIR__ . '/../../Database/connection.php';
if ($pdo === null && $connectionInclude instanceof PDO) {
    $pdo = $connectionInclude;
}
if ((!isset($pdo) || !$pdo instanceof PDO) && isset($conn) && $conn instanceof PDO) {
    $pdo = $conn;
}
if ((!isset($pdo) || !$pdo instanceof PDO) && isset($db) && $db instanceof PDO) {
    $pdo = $db;
}
if (!isset($pdo) || !$pdo instanceof PDO) {
    header("Location: EnrollStudent.php?error=" . urlencode('Database connection not initialized'));
    exit();
}

// Start session to check login state
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Authentication/Login.php");
    exit();
}

$enrollment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($enrollment_id <= 0) {
    header("Location: EnrollStudent.php");
    exit();
}

try {
    // 2. FIXED QUERY: Uses your $pdo connection to delete safely
    $delete_sql = "DELETE FROM dbo.enrollment_records WHERE id = ?";
    $stmt = $pdo->prepare($delete_sql);
    $success = $stmt->execute([$enrollment_id]);

    if ($success) {
        header("Location: EnrollStudent.php?deleted=1");
    } else {
        header("Location: EnrollStudent.php?error=Failed to delete enrollment");
    }
} catch (Exception $e) {
    header("Location: EnrollStudent.php?error=" . urlencode($e->getMessage()));
}

exit();
