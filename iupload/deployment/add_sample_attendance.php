<?php
/**
 * Sample Attendance Data Generator
 * This will create sample attendance records for demonstration
 */

require_once 'wp-config.php';

$db = get_db_connection();

echo "Adding sample attendance data...\n\n";

try {
    // Get the most recent event
    $eventStmt = $db->query("SELECT id, title FROM events ORDER BY date DESC LIMIT 1");
    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo "No events found. Please create an event first.\n";
        exit;
    }
    
    echo "Using event: {$event['title']} (ID: {$event['id']})\n";
    
    // Get some players
    $playersStmt = $db->query("SELECT id, name FROM players WHERE status = 'active' LIMIT 20");
    $players = $playersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($players)) {
        echo "No active players found.\n";
        exit;
    }
    
    echo "Found " . count($players) . " players to add attendance for.\n\n";
    
    $statuses = ['present', 'absent', 'late', 'excused'];
    $notes = [
        'present' => ['Great training session', 'Good performance', 'On time', ''],
        'absent' => ['Sick', 'Family emergency', 'School conflict', 'Not available'],
        'late' => ['Traffic delay', 'School ran late', 'Transportation issue', ''],
        'excused' => ['Medical appointment', 'Family event', 'School exam', 'Pre-approved absence']
    ];
    
    $insertStmt = $db->prepare("INSERT INTO attendance (player_id, event_id, status, notes, recorded_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
    
    $attendanceCount = 0;
    foreach ($players as $player) {
        // Randomly assign status (70% present, 15% absent, 10% late, 5% excused)
        $rand = rand(1, 100);
        if ($rand <= 70) {
            $status = 'present';
        } elseif ($rand <= 85) {
            $status = 'absent';
        } elseif ($rand <= 95) {
            $status = 'late';
        } else {
            $status = 'excused';
        }
        
        $note = $notes[$status][array_rand($notes[$status])];
        
        $insertStmt->execute([$player['id'], $event['id'], $status, $note]);
        
        echo "✓ {$player['name']}: {$status}" . ($note ? " ({$note})" : "") . "\n";
        $attendanceCount++;
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Successfully added attendance records for {$attendanceCount} players!\n";
    echo "You can now view the attendance page to see the data.\n";
    echo "Visit: http://localhost:8000/attendance.php?event_id={$event['id']}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
