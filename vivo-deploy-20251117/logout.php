<?php
// Logout functionality for VIVO United Football Manager
// Updated: July 26, 2025

require_once 'includes/auth.php';

// Destroy the session
logout();

// Redirect to login page
header('Location: login.php?logged_out=1');
exit();
?>
