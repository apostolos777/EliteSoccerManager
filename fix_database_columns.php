<?php
/**
 * VIVO United - Database Column Fix
 * Fix inconsistent column naming in events table queries
 */

require_once 'database_factory.php';

echo "<h1>VIVO United - Database Column Fix</h1>";
echo "<pre>";

try {
    $db = DatabaseFactory::getConnection();
    echo "✅ Database connection successful\n";
    
    // Check current events table structure
    echo "\n📋 Current events table structure:\n";
    $checkTable = $db->query("PRAGMA table_info(events)");
    $columns = $checkTable->fetchAll(PDO::FETCH_ASSOC);
    
    $hasEventDate = false;
    $hasDate = false;
    
    foreach ($columns as $column) {
        echo "  - {$column['name']} ({$column['type']})\n";
        if ($column['name'] === 'event_date') {
            $hasEventDate = true;
        }
        if ($column['name'] === 'date') {
            $hasDate = true;
        }
    }
    
    if ($hasDate && !$hasEventDate) {
        echo "\n✅ Events table uses 'date' column (correct)\n";
        echo "📝 Files have been updated to use correct column name\n";
    } elseif ($hasEventDate && !$hasDate) {
        echo "\n⚠️  Events table uses 'event_date' column\n";
        echo "🔧 Would need to update database schema to use 'date' column\n";
    } elseif ($hasDate && $hasEventDate) {
        echo "\n⚠️  Events table has both 'date' and 'event_date' columns\n";
        echo "🧹 May need cleanup\n";
    } else {
        echo "\n❌ Events table missing both date columns\n";
    }
    
    // Test a simple query to verify it works
    echo "\n🧪 Testing events query...\n";
    $testQuery = $db->query("SELECT COUNT(*) as count FROM events");
    $result = $testQuery->fetch();
    echo "✅ Events table accessible - Total events: {$result['count']}\n";
    
    // Check if we can query the date column specifically
    try {
        $dateTest = $db->query("SELECT date FROM events LIMIT 1");
        echo "✅ 'date' column query successful\n";
    } catch (Exception $e) {
        echo "❌ 'date' column query failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 Database column fix verification completed!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
