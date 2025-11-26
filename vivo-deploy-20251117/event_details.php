<?php
// Event Details Page with Database Integration
// VIVO United Football Manager
// Updated: July 23, 2025

require_once 'includes/color_system.php';
require_once 'database_factory.php';

// Check authentication  
if (!function_exists('isLoggedIn')) {
    require_once 'includes/auth.php';
}

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Set current page for sidebar navigation
$currentPage = 'events';

// Initialize database connection
try {
    $db = DatabaseFactory::getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get event ID
$event_id = $_GET['id'] ?? null;
if (!$event_id) {
    header('Location: events.php');
    exit;
}

// Get event details with team information
$event_query = "
    SELECT e.*, 
           t.name as team_name,
           t.age_group,
           t.coach_name,
           t.description as team_description
    FROM events e
    LEFT JOIN teams t ON e.team_id = t.id
    WHERE e.id = ?
";

$stmt = $db->prepare($event_query);
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: events.php');
    exit;
}

// Get attendance records for this event
$attendance_query = "
    SELECT a.*, 
           CASE 
               WHEN p.surname IS NOT NULL AND p.surname != '' 
               THEN (p.name || ' ' || p.surname) 
               ELSE p.name 
           END as name, 
           p.jersey_number, 
           p.position 
    FROM attendance a
    JOIN players p ON a.player_id = p.id
    WHERE a.event_id = ?
    ORDER BY p.jersey_number ASC
";

$stmt = $db->prepare($attendance_query);
$stmt->execute([$event_id]);
$attendance_records = $stmt->fetchAll();

// Get team players if team is specified
$team_players = [];
if ($event['team_id']) {
    $players_query = "
        SELECT id, 
               CASE 
                   WHEN surname IS NOT NULL AND surname != '' 
                   THEN (name || ' ' || surname) 
                   ELSE name 
               END as name, 
               jersey_number, 
               position 
        FROM players 
        WHERE team_id = ? 
        ORDER BY jersey_number ASC
    ";
    $stmt = $db->prepare($players_query);
    $stmt->execute([$event['team_id']]);
    $team_players = $stmt->fetchAll();
}

