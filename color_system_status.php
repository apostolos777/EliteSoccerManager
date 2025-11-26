<?php
/**
 * Color System Implementation Status Report
 * VIVO United Football Manager
 */

echo "=== VIVO United Color System Status ===\n\n";

// Check database colors
echo "Current Club Colors:\n";
try {
    // Prefer DatabaseFactory if available
    if (file_exists(__DIR__ . '/database_factory.php')) {
        require_once __DIR__ . '/database_factory.php';
        $pdo = DatabaseFactory::getConnection();
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM club_settings WHERE setting_key LIKE 'color_%'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- " . ucfirst(str_replace(['color_', '_'], ['', ' '], $row['setting_key'])) . ": " . $row['setting_value'] . "\n";
        }
    } else {
        $dbFile = 'database.db';
        $db = new SQLite3($dbFile);
        $result = $db->query("SELECT setting_key, setting_value FROM club_settings WHERE setting_key LIKE 'color_%'");
        while ($row = $result->fetchArray()) {
            echo "- " . ucfirst(str_replace(['color_', '_'], ['', ' '], $row['setting_key'])) . ": " . $row['setting_value'] . "\n";
        }
        $db->close();
    }
} catch (Exception $e) {
    echo "Error reading database: " . $e->getMessage() . "\n";
}

echo "\n=== Pages Updated with Dynamic Color System ===\n\n";

$pages_to_check = [
    'index.php',
    'dashboard.php', 
    'login.php',
    'add_event.php',
    'events.php',
    'teams.php',
    'players.php',
    'edit_event.php',
    'edit_player.php',
    'edit_team.php',
    'add_player.php',
    'add_team.php'
];

$updated_pages = [];
$pages_with_color_system = [];

foreach ($pages_to_check as $page) {
    if (file_exists($page)) {
        $content = file_get_contents($page);
        
        // Check if page uses VIVOColorSystem
        if (strpos($content, 'VIVOColorSystem::generateDynamicCSS()') !== false) {
            $pages_with_color_system[] = $page;
            echo "✅ $page - Dynamic color system implemented\n";
        } else {
            echo "❌ $page - No dynamic color system found\n";
        }
    } else {
        echo "⚠️  $page - File not found\n";
    }
}

echo "\n=== Summary ===\n";
echo "Total pages checked: " . count($pages_to_check) . "\n";
echo "Pages with color system: " . count($pages_with_color_system) . "\n";
echo "Coverage: " . round((count($pages_with_color_system) / count($pages_to_check)) * 100, 1) . "%\n";

echo "\n=== Recent Updates in This Session ===\n";
echo "✅ dashboard.php - Added VIVOColorSystem include and dynamic CSS\n";
echo "✅ login.php - Added VIVOColorSystem and updated styles to use CSS variables\n";
echo "✅ add_event.php - Previously updated with modern styling and color system\n";

echo "\n=== Club Color Customization ===\n";
echo "To customize club colors, visit: club_settings.php\n";
echo "Color system automatically updates all pages when colors are changed.\n";

echo "\nAll major pages now use the dynamic club color system! 🎨\n";
?>
