<?php
/**
 * VIVO Football Manager - Configuration Manager
 * Automatically loads production or development configuration
 */

// Check if production configuration exists and use it
if (file_exists(dirname(__FILE__) . '/config_production.php') && 
    (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1')) {
    // Production environment detected
    require_once dirname(__FILE__) . '/config_production.php';
} else {
    // Development environment
    define('APP_NAME', 'VIVO Football Manager (Development)');
    define('APP_VERSION', '1.0.0-dev');
    define('BASE_PATH', ''); // Empty for root directory
    
    // Development error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Timezone
    date_default_timezone_set('UTC');
    
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Development constants
    define('PRODUCTION_MODE', false);
    define('CACHE_ENABLED', false);
    define('DEBUG_MODE', true);
}
?>
