<?php
/**
 * VIVO United - Dashboard API
 * Returns JSON data for React dashboard components
 */

header('Content-Type: application/json');
require_once '../database_config.php';

try {
    $db = get_db_connection();
    
    // Get section parameter (stats, matches, scorers, etc.)
    $section = isset($_GET['section']) ? $_GET['section'] : 'stats';
    
    if ($section === 'stats') {
        // Get dashboard statistics
        $stats = [
            'players' => $db->querySingle("SELECT COUNT(*) FROM players"),
            'teams' => $db->querySingle("SELECT COUNT(*) FROM teams"),
            'events' => $db->querySingle("SELECT COUNT(*) FROM events WHERE event_date >= date('now')"),
            'attendance' => 0
        ];
        
        // Calculate attendance rate
        $totalAttendance = $db->querySingle("SELECT COUNT(*) FROM attendance");
        $presentCount = $db->querySingle("SELECT COUNT(*) FROM attendance WHERE status = 'present'");
        if ($totalAttendance > 0) {
            $stats['attendance'] = round(($presentCount / $totalAttendance) * 100);
        }
        
        echo json_encode(['stats' => $stats]);
        
    } elseif ($section === 'matches') {
        // Get upcoming matches (events marked as matches/games)
        $stmt = $db->prepare("
            SELECT 
                event_name as teams,
                event_date as date,
                event_location as venue
            FROM events
            WHERE event_date >= date('now')
            ORDER BY event_date ASC
            LIMIT 5
        ");
        $result = $stmt->execute();
        
        $matches = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $matches[] = [
                'teams' => $row['teams'],
                'date' => date('M d, Y', strtotime($row['date'])),
                'venue' => $row['venue'] ?: 'TBD'
            ];
        }
        
        echo json_encode(['matches' => $matches]);
        
    } elseif ($section === 'scorers') {
        // Get top scorers from player_stats
        $stmt = $db->prepare("
            SELECT 
                p.first_name || ' ' || p.last_name as name,
                ps.goals
            FROM player_stats ps
            JOIN players p ON ps.player_id = p.id
            WHERE ps.goals > 0
            ORDER BY ps.goals DESC
            LIMIT 5
        ");
        $result = $stmt->execute();
        
        $scorers = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $scorers[] = [
                'name' => $row['name'],
                'goals' => $row['goals']
            ];
        }
        
        echo json_encode(['scorers' => $scorers]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
