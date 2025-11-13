<?php
function checkAuthentication() {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        // Redirect to login page if not authenticated
        header("Location: index.php");
        exit;
    }
}

function checkRole($allowedRoles) {
    // Check if user has the required role
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        // Log unauthorized access attempt
        log_error("Unauthorized access attempt", [
            'user_id' => $_SESSION['user_id'] ?? 'unknown',
            'required_roles' => $allowedRoles,
            'user_role' => $_SESSION['role'] ?? 'none'
        ]);
        
        // Redirect to dashboard if not authorized
        header("Location: dashboard.php");
        exit;
    }
}
?>