<?php
/**
 * VIVO United - Fix Attendance Table Migration
 * Add missing recorded_at column to attendance table
 */

require_once 'database_factory.php';

echo "<h1>VIVO United - Attendance Table Migration</h1>";
echo "<pre>";

try {
    $db = DatabaseFactory::getConnection();
    echo "✅ Database connection successful\n";
    
    // Check if recorded_at column exists
    $checkColumn = $db->query("PRAGMA table_info(attendance)");
    $columns = $checkColumn->fetchAll(PDO::FETCH_ASSOC);
    
    $hasRecordedAt = false;
    echo "\n📋 Current attendance table structure:\n";
    foreach ($columns as $column) {
        echo "  - {$column['name']} ({$column['type']})\n";
        if ($column['name'] === 'recorded_at') {
            $hasRecordedAt = true;
        }
    }
    
    if ($hasRecordedAt) {
        echo "\n✅ recorded_at column already exists!\n";
    } else {
        echo "\n⚠️  recorded_at column is missing. Adding it now...\n";
        
        // Add the missing column
        $db->exec("ALTER TABLE attendance ADD COLUMN recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "✅ Added recorded_at column successfully!\n";
        
        // Update existing records to have a recorded_at timestamp
        $updateExisting = $db->prepare("UPDATE attendance SET recorded_at = CURRENT_TIMESTAMP WHERE recorded_at IS NULL");
        $updateExisting->execute();
        
        $updatedRows = $updateExisting->rowCount();
        echo "✅ Updated {$updatedRows} existing attendance records with timestamp\n";
    }
    
    // Verify the fix by checking the table structure again
    echo "\n📋 Updated attendance table structure:\n";
    $checkColumn = $db->query("PRAGMA table_info(attendance)");
    $columns = $checkColumn->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "  - {$column['name']} ({$column['type']})";
        if ($column['name'] === 'recorded_at') {
            echo " ✅";
        }
        echo "\n";
    }
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "\nYou can now use the attendance features without errors.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nPlease check your database configuration and try again.\n";
}

echo "</pre>";

// Optionally redirect back to attendance page after a delay
echo "<script>
setTimeout(function() {
    if (confirm('Migration completed! Would you like to go to the attendance page?')) {
        window.location.href = 'attendance.php';
    }
}, 2000);
</script>";
?>
