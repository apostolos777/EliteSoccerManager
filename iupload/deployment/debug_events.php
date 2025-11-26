<?php
// Test script to debug events.php issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>VIVO App Debug Test</h1>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_NAME'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Current Directory: " . getcwd() . "</p>";

echo "<h2>File Existence Check</h2>";
$files_to_check = [
    'includes/color_system.php',
    'wp-config.php', 
    'includes/auth.php',
    'functions.php',
    'database.db',
    'database.db'
];

foreach($files_to_check as $file) {
    $exists = file_exists($file);
    $readable = $exists ? is_readable($file) : false;
    echo "<p>$file: " . ($exists ? "EXISTS" : "MISSING") . 
         ($readable ? " (readable)" : " (not readable)") . "</p>";
}

echo "<h2>Include Test</h2>";
try {
    if (file_exists('includes/color_system.php')) {
        require_once 'includes/color_system.php';
        echo "<p>✓ color_system.php loaded</p>";
    } else {
        echo "<p>✗ color_system.php missing</p>";
    }
} catch (Exception $e) {
    echo "<p>✗ Error loading color_system.php: " . $e->getMessage() . "</p>";
}

try {
    if (file_exists('wp-config.php')) {
        require_once 'wp-config.php';
        echo "<p>✓ wp-config.php loaded</p>";
        
        // Test database connection
        $db = get_db_connection();
        echo "<p>✓ Database connection successful</p>";
        
        // Test events table
        $stmt = $db->prepare("SELECT COUNT(*) FROM events");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        echo "<p>✓ Events table accessible, found $count events</p>";
        
    } else {
        echo "<p>✗ wp-config.php missing</p>";
    }
} catch (Exception $e) {
    echo "<p>✗ Error with wp-config.php or database: " . $e->getMessage() . "</p>";
}

try {
    if (file_exists('includes/auth.php')) {
        require_once 'includes/auth.php';
        echo "<p>✓ auth.php loaded</p>";
    } else {
        echo "<p>✗ auth.php missing</p>";
    }
} catch (Exception $e) {
    echo "<p>✗ Error loading auth.php: " . $e->getMessage() . "</p>";
}

echo "<h2>Session Test</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";

if (function_exists('isLoggedIn')) {
    $loggedIn = isLoggedIn();
    echo "<p>Logged in: " . ($loggedIn ? "YES" : "NO") . "</p>";
    if (!$loggedIn) {
        echo "<p><strong>Note: User not logged in - events.php will redirect to login.php</strong></p>";
    }
} else {
    echo "<p>isLoggedIn function not available</p>";
}

echo "<h2>Test Complete</h2>";
echo "<p><a href='events.php'>Try events.php</a></p>";
echo "<p><a href='login.php'>Go to login</a></p>";
?>
