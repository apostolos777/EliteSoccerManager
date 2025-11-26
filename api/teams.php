<?php
/**
 * VIVO United - Teams API
 * Returns JSON data for React teams components
 */

header('Content-Type: application/json');
require_once '../database_config.php';

try {
    $db = get_db_connection();
    
    // Get all teams with player counts and coach information
    $stmt = $db->prepare("
        SELECT 
            t.*,
            COUNT(p.id) as player_count,
            c.name as coach_name
        FROM teams t
        LEFT JOIN players p ON t.id = p.team_id
        LEFT JOIN coaches c ON t.coach_id = c.id
        GROUP BY t.id
        ORDER BY t.name
    ");
    $result = $stmt->execute();
    
    $teams = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
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
