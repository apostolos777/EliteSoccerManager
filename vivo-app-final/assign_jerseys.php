<?php
// VIVO United Football Manager - Jersey Number Assignment
// Assign jersey numbers and additional profile info

require_once 'database_factory.php';

try {
    $db = DatabaseFactory::getConnection();
    
    echo "🏈 VIVO United Jersey Number Assignment\n";
    echo "=======================================\n\n";
    
    // Get all players without jersey numbers
    $playersStmt = $db->query("
        SELECT p.id, 
               CASE 
                   WHEN p.name IS NOT NULL AND p.surname IS NOT NULL AND p.surname != ''
                   THEN p.name || ' ' || p.surname
                   WHEN p.name IS NOT NULL 
                   THEN p.name
                   WHEN p.surname IS NOT NULL 
                   THEN p.surname
                   ELSE 'Unknown Player'
               END as name,
               p.team_id, t.name as team_name, t.age_group
        FROM players p
        LEFT JOIN teams t ON p.team_id = t.id
        WHERE p.jersey_number IS NULL AND p.status = 'active'
        ORDER BY t.age_group, name
    ");
    
    $players = $playersStmt->fetchAll();
    $jerseyCounter = [];
    
    foreach ($players as $player) {
        $teamId = $player['team_id'] ?? 'unassigned';
        
        // Initialize jersey counter for this team
        if (!isset($jerseyCounter[$teamId])) {
            // Get highest existing jersey number for this team
            $maxJerseyStmt = $db->prepare("
                SELECT MAX(jersey_number) as max_jersey 
                FROM players 
                WHERE team_id = ? AND jersey_number IS NOT NULL
            ");
            $maxJerseyStmt->execute([$teamId]);
            $maxJersey = $maxJerseyStmt->fetch()['max_jersey'] ?? 0;
            $jerseyCounter[$teamId] = $maxJersey;
        }
        
        // Assign next available jersey number
        $jerseyCounter[$teamId]++;
        $jerseyNumber = $jerseyCounter[$teamId];
        
        // Update player with jersey number and some additional info
        $updateStmt = $db->prepare("
            UPDATE players SET 
                jersey_number = ?,
                position = CASE 
                    WHEN ? % 4 = 1 THEN 'Forward'
                    WHEN ? % 4 = 2 THEN 'Midfielder' 
                    WHEN ? % 4 = 3 THEN 'Defender'
                    ELSE 'Goalkeeper'
                END,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $updateStmt->execute([
            $jerseyNumber, 
            $jerseyNumber, $jerseyNumber, $jerseyNumber, // For position assignment
            $player['id']
        ]);
        
        $teamName = $player['team_name'] ?? 'Unassigned';
        echo "✅ {$player['name']} - Jersey #{$jerseyNumber} ({$teamName})\n";
    }
    
    echo "\n📊 JERSEY ASSIGNMENT SUMMARY\n";
    echo "============================\n";
    
    // Show updated team rosters with jersey numbers
    $rosterStmt = $db->query("
        SELECT t.name as team_name, t.age_group,
               CASE 
                   WHEN p.name IS NOT NULL AND p.surname IS NOT NULL AND p.surname != ''
                   THEN p.name || ' ' || p.surname
                   WHEN p.name IS NOT NULL 
                   THEN p.name
                   WHEN p.surname IS NOT NULL 
                   THEN p.surname
                   ELSE 'Unknown Player'
               END as name, 
               p.jersey_number, p.position
        FROM teams t
        LEFT JOIN players p ON t.id = p.team_id AND p.status = 'active'
        WHERE p.id IS NOT NULL
        ORDER BY t.age_group, p.jersey_number
    ");
    
    $currentTeam = '';
    while ($player = $rosterStmt->fetch()) {
        if ($currentTeam !== $player['team_name']) {
            $currentTeam = $player['team_name'];
            echo "\n🏆 {$currentTeam}:\n";
            echo str_repeat('-', strlen($currentTeam) + 4) . "\n";
        }
        
        echo "   #{$player['jersey_number']} {$player['name']} ({$player['position']})\n";
    }
    
    echo "\n✅ Jersey number assignment completed!\n";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
