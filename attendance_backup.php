<?php
require_once 'includes/color_system.php';
require_once 'includes/auth.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// auth.php starts session when needed; avoid calling session_start() here to prevent notices
$currentPage = 'attendance';

// Database connection - use unified config
require_once 'database_config.php';
try {
    $db = DatabaseConfigSQLite::getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
$message = '';
$messageType = '';
$selectedEventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $eventId = (int)$_POST['event_id'];
    $attendanceData = $_POST['attendance'] ?? [];
    $notes = $_POST['notes'] ?? [];
    
    try {
        $db->beginTransaction();
        
        foreach ($attendanceData as $playerId => $status) {
            if (empty($status)) continue;
            
            $playerId = (int)$playerId;
            $note = trim($notes[$playerId] ?? '');
            
            $checkStmt = $db->prepare("SELECT id FROM attendance WHERE player_id = ? AND event_id = ?");
            $checkStmt->execute([$playerId, $eventId]);
            
            if ($checkStmt->fetch()) {
                $updateStmt = $db->prepare("UPDATE attendance SET status = ?, notes = ?, recorded_at = CURRENT_TIMESTAMP WHERE player_id = ? AND event_id = ?");
                $updateStmt->execute([$status, $note, $playerId, $eventId]);
            } else {
                $insertStmt = $db->prepare("INSERT INTO attendance (player_id, event_id, status, notes, recorded_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
                $insertStmt->execute([$playerId, $eventId, $status, $note]);
            }
        }
        
        $db->commit();
        $message = "Attendance recorded successfully for " . count($attendanceData) . " players!";
        $messageType = "success";
        $selectedEventId = $eventId;
        
    } catch (Exception $e) {
        $db->rollback();
        $message = "Error recording attendance: " . $e->getMessage();
        $messageType = "error";
    }
}

// Fetch all events
$eventsQuery = "
        SELECT 
                e.id,
                e.title,
                e.date as event_date,
                e.event_type,
                e.location,
                e.description,
                COUNT(a.id) as total_recorded,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count
        FROM events e
        LEFT JOIN attendance a ON e.id = a.event_id
        WHERE e.date >= DATE('now', '-30 day')
            AND e.date <= DATE('now', '+90 day')
        GROUP BY e.id, e.title, e.date, e.event_type, e.location, e.description
        ORDER BY e.date DESC
";

$eventsStmt = $db->query($eventsQuery);
$events = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get event details and players if event is selected
$eventDetails = null;
$players = [];
$attendanceStats = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'not_recorded' => 0];

if ($selectedEventId) {
    $eventStmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $eventStmt->execute([$selectedEventId]);
    $eventDetails = $eventStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($eventDetails) {
        $playersQuery = "
            SELECT 
                p.id,
                p.name as name,
                p.position as primary_position,
                CASE 
                    WHEN p.date_of_birth IS NOT NULL 
                    THEN CAST((julianday('now') - julianday(p.date_of_birth)) / 365.25 AS INTEGER)
                    ELSE NULL
                END as age,
                p.jersey_number,
                t.name as team_name,
                a.status as attendance_status,
                a.notes as attendance_notes
            FROM players p
            LEFT JOIN teams t ON p.team_id = t.id
            LEFT JOIN attendance a ON (p.id = a.player_id AND a.event_id = ?)
            WHERE p.status = 'active'
            ORDER BY name ASC
        ";
        
        $playersStmt = $db->prepare($playersQuery);
        $playersStmt->execute([$selectedEventId]);
        $players = $playersStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate stats
        foreach ($players as $player) {
            $status = $player['attendance_status'] ?? 'not_recorded';
            if (isset($attendanceStats[$status])) {
                $attendanceStats[$status]++;
            } else {
                $attendanceStats['not_recorded']++;
            }
        }
    }
}

