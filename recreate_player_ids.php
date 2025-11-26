<?php
/**
 * Recreate Unique IDs for All Players
 * Regenerates unique player IDs and updates the database
 */

require_once 'database_factory.php';

echo "<h2>🆔 Recreating Unique IDs for All Players</h2>\n";

try {
    $db = DatabaseFactory::getConnection();
    
    // Check if unique_id column exists
    echo "<h3>Checking unique_id column...</h3>\n";
    $stmt = $db->query("DESCRIBE players");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('unique_id', $columns)) {
        echo "<p>Adding unique_id column to players table...</p>\n";
        $db->exec("ALTER TABLE players ADD COLUMN unique_id VARCHAR(20) UNIQUE");
        echo "✅ Unique ID column added successfully<br>\n";
    } else {
        echo "✅ Unique ID column already exists<br>\n";
    }
    
    // Get all players
    echo "<h3>Fetching all players...</h3>\n";
    $stmt = $db->query("SELECT id, name FROM players ORDER BY id");
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($players) . " players to process</p>\n";
    
    // Clear existing unique IDs to avoid conflicts
    echo "<h3>Clearing existing unique IDs...</h3>\n";
    $db->exec("UPDATE players SET unique_id = NULL");
    echo "✅ Existing unique IDs cleared<br>\n";
    
    // Generate new unique IDs for all players
    echo "<h3>Generating new unique IDs...</h3>\n";
    $generated_count = 0;
    $used_ids = [];
    
    foreach ($players as $player) {
        // Generate VIVO-XXXXXX format unique ID
        $base_id = 'VIVO-' . str_pad($player['id'], 6, '0', STR_PAD_LEFT);
        $unique_id = $base_id;
        $counter = 1;
        
        // Ensure uniqueness (in case of ID conflicts)
        while (in_array($unique_id, $used_ids)) {
            $unique_id = $base_id . '-' . $counter;
            $counter++;
        }
        
        // Update player with new unique ID
        $update_stmt = $db->prepare("UPDATE players SET unique_id = ? WHERE id = ?");
        $update_stmt->execute([$unique_id, $player['id']]);
        
        $used_ids[] = $unique_id;
        
        echo "✅ Generated unique ID for <strong>" . htmlspecialchars($player['name']) . "</strong>: <span style='font-family: monospace; background: #f3f4f6; padding: 2px 6px; border-radius: 3px; color: #dc2626;'>{$unique_id}</span><br>\n";
        $generated_count++;
    }
    
    echo "<h3>✅ Unique ID Recreation Completed Successfully!</h3>\n";
    echo "<p>📊 <strong>Generated unique IDs for {$generated_count} players</strong></p>\n";
    
    // Display all players with their new unique IDs
    echo "<h3>📋 All Players with New Unique IDs:</h3>\n";
    $stmt = $db->query("SELECT id, name, unique_id, barcode FROM players ORDER BY id");
    $all_players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='display: grid; gap: 10px; margin: 20px 0;'>\n";
    foreach ($all_players as $player) {
        echo "<div style='border: 1px solid #e5e7eb; padding: 12px; border-radius: 6px; background: white; display: grid; grid-template-columns: 50px 1fr 150px 150px; gap: 15px; align-items: center;'>\n";
        echo "<div style='font-weight: bold; color: #dc2626;'>#{$player['id']}</div>\n";
        echo "<div style='font-weight: 600;'>" . htmlspecialchars($player['name']) . "</div>\n";
        echo "<div style='font-family: monospace; background: #f3f4f6; padding: 4px 8px; border-radius: 4px; text-align: center; color: #dc2626; font-weight: bold;'>" . htmlspecialchars($player['unique_id']) . "</div>\n";
        echo "<div style='font-family: monospace; background: #f0f9ff; padding: 4px 8px; border-radius: 4px; text-align: center; color: #0369a1; font-size: 0.9rem;'>" . htmlspecialchars($player['barcode'] ?? 'No barcode') . "</div>\n";
        echo "</div>\n";
    }
    echo "</div>\n";
    
    // Verification
    echo "<h3>🔍 Verification:</h3>\n";
    $verification_stmt = $db->query("
        SELECT 
            COUNT(*) as total_players,
            COUNT(DISTINCT unique_id) as unique_ids,
            COUNT(CASE WHEN unique_id IS NOT NULL THEN 1 END) as players_with_ids
        FROM players
    ");
    $verification = $verification_stmt->fetch();
    
    echo "<ul>\n";
    echo "<li><strong>Total Players:</strong> {$verification['total_players']}</li>\n";
    echo "<li><strong>Unique IDs Generated:</strong> {$verification['players_with_ids']}</li>\n";
    echo "<li><strong>Distinct Unique IDs:</strong> {$verification['unique_ids']}</li>\n";
    echo "<li><strong>Duplicates:</strong> " . ($verification['players_with_ids'] - $verification['unique_ids']) . "</li>\n";
    echo "</ul>\n";
    
    if ($verification['total_players'] == $verification['players_with_ids'] && $verification['players_with_ids'] == $verification['unique_ids']) {
        echo "<div style='color: #10b981; padding: 15px; border: 1px solid #10b981; border-radius: 6px; background: #f0fdf4; margin: 20px 0;'>\n";
        echo "<h4 style='margin: 0 0 10px 0;'>🎉 SUCCESS!</h4>\n";
        echo "<p style='margin: 0;'>All players now have unique, non-duplicate IDs!</p>\n";
        echo "</div>\n";
    } else {
        echo "<div style='color: #dc2626; padding: 15px; border: 1px solid #dc2626; border-radius: 6px; background: #fef2f2; margin: 20px 0;'>\n";
        echo "<h4 style='margin: 0 0 10px 0;'>⚠️ WARNING!</h4>\n";
        echo "<p style='margin: 0;'>There may be issues with unique ID generation. Please check the data.</p>\n";
        echo "</div>\n";
    }
    
    echo "<h3>📈 Next Steps:</h3>\n";
    echo "<ul>\n";
    echo "<li>✅ All players now have unique VIVO-XXXXXX format IDs</li>\n";
    echo "<li>🔄 Player profiles will display new unique IDs</li>\n";
    echo "<li>🎯 Test player profiles to verify ID display</li>\n";
    echo "<li>📊 Consider updating any external systems that reference old IDs</li>\n";
    echo "</ul>\n";
    
    echo "<h3>🔗 Quick Links:</h3>\n";
    echo "<ul>\n";
    echo "<li><a href='players.php' target='_blank'>View Players List</a></li>\n";
    echo "<li><a href='player_profile_enhanced.php?id=1' target='_blank'>Test Enhanced Profile</a></li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 15px; border: 1px solid red; border-radius: 6px; background: #fef2f2; margin: 20px 0;'>\n";
    echo "<h3>❌ Error During Unique ID Recreation</h3>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>\n";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>\n";
    echo "</div>\n";
}
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    color: #374151;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #f9fafb;
}

h2 {
    color: #dc2626;
    border-bottom: 2px solid #dc2626;
    padding-bottom: 10px;
    margin-bottom: 30px;
}

h3 {
    color: #1f2937;
    margin-top: 30px;
    margin-bottom: 15px;
    background: white;
    padding: 12px 16px;
    border-radius: 8px;
    border-left: 4px solid #dc2626;
}

a {
    color: #dc2626;
    text-decoration: none;
    font-weight: 600;
}

a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .player-grid {
        grid-template-columns: 1fr !important;
    }
    
    .player-item {
        text-align: center;
    }
}
</style>
