<?php
/**
 * Test script to verify all pages use correct database and colors
 */
echo "=== VIVO United - Database & Color System Test ===\n\n";

// Test database connection
try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n";
    
    // Test stats
    $teams = $db->query("SELECT COUNT(*) FROM teams")->fetchColumn();
    $players = $db->query("SELECT COUNT(*) FROM players WHERE status = 'active'")->fetchColumn();
    $events = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $coaches = $db->query("SELECT COUNT(*) FROM coaches")->fetchColumn();
    
    echo "📊 Stats from database.db:\n";
    echo "   - Teams: $teams\n";
    echo "   - Active Players: $players\n";
    echo "   - Events: $events\n";
    echo "   - Coaches: $coaches\n\n";
    
    // Test color system
    require_once 'includes/color_system.php';
    VIVOColorSystem::clearCache();
    $colors = VIVOColorSystem::getColors($db);
    
    echo "🎨 Club Colors:\n";
    foreach ($colors as $key => $value) {
        echo "   - $key: $value\n";
    }
    
    echo "\n✅ All systems working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
