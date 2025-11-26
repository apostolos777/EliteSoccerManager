<?php
/**
 * Remove Old Sample Data
 * This script removes the original 15 sample players and their associated data
 */

require_once 'database_factory.php';

$db = DatabaseFactory::getConnection();

echo "🧹 Removing Old Sample Data\n";
echo "============================\n\n";

try {
    // Start transaction
    $db->beginTransaction();
    
    // 1. Remove sample player statistics (for players 1-15)
    echo "🗑️  Removing sample player statistics...\n";
    $stmt = $db->prepare("DELETE FROM player_stats WHERE player_id <= 15");
    $stmt->execute();
    $deletedStats = $stmt->rowCount();
    echo "   Deleted $deletedStats player statistics records\n\n";
    
    // 2. Remove sample attendance records (for players 1-15)
    echo "🗑️  Removing sample attendance records...\n";
    $stmt = $db->prepare("DELETE FROM attendance WHERE player_id <= 15");
    $stmt->execute();
    $deletedAttendance = $stmt->rowCount();
    echo "   Deleted $deletedAttendance attendance records\n\n";
    
    // 3. Check which teams will be empty after removing sample players
    echo "📊 Checking team status before cleanup...\n";
    $result = $db->query("
        SELECT t.id, t.name, 
               COUNT(CASE WHEN p.id <= 15 THEN 1 END) as sample_players,
               COUNT(CASE WHEN p.id > 15 THEN 1 END) as real_players,
               COUNT(p.id) as total_players
        FROM teams t
        LEFT JOIN players p ON t.id = p.team_id
        GROUP BY t.id, t.name
        HAVING total_players > 0
        ORDER BY t.name
    ");
    
    $emptyTeams = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("   %s: %d sample + %d real = %d total\n", 
            $row['name'], $row['sample_players'], $row['real_players'], $row['total_players']);
        
        // Mark teams that will be empty after removing sample players
        if ($row['real_players'] == 0 && $row['sample_players'] > 0) {
            $emptyTeams[] = $row['id'];
        }
    }
    echo "\n";
    
    // 4. Remove sample players (IDs 1-15)
    echo "🗑️  Removing sample players...\n";
    $stmt = $db->prepare("DELETE FROM players WHERE id <= 15");
    $stmt->execute();
    $deletedPlayers = $stmt->rowCount();
    echo "   Deleted $deletedPlayers sample players\n\n";
    
    // 5. Remove empty teams (optional - only teams with no real players)
    if (!empty($emptyTeams)) {
        echo "🗑️  Removing empty sample teams...\n";
        $placeholders = str_repeat('?,', count($emptyTeams) - 1) . '?';
        $stmt = $db->prepare("DELETE FROM teams WHERE id IN ($placeholders)");
        $stmt->execute($emptyTeams);
        $deletedTeams = $stmt->rowCount();
        echo "   Deleted $deletedTeams empty teams\n\n";
    }
    
    // 6. Check for any orphaned events (events with no attendance)
    echo "🗑️  Checking for orphaned events...\n";
    $result = $db->query("
        SELECT e.id, e.title, COUNT(a.id) as attendance_count
        FROM events e
        LEFT JOIN attendance a ON e.id = a.event_id
        GROUP BY e.id, e.title
        HAVING attendance_count = 0
    ");
    
    $orphanedEvents = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "   Found orphaned event: {$row['title']}\n";
        $orphanedEvents[] = $row['id'];
    }
    
    if (!empty($orphanedEvents)) {
        $placeholders = str_repeat('?,', count($orphanedEvents) - 1) . '?';
        $stmt = $db->prepare("DELETE FROM events WHERE id IN ($placeholders)");
        $stmt->execute($orphanedEvents);
        $deletedEvents = $stmt->rowCount();
        echo "   Deleted $deletedEvents orphaned events\n";
    } else {
        echo "   No orphaned events found\n";
    }
    echo "\n";
    
    // Commit transaction
    $db->commit();
    
    // 7. Show final database status
    echo "📊 Final Database Status:\n";
    echo "========================\n";
    
    $playerCount = $db->query("SELECT COUNT(*) FROM players")->fetchColumn();
    $teamCount = $db->query("SELECT COUNT(*) FROM teams WHERE id IN (SELECT DISTINCT team_id FROM players WHERE team_id IS NOT NULL)")->fetchColumn();
    $eventCount = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $attendanceCount = $db->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
    $statsCount = $db->query("SELECT COUNT(*) FROM player_stats")->fetchColumn();
    
    echo "✅ Players: $playerCount (all real data)\n";
    echo "✅ Active Teams: $teamCount\n";
    echo "✅ Events: $eventCount\n";
    echo "✅ Attendance Records: $attendanceCount\n";
    echo "✅ Player Statistics: $statsCount\n\n";
    
    echo "🎯 Cleanup Summary:\n";
    echo "==================\n";
    echo "✅ Deleted $deletedPlayers sample players\n";
    echo "✅ Deleted $deletedStats player statistics\n";
    echo "✅ Deleted $deletedAttendance attendance records\n";
    if (!empty($emptyTeams)) {
        echo "✅ Deleted $deletedTeams empty teams\n";
    }
    if (!empty($orphanedEvents)) {
        echo "✅ Deleted $deletedEvents orphaned events\n";
    }
    echo "\n✨ Database now contains only real club data!\n";
    
} catch (Exception $e) {
    $db->rollback();
    echo "❌ Error during cleanup: " . $e->getMessage() . "\n";
    echo "   Database rolled back to previous state.\n";
}
?>
