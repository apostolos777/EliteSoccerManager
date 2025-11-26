<?php
// Models for Elite Football Manager

class Team {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function getAll() {
        return $this->db->fetchAll("
            SELECT t.*, o.name as organization_name 
            FROM teams t 
            LEFT JOIN organizations o ON t.organization_id = o.id 
            ORDER BY t.name
        ");
    }
    
    public function getById($id) {
        return $this->db->fetchOne("
            SELECT t.*, o.name as organization_name 
            FROM teams t 
            LEFT JOIN organizations o ON t.organization_id = o.id 
            WHERE t.id = ?
        ", [$id]);
    }
    
    public function create($data) {
        return $this->db->insert('teams', $data);
    }
    
    public function update($id, $data) {
        return $this->db->update('teams', $data, 'id = ?', [$id]);
    }
    
    public function delete($id) {
        return $this->db->delete('teams', 'id = ?', [$id]);
    }
    
    public function getPlayersCount($teamId) {
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM players WHERE team_id = ?", [$teamId]);
        return $result['count'];
    }
}

class Player {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function getAll($teamId = null) {
        $sql = "
            SELECT p.*, t.name as team_name 
            FROM players p 
            LEFT JOIN teams t ON p.team_id = t.id 
        ";
        $params = [];
        
        if ($teamId) {
            $sql .= " WHERE p.team_id = ?";
            $params[] = $teamId;
        }
        