// Calculate attendance statistics
$total_players = count($team_players);
$present_count = count(array_filter($attendance_records, function($record) {
    return $record['status'] === 'Present';
}));
$absent_count = count(array_filter($attendance_records, function($record) {
    return $record['status'] === 'Absent';
}));
$attendance_rate = $total_players > 0 ? round(($present_count / $total_players) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['title']); ?> - Event Details</title>
    <?php 
    // Include dynamic CSS system
    require_once 'includes/css_helper.php';
    vivo_include_head_css();
    ?>
    <style>
        
        .event-details-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .event-header-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .event-header-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255,255,255,0.1);
            transform: rotate(45deg);
        }
        
        .event-header-content {
            position: relative;
            z-index: 2;
        }
        
        .event-title-main {
            font-size: 2.5em;
            font-weight: bold;
            margin: 0 0 10px 0;
        }
        
        .event-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-top: 20px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1em;
        }
        
        .meta-item i {
            font-size: 1.2em;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .main-content-card,
        .sidebar-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 1.4em;
            font-weight: bold;
            color: #1f2937;
            margin: 0 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary);
        }
        
        .event-description {
            color: #4b5563;
            line-height: 1.7;
            font-size: 1.1em;
            margin-bottom: 25px;
        }
        
        .event-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f9fafb;
            border-radius: 10px;
            border-left: 4px solid var(--primary);
        }
        
        .detail-item i {
            font-size: 1.2em;
            color: var(--primary);
            margin-right: 15px;
            width: 20px;
        }
        
        .detail-content {
            flex: 1;
        }
        
        .detail-label {
            font-size: 0.9em;
            color: #6b7280;
            margin-bottom: 2px;
        }
        
        .detail-value {
            font-weight: 600;
            color: #1f2937;
        }
        
        .attendance-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: #f9fafb;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border-top: 3px solid var(--primary);
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9em;
            color: #6b7280;
            font-weight: 600;
        }
        
        .attendance-list {
            margin-top: 20px;
        }
        
        .attendance-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 15px;
            margin-bottom: 8px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid transparent;
        }
        
        .attendance-item.present {
            border-left-color: #10b981;
            background: #ecfdf5;
        }
        
        .attendance-item.absent {
            border-left-color: var(--primary);
            background: #fef2f2;
        }
        
        .player-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .jersey-number {
            background: var(--primary);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9em;
        }
        
        .player-details {
            flex: 1;
        }
        
        .player-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 2px;
        }
        
        .player-position {
            font-size: 0.9em;
            color: #6b7280;
        }
        
        .attendance-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .status-present {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-absent {
            background: #fecaca;
            color: var(--secondary);
        }
        
        .quick-actions {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95em;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--secondary);
            color: white;
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
            color: #111827;
        }
        
        .type-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .type-training { background: #dbeafe; color: #1e40af; }
        .type-match { background: #fecaca; color: var(--secondary); }
        .type-meeting { background: #d1fae5; color: #065f46; }
        .type-other { background: #f3e8ff; color: #7c3aed; }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9em;
        }
        
        .status-scheduled { background: #fef3c7; color: #92400e; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fecaca; color: var(--secondary); }
        
        .no-attendance {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        
        .no-attendance i {
            font-size: 3em;
            margin-bottom: 15px;
            color: #d1d5db;
        }
        
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .event-details-grid {
                grid-template-columns: 1fr;
            }
            
            .attendance-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .event-meta {
                flex-direction: column;
                gap: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .attendance-stats {
                grid-template-columns: 1fr;
            }
            
            .event-title-main {
                font-size: 2em;
            }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="content-main">
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title">
                    <i class="fas fa-calendar-alt"></i>
                    Event Details
                </h1>
                <p class="page-subtitle">View and manage event information and attendance</p>
                
                <div class="page-actions">
                    <a href="events.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Events
                    </a>
                    <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i>
                        Edit Event
                    </a>
                </div>
            </div>
        </div>
        
        <div class="event-details-container">
                <!-- Event Header -->
                <div class="event-header-card">
                    <div class="event-header-content">
                        <span class="type-badge type-<?php echo strtolower($event['event_type']); ?>">
                            <?php echo htmlspecialchars($event['event_type']); ?>
                        </span>
                        <h1 class="event-title-main"><?php echo htmlspecialchars($event['title']); ?></h1>
                        <span class="status-badge status-<?php echo strtolower($event['status']); ?>">
                            <?php echo htmlspecialchars($event['status']); ?>
                        </span>
                        
                        <div class="event-meta">
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo date('l, F j, Y', strtotime($event['date'])); ?></span>
                            </div>
                            
                            <?php if ($event['time']): ?>
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo date('g:i A', strtotime($event['time'])); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($event['location']): ?>
                                <div class="meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($event['location']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($event['team_name']): ?>
                                <div class="meta-item">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo htmlspecialchars($event['team_name']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="content-grid">
                    <!-- Main Content -->
                    <div class="main-content-card">
                        <h2 class="section-title">Event Details</h2>
                        
                        <?php if ($event['description']): ?>
                            <div class="event-description">
                                <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="event-details-grid">
                            <?php if ($event['opponent']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-handshake"></i>
                                    <div class="detail-content">
                                        <div class="detail-label">Opponent</div>
                                        <div class="detail-value">
                                            <?php echo htmlspecialchars($event['opponent']); ?>
                                            <?php if ($event['is_home_game']): ?>
                                                <span style="color: var(--success); margin-left: 8px;">(Home)</span>
                                            <?php else: ?>
                                                <span style="color: var(--primary); margin-left: 8px;">(Away)</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="detail-item">
                                <i class="fas fa-info-circle"></i>
                                <div class="detail-content">
                                    <div class="detail-label">Event Type</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($event['event_type']); ?></div>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-flag"></i>
                                <div class="detail-content">
                                    <div class="detail-label">Status</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($event['status']); ?></div>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-calendar-plus"></i>
                                <div class="detail-content">
                                    <div class="detail-label">Created</div>
                                    <div class="detail-value"><?php echo date('M j, Y g:i A', strtotime($event['created_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="quick-actions">
                            <h3 class="section-title">Quick Actions</h3>
                            <div class="action-buttons">
                                <a href="attendance.php?event_id=<?php echo $event['id']; ?>" class="btn-action btn-primary">
                                    <i class="fas fa-clipboard-check"></i> Manage Attendance
                                </a>
                                <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn-action btn-secondary">
                                    <i class="fas fa-edit"></i> Edit Event
                                </a>
                                <a href="events.php" class="btn-action btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Events
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="sidebar-card">
                        <h2 class="section-title">Attendance Overview</h2>
                        
                        <?php if ($event['team_id'] && $total_players > 0): ?>
                            <div class="attendance-stats">
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo $total_players; ?></div>
                                    <div class="stat-label">Total Players</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo $present_count; ?></div>
                                    <div class="stat-label">Present</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo $absent_count; ?></div>
                                    <div class="stat-label">Absent</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo $attendance_rate; ?>%</div>
                                    <div class="stat-label">Attendance Rate</div>
                                </div>
                            </div>
                            
                            <?php if (!empty($attendance_records)): ?>
                                <div class="attendance-list">
                                    <h3 style="margin-bottom: 15px; color: #374151;">Player Attendance</h3>
                                    <?php foreach ($attendance_records as $record): ?>
                                        <div class="attendance-item <?php echo strtolower($record['status']); ?>">
                                            <div class="player-info">
                                                <div class="jersey-number"><?php echo $record['jersey_number']; ?></div>
                                                <div class="player-details">
                                                    <div class="player-name"><?php echo htmlspecialchars($record['name']); ?></div>
                                                    <div class="player-position"><?php echo htmlspecialchars($record['position']); ?></div>
                                                </div>
                                            </div>
                                            <span class="attendance-status status-<?php echo strtolower($record['status']); ?>">
                                                <?php echo htmlspecialchars($record['status']); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-attendance">
                                    <i class="fas fa-clipboard-list"></i>
                                    <h3>No Attendance Records</h3>
                                    <p>No attendance has been recorded for this event yet.</p>
                                    <a href="attendance.php?event=<?php echo $event['id']; ?>" class="btn-action btn-primary" style="margin-top: 15px;">
                                        <i class="fas fa-plus"></i> Add Attendance
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="no-attendance">
                                <i class="fas fa-info-circle"></i>
                                <h3>No Team Assigned</h3>
                                <p>This event is not associated with a specific team.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/sidebar.js"></script>
</body>
</html>
