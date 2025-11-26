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
    return date($format, strtotime($date));
}

function formatTime($time, $format = 'g:i A') {
    if (empty($time) || $time === '00:00:00') {
        return 'Not set';
    }
    return date($format, strtotime($time));
}

function getStatusBadge($status) {
    $classes = [
        'present' => 'badge-success',
        'absent' => 'badge-danger',
        'late' => 'badge-warning',
        'excused' => 'badge-secondary'
    ];
    
    $class = $classes[$status] ?? 'badge-secondary';
    return "<span class=\"badge $class\">$status</span>";
}

function alert($message, $type = 'info') {
    return "<div class=\"alert alert-$type\">$message</div>";
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
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
?>
