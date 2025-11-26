<?php
/**
 * Migrate data from vivo_football.db.backup to database.db
 */

try {
    // Connect to both databases
    $oldDb = new PDO('sqlite:vivo_football.db.backup');
    $newDb = new PDO('sqlite:database.db');
    
    $oldDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to both databases\n";
    
    // Get list of tables from old database
    $tables = $oldDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables in backup: " . implode(', ', $tables) . "\n";
    
    // For each table, copy data if it doesn't exist in new DB or is different
    foreach ($tables as $table) {
        echo "Processing table: $table\n";
        
        // Check if table exists in new database
        $checkTable = $newDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'")->fetchColumn();
        
        if (!$checkTable) {
            echo "Table $table doesn't exist in new database, skipping...\n";
            continue;
        }
        
        // Copy data from old to new (INSERT OR REPLACE to handle duplicates)
        $oldData = $oldDb->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($oldData)) {
            echo "No data in $table\n";
            continue;
        }
        
        // Get column names
        $columns = array_keys($oldData[0]);
        $placeholders = ':' . implode(', :', $columns);
        $columnList = implode(', ', $columns);
        
        $insertSql = "INSERT OR REPLACE INTO $table ($columnList) VALUES ($placeholders)";
        $stmt = $newDb->prepare($insertSql);
        
        $copied = 0;
        foreach ($oldData as $row) {
            try {
                $stmt->execute($row);
                $copied++;
            } catch (Exception $e) {
                echo "Error copying row in $table: " . $e->getMessage() . "\n";
            }
        }
        
        echo "Copied $copied rows to $table\n";
    }
    
    echo "Data migration completed!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
