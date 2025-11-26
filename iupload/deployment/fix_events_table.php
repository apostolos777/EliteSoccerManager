<?php
/**
 * VIVO United - Fix Events Table Schema
 * Fix column naming inconsistencies in events table and add missing age_groups column
 */

require_once 'database_factory.php';

echo "<h1>VIVO United - Events Table Schema Fix</h1>";
echo "<pre>";

try {
    $db = DatabaseFactory::getConnection();
    echo "✅ Database connection successful\n";
    
    // Check current events table structure
    echo "\n📋 Current events table structure:\n";
    $eventsColumns = $db->query("PRAGMA table_info(events)");
    $columns = $eventsColumns->fetchAll(PDO::FETCH_ASSOC);
    $hasEventDate = false;
    $hasAgeGroups = false;
    $hasDate = false;
    $hasName = false;
    $hasTitle = false;
    
    foreach ($columns as $column) {
        echo "  - {$column['name']} ({$column['type']})\n";
        if ($column['name'] === 'event_date') $hasEventDate = true;
        if ($column['name'] === 'date') $hasDate = true;
        if ($column['name'] === 'name') $hasName = true;
        if ($column['name'] === 'title') $hasTitle = true;
        if ($column['name'] === 'age_groups') $hasAgeGroups = true;
    }
    
    $needsFix = false;
    
    // Check for missing age_groups column
    if (!$hasAgeGroups) {
        echo "\n⚠️  Missing 'age_groups' column. Adding...\n";
        $db->exec("ALTER TABLE events ADD COLUMN age_groups TEXT");
        echo "✅ Added age_groups column\n";
        $needsFix = true;
    }
    
    // Fix event_date to date column
    if ($hasEventDate && !$hasDate) {
        echo "\n⚠️  Found 'event_date' column, but should be 'date'. Fixing...\n";
        
        // Create temporary table with correct structure
        $db->exec("CREATE TABLE events_temp (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            event_type VARCHAR(20) NOT NULL,
            date DATE NOT NULL,
            time TIME,
            location VARCHAR(200),
            team_id INTEGER,
            opponent VARCHAR(100),
            is_home_game BOOLEAN DEFAULT 1,
            status VARCHAR(20) DEFAULT 'Scheduled',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Copy data from old table to new table
        $db->exec("INSERT INTO events_temp (id, title, description, event_type, date, location, status, created_at, updated_at)
                   SELECT id, title, description, event_type, event_date, location, status, created_at, updated_at FROM events");
        
        // Drop old table and rename new table
        $db->exec("DROP TABLE events");
        $db->exec("ALTER TABLE events_temp RENAME TO events");
        
        echo "✅ Fixed 'event_date' column to 'date'\n";
        $needsFix = true;
    }
    
    // Add 'name' column as alias for 'title' if needed (for backwards compatibility)
    if ($hasTitle && !$hasName) {
        echo "\n📝 Adding 'name' as view/alias for 'title' column for backwards compatibility...\n";
        // We'll handle this in the application code instead of adding redundant column
        echo "✅ Will handle 'name' references in application code\n";
    }
    
    if (!$needsFix && $hasDate && $hasTitle) {
        echo "\n✅ Events table structure is already correct!\n";
    }
    
    // Verify the final structure
    echo "\n📋 Final events table structure:\n";
    $finalColumns = $db->query("PRAGMA table_info(events)");
    foreach ($finalColumns->fetchAll(PDO::FETCH_ASSOC) as $column) {
        echo "  - {$column['name']} ({$column['type']})\n";
    }
    
    // Test the fixed structure
    echo "\n🔍 Testing events query with fixed structure:\n";
    $testQuery = $db->query("SELECT id, title, date, location FROM events LIMIT 3");
    $events = $testQuery->fetchAll(PDO::FETCH_ASSOC);
    if (empty($events)) {
        echo "  No events found (table is empty)\n";
    } else {
        foreach ($events as $event) {
            echo "  Event: {$event['title']} on {$event['date']} at {$event['location']}\n";
        }
    }
    
    echo "\n🎉 Events table schema fix completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
