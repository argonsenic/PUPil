<?php
/**
 * Logout API Endpoint
 * POST /api/auth/logout.php
 */

require_once __DIR__ . '/../config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Method not allowed', null, 405);
}

// Destroy session
session_destroy();

sendResponse(true, 'Logout successful');
