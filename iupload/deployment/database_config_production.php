<?php
/**
 * Production Database Configuration for VIVO United Football Manager
 * This file provides database connectivity when wp-includes is not available
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

/**
 * Simple Database Connection Class for Production
 */
class DatabaseConfigProduction {
    private static $connection = null;
    
    public static function getConnection() {
        if (self::$connection === null) {
            self::$connection = self::createConnection();
        }
        return self::$connection;
    }
    
    private static function createConnection() {
        try {
            // Check for wp-config.php constants
            if (defined('DB_TYPE') && DB_TYPE === 'mysql') {
                // MySQL connection - check if all required constants are defined
                if (!defined('DB_USER') || !defined('DB_PASSWORD')) {
                    throw new Exception('MySQL configuration incomplete. Please define DB_USER and DB_PASSWORD in wp-config.php');
                }
                
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    defined('DB_HOST') ? DB_HOST : 'localhost',
                    defined('DB_NAME') ? DB_NAME : 'ehostcoz_wp995',
                    defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4'
                );
                
                $connection = new PDO($dsn, DB_USER, DB_PASSWORD, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]);
                
                // Set timezone for MySQL
                $connection->exec("SET time_zone = '+02:00'"); // South Africa timezone
                
            } else {
                // SQLite connection (fallback)
                $db_file = defined('DB_FILE') ? DB_FILE : ABSPATH . 'database.db';
                
                // Ensure directory exists
                $db_dir = dirname($db_file);
                if (!is_dir($db_dir)) {
                    mkdir($db_dir, 0755, true);
                }
                
                $connection = new PDO("sqlite:" . $db_file, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                
                // Create tables if they don't exist (SQLite only)
                self::createTables($connection);
            }
            
            return $connection;
            
        } catch (PDOException $e) {
            // In production, log error and show user-friendly message
            error_log("Database connection error: " . $e->getMessage());
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            } else {
                throw new Exception("Database connection failed. Please check your configuration.");
            }
        }
    }
    
    private static function createTables($db) {
        $tables = [
            "CREATE TABLE IF NOT EXISTS teams (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                age_group TEXT,
                coach TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS players (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                surname TEXT NOT NULL,
                age INTEGER,
                position TEXT,
                team_id INTEGER,
                jersey_number INTEGER,
                is_active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (team_id) REFERENCES teams(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                event_type TEXT NOT NULL,
                date DATE NOT NULL,
                time TIME,
                location TEXT,
                team_id INTEGER,
                age_groups TEXT,
                description TEXT,
                status TEXT DEFAULT 'Scheduled',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (team_id) REFERENCES teams(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS attendance (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_id INTEGER NOT NULL,
                player_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT 'pending',
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (event_id) REFERENCES events(id),
                FOREIGN KEY (player_id) REFERENCES players(id),
                UNIQUE(event_id, player_id)
            )"
        ];
        
        foreach ($tables as $sql) {
            try {
                $db->exec($sql);
            } catch (PDOException $e) {
                error_log("Table creation error: " . $e->getMessage());
            }
        }
    }
}

// WordPress-style compatibility functions
if (!function_exists('wp_die')) {
    function wp_die($message, $title = 'Error', $args = array()) {
        http_response_code(500);
        echo "<!DOCTYPE html>
<html>
<head>
    <title>$title</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f1f1f1; }
        .error-message { background: #fff; border-left: 4px solid #dc3232; padding: 20px; margin: 20px 0; }
        .error-message h1 { color: #dc3232; margin-top: 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; border-radius: 3px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class='error-message'>
        <h1>$title</h1>
        <p>$message</p>
        <a href='/' class='btn'>Go Back</a>
        <a href='test_database.php' class='btn'>Test Database</a>
    </div>
</body>
</html>";
        exit;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
?>
