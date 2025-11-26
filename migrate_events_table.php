<?php
/**
 * Migration Script: Add Missing Columns to Events Table
 * This script adds the missing columns required for event creation
 */

require_once 'wp-config.php';

try {
    $db = get_db_connection();
    
    echo "🔧 VIVO United Database Migration\n";
    echo "================================\n";
    echo "Adding missing columns to events table...\n\n";
    
    $migrations = [
        'age_groups' => 'ALTER TABLE events ADD COLUMN age_groups TEXT',
        'max_participants' => 'ALTER TABLE events ADD COLUMN max_participants INTEGER',
        'cost' => 'ALTER TABLE events ADD COLUMN cost DECIMAL(10,2)',
        'equipment_needed' => 'ALTER TABLE events ADD COLUMN equipment_needed TEXT',
        'notes' => 'ALTER TABLE events ADD COLUMN notes TEXT',
        'is_mandatory' => 'ALTER TABLE events ADD COLUMN is_mandatory BOOLEAN DEFAULT 0'
    ];
    
    foreach ($migrations as $column => $sql) {
        try {
            $db->exec($sql);
            echo "✅ Added column: $column\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'duplicate column name') !== false) {
                echo "⚠️  Column $column already exists\n";
            } else {
                echo "❌ Error adding $column: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n📊 Updated Events Table Structure:\n";
    $result = $db->query('PRAGMA table_info(events)');
    while ($row = $result->fetch()) {
        $nullable = $row['notnull'] ? 'NOT NULL' : 'NULL';
        $default = $row['dflt_value'] ? " DEFAULT {$row['dflt_value']}" : '';
        echo "  - {$row['name']} ({$row['type']}) $nullable$default\n";
    }
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "Event creation should now work properly.\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
