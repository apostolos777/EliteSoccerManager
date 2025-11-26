<?php
// Simple script to test database stats without authentication

try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Stats Test</h2>\n";
    
    // Team statistics
    $stmt = $db->prepare("SELECT COUNT(*) FROM teams");
    $stmt->execute();
    $total_teams = $stmt->fetchColumn();
    echo "<p>Teams: $total_teams</p>\n";
    
    // Player statistics
    $stmt = $db->prepare("SELECT COUNT(*) FROM players WHERE status = 'active'");
    $stmt->execute();
    $active_players = $stmt->fetchColumn();
    echo "<p>Active Players: $active_players</p>\n";
    
    // Event statistics
    $stmt = $db->prepare("SELECT COUNT(*) FROM events");
    $stmt->execute();
    $total_events = $stmt->fetchColumn();
    echo "<p>Events: $total_events</p>\n";
    
    // Goals from player stats
    $stmt = $db->prepare("SELECT SUM(goals) FROM player_stats");
    $stmt->execute();
    $goals_scored = $stmt->fetchColumn() ?: 0;
    echo "<p>Goals Scored: $goals_scored</p>\n";
    
    // Coach statistics (test if coaches table exists)
    $total_coaches = 0;
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM coaches WHERE status = 'active'");
        $stmt->execute();
        $total_coaches = $stmt->fetchColumn();
        echo "<p>Coaches: $total_coaches</p>\n";
    } catch (Exception $e) {
        echo "<p>Coaches: 0 (table doesn't exist: " . $e->getMessage() . ")</p>\n";
    }
    
    echo "<h3>Sample Teams:</h3>\n";
    $stmt = $db->prepare("SELECT name FROM teams LIMIT 5");
    $stmt->execute();
    $teams = $stmt->fetchAll();
    foreach ($teams as $team) {
        echo "<p>- " . htmlspecialchars($team['name']) . "</p>\n";
    }
    
    echo "<h3>Sample Players:</h3>\n";
    $stmt = $db->prepare("SELECT name, position FROM players LIMIT 5");
    $stmt->execute();
    $players = $stmt->fetchAll();
    foreach ($players as $player) {
        echo "<p>- " . htmlspecialchars($player['name']) . " (" . htmlspecialchars($player['position']) . ")</p>\n";
    }

} catch (Exception $e) {
    echo "<p>Database Error: " . $e->getMessage() . "</p>\n";
}
?>