$totalPlayers = count($players);
$attendanceRate = $totalPlayers > 0 ? round(($attendanceStats['present'] / $totalPlayers) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - VIVO United</title>
    <?php 
    // Include dynamic CSS system
    require_once 'includes/css_helper.php';
    vivo_include_head_css($db);
    ?>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="content-main">
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title">
                    <i class="fas fa-clipboard-check"></i>
                    Attendance Management
                </h1>
                <p class="page-subtitle">Track and manage player attendance for events and training sessions</p>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Event Selection -->
        <div class="dashboard-section">
            <h2 class="section-title">
                <i class="fas fa-calendar-alt"></i>
                Select Event
            </h2>
            <div class="card">
                <form method="GET" class="form-modern">
                    <div class="form-group">
                        <label for="event_id">Choose Event</label>
                        <select name="event_id" id="event_id" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Select an Event --</option>
                            <?php if (empty($events)): ?>
                                <option disabled>No events found</option>
                            <?php else: ?>
                                <?php foreach ($events as $event): ?>
                                    <?php
                                    $eventDate = new DateTime($event['event_date']);
                                    $now = new DateTime();
                                    $isPast = $eventDate < $now;
                                    $statusIcon = $isPast ? '📅' : '🔮';
                                    ?>
                                    <option value="<?= $event['id'] ?>" <?= $selectedEventId == $event['id'] ? 'selected' : '' ?>>
                                        <?= $statusIcon ?> <?= htmlspecialchars($event['title']) ?> - 
                                        <?= $eventDate->format('M j, Y g:i A') ?>
                                        <?php if ($event['total_recorded'] > 0): ?>
                                            (<?= $event['present_count'] ?>/<?= $event['total_recorded'] ?> present)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($eventDetails): ?>
            <!-- Event Details -->
            <div class="dashboard-section">
                <h2 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Event Details
                </h2>
                <div class="card">
                    <div class="event-details-grid">
                        <div class="detail-item">
                            <i class="fas fa-calendar-alt"></i>
                            <div>
                                <strong>Date & Time</strong>
                                <span><?= date('F j, Y g:i A', strtotime($eventDetails['date'] . ' ' . ($eventDetails['time'] ?? '12:00'))) ?></span>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <i class="fas fa-tag"></i>
                            <div>
                                <strong>Type</strong>
                                <span><?= ucfirst(htmlspecialchars($eventDetails['event_type'])) ?></span>
                            </div>
                        </div>
                        
                        <?php if ($eventDetails['location']): ?>
                            <div class="detail-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <strong>Location</strong>
                                    <span><?= htmlspecialchars($eventDetails['location']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($eventDetails['description'])): ?>
                            <div class="detail-item">
                                <i class="fas fa-info-circle"></i>
                                <div>
                                    <strong>Description</strong>
                                    <span><?= htmlspecialchars($eventDetails['description']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($totalPlayers > 0): ?>
                <!-- Attendance Stats -->
                <div class="dashboard-section">
                    <div class="stats-grid">
                        <div class="stat-card success">
                            <div class="stat-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number" id="present-count"><?= $attendanceStats['present'] ?></div>
                                <div class="stat-label">Present</div>
                            </div>
                        </div>
                        
                        <div class="stat-card danger">
                            <div class="stat-icon">
                                <i class="fas fa-times"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number" id="absent-count"><?= $attendanceStats['absent'] ?></div>
                                <div class="stat-label">Absent</div>
                            </div>
                        </div>
                        
                        <div class="stat-card warning">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number" id="late-count"><?= $attendanceStats['late'] ?></div>
                                <div class="stat-label">Late</div>
                            </div>
                        </div>
                        
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number" id="rate-count"><?= $attendanceRate ?>%</div>
                                <div class="stat-label">Attendance Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Attendance Form -->
                <div class="dashboard-section">
                    <h2 class="section-title">
                        <i class="fas fa-clipboard-list"></i>
                        Player Attendance
                        <span class="badge badge-primary"><?= count($players) ?> Players</span>
                    </h2>
                    
                    <form method="POST">
                        <input type="hidden" name="event_id" value="<?= $selectedEventId ?>">
                        
                        <!-- Bulk Actions -->
                        <div class="card bulk-actions-card">
                            <h3>Quick Actions</h3>
                            <div class="bulk-actions">
                                <div class="selection-controls">
                                    <label>
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        Select All Players
                                    </label>
                                    <span id="selectedCount" class="selection-count">0 selected</span>
                                </div>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-success" onclick="markAll('present')">
                                        <i class="fas fa-check"></i>
                                        Mark All Present
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="markAll('absent')">
                                        <i class="fas fa-times"></i>
                                        Mark All Absent
                                    </button>
                                    <button type="button" class="btn btn-warning" onclick="markAll('late')">
                                        <i class="fas fa-clock"></i>
                                        Mark All Late
                                    </button>
                                    <button type="button" class="btn btn-info" onclick="markSelected('present')">
                                        <i class="fas fa-check-circle"></i>
                                        Mark Selected Present
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" onclick="markSelected('absent')">
                                        <i class="fas fa-times-circle"></i>
                                        Mark Selected Absent
                                    </button>
                                    <button type="button" class="btn btn-outline-warning" onclick="markSelected('late')">
                                        <i class="fas fa-clock"></i>
                                        Mark Selected Late
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="clearAll()">
                                        <i class="fas fa-undo"></i>
                                        Clear All
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Top Save Button -->
                        <div class="form-actions">
                            <button type="submit" name="save_attendance" class="btn btn-primary btn-large">
                                <i class="fas fa-save"></i>
                                Save Attendance for <?= count($players) ?> Players
                            </button>
                        </div>
                        
                        <!-- Players Grid -->
                        <div class="players-grid-professional">
                            <?php foreach ($players as $player): ?>
                                <div class="player-card-pro attendance-card">
                                    <div class="player-header">
                                        <div class="player-avatar">
                                            <?php
                                            $nameParts = explode(' ', trim($player['name']));
                                            $initials = '';
                                            foreach (array_slice($nameParts, 0, 2) as $part) {
                                                if (!empty($part)) {
                                                    $initials .= strtoupper(substr($part, 0, 1));
                                                }
                                            }
                                            echo $initials ?: '?';
                                            ?>
                                        </div>
                                        <div class="player-info">
                                            <h3><?= htmlspecialchars($player['name']) ?></h3>
                                            <div class="player-meta">
                                                <?= htmlspecialchars($player['position'] ?? 'Unknown') ?>
                                                <?php if ($player['jersey_number']): ?>
                                                    • #<?= $player['jersey_number'] ?>
                                                <?php endif; ?>
                                                <?php if ($player['age']): ?>
                                                    • Age <?= $player['age'] ?>
                                                <?php endif; ?>
                                                <?php if ($player['team_name']): ?>
                                                    • <?= htmlspecialchars($player['team_name']) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="player-selection">
                                        <label>
                                            <input type="checkbox" class="player-checkbox" data-player-id="<?= $player['id'] ?>" onchange="updateSelectionCount()">
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="attendance-controls">
                                        <button type="button" class="attendance-btn present <?= ($player['attendance_status'] ?? '') === 'present' ? 'active' : '' ?>"
                                                onclick="setAttendance(<?= $player['id'] ?>, 'present')">
                                            <i class="fas fa-check"></i>
                                            Present
                                        </button>
                                        <button type="button" class="attendance-btn absent <?= ($player['attendance_status'] ?? '') === 'absent' ? 'active' : '' ?>"
                                                onclick="setAttendance(<?= $player['id'] ?>, 'absent')">
                                            <i class="fas fa-times"></i>
                                            Absent
                                        </button>
                                        <button type="button" class="attendance-btn late <?= ($player['attendance_status'] ?? '') === 'late' ? 'active' : '' ?>"
                                                onclick="setAttendance(<?= $player['id'] ?>, 'late')">
                                            <i class="fas fa-clock"></i>
                                            Late
                                        </button>
                                        <button type="button" class="attendance-btn excused <?= ($player['attendance_status'] ?? '') === 'excused' ? 'active' : '' ?>"
                                                onclick="setAttendance(<?= $player['id'] ?>, 'excused')">
                                            <i class="fas fa-shield-alt"></i>
                                            Excused
                                        </button>
                                    </div>
                                    
                                    <input type="hidden" name="attendance[<?= $player['id'] ?>]" id="attendance_<?= $player['id'] ?>" 
                                           value="<?= htmlspecialchars($player['attendance_status'] ?? '') ?>">
                                    
                                    <div class="form-group">
                                        <label for="notes_<?= $player['id'] ?>" class="sr-only">Notes for <?= htmlspecialchars($player['name']) ?></label>
                                        <input type="text" name="notes[<?= $player['id'] ?>]" id="notes_<?= $player['id'] ?>" class="form-control" 
                                               placeholder="Optional notes (injury, emergency, etc.)" 
                                               value="<?= htmlspecialchars($player['attendance_notes'] ?? '') ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="save_attendance" class="btn btn-primary btn-large">
                                <i class="fas fa-save"></i>
                                Save Attendance for <?= count($players) ?> Players
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-users-slash"></i>
                        </div>
                        <h3>No Players Found</h3>
                        <p>There are no active players to record attendance for this event.</p>
                        <a href="players.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i>
                            Add Players
                        </a>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <h3>Select an Event</h3>
                    <p>Choose an event from the dropdown above to start recording attendance.</p>
                    <?php if (empty($events)): ?>
                        <div class="empty-note">
                            <strong>No events found!</strong> You may need to create some events first.
                        </div>
                        <a href="events.php" class="btn btn-primary">
                            <i class="fas fa-calendar-plus"></i>
                            Create Event
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        /* Global text color fix */
        body {
            color: var(--text-color, #374151) !important;
        }
        
        .main-content {
            color: var(--text-color, #374151) !important;
        }
        
        .main-content * {
            color: inherit;
        }
        
        h1, h2, h3, h4, h5, h6, p, div, span, label {
            color: var(--text-color, #374151) !important;
        }
        
        /* Attendance-specific styles using global color system */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        /* Event Details Grid */
        .event-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        .detail-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.5rem;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            transition: all 0.2s ease;
        }
        
        .detail-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .detail-item i {
            color: var(--primary-color);
            font-size: 1.25rem;
            width: 24px;
            text-align: center;
            margin-top: 0.125rem;
        }
        
        .detail-item div {
            flex: 1;
        }
        
        .detail-item strong {
            display: block;
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .detail-item span {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 1rem;
        }
        
        /* Modern Bulk Actions */
        .bulk-actions-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            margin-bottom: 2rem;
        }
        
        .bulk-actions-card h3 {
            margin: 0 0 1.5rem 0;
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .bulk-actions {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .selection-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        
        .selection-controls label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            color: white;
        }
        
        .selection-controls input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: white;
            cursor: pointer;
        }
        
        .selection-count {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 0.75rem;
        }
        
        .action-buttons .btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            transition: all 0.2s ease;
        }
        
        .action-buttons .btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }
        
        /* Modern Player Cards */
        .players-grid-professional {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        @media (max-width: 768px) {
            .players-grid-professional {
                grid-template-columns: 1fr;
            }
        }
        
        .player-card-pro.attendance-card {
            position: relative;
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .player-card-pro.attendance-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--border-color);
            transition: all 0.3s ease;
        }
        
        .player-card-pro.attendance-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        
        .player-card-pro.attendance-card.has-status::before {
            background: var(--success-color);
        }
        
        .player-selection {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 2;
        }
        
        .player-checkbox {
            width: 20px;
            height: 20px;
            accent-color: var(--primary-color);
            cursor: pointer;
        }
        
        .player-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding-right: 2rem;
        }
        
        .player-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            flex-shrink: 0;
        }
        
        .player-info h3 {
            margin: 0 0 0.25rem 0;
            color: var(--text-primary);
            font-size: 1.125rem;
            font-weight: 600;
        }
        
        .player-meta {
            color: var(--text-muted);
            font-size: 0.875rem;
            line-height: 1.4;
        }
        
        /* Modern Attendance Controls */
        .attendance-controls {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }
        
        .attendance-btn {
            padding: 0.625rem 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background: var(--card-bg);
            color: var(--text-color, #374151);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            opacity: 1;
        }
        
        /* Default colors for each button type */
        .attendance-btn.present {
            border-color: var(--success-color) !important;
            color: white !important;
            background: var(--success-color) !important;
            opacity: 1 !important;
        }
        
        .attendance-btn.absent {
            border-color: var(--danger-color) !important;
            color: white !important;
            background: var(--danger-color) !important;
            opacity: 1 !important;
        }
        
        .attendance-btn.late {
            border-color: var(--warning-color) !important;
            color: white !important;
            background: var(--warning-color) !important;
            opacity: 1 !important;
        }
        
        .attendance-btn.excused {
            border-color: var(--info-color) !important;
            color: white !important;
            background: var(--info-color) !important;
            opacity: 1 !important;
        }
        
        .attendance-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-1px);
            opacity: 1;
        }
        
        /* Individual button hover colors */
        .attendance-btn.present:hover:not(.active) {
            border-color: var(--success-color);
            color: white;
            background: var(--success-color);
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .attendance-btn.absent:hover:not(.active) {
            border-color: var(--danger-color);
            color: white;
            background: var(--danger-color);
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .attendance-btn.late:hover:not(.active) {
            border-color: var(--warning-color);
            color: white;
            background: var(--warning-color);
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .attendance-btn.excused:hover:not(.active) {
            border-color: var(--info-color);
            color: white;
            background: var(--info-color);
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .attendance-btn.present.active {
            background: var(--success-color) !important;
            border-color: var(--success-color) !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
            opacity: 1 !important;
        }
        
        .attendance-btn.absent.active {
            background: var(--danger-color) !important;
            border-color: var(--danger-color) !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            opacity: 1 !important;
        }
        
        .attendance-btn.late.active {
            background: var(--warning-color) !important;
            border-color: var(--warning-color) !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(234, 179, 8, 0.3);
            opacity: 1 !important;
        }
        
        .attendance-btn.excused.active {
            background: var(--info-color) !important;
            border-color: var(--info-color) !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            opacity: 1 !important;
        }
        
        /* Notes Input */
        .form-group {
            margin-top: 0.5rem;
        }
        
        .form-group input[type="text"] {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--input-bg);
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .form-group input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        /* Stats Grid Modern */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-color);
        }
        
        .stat-card.success::before { background: var(--success-color); }
        .stat-card.danger::before { background: var(--danger-color); }
        .stat-card.warning::before { background: var(--warning-color); }
        .stat-card.primary::before { background: var(--primary-color); }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }
        
        .stat-card.success .stat-icon { background: var(--success-color); }
        .stat-card.danger .stat-icon { background: var(--danger-color); }
        .stat-card.warning .stat-icon { background: var(--warning-color); }
        .stat-card.primary .stat-icon { background: var(--primary-color); }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: var(--text-muted);
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        /* Form Actions */
        .form-actions {
            margin-top: 2rem;
            text-align: center;
        }
        
        .btn-large {
            padding: 1rem 2rem;
            font-size: 1.125rem;
            font-weight: 600;
            border-radius: 12px;
            min-width: 200px;
        }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            margin: 2rem 0;
        }
        
        .empty-icon {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }
        
        .empty-state h3 {
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 2rem;
            font-size: 1rem;
            line-height: 1.6;
        }
        
        .empty-note {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-text, #92400e);
            padding: 1rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            border-left: 4px solid var(--warning-color);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .event-details-grid {
                grid-template-columns: 1fr;
            }
            
            .players-grid-professional {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
            
            .attendance-controls {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <script>
        function setAttendance(playerId, status) {
            document.getElementById('attendance_' + playerId).value = status;
            
            const card = document.querySelector(`input[id="attendance_${playerId}"]`).closest('.player-card-pro');
            if (card) {
                const buttons = card.querySelectorAll('.attendance-btn');
                
                buttons.forEach(btn => btn.classList.remove('active'));
                const targetButton = card.querySelector(`.attendance-btn.${status}`);
                if (targetButton) {
                    targetButton.classList.add('active');
                }
            }
            
            updateStats();
        }
        
        function markAll(status) {
            document.querySelectorAll('[name^="attendance["]').forEach(input => {
                const playerId = input.name.match(/\d+/)[0];
                setAttendance(parseInt(playerId), status);
            });
        }
        
        function clearAll() {
            document.querySelectorAll('[name^="attendance["]').forEach(input => {
                input.value = '';
            });
            
            document.querySelectorAll('.status-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            updateStats();
        }
        
        function updateStats() {
            const stats = { present: 0, absent: 0, late: 0, excused: 0, not_recorded: 0 };
            
            document.querySelectorAll('[name^="attendance["]').forEach(input => {
                const status = input.value || 'not_recorded';
                if (stats.hasOwnProperty(status)) {
                    stats[status]++;
                } else {
                    stats.not_recorded++;
                }
            });
            
            const total = Object.values(stats).reduce((a, b) => a + b, 0);
            const rate = total > 0 ? Math.round((stats.present / total) * 100) : 0;
            
            // Update stat displays
            const presentEl = document.getElementById('present-count');
            const absentEl = document.getElementById('absent-count');
            const lateEl = document.getElementById('late-count');
            const rateEl = document.getElementById('rate-count');
            
            if (presentEl) presentEl.textContent = stats.present;
            if (absentEl) absentEl.textContent = stats.absent;
            if (lateEl) lateEl.textContent = stats.late;
            if (rateEl) rateEl.textContent = rate + '%';
        }
        
        // Multiple selection functions
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const playerCheckboxes = document.querySelectorAll('.player-checkbox');
            
            if (selectAllCheckbox) {
                playerCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                
                updateSelectionCount();
            }
        }
        
        function updateSelectionCount() {
            const selectedCheckboxes = document.querySelectorAll('.player-checkbox:checked');
            const count = selectedCheckboxes.length;
            const totalCheckboxes = document.querySelectorAll('.player-checkbox');
            
            const selectedCountElement = document.getElementById('selectedCount');
            if (selectedCountElement) {
                selectedCountElement.textContent = `${count} of ${totalCheckboxes.length} selected`;
            }
            
            // Update select all checkbox state
            const selectAllCheckbox = document.getElementById('selectAll');
            if (selectAllCheckbox) {
                if (count === 0) {
                    selectAllCheckbox.indeterminate = false;
                    selectAllCheckbox.checked = false;
                } else if (count === totalCheckboxes.length) {
                    selectAllCheckbox.indeterminate = false;
                    selectAllCheckbox.checked = true;
                } else {
                    selectAllCheckbox.indeterminate = true;
                }
            }
        }
        
        function markSelected(status) {
            const selectedCheckboxes = document.querySelectorAll('.player-checkbox:checked');
            
            if (selectedCheckboxes.length === 0) {
                alert('Please select at least one player first.');
                return;
            }
            
            selectedCheckboxes.forEach(checkbox => {
                const playerId = checkbox.dataset.playerId;
                setAttendance(parseInt(playerId), status);
            });
        }
        
        // Initialize stats on page load
        document.addEventListener('DOMContentLoaded', function() {
            try {
                updateStats();
                updateSelectionCount();
            } catch (error) {
                console.warn('Attendance page initialization error:', error);
            }
        });
    </script>
</body>
</html>
