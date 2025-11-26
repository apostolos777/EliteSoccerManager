<?php
/**
 * VIVO United Football Manager
 * WordPress-style configuration and connection
 */

// Database Configuration - Local SQLite
define('DB_HOST', 'localhost');
define('DB_NAME', 'vivo_football');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_CHARSET', 'utf8mb4');
define('USE_SQLITE', true);
define('SQLITE_DB_PATH', __DIR__ . '/database.db');

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
                // SQLite connection
                $wpdb = new PDO(
                    'sqlite:' . SQLITE_DB_PATH,
                    null,
                    null,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    )
                );
            } else {
                // MySQL connection
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

            // Enable foreign keys for SQLite
            if (USE_SQLITE) {
                $wpdb->exec('PRAGMA foreign_keys = ON');
            }

        } catch (PDOException $e) {
            wp_die('Database connection failed: ' . $e->getMessage());
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
            <title>Database Error</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 50px; }
                .error { background: #fff; border-left: 4px solid #dc3232; padding: 20px; }
            </style>
        </head>
        <body>
            <div class="error">
                <h1>Database Connection Error</h1>
                <p>' . htmlspecialchars($message) . '</p>
                <p><strong>Please check:</strong></p>
                <ul>
                    <li>Database credentials are correct</li>
                    <li>Database server is running</li>
                    <li>Database exists</li>
                </ul>
            </div>
        </body>
        </html>';
        exit;
    }
}

/**
 * WordPress-style query function
 */
function wp_query($sql, $params = array()) {
    $db = get_db_connection();
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log('Database query error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get single row
 */
function wp_get_row($sql, $params = array()) {
    $stmt = wp_query($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

/**
 * Get multiple rows
 */
function wp_get_results($sql, $params = array()) {
    $stmt = wp_query($sql, $params);
    return $stmt ? $stmt->fetchAll() : array();
}

/**
 * Get single value
 */
function wp_get_var($sql, $params = array()) {
    $stmt = wp_query($sql, $params);
    if ($stmt) {
        $row = $stmt->fetch(PDO::FETCH_NUM);
        return $row ? $row[0] : null;
    }
    return null;
}

/**
 * Get flash message from session
 */
if (!function_exists('get_flash')) {
    function get_flash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}

/**
 * Set flash message in session
 */
if (!function_exists('set_flash')) {
    function set_flash($message, $type = 'info') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    }
}

// Initialize connection
get_db_connection();
?>
