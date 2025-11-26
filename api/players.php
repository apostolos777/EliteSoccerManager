<?php
/**
 * VIVO United - Players API
 * Returns JSON data for React players components
 */

header('Content-Type: application/json');
require_once '../database_config.php';

try {
    $db = get_db_connection();
    
    // Get all players with team information
    $stmt = $db->prepare("
        SELECT 
            p.*,
            t.name as team_name,
            t.id as team_id
        FROM players p
        LEFT JOIN teams t ON p.team_id = t.id
        ORDER BY p.last_name, p.first_name
    ");
    $result = $stmt->execute();
    
    $players = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $players[] = [
            'id' => $row['id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'] ?? null,
            'date_of_birth' => $row['date_of_birth'] ?? null,
            'jersey_number' => $row['jersey_number'] ?? null,
            'team_id' => $row['team_id'] ?? null,
            'team_name' => $row['team_name'] ?? null
        ];
    }
    
    echo json_encode(['players' => $players]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
