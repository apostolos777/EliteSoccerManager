<?php
/**
 * VIVO United Football Manager - Authentication with MySQL Users Table
 * Compatible with existing users table in ehostcoz_wp995
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'first_name' => $_SESSION['first_name'] ?? null,
        'last_name' => $_SESSION['last_name'] ?? null,
        'role' => $_SESSION['role'] ?? 'viewer',
        'full_name' => ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')
    ];
}

/**
 * Login user with username/email and password
 */
function loginUser($username_or_email, $password) {
    try {
        $db = DatabaseConfig::getConnection();
        
        // Find user by username or email
        $stmt = $db->prepare("
            SELECT id, username, email, password_hash, first_name, last_name, role, status 
            FROM users 
            WHERE (username = ? OR email = ?) AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$username_or_email, $username_or_email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found or inactive'];
        }
        
        // Check password
        $password_valid = false;
        
        // Check if it's a hashed password (starts with $2y$)
        if (strpos($user['password_hash'], '$2y$') === 0) {
            $password_valid = password_verify($password, $user['password_hash']);
        } 
        // Check for simple MD5 hash (for superadmin)
        elseif (strlen($user['password_hash']) === 32) {
            $password_valid = (md5($password) === $user['password_hash']);
        }
        // Check for plain text (for vivoadmin)
        else {
            $password_valid = ($password === $user['password_hash']);
        }
        
        if (!$password_valid) {
            return ['success' => false, 'message' => 'Invalid password'];
        }
        
        // Set session variables
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        // Update last login time
        $update_stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $update_stmt->execute([$user['id']]);
        
        return [
            'success' => true, 
            'message' => 'Login successful',
            'user' => $user
        ];
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Login system error'];
    }
}

/**
 * Logout user
 */
function logoutUser() {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    return true;
}

/**
 * Check user role/permission
 */
function userCan($capability) {
    $user = getCurrentUser();
    if (!$user) return false;
    
    $role = $user['role'];
    
    // Define capabilities for each role
    $capabilities = [
        'super_administrator' => ['manage_all', 'manage_users', 'manage_teams', 'manage_players', 'manage_events', 'view_reports'],
        'administrator' => ['manage_teams', 'manage_players', 'manage_events', 'view_reports'],
        'coach' => ['manage_players', 'manage_events', 'view_reports'],
        'staff' => ['view_reports', 'manage_attendance'],
        'viewer' => ['view_reports']
    ];
    
    return isset($capabilities[$role]) && in_array($capability, $capabilities[$role]);
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Require specific capability
 */
function requireCapability($capability) {
    requireLogin();
    if (!userCan($capability)) {
        wp_die('You do not have permission to access this page.', 'Access Denied', ['response' => 403]);
    }
}

/**
 * Auto-login for development (if enabled)
 */
function checkAutoLogin() {
    // Only in development mode and if not already logged in
    if (defined('WP_DEBUG') && WP_DEBUG && !isLoggedIn()) {
        // Try to find the vivoadmin user for auto-login
        try {
            $db = DatabaseConfig::getConnection();
            $stmt = $db->prepare("SELECT id, username, email, first_name, last_name, role FROM users WHERE username = 'vivoadmin' AND status = 'active' LIMIT 1");
            $stmt->execute();
            $user = $stmt->fetch();
            
            if ($user) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();
                $_SESSION['auto_login'] = true;
            }
        } catch (Exception $e) {
            // Silent fail for auto-login
        }
    }
}

// Initialize auto-login check (only in development)
if (defined('WP_DEBUG') && WP_DEBUG) {
    checkAutoLogin();
}
?>
