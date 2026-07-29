<?php
/**
 * API Authentication Helper
 * 
 * Provides role-based access control for API endpoints.
 * Ensures only authenticated admin users can perform write operations.
 */

/**
 * Require admin role for API access
 * 
 * Terminates execution with 403 error if user is not admin.
 * Should be called at the top of update/delete API endpoints.
 * 
 * @return void Exits with JSON error if not admin
 */
function requireAdmin() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user has admin role
    $role = $_SESSION['role'] ?? 'open';
    
    if ($role !== 'admin') {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Akses ditolak. Operasi ini memerlukan role Admin.',
            'code' => 'FORBIDDEN'
        ]);
        exit;
    }
}

/**
 * Get current user role
 * 
 * @return string 'admin' or 'open'
 */
function getApiRole() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['role'] ?? 'open';
}

/**
 * Get current admin name (if logged in as admin)
 * 
 * @return string|null Admin name or null if not admin
 */
function getApiAdminName() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $role = $_SESSION['role'] ?? 'open';
    if ($role === 'admin') {
        return $_SESSION['admin_name'] ?? 'Admin';
    }
    return null;
}
