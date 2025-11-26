<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// For development, auto-login
if (!isLoggedIn()) {
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
}
?>
