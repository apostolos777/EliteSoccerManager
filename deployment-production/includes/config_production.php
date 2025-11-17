<?php
/**
 * VIVO Football Manager - Production Configuration
 * Use this configuration for production deployments
 */

// Application details
define('APP_NAME', 'VIVO Football Manager');
define('APP_VERSION', '1.0.0');
define('BASE_PATH', ''); // Empty for root directory

// Production error handling - DISABLE error display
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../logs/error.log');

// Security settings
// Only change session ini settings if a session has not already been started.
// Changing these while a session is active produces warnings under the built-in
// server and some environments.
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    // Keep secure cookie enabled where HTTPS is used; don't force it in CLI/dev
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    ini_set('session.use_strict_mode', 1);
    // Some PHP versions don't support cookie_samesite via ini_set; guard it.
    if (PHP_VERSION_ID >= 70300) {
        ini_set('session.cookie_samesite', 'Strict');
    }
} else {
    // Session already active — skip modifying session ini settings to avoid warnings
}

// Performance settings
ini_set('zlib.output_compression', 1);
// OPcache cannot always be toggled at runtime (and may emit warnings). Only
// attempt to tweak opcache-related settings if opcache is available and not
// explicitly disabled. Avoid forcing opcache.enable here.
if (extension_loaded('Zend OPcache') || extension_loaded('opcache')) {
    $opcacheEnabled = ini_get('opcache.enable');
    if ($opcacheEnabled !== false && $opcacheEnabled !== '0') {
        // Safe to set runtime opcache tuning options
        ini_set('opcache.memory_consumption', 128);
        ini_set('opcache.interned_strings_buffer', 8);
        ini_set('opcache.max_accelerated_files', 4000);
        ini_set('opcache.revalidate_freq', 60);
    }
}

// Timezone
date_default_timezone_set('UTC');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define production database path (relative to includes directory)
// Standardize on `database.db` which is managed by DatabaseFactory
define('DB_PATH', dirname(__FILE__) . '/../database.db');

// Production constants
define('PRODUCTION_MODE', true);
define('CACHE_ENABLED', true);
define('DEBUG_MODE', false);
?>
