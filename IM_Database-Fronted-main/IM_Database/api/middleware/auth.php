<?php
/**
 * Authentication Middleware
 * Validates session and role-based access
 */

/**
 * Check if user is authenticated
 */
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, 'Authentication required', null, 401);
    }
}

/**
 * Check if user has specific role
 * @param string $role
 */
function requireRole($role) {
    requireAuth();
    
    if ($_SESSION['role'] !== $role) {
        sendResponse(false, 'Access denied. Insufficient permissions', null, 403);
    }
}

/**
 * Check if user has one of multiple roles
 * @param array $roles
 */
function requireAnyRole($roles) {
    requireAuth();
    
    if (!in_array($_SESSION['role'], $roles)) {
        sendResponse(false, 'Access denied. Insufficient permissions', null, 403);
    }
}
