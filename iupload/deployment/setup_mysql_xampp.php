<?php
/**
 * VIVO United Football Manager - MySQL Database Setup for XAMPP
 * Run this script to create the MySQL database and tables
 */

echo "<h1>🚀 VIVO United - MySQL Database Setup</h1>";
echo "<hr>";

try {
    // Connect to MySQL server (without database)
    $host = 'localhost';
    $username = 'root';
    $password = ''; // XAMPP default
    
    echo "<h3>📡 Connecting to MySQL server...</h3>";
    $pdo = new PDO("mysql:host={$host}", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to MySQL server successfully!<br><br>";
    
    // Create database
    echo "<h3>🗄️ Creating database 'vivo_football'...</h3>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS vivo_football CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database 'vivo_football' created successfully!<br><br>";
    
    // Select the database
    $pdo->exec("USE vivo_football");
    echo "✅ Selected database 'vivo_football'<br><br>";
    
    // Test the database factory
    echo "<h3>🧪 Testing Database Factory...</h3>";
    require_once 'database_factory.php';
    
    $db = DatabaseFactory::getConnection();
    echo "✅ Database Factory connection successful!<br><br>";
    
    // Test tables
    echo "<h3>📋 Checking tables...</h3>";
    $tables = ['teams', 'players', 'events', 'attendance'];
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "✅ Table '$table' exists with $count records<br>";
    }
    
    echo "<br><h3>🎉 MySQL Database Setup Complete!</h3>";
    echo "<p><strong>Database:</strong> vivo_football</p>";
    echo "<p><strong>Host:</strong> localhost</p>";
    echo "<p><strong>Username:</strong> root</p>";
    echo "<p><strong>Password:</strong> (empty)</p>";
    echo "<br>";
    echo "<a href='index.php' style='background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Launch VIVO United App</a>";
    echo "<br><br>";
    echo "<a href='dashboard.php' style='background: #059669; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>📊 Go to Dashboard</a>";
    
} catch (Exception $e) {
    echo "❌ <strong>Error:</strong> " . $e->getMessage() . "<br><br>";
    echo "<h3>💡 Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Make sure XAMPP is running</li>";
    echo "<li>Make sure MySQL service is started in XAMPP</li>";
    echo "<li>Check that port 3306 is not blocked</li>";
    echo "</ul>";
}
?>
