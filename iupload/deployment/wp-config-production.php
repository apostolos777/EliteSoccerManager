<?php
/**
 * VIVO United Football Manager - PRODUCTION Configuration
 * This file should be used on the live server
 */

// Error handling for production
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Database Configuration - Production MySQL
// THESE NEED TO BE UPDATED WITH YOUR ACTUAL DATABASE CREDENTIALS
define('DB_HOST', 'localhost'); // or your MySQL server host
define('DB_NAME', 'vivoapp_database'); // your actual database name
define('DB_USER', 'vivoapp_user'); // your database username
define('DB_PASSWORD', 'your_password_here'); // your database password
define('DB_CHARSET', 'utf8mb4');

// Use MySQL for production (set to false)
define('USE_SQLITE', false);

// Fallback to SQLite if MySQL credentials not configured
if (DB_PASSWORD === 'your_password_here') {
    define('USE_SQLITE', true);
    define('SQLITE_DB_PATH', __DIR__ . '/database.db');
}

// Security settings for production
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // HTTPS
ini_set('session.use_strict_mode', 1);

// Global database connection variable
$wpdb = null;

/**
 * Get database connection - WordPress style
 */
if (!function_exists('get_db_connection')) {
function get_db_connection() {
    global $wpdb;

    if ($wpdb === null) {
        try {
            if (USE_SQLITE) {
                // SQLite connection (fallback)
                if (!file_exists(SQLITE_DB_PATH)) {
                    wp_die('SQLite database file not found: ' . SQLITE_DB_PATH);
                }
                
                $wpdb = new PDO(
                    'sqlite:' . SQLITE_DB_PATH,
                    null,
                    null,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    )
                );
                
                // Enable foreign keys for SQLite
                $wpdb->exec('PRAGMA foreign_keys = ON');
                
            } else {
                // MySQL connection (production)
                $wpdb = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
                    DB_USER,
                    DB_PASSWORD,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    )
                );
            }

        } catch (PDOException $e) {
            // More detailed error for troubleshooting
            $error_details = "Database connection failed.\n";
            $error_details .= "Error: " . $e->getMessage() . "\n";
            $error_details .= "Using SQLite: " . (USE_SQLITE ? 'Yes' : 'No') . "\n";
            
            if (USE_SQLITE) {
                $error_details .= "SQLite Path: " . SQLITE_DB_PATH . "\n";
                $error_details .= "File exists: " . (file_exists(SQLITE_DB_PATH) ? 'Yes' : 'No') . "\n";
            } else {
                $error_details .= "MySQL Host: " . DB_HOST . "\n";
                $error_details .= "Database: " . DB_NAME . "\n";
                $error_details .= "Username: " . DB_USER . "\n";
            }
            
            wp_die($error_details);
        }
    }
    
    return $wpdb;
}
}

/**
 * WordPress-style error handler
 */
if (!function_exists('wp_die')) {
    function wp_die($message) {
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Database Error - VIVO United</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
                    margin: 0; padding: 20px; background: #f1f1f1; 
                }
                .error { 
                    background: #fff; 
                    border-left: 4px solid #dc3232; 
                    padding: 20px; 
                    margin: 20px auto;
                    max-width: 600px;
                    border-radius: 4px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                h1 { color: #23282d; margin-top: 0; }
                pre { background: #f8f8f8; padding: 10px; border-radius: 4px; overflow-x: auto; }
                .btn { 
                    display: inline-block; 
                    padding: 8px 16px; 
                    background: #0073aa; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 4px; 
                    margin-top: 10px;
                }
            </style>
        </head>
        <body>
            <div class="error">
                <h1>🔧 Database Connection Error</h1>
                <pre>' . htmlspecialchars($message) . '</pre>
                
                <h3>Troubleshooting Steps:</h3>
                <ol>
                    <li><strong>Check database credentials</strong> in wp-config.php</li>
                    <li><strong>Verify database server</strong> is running</li>
                    <li><strong>Ensure database exists</strong> and user has permissions</li>
                    <li><strong>Check file permissions</strong> for SQLite database</li>
                </ol>
                
                <p><strong>Need help?</strong> 
                   <a href="debug_events.php" class="btn">Run Diagnostic Test</a>
                </p>
            </div>
        </body>
        </html>';
        exit;
    }
}

/**
 * Test database connection
 */
if (!function_exists('test_db_connection')) {
    function test_db_connection() {
        try {
            $db = get_db_connection();
            
            // Test basic query
            $result = $db->query("SELECT 1 as test");
            $row = $result->fetch();
            
            return $row['test'] === 1;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Auto-start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
