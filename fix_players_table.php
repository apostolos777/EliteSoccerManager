<?php
// Fix Players Table - Add Missing unique_player_id Column
require_once __DIR__ . '/wp-config.php';

echo "<h1>Fixing Players Table Structure</h1>\n";

try {
    $db = get_db_connection();
    echo "<p>✅ Database connection successful</p>\n";
    
    // Check current table structure
    echo "<h3>Current Players Table Structure:</h3>\n";
    $columns = $db->query("PRAGMA table_info(players)")->fetchAll();
    $existing_columns = [];
    echo "<ul>\n";
    foreach ($columns as $col) {
        $existing_columns[] = $col['name'];
        echo "<li>" . $col['name'] . " (" . $col['type'] . ")</li>\n";
    }
    echo "</ul>\n";
    
    // Check if unique_player_id column exists
    if (!in_array('unique_player_id', $existing_columns)) {
        echo "<h3>Adding Missing unique_player_id Column:</h3>\n";
        
        // SQLite doesn't allow adding UNIQUE constraints to existing tables with data
        // So we add the column without UNIQUE first, then create a unique index
        $db->exec("ALTER TABLE players ADD COLUMN unique_player_id TEXT");
        echo "<p>✅ Added unique_player_id column</p>\n";
        
        // Generate unique IDs for existing players first
        echo "<p>Generating unique IDs for existing players...</p>\n";
        $players = $db->query("SELECT id FROM players WHERE unique_player_id IS NULL OR unique_player_id = ''")->fetchAll();
        foreach ($players as $player) {
            $unique_id = 'VIVO_' . str_pad($player['id'], 6, '0', STR_PAD_LEFT);
            $db->prepare("UPDATE players SET unique_player_id = ? WHERE id = ?")->execute([$unique_id, $player['id']]);
        }
        echo "<p>✅ Generated unique IDs for " . count($players) . " existing players</p>\n";
        
        // Now create a unique index
        try {
            $db->exec("CREATE UNIQUE INDEX idx_players_unique_id ON players(unique_player_id)");
            echo "<p>✅ Created unique index on unique_player_id</p>\n";
        } catch (Exception $e) {
            echo "<p>⚠️ Could not create unique index: " . $e->getMessage() . "</p>\n";
        }
    } else {
        echo "<p>✅ unique_player_id column already exists</p>\n";
        
        // Check if there are players without unique IDs
        $players_without_id = $db->query("SELECT id FROM players WHERE unique_player_id IS NULL OR unique_player_id = ''")->fetchAll();
        if (!empty($players_without_id)) {
            echo "<p>Generating unique IDs for players missing them...</p>\n";
            foreach ($players_without_id as $player) {
                $unique_id = 'VIVO_' . str_pad($player['id'], 6, '0', STR_PAD_LEFT);
                $db->prepare("UPDATE players SET unique_player_id = ? WHERE id = ?")->execute([$unique_id, $player['id']]);
            }
            echo "<p>✅ Generated unique IDs for " . count($players_without_id) . " players</p>\n";
        }
    }
    
    // Check other potentially missing columns for modern player management
    $required_columns = [
        'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        'first_name' => 'TEXT NOT NULL',
        'last_name' => 'TEXT NOT NULL',
        'date_of_birth' => 'TEXT',
        'unique_player_id' => 'TEXT',
        'jersey_number' => 'INTEGER',
        'position' => 'TEXT',
        'team_id' => 'INTEGER',
        'parent_name' => 'TEXT',
        'parent_email' => 'TEXT',
        'parent_phone' => 'TEXT',
        'emergency_contact' => 'TEXT',
        'medical_notes' => 'TEXT',
        'is_active' => 'INTEGER DEFAULT 1',
        'created_at' => 'TEXT',
        'updated_at' => 'TEXT'
    ];
    
    echo "<h3>Checking All Required Columns:</h3>\n";
    foreach ($required_columns as $col_name => $col_def) {
        if (!in_array($col_name, $existing_columns)) {
            echo "<p>❌ Missing: $col_name - Adding...</p>\n";
            try {
                $db->exec("ALTER TABLE players ADD COLUMN $col_name $col_def");
                echo "<p>✅ Added $col_name column</p>\n";
            } catch (Exception $e) {
                echo "<p>⚠️ Could not add $col_name: " . $e->getMessage() . "</p>\n";
            }
        } else {
            echo "<p>✅ $col_name exists</p>\n";
        }
    }
    
    echo "<h3>Final Players Table Structure:</h3>\n";
    $final_columns = $db->query("PRAGMA table_info(players)")->fetchAll();
    echo "<ul>\n";
    foreach ($final_columns as $col) {
        echo "<li>" . $col['name'] . " (" . $col['type'] . ")</li>\n";
    }
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<h3>Next Steps:</h3>\n";
echo "<p>After running this fix, player creation should work properly.</p>\n";
echo "<p><a href='add_player.php'>Test adding a player</a></p>\n";
?>
