<?php
// includes/auth.php
// ------------------------------------------------------------
// Secure Session Management & Role-Based Access Control Helper
// ------------------------------------------------------------

if (session_status() === PHP_SESSION_NONE) {
    // Configure session cookie parameters for basic security
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    // Set secure flag if accessing over HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

/**
 * Checks if the user is currently logged in.
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Gets the current logged in user's role.
 * @return string|null
 */
function get_user_role() {
    return $_SESSION['role'] ?? null;
}

/**
 * Gets the current logged in user's full name.
 * @return string|null
 */
function get_user_name() {
    return $_SESSION['user_name'] ?? null;
}

/**
 * Gets the current logged in user's ID.
 * @return int|null
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Redirects to the login page if the user is not logged in.
 * Saves current URL for post-login redirect.
 */
function require_login() {
    global $base_path;
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: " . $base_path . "auth/login.php");
        exit;
    }
}

/**
 * Enforces role-based access control.
 * Redirects unauthorized users to the home page with an error.
 * @param array|string $allowed_roles Single role string or array of allowed roles
 */
function require_role($allowed_roles) {
    global $base_path;
    
    // Ensure the user is logged in first
    require_login();
    
    $user_role = get_user_role();
    $allowed = is_array($allowed_roles) ? $allowed_roles : [$allowed_roles];
    
    if (!in_array($user_role, $allowed)) {
        // Redirect unauthorized users to index.php with error message
        header("Location: " . $base_path . "index.php?error=unauthorized");
        exit;
    }
}
?>
