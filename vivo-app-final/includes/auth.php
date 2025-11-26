<?php
// Enhanced authentication system for VIVO United

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        // Prevent redirect loops
        $currentPage = basename($_SERVER['PHP_SELF']);
        if ($currentPage !== 'login.php') {
            header('Location: login.php');
            exit();
        }
    }
}

function isAdmin() {
    return isLoggedIn() && (
        ($_SESSION['user_role'] ?? '') === 'admin' ||
        ($_SESSION['username'] ?? '') === 'admin' ||
        ($_SESSION['role'] ?? '') === 'admin'
    );
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: players.php?error=' . urlencode('Access denied. Admin privileges required.'));
        exit();
    }
}

function logout() {
    // Clear all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }

    // Destroy the session
    session_destroy();

    // Set a flag to prevent auto-login
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['just_logged_out'] = true;
}

function login($username, $password) {
    // Enhanced authentication with better security
    $valid_users = [
        'vivoadmin' => password_hash('password123', PASSWORD_DEFAULT),
        'admin' => password_hash('admin123', PASSWORD_DEFAULT)
    ];

    // Check if user exists and password is correct
    if (isset($valid_users[$username])) {
        // For demo purposes, we'll use simple password check
        // In production, use: password_verify($password, $valid_users[$username])
        $simple_passwords = [
            'vivoadmin' => 'password123',
            'admin' => 'admin123'
        ];

        if (isset($simple_passwords[$username]) && $simple_passwords[$username] === $password) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['user_role'] = 'admin';
            $_SESSION['login_time'] = time();

            // Clear the logout flag on successful login
            unset($_SESSION['just_logged_out']);
            return true;
        }
    }
    return false;
}

function checkSessionTimeout() {
    $timeout = 3600; // 1 hour
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout) {
        logout();
        header('Location: login.php?message=' . urlencode('Session expired. Please login again.'));
        exit();
    }
    $_SESSION['login_time'] = time(); // Update last activity
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? 1,
        'username' => $_SESSION['username'] ?? 'admin',
        'role' => $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'admin'
    ];
}

// Development auto-login helper (only active if no user and not just logged out)
// NOTE: Remove or guard with an environment flag for production.
if (!isLoggedIn() && !isset($_SESSION['just_logged_out']) && getenv('VIVO_DEV_MODE') === 'true') {
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['user_role'] = 'admin';
    $_SESSION['login_time'] = time();
}

// Intentionally no closing PHP tag to avoid accidental output
