<?php
/**
 * Update Players Table to Separate First Name and Surname
 * This script adds a surname column and properly splits the names
 */

require_once 'database_factory.php';

$db = DatabaseFactory::getConnection();

echo "🔧 Updating Players Table Structure\n";
echo "====================================\n\n";

try {
    // Add surname column to players table
    echo "📝 Adding surname column to players table...\n";
    $db->exec("ALTER TABLE players ADD COLUMN surname TEXT");
    echo "   ✅ Surname column added successfully\n\n";
    
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "   ℹ️  Surname column already exists\n\n";
    } else {
        echo "   ❌ Error adding column: " . $e->getMessage() . "\n\n";
    }
}

try {
    // Clear existing imported players (keep only real data structure)
    echo "🗑️  Clearing existing imported player data...\n";
    $db->exec("DELETE FROM players WHERE id > 15");
    echo "   ✅ Cleared imported players\n\n";
    
    // Reset auto-increment counter
    echo "🔄 Resetting player ID counter...\n";
    $db->exec("DELETE FROM sqlite_sequence WHERE name='players'");
    $db->exec("INSERT INTO sqlite_sequence (name, seq) VALUES ('players', 15)");
    echo "   ✅ ID counter reset\n\n";
    
    echo "📊 Current players table structure:\n";
    $result = $db->query("PRAGMA table_info(players)");
    while ($row = $result->fetch()) {
        echo "   - {$row['name']} ({$row['type']})" . ($row['notnull'] ? " NOT NULL" : "") . "\n";
    }
    echo "\n";
    
    echo "✅ Players table is ready for proper name import!\n";
    echo "Next step: Re-run the import script with the CSV data.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
