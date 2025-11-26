<?php
/**
 * VIVO United - Teams API
 * Returns JSON data for React teams components
 */

header('Content-Type: application/json');
require_once '../database_config.php';

try {
    $db = DatabaseConfigSQLite::getConnection();
    
    // Get all teams with player counts
    $stmt = $db->query("
        SELECT 
            t.*,
            COUNT(p.id) as player_count
        FROM teams t
        LEFT JOIN players p ON t.id = p.team_id
        GROUP BY t.id
        ORDER BY t.name
    ");
    
    $teams = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $teams[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'age_group' => $row['age_group'] ?? null,
            'player_count' => $row['player_count'],
            'coach_name' => $row['coach_name'] ?? 'Unassigned'
        ];
    }
    
    echo json_encode(['teams' => $teams]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
