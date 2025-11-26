<?php
require_once 'includes/config.php';

function getPageTitle($page = '') {
    $title = APP_NAME;
    if ($page) {
        $title .= ' - ' . $page;
    }
    return $title;
}

function getCurrentPage() {
    $page = basename($_SERVER['PHP_SELF'], '.php');
    return $page === 'index' ? 'dashboard' : $page;
}

function formatDate($date, $format = 'M j, Y') {
    if (empty($date) || $date === '0000-00-00') {
        return 'Not set';
    }
    try {
        return date($format, strtotime($date));
    } catch (Exception $e) {
        return 'Invalid date';
    }
}

function formatTime($time, $format = 'g:i A') {
    if (empty($time) || $time === '00:00:00') {
        return 'Not set';
    }
    try {
        return date($format, strtotime($time));
    } catch (Exception $e) {
        return 'Invalid time';
    }
}

function getStatusBadge($status) {
    $classes = [
        'present' => 'badge-success',
        'absent' => 'badge-danger',
        'late' => 'badge-warning',
        'excused' => 'badge-secondary',
        'active' => 'badge-success',
        'inactive' => 'badge-secondary',
        'scheduled' => 'badge-info',
        'completed' => 'badge-success',
        'cancelled' => 'badge-danger'
    ];

    $class = $classes[$status] ?? 'badge-secondary';
    return "<span class=\"badge $class\">" . htmlspecialchars($status) . "</span>";
}

function alert($message, $type = 'info') {
    $validTypes = ['success', 'error', 'warning', 'info'];
    $type = in_array($type, $validTypes) ? $type : 'info';
    return "<div class=\"alert alert-$type\">" . htmlspecialchars($message) . "</div>";
}

if (!function_exists('redirect')) {
    function redirect($url) {
        if (!headers_sent()) {
            header("Location: $url");
            exit;
        } else {
            echo "<script>window.location.href='" . htmlspecialchars($url) . "';</script>";
            exit;
        }
    }
}

// Enhanced flash message functions are defined at the end of the file

// Database helper functions
// Note: get_db_connection() is defined in wp-config.php

// Security helper
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Form validation helpers
function validate_required($value, $fieldName) {
    if (empty(trim($value))) {
        return "$fieldName is required.";
    }
    return null;
}

function validate_email($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format.";
    }
    return null;
}

function validate_date($date) {
    if (empty($date)) return null;

    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        return "Invalid date format. Use YYYY-MM-DD.";
    }
    return null;
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// CSRF protection helpers
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validate_csrf_token')) {
    function validate_csrf_token($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($token)) return false;
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}

if (!function_exists('csrf_input_field')) {
    function csrf_input_field() {
        $t = generate_csrf_token();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($t) . '">';
    }
}

function validateRequired($fields, $data) {
    $errors = [];
    foreach ($fields as $field => $label) {
        if (empty($data[$field])) {
            $errors[] = "$label is required";
        }
    }
    return $errors;
}

function uploadFile($file, $allowedTypes = ['jpg', 'jpeg', 'png'], $maxSize = 2097152) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload failed'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $filename = uniqid() . '.' . $extension;
    $destination = UPLOAD_PATH . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'message' => 'Failed to save file'];
    }
}

// Simple authentication check
function checkLogin() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // For development with SQLite, auto-login
    if (!isset($_SESSION['logged_in'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = 'admin';
        $_SESSION['user_role'] = 'admin';
    }
    
    return $_SESSION['logged_in'] ?? false;
}

// Flash message helpers
if (!function_exists('set_flash')) {
    function set_flash($message, $type = 'success') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'] = ['m' => $message, 't' => $type];
    }
}

if (!function_exists('get_flash')) {
    function get_flash() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!empty($_SESSION['flash'])) {
            $f = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $f;
        }
        return null;
    }
}
