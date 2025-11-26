<?php
/**
 * VIVO United - Players API
 * Returns JSON data for React players components
 */

header('Content-Type: application/json');
require_once '../database_config.php';

try {
    $db = DatabaseConfigSQLite::getConnection();
    
    // Get all players with team information
    $stmt = $db->query("
        SELECT 
            p.*,
            t.name as team_name,
            t.id as team_id
        FROM players p
        LEFT JOIN teams t ON p.team_id = t.id
        ORDER BY COALESCE(p.surname, p.name), COALESCE(p.name, '')
    ");
    
    $players = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $players[] = [
            'id' => $row['id'],
            'first_name' => $row['name'] ?? '',
            'last_name' => $row['surname'] ?? '',
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
