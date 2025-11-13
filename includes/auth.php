<?php
/**
 * Authentication and authorization helper functions
 * Provides functions for checking login status and enforcing access control
 */

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Require user to be logged in, redirect to login page if not
 * @return void
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require user to have specific role, return 403 if not
 * @param string $role Required role (e.g., 'admin', 'owner')
 * @return void
 */
function require_role(string $role) {
    if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== $role) {
        header('HTTP/1.1 403 Forbidden');
        echo "Brak dostępu.";
        exit;
    }
}

/**
 * Check if current user is admin
 * @return bool True if admin, false otherwise
 */
function is_admin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Helper function to get user email by ID
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @return string User email or empty string if not found
 */
function get_user_email($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id'=>$user_id]);
    $r = $stmt->fetch();
    return $r['email'] ?? '';
}