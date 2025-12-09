<?php
/**
 * Restore Attendance Data from Backup Database
 * 
 * Usage:
 * php restore_attendance_from_backup.php [backup_file] [duplicate_handling] [dry_run]
 * 
 * Examples:
 * php restore_attendance_from_backup.php vivo_football.db.backup skip false
 * php restore_attendance_from_backup.php database.db.backup.20251208_221511 update true
 */

// Configuration
$backupFile = $argv[1] ?? 'vivo_football.db.backup';
$duplicateHandling = $argv[2] ?? 'skip'; // 'skip' or 'update'
$dryRun = ($argv[3] ?? 'false') === 'true'; // Preview without actually importing

// Validate inputs
if ($duplicateHandling !== 'skip' && $duplicateHandling !== 'update') {
    echo "Error: duplicate_handling must be 'skip' or 'update'\n";
    exit(1);
}

if (!file_exists($backupFile)) {
    echo "Error: Backup file not found: $backupFile\n";
    exit(1);
}

echo "=== VIVO Attendance Restore ===\n";
echo "Backup File: $backupFile\n";
echo "Duplicate Handling: $duplicateHandling\n";
echo "Dry Run: " . ($dryRun ? 'YES' : 'NO') . "\n";
echo "---\n\n";

try {
    // Connect to backup database
    $backup = new PDO('sqlite:' . $backupFile);
    $backup->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get backup records count
    $backupCount = $backup->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
    echo "Attendance records in backup: $backupCount\n";
    
    // Connect to current database
    $current = new PDO('sqlite:database.db');
    $current->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get current records count
    $currentCount = $current->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
    echo "Attendance records in current DB: $currentCount\n\n";
    
    // Get records from backup
    $stmt = $backup->query("SELECT player_id, event_id, status, notes FROM attendance ORDER BY player_id, event_id");
    $backupRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats = [
        'imported' => 0,
        'skipped' => 0,
        'updated' => 0,
        'errors' => 0,
    ];
    
    $errors = [];
    
    echo "Processing records...\n";
    echo str_repeat("-", 80) . "\n";
    
    if (!$dryRun) {
        $current->beginTransaction();
    }
    
    foreach ($backupRecords as $record) {
        $playerId = (int)$record['player_id'];
        $eventId = (int)$record['event_id'];
        $status = strtolower($record['status'] ?? 'not_recorded');
        $notes = $record['notes'] ?? '';
        
        // Check if record exists
        $checkStmt = $current->prepare("SELECT id FROM attendance WHERE player_id = ? AND event_id = ?");
        $checkStmt->execute([$playerId, $eventId]);
        $exists = $checkStmt->fetch();
        
        if ($exists) {
            if ($duplicateHandling === 'skip') {
                $stats['skipped']++;
                echo "SKIP: Player $playerId at Event $eventId (already exists)\n";
            } else { // update
                if (!$dryRun) {
                    $updateStmt = $current->prepare("UPDATE attendance SET status = ?, notes = ?, recorded_at = CURRENT_TIMESTAMP WHERE player_id = ? AND event_id = ?");
                    $updateStmt->execute([$status, $notes, $playerId, $eventId]);
                }
                $stats['updated']++;
                echo "UPDATE: Player $playerId at Event $eventId (status: $status)\n";
            }
        } else {
            if (!$dryRun) {
                $insertStmt = $current->prepare("INSERT INTO attendance (player_id, event_id, status, notes, recorded_at, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                $insertStmt->execute([$playerId, $eventId, $status, $notes]);
            }
            $stats['imported']++;
            echo "IMPORT: Player $playerId at Event $eventId (status: $status)\n";
        }
    }
    
    if (!$dryRun) {
        $current->commit();
    }
    
    echo str_repeat("-", 80) . "\n\n";
    
    // Print summary
    echo "=== SUMMARY ===\n";
    echo "Imported: " . $stats['imported'] . "\n";
    echo "Updated: " . $stats['updated'] . "\n";
    echo "Skipped: " . $stats['skipped'] . "\n";
    echo "Errors: " . $stats['errors'] . "\n";
    echo "---\n";
    echo "Total: " . ($stats['imported'] + $stats['updated'] + $stats['skipped']) . "\n\n";
    
    if ($dryRun) {
        echo "DRY RUN: No changes were made to the database.\n";
        echo "Run again without dry_run=true to actually import the data.\n";
    } else {
        echo "✅ Restore completed successfully!\n";
        $newCount = $current->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
        echo "Final count: $newCount records\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
