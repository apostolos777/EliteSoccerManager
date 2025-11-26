<?php
// Fix Players Table - Complete Rebuild Approach
require_once __DIR__ . '/wp-config.php';

echo "<h1>Fixing Players Table Structure (Rebuild Method)</h1>\n";

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
        echo "<h3>Rebuilding Players Table with Proper Structure:</h3>\n";
        
        // Start transaction
        $db->beginTransaction();
        
        // Get all existing data
        echo "<p>Backing up existing player data...</p>\n";
        $existing_players = $db->query("SELECT * FROM players")->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>✅ Backed up " . count($existing_players) . " players</p>\n";
        
        // Check for data quality issues
        $issues = [];
        foreach ($existing_players as $i => $player) {
            if (empty($player['first_name'])) {
                $issues[] = "Player ID {$player['id']}: missing first_name";
            }
            if (empty($player['last_name'])) {
                $issues[] = "Player ID {$player['id']}: missing last_name";
            }
        }
        
        if (!empty($issues)) {
            echo "<p>⚠️ Found data quality issues that will be fixed:</p>\n<ul>\n";
            foreach (array_slice($issues, 0, 10) as $issue) { // Show first 10 issues
                echo "<li>$issue</li>\n";
            }
            if (count($issues) > 10) {
                echo "<li>... and " . (count($issues) - 10) . " more issues</li>\n";
            }
            echo "</ul>\n";
        }
        
        // Create new table with proper structure
        echo "<p>Creating new players table structure...</p>\n";
        $new_table_sql = "
        CREATE TABLE players_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT,
            last_name TEXT,
            date_of_birth TEXT,
            unique_player_id TEXT,
            jersey_number INTEGER,
            position TEXT,
            team_id INTEGER,
            parent_name TEXT,
            parent_email TEXT,
            parent_phone TEXT,
            emergency_contact TEXT,
            medical_notes TEXT,
            is_active INTEGER DEFAULT 1,
            created_at TEXT,
            updated_at TEXT
        )";
        
        $db->exec($new_table_sql);
        echo "<p>✅ Created new table structure</p>\n";
        
        // Migrate existing data
        echo "<p>Migrating existing player data...</p>\n";
        $insert_sql = "INSERT INTO players_new (";
        $value_placeholders = "VALUES (";
        
        // Build dynamic insert based on existing columns
        $common_columns = [];
        $new_columns = ['id', 'first_name', 'last_name', 'date_of_birth', 'jersey_number', 'position', 'team_id', 'parent_name', 'parent_email', 'parent_phone', 'emergency_contact', 'medical_notes', 'is_active', 'created_at', 'updated_at'];
        
        foreach ($new_columns as $col) {
            if (in_array($col, $existing_columns)) {
                $common_columns[] = $col;
            }
        }
        
        $insert_sql .= implode(', ', $common_columns) . ', unique_player_id) ';
        $value_placeholders .= str_repeat('?, ', count($common_columns)) . '?)';
        $insert_sql .= $value_placeholders;
        
        $stmt = $db->prepare($insert_sql);
        
        $migrated_count = 0;
        foreach ($existing_players as $player) {
            $values = [];
            foreach ($common_columns as $col) {
                $value = $player[$col] ?? null;
                // Handle NULL names by providing defaults
                if ($col === 'first_name' && empty($value)) {
                    $value = 'Unknown';
                } elseif ($col === 'last_name' && empty($value)) {
                    $value = 'Player';
                }
                $values[] = $value;
            }
            // Add unique_player_id
            $unique_id = 'VIVO_' . str_pad($player['id'], 6, '0', STR_PAD_LEFT);
            $values[] = $unique_id;
            
            if ($stmt->execute($values)) {
                $migrated_count++;
            } else {
                echo "<p>⚠️ Failed to migrate player ID: " . $player['id'] . "</p>\n";
            }
        }
        
        echo "<p>✅ Migrated $migrated_count players</p>\n";
        
        // Drop old table and rename new one
        echo "<p>Replacing old table...</p>\n";
        $db->exec("DROP TABLE players");
        $db->exec("ALTER TABLE players_new RENAME TO players");
        
        // Create unique index
        $db->exec("CREATE UNIQUE INDEX idx_players_unique_id ON players(unique_player_id)");
        echo "<p>✅ Created unique index on unique_player_id</p>\n";
        
        // Commit transaction
        $db->commit();
        echo "<p>✅ Table rebuild completed successfully!</p>\n";
        
    } else {
        echo "<p>✅ unique_player_id column already exists</p>\n";
        
        // Just ensure all players have unique IDs
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
    
    echo "<h3>Final Players Table Structure:</h3>\n";
    $final_columns = $db->query("PRAGMA table_info(players)")->fetchAll();
    echo "<ul>\n";
    foreach ($final_columns as $col) {
        echo "<li>" . $col['name'] . " (" . $col['type'] . ")</li>\n";
    }
    echo "</ul>\n";
    
    // Test unique_player_id functionality
    echo "<h3>Testing unique_player_id:</h3>\n";
    $sample_players = $db->query("SELECT id, first_name, last_name, unique_player_id FROM players LIMIT 5")->fetchAll();
    echo "<ul>\n";
    foreach ($sample_players as $player) {
        echo "<li>ID: {$player['id']}, Name: {$player['first_name']} {$player['last_name']}, Unique ID: {$player['unique_player_id']}</li>\n";
    }
    echo "</ul>\n";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Stack trace: " . htmlspecialchars($e->getTraceAsString()) . "</p>\n";
}

echo "<h3>Next Steps:</h3>\n";
echo "<p>After running this fix, player creation should work properly.</p>\n";
echo "<p><a href='add_player.php'>Test adding a player</a></p>\n";
?>
