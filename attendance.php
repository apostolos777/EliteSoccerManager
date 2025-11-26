<?php
/**
 * VIVO United - Attendance Management
 */

// Load configuration and authentication
require_once 'includes/color_system.php';
require_once 'includes/auth.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$currentPage = 'attendance';

// Database connection - use unified config
require_once 'database_config.php';
try {
    $db = DatabaseConfigSQLite::getConnection();
} catch (Exception $e) { 
    die("Database connection failed: " . $e->getMessage()); 
}

// Clear color cache to ensure fresh data
if (class_exists('VIVOColorSystem')) {
    VIVOColorSystem::clearCache();
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
        $savedCount = 0;

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

            $savedCount++;
        }

        $db->commit();
        $message = "Attendance recorded successfully for " . $savedCount . " players!";
        $messageType = "success";
        $selectedEventId = $eventId;
        
    } catch (Exception $e) {
        $db->rollback();
        $message = "Error recording attendance: " . $e->getMessage();
        $messageType = "error";
    }
}

// Fetch all events (no date restrictions)
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
        // Use correlated subqueries to fetch the latest attendance status/notes for the selected event
        $playersQuery = "
            SELECT 
                p.id,
                p.name as name,
                p.surname as surname,
                p.position as primary_position,
                p.primary_position as primary_position_fallback,
                p.secondary_position,
                p.nickname,
                p.profile_image,
                p.date_of_birth,
                p.contact_number,
                CASE 
                    WHEN p.date_of_birth IS NOT NULL 
                    THEN CAST((julianday('now') - julianday(p.date_of_birth)) / 365.25 AS INTEGER)
                    ELSE NULL
                END as age,
                p.jersey_number,
                t.name as team_name,
                (
                    SELECT a2.status FROM attendance a2
                    WHERE a2.player_id = p.id AND a2.event_id = :event_id
                    ORDER BY a2.recorded_at DESC, a2.id DESC LIMIT 1
                ) as attendance_status,
                (
                    SELECT a3.notes FROM attendance a3
                    WHERE a3.player_id = p.id AND a3.event_id = :event_id
                    ORDER BY a3.recorded_at DESC, a3.id DESC LIMIT 1
                ) as attendance_notes
            FROM players p
            LEFT JOIN teams t ON p.team_id = t.id
            WHERE p.status = 'active'
            ORDER BY name ASC
        ";

        $playersStmt = $db->prepare($playersQuery);
        $playersStmt->execute(['event_id' => $selectedEventId]);
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
    // Force inline color variables as a fallback so this page always has theme colors
    if (class_exists('VIVOColorSystem')) {
        echo "<!-- Inline VIVO color variables for attendance page -->\n";
        echo "<style id=\"attendance-inline-colors\">\n";
        // pass the active DB connection if available
        echo VIVOColorSystem::generateCSSVariables(isset($db) ? $db : null);
        echo "</style>\n";
    }
    ?>
