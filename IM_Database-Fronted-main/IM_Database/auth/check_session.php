<?php
/**
 * Check Session
 * Returns user session information
 */

header('Content-Type: application/json');

session_start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $user = [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'role_id' => $_SESSION['role_id'] ?? null,
        'account_code' => $_SESSION['account_code'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'profile' => $_SESSION['profile'] ?? null
    ];
    
    echo json_encode([
        'logged_in' => true,
        'user' => $user
    ]);
} else {
    echo json_encode([
        'logged_in' => false,
        'user' => null
    ]);
}
