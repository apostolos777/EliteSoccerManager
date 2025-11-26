<?php
/**
 * VIVO United - Events JSON API
 * Provides events data for the calendar
 */

// Prevent caching so clients always fetch fresh events
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'database_factory.php';
require_once 'includes/auth.php';

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = DatabaseFactory::getConnection();
    
    // Get date range from parameters
    $start = $_GET['start'] ?? date('Y-m-01');
    $end = $_GET['end'] ?? date('Y-m-t');
    
    // Query events within the date range
    $stmt = $db->prepare("
        SELECT 
            id,
            title,
            description,
            date,
            time,
            location,
            event_type,
            team_id,
            created_at,
            updated_at
        FROM events 
        WHERE date >= ? AND date <= ?
        ORDER BY date ASC, time ASC
    ");
    
    $stmt->execute([$start, $end]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format events for FullCalendar
    $formattedEvents = [];
    foreach ($events as $event) {
        $startDateTime = $event['date'];
        if ($event['time']) {
            $startDateTime .= 'T' . $event['time'];
        }
        
        $formattedEvent = [
            'id' => $event['id'],
            'title' => $event['title'],
            'start' => $startDateTime,
            'description' => $event['description'],
            'location' => $event['location'],
            'color' => getEventColor($event['event_type']),
            'extendedProps' => [
                'event_type' => $event['event_type'],
                'team_id' => $event['team_id'],
                'description' => $event['description'],
                'location' => $event['location']
            ]
        ];
        
        $formattedEvents[] = $formattedEvent;
    }
    
    echo json_encode([
        'success' => true,
        'events' => $formattedEvents,
        'count' => count($formattedEvents)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading events: ' . $e->getMessage()
    ]);
}

function getEventColor($eventType) {
    $colors = [
        'training' => '#3b82f6',   // Blue
        'match' => '#ef4444',      // Red  
        'meeting' => '#10b981',    // Green
        'social' => '#f59e0b',     // Yellow
        'tournament' => '#8b5cf6', // Purple
    ];
    
    return $colors[strtolower($eventType)] ?? '#6b7280'; // Default gray
}
?>
