<?php
require_once 'database_factory.php';

if (!isset($_GET['setup']) || $_GET['setup'] !== 'vivo-colors-2025') {
    die('Access denied. Use: ?setup=vivo-colors-2025');
}

echo "<h1>VIVO United - Club Colors Setup</h1><pre>";

try {
    $db = DatabaseFactory::getConnection();
    echo "✅ Database connection successful\n";
    
    $createTableSQL = "CREATE TABLE IF NOT EXISTS club_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT UNIQUE NOT NULL,
        setting_value TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    $db->exec($createTableSQL);
    echo "✅ Club settings table created\n";
    
    $defaultSettings = [
        'color_primary' => '#dc2626',
        'color_secondary' => '#991b1b', 
        'color_accent' => '#fef2f2',
        'color_success' => '#059669',
        'color_warning' => '#d97706',
        'color_danger' => '#dc2626',
        'club_name' => 'VIVO United',
        'club_logo' => ''
    ];
    
    $stmt = $db->prepare("INSERT OR REPLACE INTO club_settings (setting_key, setting_value) VALUES (?, ?)");
    
    foreach ($defaultSettings as $key => $value) {
        $stmt->execute([$key, $value]);
        echo "✅ Set $key = $value\n";
    }
    
    echo "\n🎨 Setup complete! Test at player_profile.php?id=8\n";
    echo "⚠️ Delete this file after running!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
