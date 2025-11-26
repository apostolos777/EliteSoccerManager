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
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Enable for HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Performance settings
ini_set('zlib.output_compression', 1);
ini_set('opcache.enable', 1);
ini_set('opcache.memory_consumption', 128);
ini_set('opcache.interned_strings_buffer', 8);
ini_set('opcache.max_accelerated_files', 4000);
ini_set('opcache.revalidate_freq', 60);

// Timezone
date_default_timezone_set('UTC');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define production database path (relative to includes directory)
define('DB_PATH', dirname(__FILE__) . '/../vivo_football.db');

// Production constants
define('PRODUCTION_MODE', true);
define('CACHE_ENABLED', true);
define('DEBUG_MODE', false);
?>