</head>
<body>
    <?php include 'includes/layout_start.php'; ?>
    
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
            <!-- Event Details (compact single-row) -->
            <div class="dashboard-section">
                <h2 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Event Details
                </h2>
                <div class="card">
                    <div class="event-details-row">
                        <div class="ev-item ev-datetime">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?= date('F j, Y g:i A', strtotime($eventDetails['date'] . ' ' . ($eventDetails['time'] ?? '12:00'))) ?></span>
                        </div>

                        <div class="ev-item ev-type">
                            <i class="fas fa-tag"></i>
                            <span><?= ucfirst(htmlspecialchars($eventDetails['event_type'])) ?></span>
                        </div>

                        <?php if ($eventDetails['location']): ?>
                            <div class="ev-item ev-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= htmlspecialchars($eventDetails['location']) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($eventDetails['description'])): ?>
                            <div class="ev-item ev-desc" title="<?= htmlspecialchars($eventDetails['description']) ?>">
                                <i class="fas fa-info-circle"></i>
                                <span><?= htmlspecialchars($eventDetails['description']) ?></span>
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
                        <span class="badge badge-primary" id="playerCountBadge"><?= count($players) ?> Players</span>
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
                                    <span id="selectedCount" class="badge badge-primary selection-count">0 selected</span>
                                </div>
                                <div class="agegroup-controls" style="margin-left:1rem;">
                                    <label>Age Groups</label>
                                    <div id="ageGroupList" style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <?php
                                        // Predefined age groups used elsewhere in the app (extended)
                                        $ageGroupsList = require_once __DIR__ . '/includes/age_groups.php';
                                        foreach ($ageGroupsList as $ag): ?>
                                            <label style="font-weight:400;">
                                                <input type="checkbox" class="age-group-checkbox" value="<?= $ag ?>" onchange="toggleAgeGroupSelection(this)"> <?= $ag ?>
                                            </label>
                                        <?php endforeach; ?>
                                        <label style="font-weight:400;">
                                            <input type="checkbox" id="agegroup-select-all" onchange="toggleAllAgeGroups(this)"> All
                                        </label>
                                    </div>
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
                                    <button type="submit" name="save_attendance" class="btn btn-primary">
                                        <i class="fas fa-save"></i>
                                        Save Attendance
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Top Save Button removed to reduce duplication -->
                        
                        <!-- Players Grid -->
                        <div class="players-grid-professional">
                            <?php foreach ($players as $player): ?>
                                <?php
                                // Prepare name parts and prefer stored surname when available
                                $name_full = trim($player['name'] ?? '');
                                $db_surname = trim($player['surname'] ?? '');
                                $name_parts_all = $name_full !== '' ? preg_split('/\s+/', $name_full) : [];
                                $first_name = count($name_parts_all) ? $name_parts_all[0] : $name_full;
                                $surname_display = '';
                                if ($db_surname !== '') {
                                    $surname_display = $db_surname;
                                    // if the full name ends with the stored surname, derive a cleaner first name
                                    if (preg_match('/\b' . preg_quote($db_surname, '/') . '\b$/i', $name_full)) {
                                        $derived = trim(preg_replace('/\b' . preg_quote($db_surname, '/') . '\b$/i', '', $name_full));
                                        if ($derived !== '') $first_name = $derived;
                                    }
                                } else {
                                    if (!empty($name_parts_all)) {
                                        $surname_display = array_pop($name_parts_all);
                                        $first_name = count($name_parts_all) ? $name_parts_all[0] : $first_name;
                                    }
                                }
                                // Determine age group label from computed age (if available)
                                $ageVal = isset($player['age']) && $player['age'] !== null ? (int)$player['age'] : null;
                                $playerAgeGroup = '';
                                if ($ageVal !== null) {
                                    if ($ageVal < 6) $playerAgeGroup = 'U6';
                                    elseif ($ageVal <= 7) $playerAgeGroup = 'U7';
                                    elseif ($ageVal <= 8) $playerAgeGroup = 'U8';
                                    elseif ($ageVal <= 9) $playerAgeGroup = 'U9';
                                    elseif ($ageVal <= 10) $playerAgeGroup = 'U10';
                                    elseif ($ageVal <= 11) $playerAgeGroup = 'U11';
                                    elseif ($ageVal <= 12) $playerAgeGroup = 'U12';
                                    elseif ($ageVal <= 13) $playerAgeGroup = 'U13';
                                    elseif ($ageVal <= 14) $playerAgeGroup = 'U14';
                                    elseif ($ageVal <= 15) $playerAgeGroup = 'U15';
                                    elseif ($ageVal <= 16) $playerAgeGroup = 'U16';
                                    elseif ($ageVal <= 17) $playerAgeGroup = 'U17';
                                    elseif ($ageVal <= 18) $playerAgeGroup = 'U18';
                                    elseif ($ageVal <= 19) $playerAgeGroup = 'U19';
                                    elseif ($ageVal <= 20) $playerAgeGroup = 'U20';
                                    elseif ($ageVal <= 21) $playerAgeGroup = 'U21';
                                    else $playerAgeGroup = 'Senior';
                                } else {
                                    // Fallback: try to derive age group from team name if present, e.g., "U12" in team name
                                    $teamName = $player['team_name'] ?? '';
                                    if ($teamName) {
                                        // match patterns like "U12" or "Under 12" or just the number
                                        if (preg_match('/\b(U\d{1,2})\b/i', $teamName, $m)) {
                                            $playerAgeGroup = strtoupper($m[1]);
                                        } elseif (preg_match('/\bUnder\s*(\d{1,2})\b/i', $teamName, $m2)) {
                                            $playerAgeGroup = 'U' . $m2[1];
                                        } elseif (preg_match('/\b(\d{1,2})\b/', $teamName, $m3)) {
                                            // fallback: if team name contains a standalone number, assume Under X
                                            $playerAgeGroup = 'U' . $m3[1];
                                        } elseif (stripos($teamName, 'senior') !== false) {
                                            $playerAgeGroup = 'Senior';
                                        }
                                    }
                                }
                                ?>
                                <div class="player-card-pro attendance-card" data-age-group="<?= $playerAgeGroup ?>" data-team="<?= htmlspecialchars($player['team_name'] ?? '') ?>">
                                    <div class="player-header">
                                        <div class="player-avatar">
                                            <?php
                                            if (!empty($player['profile_image'])) {
                                                // show image if present
                                                $img = htmlspecialchars($player['profile_image']);
                                                $alt = htmlspecialchars($name_full ?: ($first_name . ' ' . $surname_display));
                                                echo '<img src="' . $img . '" alt="' . $alt . '" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">';
                                            } else {
                                                $nameParts = explode(' ', $name_full);
                                                $initials = '';
                                                foreach (array_slice($nameParts, 0, 2) as $part) {
                                                    if (!empty($part)) {
                                                        $initials .= strtoupper(substr($part, 0, 1));
                                                    }
                                                }
                                                echo '<span style="display:inline-block;width:100%;text-align:center;">' . ($initials ?: '?') . '</span>';
                                            }
                                            ?>
                                        </div>
                                        <div class="player-info">
                                            <h3>
                                                <a href="player_profile.php?id=<?= $player['id'] ?>"><?= htmlspecialchars($first_name ?: $name_full) ?></a>
                                                <?php if ($surname_display && strcasecmp($surname_display, $first_name) !== 0): ?>
                                                    <small class="player-surname" style="font-weight:600;margin-left:.5rem;color:var(--text-muted);"><?= htmlspecialchars($surname_display) ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty($player['nickname'])): ?> <small style="font-weight:500; margin-left:.4rem;">&ldquo;<?= htmlspecialchars($player['nickname']) ?>&rdquo;</small><?php endif; ?>
                                            </h3>
                                            <div class="player-meta">
                                                <?php $pos = htmlspecialchars($player['primary_position'] ?? $player['primary_position_fallback'] ?? 'Unknown'); ?>
                                                <strong style="font-weight:600;">Position:</strong> <?= $pos ?>
                                                <?php if ($player['jersey_number']): ?> • <strong>#</strong><?= $player['jersey_number'] ?><?php endif; ?>
                                                <?php if ($player['age']): ?> • <strong>Age:</strong> <?= $player['age'] ?>
                                                <?php elseif (!empty($player['date_of_birth'])): ?> • <strong>DOB:</strong> <?= htmlspecialchars($player['date_of_birth']) ?>
                                                <?php endif; ?>
                                                <?php if (!empty($player['team_name'])): ?> • <strong>Team:</strong> <?= htmlspecialchars($player['team_name']) ?><?php endif; ?>
                                                <?php if (!empty($player['contact_number'])): ?> • <a href="tel:<?= htmlspecialchars($player['contact_number']) ?>"><?= htmlspecialchars($player['contact_number']) ?></a><?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="player-selection">
                                        <label>
                                            <input type="checkbox" class="player-checkbox" data-player-id="<?= $player['id'] ?>" onchange="savePlayerSelection(this.dataset.playerId, this.checked); updateSelectionCount();">
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
    
    <?php include 'includes/layout_end.php'; ?>
    
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
        /* Compact single-row event details */
        .event-details-row {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            padding: 0.35rem 0.75rem;
            background: var(--card-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-size: 0.92rem;
            white-space: nowrap;
            overflow: hidden;
        }

        .event-details-row .ev-item { display:flex; align-items:center; gap:0.5rem; color:var(--text-muted); }
        .event-details-row .ev-item i { color: var(--primary); min-width:18px; }
    .event-details-row .ev-item span { display: inline-block; max-width: 42ch; }
        
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
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
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
            gap: 0.75rem;
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

        /* Outline buttons in bulk action area should use theme colors */
        /* Solid theme-colored bulk action buttons */
        .action-buttons .btn.btn-outline-danger {
            background: var(--danger) !important;
            border-color: var(--danger) !important;
            color: white !important;
        }

        .action-buttons .btn.btn-outline-danger:hover {
            background: var(--danger-dark, var(--danger)) !important;
            color: white !important;
            border-color: var(--danger-dark, var(--danger)) !important;
        }

        .action-buttons .btn.btn-outline-warning {
            background: var(--warning) !important;
            border-color: var(--warning) !important;
            color: white !important;
        }

        .action-buttons .btn.btn-outline-warning:hover {
            background: var(--warning-dark, var(--warning)) !important;
            color: white !important;
            border-color: var(--warning-dark, var(--warning)) !important;
        }
        
        /* Modern Player Cards */
        .players-grid-professional {
            display: grid;
            grid-template-columns: repeat(4, 1fr); /* 4 columns on wide screens */
            gap: 0.75rem;
            margin-top: 0.6rem;
        }

        /* Responsive: 2 columns on medium screens, 1 column on small screens */
        @media (max-width: 1024px) {
            .players-grid-professional {
                grid-template-columns: repeat(2, 1fr);
            }
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
            background: var(--input-bg);
            color: var(--text-primary);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.16s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        /* Colored attendance buttons using the color system variables */
        .attendance-btn.present {
            border-color: var(--success) !important;
            color: white !important;
            background: var(--success) !important;
        }

        .attendance-btn.absent {
            border-color: var(--danger) !important;
            color: white !important;
            background: var(--danger) !important;
        }

        .attendance-btn.late {
            border-color: var(--warning) !important;
            color: white !important;
            background: var(--warning) !important;
        }

        .attendance-btn.excused {
            border-color: var(--primary) !important;
            color: white !important;
            background: var(--primary) !important;
        }

        .attendance-btn:hover {
            filter: brightness(0.98);
            transform: translateY(-1px);
        }

        .attendance-btn.active {
            box-shadow: 0 6px 18px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        
        /* Individual button hover colors - now using hardcoded colors */
        
        /* Active state colors now using hardcoded values */
        
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

        /* Force player count badge text to white */
        .section-title .badge,
        .badge.badge-primary {
            color: white !important;
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
            // Clear all attendance hidden inputs
            document.querySelectorAll('[name^="attendance["]').forEach(input => {
                input.value = '';
            });

            // Remove active state from attendance buttons
            document.querySelectorAll('.attendance-btn').forEach(btn => btn.classList.remove('active'));

            // Uncheck all player selection checkboxes and persist the change
            document.querySelectorAll('.player-checkbox').forEach(cb => {
                cb.checked = false;
                try { savePlayerSelection(cb.dataset.playerId, false); } catch (e) {}
            });

            // Also clear age-group checkbox states and persisted age-group selection for this event
            try {
                document.querySelectorAll('.age-group-checkbox').forEach(cb => cb.checked = false);
                const master = document.getElementById('agegroup-select-all');
                if (master) master.checked = false;
                // remove persisted age groups for the selected event
                if (selectedEventId) {
                    try { localStorage.removeItem('attendance_agegroups_' + selectedEventId); } catch (e) {}
                }
            } catch (e) {}

            updateStats();
            updateAgeGroupFilter(); // Update player visibility after clearing age groups
            updateSelectionCount();
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
            
            if (selectAllCheckbox) {
                // Only affect visible players (not filtered out)
                const visiblePlayerCards = document.querySelectorAll('.player-card-pro:not([style*="display: none"])');
                const visibleCheckboxes = Array.from(visiblePlayerCards).map(card => card.querySelector('.player-checkbox')).filter(cb => cb);
                
                visibleCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                    // persist selection
                    savePlayerSelection(checkbox.dataset.playerId, checkbox.checked);
                });
                
                updateSelectionCount();
            }
        }
        
        function updateSelectionCount() {
            // Count only checkboxes from visible players (not filtered out)
            const visiblePlayerCards = document.querySelectorAll('.player-card-pro:not([style*="display: none"])');
            const visibleCheckboxes = Array.from(visiblePlayerCards).map(card => card.querySelector('.player-checkbox')).filter(cb => cb);
            const selectedCheckboxes = visibleCheckboxes.filter(cb => cb.checked);
            
            const count = selectedCheckboxes.length;
            const visibleTotal = visibleCheckboxes.length;
            
            console.log('UpdateSelectionCount:', { count, visibleTotal, visibleCheckboxes: visibleCheckboxes.length, selectedCheckboxes: selectedCheckboxes.length });
            
            const selectedCountElement = document.getElementById('selectedCount');
            if (selectedCountElement) {
                selectedCountElement.textContent = `${count} of ${visibleTotal} selected`;
            }
            
            // Update the save button text to show selected count
            const saveButton = document.querySelector('.form-actions button[name="save_attendance"]');
            console.log('Save button found:', saveButton);
            if (saveButton) {
                const saveIcon = saveButton.querySelector('i') ? saveButton.querySelector('i').outerHTML + ' ' : '';
                if (count > 0) {
                    saveButton.innerHTML = `${saveIcon}Save Attendance for ${count} Selected Player${count !== 1 ? 's' : ''}`;
                } else {
                    saveButton.innerHTML = `${saveIcon}Save Attendance for ${visibleTotal} Player${visibleTotal !== 1 ? 's' : ''}`;
                }
                console.log('Save button updated to:', saveButton.innerHTML);
            }
            
            // Update the player count badge to show visible players
            const playerCountBadge = document.getElementById('playerCountBadge');
            if (playerCountBadge) {
                playerCountBadge.textContent = `${visibleTotal} Player${visibleTotal !== 1 ? 's' : ''}`;
                console.log('Player count badge updated to:', playerCountBadge.textContent);
            }
            
            // Update select all checkbox state (only for visible players)
            const selectAllCheckbox = document.getElementById('selectAll');
            if (selectAllCheckbox) {
                if (count === 0) {
                    selectAllCheckbox.indeterminate = false;
                    selectAllCheckbox.checked = false;
                } else if (count === visibleTotal) {
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

        // expose the selected event id to JS for localStorage keys
        const selectedEventId = <?= json_encode($selectedEventId) ?>;

        // LocalStorage helpers for player selection and age-group persistence
        function savePlayerSelection(playerId, checked) {
            if (!selectedEventId) return;
            try { localStorage.setItem('attendance_selected_' + selectedEventId + '_' + playerId, checked ? '1' : '0'); } catch (e) {}
        }

        function restorePlayerSelections() {
            if (!selectedEventId) return;
            document.querySelectorAll('.player-checkbox').forEach(cb => {
                const playerId = cb.dataset.playerId;
                try {
                    const val = localStorage.getItem('attendance_selected_' + selectedEventId + '_' + playerId);
                    if (val !== null) cb.checked = (val === '1');
                } catch (e) {}
            });
            updateSelectionCount();
        }

        function saveAgeGroupState() {
            if (!selectedEventId) return;
            try {
                const selected = Array.from(document.querySelectorAll('.age-group-checkbox:checked')).map(cb => cb.value);
                localStorage.setItem('attendance_agegroups_' + selectedEventId, JSON.stringify(selected));
            } catch (e) {}
        }

        function restoreAgeGroupState() {
            if (!selectedEventId) return;
            try {
                const raw = localStorage.getItem('attendance_agegroups_' + selectedEventId);
                if (!raw) return;
                const arr = JSON.parse(raw);
                if (!Array.isArray(arr)) return;
                document.querySelectorAll('.age-group-checkbox').forEach(cb => { cb.checked = arr.includes(cb.value); });
                const master = document.getElementById('agegroup-select-all');
                if (master) master.checked = document.querySelectorAll('.age-group-checkbox:checked').length === document.querySelectorAll('.age-group-checkbox').length;
                // After restoring the age group checkboxes, apply the filter immediately
                try { updateAgeGroupFilter(); } catch (e) { console.warn('Failed to apply age group filter on restore', e); }
            } catch (e) {}
        }

        // Age group selection helpers
        // Update visible players based on selected age groups (also consider team name when age group is missing)
        function updateAgeGroupFilter() {
            const selectedCheckboxes = document.querySelectorAll('.age-group-checkbox:checked');
            const selected = Array.from(selectedCheckboxes).map(cb => {
                const value = cb.value.toUpperCase();
                // Normalize "Under X" to "UX" format for consistent matching
                const normalized = value.replace(/^UNDER\s*(\d+)$/, 'U$1');
                return normalized;
            });
            const playerCards = document.querySelectorAll('.player-card-pro');

            const anySelectedGroups = selected.length > 0;

            playerCards.forEach(card => {
                const ag = (card.getAttribute('data-age-group') || '').toString().toUpperCase();
                const team = (card.getAttribute('data-team') || '').toString().toUpperCase();
                const checkbox = card.querySelector('.player-checkbox');

                let matches = false;
                if (!anySelectedGroups) {
                    matches = true;
                } else {
                    // Direct age-group match (both normalized to UX format)
                    if (ag && selected.includes(ag)) matches = true;

                    // Also check if the selected group matches the player's age group in different formats
                    if (!matches && ag) {
                        for (const sel of selected) {
                            // Check if selected "UX" matches player's "UX"
                            if (sel === ag) { matches = true; break; }
                            
                            // Check if selected "Under X" matches player's "UX"
                            if (sel.startsWith('UNDER ') && ag.startsWith('U')) {
                                const selNum = sel.replace('UNDER ', '');
                                const agNum = ag.replace('U', '');
                                if (selNum === agNum) { matches = true; break; }
                            }
                            
                            // Handle special cases like Senior, Social, etc.
                            if ((sel === 'SENIOR' && ag === 'SENIOR') ||
                                (sel === 'SOCIAL' && ag === 'SOCIAL') ||
                                (sel === 'FUTSAL' && ag === 'FUTSAL') ||
                                (sel === 'MIXED' && ag === 'MIXED') ||
                                (sel === 'VETERANS' && ag === 'VETERANS') ||
                                (sel === 'LEGENDS' && ag === 'LEGENDS') ||
                                (sel === 'FIRST TEAM' && ag === 'FIRST TEAM') ||
                                (sel === 'U23' && ag === 'U23') ||
                                (sel === '5-A-SIDE' && ag === '5-A-SIDE') ||
                                (sel === 'OVER 35' && ag === 'OVER 35')) {
                                matches = true; break;
                            }
                        }
                    }

                    // Attempt to match selected age groups against team name when age-group is missing or not matching
                    if (!matches && team) {
                        for (const sel of selected) {
                            if (sel === 'SENIOR' && team.indexOf('SENIOR') !== -1) { matches = true; break; }
                            
                            // Handle "Under X" format
                            if (sel.startsWith('UNDER ')) {
                                const num = sel.replace('UNDER ', '');
                                const re = new RegExp('\\b(?:U' + num + '|UNDER\\s*' + num + ')\\b');
                                if (re.test(team)) { matches = true; break; }
                            }
                            
                            // Handle "UX" format
                            if (/^U(\d{1,2})$/.test(sel)) {
                                const m = sel.match(/^U(\d{1,2})$/);
                                const n = m ? m[1] : sel.replace(/^U/, '');
                                const re = new RegExp('\\b(?:U' + n + '|UNDER\\s*' + n + ')\\b');
                                if (re.test(team)) { matches = true; break; }
                            }
                        }
                    }
                }

                card.style.display = matches ? '' : 'none';
                // Only auto-check visible players when at least one age-group is actively selected
                if (checkbox) {
                    if (anySelectedGroups) {
                        checkbox.checked = matches;
                        const pid = checkbox.dataset.playerId;
                        if (pid) {
                            savePlayerSelection(pid, checkbox.checked);
                            // Auto-set attendance status to "present" for filtered players
                            if (matches) {
                                setAttendance(parseInt(pid), 'present');
                            } else {
                                // Clear attendance status for players that don't match the filter
                                const attendanceInput = document.getElementById('attendance_' + pid);
                                if (attendanceInput) {
                                    attendanceInput.value = '';
                                    // Remove active class from all attendance buttons for this player
                                    const card = checkbox.closest('.player-card-pro');
                                    if (card) {
                                        card.querySelectorAll('.attendance-btn').forEach(btn => btn.classList.remove('active'));
                                    }
                                }
                            }
                        }
                    } else {
                        // no age group filter: restore persisted per-player selection if present
                        const pid = checkbox.dataset.playerId;
                        try {
                            const val = localStorage.getItem('attendance_selected_' + selectedEventId + '_' + pid);
                            if (val !== null) checkbox.checked = (val === '1');
                        } catch (e) {}
                    }
                }
            });

            updateSelectionCount();
        }

        function toggleAgeGroupSelection(checkbox) {
            // When an age-group checkbox toggles, re-run the filter and persist the age-group state
            updateAgeGroupFilter();
            saveAgeGroupState();
        }

        function toggleAllAgeGroups(master) {
            const checked = master.checked;
            document.querySelectorAll('.age-group-checkbox').forEach(cb => { cb.checked = checked; });
            updateAgeGroupFilter();
            saveAgeGroupState();
        }
        
        // Initialize stats and restore persisted selections on page load
        document.addEventListener('DOMContentLoaded', function() {
            try {
                // restore persisted age-group selections and player checkbox selections
                restoreAgeGroupState();
                restorePlayerSelections();

                updateStats();
                updateSelectionCount();
            } catch (error) {
                console.warn('Attendance page initialization error:', error);
            }
        });
    </script>
</body>
</html>
