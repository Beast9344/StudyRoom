<?php
session_start();

/**
 * Check if the user has the required role.
 *
 * @param string|array $role The required role or an array of allowed roles.
 * @param string $redirectUrl The URL to redirect unauthorized users (default: login.php).
 */
function checkRole($role, $redirectUrl = 'login.php') {
    // Check if the user is logged in
    if (!isset($_SESSION['user'])) {
        $_SESSION['error'] = "You must be logged in to access this page.";
        header("Location: $redirectUrl");
        exit();
    }

    // Convert single role to an array for flexibility
    $allowedRoles = is_array($role) ? $role : [$role];

    // Check if the user's role is allowed
    if (!in_array($_SESSION['user']['role'], $allowedRoles)) {
        $_SESSION['error'] = "You do not have permission to access this page.";
        header("Location: $redirectUrl");
        exit();
    }
}
?>