        $sql .= " ORDER BY p.name";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getById($id) {
        return $this->db->fetchOne("
            SELECT p.*, t.name as team_name, t.age_group 
            FROM players p 
            LEFT JOIN teams t ON p.team_id = t.id 
            WHERE p.id = ?
        ", [$id]);
    }
    
    public function create($data) {
        // Generate unique player ID if not provided
        if (empty($data['unique_player_id'])) {
            $data['unique_player_id'] = $this->generateUniqueId();
        }
        return $this->db->insert('players', $data);
    }
    
    public function update($id, $data) {
        return $this->db->update('players', $data, 'id = ?', [$id]);
    }
    
    public function delete($id) {
        return $this->db->delete('players', 'id = ?', [$id]);
    }
    
    private function generateUniqueId() {
        $year = date('y');
        $month = date('m');
        $day = date('d');
        
        // Get next sequence number
        $result = $this->db->fetchOne("
            SELECT COUNT(*) as count 
            FROM players 
            WHERE unique_player_id LIKE ?
        ", [$year . $month . $day . '%']);
        
        $sequence = str_pad($result['count'] + 1, 3, '0', STR_PAD_LEFT);
        
        return $year . $month . $day . '-' . $sequence;
    }
    
    public function searchByName($name) {
        return $this->db->fetchAll("
            SELECT p.*, t.name as team_name 
            FROM players p 
            LEFT JOIN teams t ON p.team_id = t.id 
            WHERE p.name LIKE ? 
            ORDER BY p.name
        ", ['%' . $name . '%']);
    }
    
    public function getPlayerCountByTeam($teamId) {
        $sql = "SELECT COUNT(*) as count FROM players WHERE team_id = ?";
        $result = $this->db->fetchOne($sql, [$teamId]);
        return $result['count'] ?? 0;
    }
    
    public function getByTeam($teamId) {
        return $this->db->fetchAll("
            SELECT p.*, t.name as team_name 
            FROM players p 
            LEFT JOIN teams t ON p.team_id = t.id 
            WHERE p.team_id = ? 
            ORDER BY p.name
        ", [$teamId]);
    }
}

class Event {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function getAll($teamId = null, $startDate = null, $endDate = null) {
        $sql = "
            SELECT e.* 
            FROM events e 
            WHERE 1=1
        ";
        $params = [];
        
        if ($teamId) {
            // Match events by age_group if team is specified
            $sql .= " AND e.age_group = (SELECT age_group FROM teams WHERE id = ?)";
            $params[] = $teamId;
        }
        
        if ($startDate) {
            $sql .= " AND e.event_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND e.event_date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY e.event_date DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getById($id) {
        return $this->db->fetchOne("
            SELECT e.* 
            FROM events e 
            WHERE e.id = ?
        ", [$id]);
    }
    
    public function create($data) {
        return $this->db->insert('events', $data);
    }
    
    public function update($id, $data) {
        return $this->db->update('events', $data, 'id = ?', [$id]);
    }
    
    public function delete($id) {
        return $this->db->delete('events', 'id = ?', [$id]);
    }
    
    public function getUpcoming($limit = 10) {
        return $this->db->fetchAll("
            SELECT e.* 
            FROM events e 
            WHERE e.event_date >= CURDATE() 
            ORDER BY e.event_date ASC 
            LIMIT ?
        ", [$limit]);
    }
}

class Attendance {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function getByEvent($eventId) {
        return $this->db->fetchAll("
            SELECT a.*, p.name, p.jersey_number 
            FROM attendance a 
            LEFT JOIN players p ON a.player_id = p.id 
            WHERE a.event_id = ? 
            ORDER BY p.name
        ", [$eventId]);
    }
    
    public function markAttendance($eventId, $playerId, $status, $notes = '') {
        // Check if attendance already exists
        $existing = $this->db->fetchOne("
            SELECT id FROM attendance 
            WHERE event_id = ? AND player_id = ?
        ", [$eventId, $playerId]);
        
        if ($existing) {
            // Update existing attendance
            return $this->db->update('attendance', [
                'status' => $status,
                'notes' => $notes,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'event_id = ? AND player_id = ?', [$eventId, $playerId]);
        } else {
            // Create new attendance record
            return $this->db->insert('attendance', [
                'event_id' => $eventId,
                'player_id' => $playerId,
                'status' => $status,
                'notes' => $notes
            ]);
        }
    }
    
    public function getPlayerAttendance($playerId, $startDate = null, $endDate = null) {
        $sql = "
            SELECT a.*, e.title, e.event_date as date, e.event_type as type 
            FROM attendance a 
            LEFT JOIN events e ON a.event_id = e.id 
            WHERE a.player_id = ?
        ";
        $params = [$playerId];
        
        if ($startDate) {
            $sql .= " AND e.event_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND e.event_date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY e.event_date DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function recordAttendance($data) {
        // Check if attendance already exists
        $existing = $this->db->fetchOne("
            SELECT id FROM attendance 
            WHERE event_id = ? AND player_id = ?
        ", [$data['event_id'], $data['player_id']]);
        
        if ($existing) {
            // Update existing attendance
            $updateData = [
                'status' => $data['status'],
                'notes' => $data['notes'] ?? '',
                'recorded_by' => $data['recorded_by'] ?? 'System',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            return $this->db->update('attendance', $updateData, 'event_id = ? AND player_id = ?', 
                                   [$data['event_id'], $data['player_id']]);
        } else {
            // Create new attendance record
            $insertData = [
                'event_id' => $data['event_id'],
                'player_id' => $data['player_id'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? '',
                'recorded_by' => $data['recorded_by'] ?? 'System'
            ];
            return $this->db->insert('attendance', $insertData);
        }
    }
    
    public function getAttendanceByEvent($eventId) {
        return $this->db->fetchAll("
            SELECT a.*, p.name, p.jersey_number, p.id as unique_player_id
            FROM attendance a 
            LEFT JOIN players p ON a.player_id = p.id 
            WHERE a.event_id = ? 
            ORDER BY p.name
        ", [$eventId]);
    }
    
    public function getAttendanceCountByEvent($eventId) {
        $sql = "SELECT COUNT(*) as count FROM attendance WHERE event_id = ?";
        $result = $this->db->fetchOne($sql, [$eventId]);
        return $result['count'] ?? 0;
    }
}
?>
