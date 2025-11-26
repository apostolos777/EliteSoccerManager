<?php
// Add sample player statistics to make enhanced profiles more meaningful

require_once 'database_factory.php';

$db = DatabaseFactory::getConnection();

echo "🏆 Adding Sample Player Statistics\n";
echo "==================================\n\n";

// Sample statistics for players
$sample_stats = [
    // Player 1 (Robert Wilson)
    ['player_id' => 1, 'season' => '2024-25', 'games_played' => 12, 'goals' => 8, 'assists' => 3, 'yellow_cards' => 2, 'red_cards' => 0, 'minutes_played' => 980],
    ['player_id' => 1, 'season' => '2023-24', 'games_played' => 18, 'goals' => 12, 'assists' => 5, 'yellow_cards' => 3, 'red_cards' => 1, 'minutes_played' => 1520],
    
    // Player 2 (Michael Chen)
    ['player_id' => 2, 'season' => '2024-25', 'games_played' => 15, 'goals' => 2, 'assists' => 8, 'yellow_cards' => 1, 'red_cards' => 0, 'minutes_played' => 1200],
    ['player_id' => 2, 'season' => '2023-24', 'games_played' => 20, 'goals' => 3, 'assists' => 12, 'yellow_cards' => 2, 'red_cards' => 0, 'minutes_played' => 1680],
    
    // Player 3 (David Thompson)
    ['player_id' => 3, 'season' => '2024-25', 'games_played' => 14, 'goals' => 0, 'assists' => 1, 'yellow_cards' => 4, 'red_cards' => 0, 'minutes_played' => 1260],
    ['player_id' => 3, 'season' => '2023-24', 'games_played' => 22, 'goals' => 1, 'assists' => 2, 'yellow_cards' => 6, 'red_cards' => 1, 'minutes_played' => 1890],
    
    // Player 4 (James Anderson)
    ['player_id' => 4, 'season' => '2024-25', 'games_played' => 16, 'goals' => 6, 'assists' => 4, 'yellow_cards' => 3, 'red_cards' => 0, 'minutes_played' => 1320],
    ['player_id' => 4, 'season' => '2023-24', 'games_played' => 19, 'goals' => 9, 'assists' => 6, 'yellow_cards' => 4, 'red_cards' => 0, 'minutes_played' => 1560],
    
    // Player 5 (Sarah Martinez)
    ['player_id' => 5, 'season' => '2024-25', 'games_played' => 13, 'goals' => 10, 'assists' => 7, 'yellow_cards' => 1, 'red_cards' => 0, 'minutes_played' => 1140],
    ['player_id' => 5, 'season' => '2023-24', 'games_played' => 17, 'goals' => 14, 'assists' => 9, 'yellow_cards' => 2, 'red_cards' => 0, 'minutes_played' => 1480],
    
    // Player 13 (Our test player)
    ['player_id' => 13, 'season' => '2024-25', 'games_played' => 11, 'goals' => 5, 'assists' => 2, 'yellow_cards' => 2, 'red_cards' => 0, 'minutes_played' => 890],
    ['player_id' => 13, 'season' => '2023-24', 'games_played' => 16, 'goals' => 8, 'assists' => 4, 'yellow_cards' => 3, 'red_cards' => 0, 'minutes_played' => 1320],
    ['player_id' => 13, 'season' => '2022-23', 'games_played' => 14, 'goals' => 6, 'assists' => 3, 'yellow_cards' => 1, 'red_cards' => 0, 'minutes_played' => 1120],
];

try {
    // Clear existing stats first
    $db->exec("DELETE FROM player_stats");
    echo "🗑️  Cleared existing player statistics\n";
    
    // Insert sample statistics
    $stmt = $db->prepare("
        INSERT INTO player_stats (player_id, season, games_played, goals, assists, yellow_cards, red_cards, minutes_played)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $inserted = 0;
    foreach ($sample_stats as $stat) {
        $stmt->execute([
            $stat['player_id'],
            $stat['season'],
            $stat['games_played'],
            $stat['goals'],
            $stat['assists'],
            $stat['yellow_cards'],
            $stat['red_cards'],
            $stat['minutes_played']
        ]);
        $inserted++;
    }
    
    echo "✅ Added $inserted player statistics records\n\n";
    
    // Show summary
    echo "📊 Statistics Summary:\n";
    $result = $db->query("
        SELECT 
            p.name as player_name,
            COUNT(ps.id) as seasons,
            SUM(ps.games_played) as total_games,
            SUM(ps.goals) as total_goals,
            SUM(ps.assists) as total_assists
        FROM players p
        LEFT JOIN player_stats ps ON p.id = ps.player_id
        WHERE ps.player_id IS NOT NULL
        GROUP BY p.id, p.name
        ORDER BY total_goals DESC
    ");
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf(
            "  %s: %d seasons, %d games, %d goals, %d assists\n",
            $row['player_name'],
            $row['seasons'],
            $row['total_games'],
            $row['total_goals'],
            $row['total_assists']
        );
    }
    
    echo "\n🎯 Sample statistics have been added!\n";
    echo "The enhanced player profiles will now show meaningful career data.\n";
    
} catch (Exception $e) {
    echo "❌ Error adding statistics: " . $e->getMessage() . "\n";
}
?>
