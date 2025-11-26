<?php
/**
 * VIVO United - Create Club Settings Table
 * Create the missing club_settings table with default colors
 */

require_once 'database_factory.php';

echo "<h1>VIVO United - Club Settings Setup</h1>";
echo "<pre>";

try {
    $db = DatabaseFactory::getConnection();
    echo "✅ Database connection successful\n";
    
    // Create club_settings table
    $createTableSQL = "CREATE TABLE IF NOT EXISTS club_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT UNIQUE NOT NULL,
        setting_value TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    $db->exec($createTableSQL);
    echo "✅ Club settings table created\n";
    
    // Insert default color settings
    $defaultSettings = [
        'color_primary' => '#dc2626',
        'color_secondary' => '#991b1b',
        'color_accent' => '#fef2f2',
        'color_success' => '#059669',
        'color_warning' => '#d97706',
        'color_danger' => '#dc2626',
        'club_name' => 'VIVO United',
        'club_description' => 'Elite Football Club'
    ];
    
    foreach ($defaultSettings as $key => $value) {
        $stmt = $db->prepare("INSERT OR IGNORE INTO club_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
    
    echo "✅ Default settings inserted\n";
    
    // Verify the settings
    echo "\n📋 Club settings created:\n";
    $settingsQuery = $db->query("SELECT setting_key, setting_value FROM club_settings ORDER BY setting_key");
    foreach ($settingsQuery->fetchAll(PDO::FETCH_ASSOC) as $setting) {
        echo "  {$setting['setting_key']}: {$setting['setting_value']}\n";
    }
    
    echo "\n🎉 Club settings table setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
