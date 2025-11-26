<?php
// Database configuration for VIVO United Football Manager
require_once __DIR__ . '/../database_config.php';

// Use the existing SQLite configuration
$pdo = DatabaseConfigSQLite::getConnection();

if (!$pdo) {
    die("Database connection failed: Unable to connect to SQLite database");
}
?>
