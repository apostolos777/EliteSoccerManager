<?php
// VIVO United Football Manager - Team Assignment
// Create U8 and U9 teams and assign imported players

require_once 'database_factory.php';

try {
    $db = DatabaseFactory::getConnection();
    
    echo "🏈 VIVO United Team Assignment Tool\n";
    echo "===================================\n\n";
    
    // Check if U8 and U9 teams exist, create if not
    $teams = ['U-8', 'U-9'];
    
    foreach ($teams as $ageGroup) {
        $checkTeam = $db->prepare("SELECT id FROM teams WHERE age_group = ?");
        $checkTeam->execute([$ageGroup]);
        $existingTeam = $checkTeam->fetch();
        
        if (!$existingTeam) {
            // Create the team
            $insertTeam = $db->prepare("
                INSERT INTO teams (name, age_group, status, created_at, updated_at) 
                VALUES (?, ?, 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $teamName = "VIVO United " . $ageGroup;
            $insertTeam->execute([$teamName, $ageGroup]);
            $teamId = $db->lastInsertId();
            echo "✅ Created team: {$teamName} (ID: {$teamId})\n";
        } else {
            $teamId = $existingTeam['id'];
            echo "ℹ️  Team {$ageGroup} already exists (ID: {$teamId})\n";
        }
    }
    
    echo "\n";
    
    // Assign U8 players to U-8 team
    $u8Team = $db->prepare("SELECT id FROM teams WHERE age_group = 'U-8'")->execute();
    $u8Team = $db->prepare("SELECT id FROM teams WHERE age_group = 'U-8'");
    $u8Team->execute();
    $u8TeamData = $u8Team->fetch();
    
    if ($u8TeamData) {
        $u8Players = [
            'Daniel Bothma', 'Miles Dignum', 'D\'vaunte Sidney Williams', 
            'Alexander Labuschagne', 'Caleb Mason Ackerberg', 'Gabrijel Marojević',
            'Jean-Luc Vorster', 'Alexander Ellis', 'James Wood', 
            'Ukaedin Ziano Gardiner', 'Zane Blignaut'
        ];
        
        foreach ($u8Players as $playerName) {
            $updatePlayer = $db->prepare("
                UPDATE players SET team_id = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE name = ? AND status = 'active'
            ");
            $updatePlayer->execute([$u8TeamData['id'], $playerName]);
            
            if ($updatePlayer->rowCount() > 0) {
                echo "✅ Assigned {$playerName} to U-8 team\n";
            }
        }
    }
    
    // Assign U9 players to U-9 team  
    $u9Team = $db->prepare("SELECT id FROM teams WHERE age_group = 'U-9'");
    $u9Team->execute();
    $u9TeamData = $u9Team->fetch();
    
    if ($u9TeamData) {
        $u9Players = [
            'Blake De Beer', 'Blake McFarland', 'Nicholas Van Wyk',
            'Hugo Vogt', 'Benjamin Van Zyl', 'Jaydin Nordin'
        ];
        
        foreach ($u9Players as $playerName) {
            $updatePlayer = $db->prepare("
                UPDATE players SET team_id = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE name = ? AND status = 'active'
            ");
            $updatePlayer->execute([$u9TeamData['id'], $playerName]);
            
            if ($updatePlayer->rowCount() > 0) {
                echo "✅ Assigned {$playerName} to U-9 team\n";
            }
        }
    }
    
    echo "\n📊 TEAM ASSIGNMENT SUMMARY\n";
    echo "==========================\n";
    
    // Show team statistics
    $teamStats = $db->query("
        SELECT t.name, t.age_group, COUNT(p.id) as player_count
        FROM teams t
        LEFT JOIN players p ON t.id = p.team_id AND p.status = 'active'
        GROUP BY t.id, t.name, t.age_group
        ORDER BY t.age_group
    ");
    
    while ($team = $teamStats->fetch()) {
        echo "🏆 {$team['name']}: {$team['player_count']} players\n";
    }
    
    echo "\n✅ Team assignments completed!\n";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
