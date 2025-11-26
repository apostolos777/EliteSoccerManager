<?php
/**
 * Check SQLite Database Contents
 */

require_once 'wp-config.php';

echo "<h1>VIVO United - Database Contents Check</h1>";
echo "<pre>";

try {
    $db = get_db_connection();
    echo "✅ Database connection successful\n";
    echo "Database file: " . SQLITE_DB_PATH . "\n";
    echo "File exists: " . (file_exists(SQLITE_DB_PATH) ? 'YES' : 'NO') . "\n";
    echo "File size: " . filesize(SQLITE_DB_PATH) . " bytes\n\n";
    
    // Check all tables
    echo "📋 Available tables:\n";
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll();
    foreach ($tables as $table) {
        echo "  - {$table['name']}\n";
    }
    
    // Check players table
    echo "\n👥 Players table:\n";
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM players");
        $count = $stmt->fetch()['count'];
        echo "  Total players: $count\n";
        
        if ($count > 0) {
            $stmt = $db->query("SELECT id, name, surname, team_id FROM players LIMIT 5");
            $players = $stmt->fetchAll();
            foreach ($players as $player) {
                echo "  - {$player['id']}: {$player['name']} {$player['surname']} (Team: {$player['team_id']})\n";
            }
        }
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
    
    // Check teams table
    echo "\n🛡️ Teams table:\n";
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM teams");
        $count = $stmt->fetch()['count'];
        echo "  Total teams: $count\n";
        
        if ($count > 0) {
            $stmt = $db->query("SELECT id, name FROM teams LIMIT 5");
            $teams = $stmt->fetchAll();
            foreach ($teams as $team) {
                echo "  - {$team['id']}: {$team['name']}\n";
            }
        }
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
    
    // Check events table
    echo "\n📅 Events table:\n";
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM events");
        $count = $stmt->fetch()['count'];
        echo "  Total events: $count\n";
        
        if ($count > 0) {
            $stmt = $db->query("SELECT id, title, date FROM events LIMIT 5");
            $events = $stmt->fetchAll();
            foreach ($events as $event) {
                echo "  - {$event['id']}: {$event['title']} on {$event['date']}\n";
            }
        }
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
