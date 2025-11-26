<?php
/**
 * VIVO United - Events API
 * RESTful API for calendar event management
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../includes/auth.php';

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Initialize database connection
require_once __DIR__ . '/../wp-config.php';
$db = get_db_connection();
$user = getCurrentUser();
$isAdmin = isset($user['role']) && $user['role'] === 'admin';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            handleEventsList();
            break;
        case 'create':
            handleEventCreate();
            break;
        case 'update':
            handleEventUpdate();
            break;
        case 'update_date':
            handleEventDateUpdate();
            break;
        case 'delete':
            handleEventDelete();
            break;
        case 'teams':
            handleTeamsList();
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handleEventsList() {
    global $db, $isAdmin, $user;
    
    $start = $_GET['start'] ?? '';
    $end = $_GET['end'] ?? '';
    
    $query = "
        SELECT 
            e.*,
            t.name as team_name
        FROM events e
        LEFT JOIN teams t ON e.team_id = t.id
        WHERE 1=1
    ";
    
    $whereConditions = [];
    
    if ($start) {
        $query .= " AND e.date >= :start_date";
    }
    
    if ($end) {
        $query .= " AND e.date <= :end_date";
    }
    
    // If not admin, show only events for user's teams (if implemented)
    // For now, show all events to all logged-in users
    
    $query .= " ORDER BY e.date ASC, e.time ASC";
    
    $stmt = $db->prepare($query);
    
    if ($start) {
        $stmt->bindValue(':start_date', date('Y-m-d', strtotime($start)));
    }
    
    if ($end) {
        $stmt->bindValue(':end_date', date('Y-m-d', strtotime($end)));
    }
    
    $result = $stmt->execute();
    if (!$result) {
        throw new Exception('Failed to execute query');
    }
    
    $events = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $events[] = $row;
    }
    
    // Format events for FullCalendar
    $calendarEvents = [];
    foreach ($events as $event) {
        $startDateTime = $event['date'];
        if ($event['time']) {
            $startDateTime .= 'T' . $event['time'];
        }
        
        $calendarEvent = [
            'id' => $event['id'],
            'title' => $event['title'],
            'start' => $startDateTime,
            'allDay' => empty($event['time']),
            'extendedProps' => [
                'description' => $event['description'],
                'event_type' => $event['event_type'],
                'location' => $event['location'],
                'team_id' => $event['team_id'],
                'team_name' => $event['team_name'],
                'opponent' => $event['opponent'],
                'is_home_game' => $event['is_home_game'],
                'status' => $event['status'],
                'age_groups' => $event['age_groups'],
                'max_participants' => $event['max_participants'],
                'cost' => $event['cost'],
                'equipment_needed' => $event['equipment_needed'],
                'notes' => $event['notes'],
                'is_mandatory' => $event['is_mandatory']
            ]
        ];
        
        $calendarEvents[] = $calendarEvent;
    }
    
    echo json_encode(['success' => true, 'events' => $calendarEvents]);
}

function handleEventCreate() {
    global $db;
    
    $data = validateEventData($_POST);
    
    $query = "
        INSERT INTO events (
            title, description, event_type, date, time, location, team_id, 
            opponent, is_home_game, status, age_groups, max_participants, 
            cost, equipment_needed, notes, is_mandatory, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
        )
    ";
    
    $stmt = $db->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $db->lastErrorMsg());
    }
    
    $stmt->bindValue(1, $data['title'], SQLITE3_TEXT);
    $stmt->bindValue(2, $data['description'], SQLITE3_TEXT);
    $stmt->bindValue(3, $data['event_type'], SQLITE3_TEXT);
    $stmt->bindValue(4, $data['date'], SQLITE3_TEXT);
    $stmt->bindValue(5, $data['time'], SQLITE3_TEXT);
    $stmt->bindValue(6, $data['location'], SQLITE3_TEXT);
    $stmt->bindValue(7, $data['team_id'], SQLITE3_INTEGER);
    $stmt->bindValue(8, $data['opponent'], SQLITE3_TEXT);
    $stmt->bindValue(9, $data['is_home_game'], SQLITE3_INTEGER);
    $stmt->bindValue(10, $data['status'], SQLITE3_TEXT);
    $stmt->bindValue(11, $data['age_groups'], SQLITE3_TEXT);
    $stmt->bindValue(12, $data['max_participants'], SQLITE3_INTEGER);
    $stmt->bindValue(13, $data['cost'], SQLITE3_FLOAT);
    $stmt->bindValue(14, $data['equipment_needed'], SQLITE3_TEXT);
    $stmt->bindValue(15, $data['notes'], SQLITE3_TEXT);
    $stmt->bindValue(16, $data['is_mandatory'], SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Event created successfully']);
    } else {
        throw new Exception('Failed to create event: ' . $db->lastErrorMsg());
    }
}

function handleEventUpdate() {
    global $db;
    
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        throw new Exception('Event ID is required');
    }
    
    $data = validateEventData($_POST);
    
    $query = "
        UPDATE events SET 
            title = ?, description = ?, event_type = ?, date = ?, time = ?, 
            location = ?, team_id = ?, opponent = ?, is_home_game = ?, 
            status = ?, age_groups = ?, max_participants = ?, cost = ?, 
            equipment_needed = ?, notes = ?, is_mandatory = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ";
    
    $stmt = $db->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $db->lastErrorMsg());
    }
    
    $stmt->bindValue(1, $data['title'], SQLITE3_TEXT);
    $stmt->bindValue(2, $data['description'], SQLITE3_TEXT);
    $stmt->bindValue(3, $data['event_type'], SQLITE3_TEXT);
    $stmt->bindValue(4, $data['date'], SQLITE3_TEXT);
    $stmt->bindValue(5, $data['time'], SQLITE3_TEXT);
    $stmt->bindValue(6, $data['location'], SQLITE3_TEXT);
    $stmt->bindValue(7, $data['team_id'], SQLITE3_INTEGER);
    $stmt->bindValue(8, $data['opponent'], SQLITE3_TEXT);
    $stmt->bindValue(9, $data['is_home_game'], SQLITE3_INTEGER);
    $stmt->bindValue(10, $data['status'], SQLITE3_TEXT);
    $stmt->bindValue(11, $data['age_groups'], SQLITE3_TEXT);
    $stmt->bindValue(12, $data['max_participants'], SQLITE3_INTEGER);
    $stmt->bindValue(13, $data['cost'], SQLITE3_FLOAT);
    $stmt->bindValue(14, $data['equipment_needed'], SQLITE3_TEXT);
    $stmt->bindValue(15, $data['notes'], SQLITE3_TEXT);
    $stmt->bindValue(16, $data['is_mandatory'], SQLITE3_INTEGER);
    $stmt->bindValue(17, $id, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
    } else {
        throw new Exception('Failed to update event: ' . $db->lastErrorMsg());
    }
}

function handleEventDateUpdate() {
    global $db;
    
    $id = intval($_POST['id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    
    if (!$id || !$date) {
        throw new Exception('Event ID and date are required');
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('Invalid date format');
    }
    
    // Validate time format if provided
    if ($time && !preg_match('/^\d{2}:\d{2}$/', $time)) {
        throw new Exception('Invalid time format');
    }
    
    $query = "UPDATE events SET date = ?, time = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $db->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $db->lastErrorMsg());
    }
    
    $stmt->bindValue(1, $date, SQLITE3_TEXT);
    $stmt->bindValue(2, $time ?: null, SQLITE3_TEXT);
    $stmt->bindValue(3, $id, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Event date updated successfully']);
    } else {
        throw new Exception('Failed to update event date: ' . $db->lastErrorMsg());
    }
}

function handleEventDelete() {
    global $db;
    
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        throw new Exception('Event ID is required');
    }
    
    $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $db->lastErrorMsg());
    }
    
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
    } else {
        throw new Exception('Failed to delete event: ' . $db->lastErrorMsg());
    }
}

function handleTeamsList() {
    global $db;
    
    $result = $db->query("SELECT id, name FROM teams ORDER BY name");
    if (!$result) {
        throw new Exception('Failed to fetch teams: ' . $db->lastErrorMsg());
    }
    
    $teams = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $teams[] = $row;
    }
    
    echo json_encode(['success' => true, 'teams' => $teams]);
}

function validateEventData($data) {
    $validated = [];
    
    // Required fields
    $validated['title'] = trim($data['title'] ?? '');
    if (empty($validated['title'])) {
        throw new Exception('Event title is required');
    }
    
    $validated['event_type'] = $data['event_type'] ?? 'other';
    $allowedTypes = ['training', 'match', 'meeting', 'other'];
    if (!in_array($validated['event_type'], $allowedTypes)) {
        $validated['event_type'] = 'other';
    }
    
    $validated['date'] = $data['date'] ?? '';
    if (empty($validated['date'])) {
        throw new Exception('Event date is required');
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $validated['date'])) {
        throw new Exception('Invalid date format');
    }
    
    // Optional fields
    $validated['description'] = trim($data['description'] ?? '');
    $validated['time'] = trim($data['time'] ?? '') ?: null;
    $validated['location'] = trim($data['location'] ?? '');
    $validated['team_id'] = !empty($data['team_id']) ? intval($data['team_id']) : null;
    $validated['opponent'] = trim($data['opponent'] ?? '');
    $validated['is_home_game'] = isset($data['is_home_game']) ? 1 : 1; // Default to home
    $validated['status'] = $data['status'] ?? 'Scheduled';
    $validated['age_groups'] = trim($data['age_groups'] ?? '');
    $validated['max_participants'] = !empty($data['max_participants']) ? intval($data['max_participants']) : null;
    $validated['cost'] = !empty($data['cost']) ? floatval($data['cost']) : null;
    $validated['equipment_needed'] = trim($data['equipment_needed'] ?? '');
    $validated['notes'] = trim($data['notes'] ?? '');
    $validated['is_mandatory'] = isset($data['is_mandatory']) ? 1 : 0;
    
    // Validate time format if provided
    if ($validated['time'] && !preg_match('/^\d{2}:\d{2}$/', $validated['time'])) {
        throw new Exception('Invalid time format');
    }
    
    // Validate status
    $allowedStatuses = ['Scheduled', 'Ongoing', 'Completed', 'Cancelled'];
    if (!in_array($validated['status'], $allowedStatuses)) {
        $validated['status'] = 'Scheduled';
    }
    
    // Sanitize inputs
    $validated['title'] = htmlspecialchars($validated['title'], ENT_QUOTES, 'UTF-8');
    $validated['description'] = htmlspecialchars($validated['description'], ENT_QUOTES, 'UTF-8');
    $validated['location'] = htmlspecialchars($validated['location'], ENT_QUOTES, 'UTF-8');
    $validated['opponent'] = htmlspecialchars($validated['opponent'], ENT_QUOTES, 'UTF-8');
    $validated['age_groups'] = htmlspecialchars($validated['age_groups'], ENT_QUOTES, 'UTF-8');
    $validated['equipment_needed'] = htmlspecialchars($validated['equipment_needed'], ENT_QUOTES, 'UTF-8');
    $validated['notes'] = htmlspecialchars($validated['notes'], ENT_QUOTES, 'UTF-8');
    
    return $validated;
}
?>
