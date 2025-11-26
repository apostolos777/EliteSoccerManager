<?php
// VIVO United Football Manager Dashboard
// Football Sports Club Theme

// Include dynamic color system
require_once 'includes/color_system.php';

// Check authentication
if (!function_exists('isLoggedIn')) {
    require_once 'includes/auth.php';
}

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Set current page for sidebar navigation
$currentPage = 'dashboard';

// Check authentication
if (!function_exists('isLoggedIn')) {
    require_once 'includes/auth.php';
}

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Set current page for sidebar navigation
$currentPage = 'dashboard';

// Initialize database connection - use unified config
require_once 'database_config.php';
try {
    $db = DatabaseConfigSQLite::getConnection();
} catch (Throwable $e) { 
    die("Database connection failed: " . $e->getMessage()); 
}

// Load club settings for dynamic colors
$clubSettings = [];
try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM club_settings");
    if ($stmt) {
        while ($row = $stmt->fetch()) {
            if (!empty($row['setting_key'])) {
                $clubSettings[$row['setting_key']] = $row['setting_value'] ?? '';
            }
        }
    }
    error_log("Dashboard - Club settings loaded: " . json_encode($clubSettings));
} catch (Exception $e) {
    error_log("Dashboard - Failed to load club settings: " . $e->getMessage());
}

// Clear color cache to ensure fresh data
if (class_exists('VIVOColorSystem')) {
    VIVOColorSystem::clearCache();
}

// Get comprehensive statistics
try {
    // Team statistics
    $total_teams = $db->query("SELECT COUNT(*) FROM teams")->fetchColumn();
    $teams_with_players = $db->query("
        SELECT COUNT(DISTINCT team_id) 
        FROM players 
        WHERE team_id IS NOT NULL
    ")->fetchColumn();
    
    // Player statistics
    $total_players = $db->query("SELECT COUNT(*) FROM players")->fetchColumn();
    $active_players = $db->query("
        SELECT COUNT(*) 
        FROM players 
        WHERE status = 'active'
    ")->fetchColumn();
    
    // Event statistics
    $total_events = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $upcoming_events = $db->query("
        SELECT COUNT(*) 
        FROM events 
        WHERE date >= date('now') AND status = 'Scheduled'
    ")->fetchColumn();
    
    // Attendance statistics
    $total_attendance_records = $db->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
    $attendance_rate = 0;
    if ($total_attendance_records > 0) {
        $present_count = $db->query("
            SELECT COUNT(*) 
            FROM attendance 
            WHERE status = 'present'
        ")->fetchColumn();
        $attendance_rate = round(($present_count / $total_attendance_records) * 100, 1);
    }
    
    // Recent events (for activity and news)
    $recent_events = $db->query(
        "SELECT e.*, t.name as team_name FROM events e LEFT JOIN teams t ON e.team_id = t.id ORDER BY e.created_at DESC LIMIT 5"
    )->fetchAll();

    // Upcoming matches / fixtures
    $upcoming_matches = $db->query(
        "SELECT e.*, t.name as team_name FROM events e LEFT JOIN teams t ON e.team_id = t.id WHERE date >= date('now') ORDER BY date ASC LIMIT 5"
    )->fetchAll();

    // Recent results (completed matches)
    $recent_results = $db->query(
        "SELECT e.*, t.name as team_name FROM events e LEFT JOIN teams t ON e.team_id = t.id WHERE date < date('now') ORDER BY date DESC LIMIT 5"
    )->fetchAll();

    // Simple standings approximation (teams ordered by number of active players)
    $standings = $db->query(
        "SELECT t.id, t.name, t.age_group, COUNT(p.id) as players_count FROM teams t LEFT JOIN players p ON t.id = p.team_id AND p.is_active = 1 GROUP BY t.id ORDER BY players_count DESC LIMIT 10"
    )->fetchAll();

    // Player gallery (active players with possible images)
    $players_gallery = $db->query(
        "SELECT id, first_name, last_name, jersey_number FROM players WHERE is_active = 1 ORDER BY last_name LIMIT 8"
    )->fetchAll();

    // Top scorers from player_stats if available
    $top_scorers = [];
    try {
        $top_scorers = $db->query(
            "SELECT p.id, p.first_name, p.last_name, ps.goals FROM player_stats ps JOIN players p ON ps.player_id = p.id ORDER BY ps.goals DESC LIMIT 5"
        )->fetchAll();
    } catch (Exception $e) {
        // Stats may not exist in all DBs - keep top_scorers empty
        $top_scorers = [];
    }
    
    // Top teams by player count
    $top_teams = $db->query("
        SELECT t.*, COUNT(p.id) as player_count
        FROM teams t
        LEFT JOIN players p ON t.id = p.team_id AND p.status = 'active'
        GROUP BY t.id
        ORDER BY player_count DESC
        LIMIT 5
    ")->fetchAll();
    
    // Age group distribution
    $age_groups = $db->query("
        SELECT age_group, COUNT(*) as count
        FROM teams 
        WHERE age_group IS NOT NULL
        GROUP BY age_group
        ORDER BY age_group
    ")->fetchAll();

} catch (Exception $e) {
    // Set default values if database queries fail
    $total_teams = 0;
    $teams_with_players = 0;
    $total_players = 0;
    $active_players = 0;
    $total_events = 0;
    $upcoming_events = 0;
    $total_attendance_records = 0;
    $attendance_rate = 0;
    $recent_events = [];
    $top_teams = [];
    $age_groups = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - VIVO United Football Manager</title>
    
    <!-- Dynamic Color System CSS -->
    <style><?php echo VIVOColorSystem::generateDynamicCSS($db); ?></style>
    
    <?php 
    // Include dynamic CSS system
    require_once 'includes/css_helper.php';
    vivo_include_head_css($db);
    ?>
    <!-- Cache busting timestamp: <?php echo date('Y-m-d H:i:s'); ?> -->
    <style>
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: var(--space-8);
            border-radius: var(--radius-xl);
            margin-bottom: var(--space-8);
            position: relative;
            overflow: hidden;
        }
        
        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 100%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            opacity: 0.3;
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .welcome-content {
            position: relative;
            z-index: 2;
        }
        
        /* Stats Grid - Ensure proper clickability */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-8);
        }
        
        .stat-card {
            background: var(--white);
            padding: var(--space-5);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border-top: 4px solid var(--primary-green);
            transition: all var(--transition-fast);
            display: block !important;
            text-decoration: none !important;
            color: inherit !important;
            cursor: pointer !important;
            position: relative;
            z-index: 1;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            text-decoration: none !important;
            color: inherit !important;
        }
        
        .stat-card:focus {
            outline: 2px solid var(--primary-green);
            outline-offset: 2px;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-green);
            line-height: 1;
            margin-bottom: var(--space-2);
            font-family: var(--font-display);
        }
        
        .stat-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-5);
            margin-bottom: var(--space-8);
        }
        
        .quick-action-card {
            background: var(--white);
            padding: var(--space-6);
            border-radius: var(--radius-lg);
            text-align: center;
            text-decoration: none;
            color: var(--text-primary);
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
            border-top: 4px solid var(--primary-green);
        }
        
        .quick-action-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-xl);
            text-decoration: none;
            color: var(--text-primary);
        }
        
        .quick-action-icon {
            font-size: 2.5rem;
            color: var(--primary-green);
            margin-bottom: var(--space-3);
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: var(--space-8);
        }
        
        .recent-activity {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            box-shadow: var(--shadow-sm);
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: var(--space-4);
            border-bottom: 1px solid var(--border-light);
            transition: var(--transition-fast);
        }
        
        .activity-item:hover {
            background: var(--light-gray);
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-green);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: var(--space-4);
        }
        
        .team-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: var(--space-5);
            margin-bottom: var(--space-4);
            box-shadow: var(--shadow-sm);
            border-left: 4px solid var(--primary-green);
        }
        
        .team-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateX(4px);
        }

        /* New layout for left stacked widgets */
        .quick-actions { display:flex; gap: var(--space-6); align-items:flex-start; margin-bottom: var(--space-8); }
        .left-stack { width: 260px; display:flex; flex-direction:column; gap: var(--space-4); }
        .stack-card { background: var(--white); border-radius: 12px; padding: 12px; display:flex; gap: 12px; align-items:center; box-shadow: var(--shadow-sm); border:1px solid var(--border-light); }
        .stack-card-icon { width:44px; height:44px; display:flex; align-items:center; justify-content:center; background:var(--light-gray); border-radius:10px; color:var(--primary-dark); font-size:16px; }
        .stack-card-body h5 { margin:0; font-size:0.95rem; color:var(--text-primary); }
        .stack-card-body small { display:block; color:var(--text-secondary); margin-top:4px; }

        /* Main actions area - 3 tall columns */
        .main-actions { display:grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-5); flex: 1; }
        .action-card { background: var(--white); border-radius: 12px; padding: 30px 24px; display:flex; flex-direction:column; align-items:center; justify-content:flex-start; text-align:center; box-shadow: var(--shadow-sm); height: 520px; border: 1px solid var(--border-light); text-decoration:none; color:inherit; }
        .action-card--tall { transition: transform .18s ease, box-shadow .18s ease; }
        .action-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-md); }
        .action-icon { font-size:44px; color:var(--primary-green); margin-bottom: 18px; }
        .action-card h3 { margin: 0 0 6px 0; font-size:1.1rem; }
        .action-card p { margin: 0; color: var(--text-secondary); }

        /* Responsive adjustments */
        @media (max-width: 980px) {
            .quick-actions { flex-direction:column; }
            .left-stack { width:100%; flex-direction:row; overflow:auto; }
            .main-actions { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-4); }
            .action-card { height: 420px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="content-main">
            <!-- Header Section -->
            <div class="page-header">
                <div class="page-header-content">
                    <h1 class="page-title">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </h1>
                    <div style="color: rgba(255,255,255,0.8);">
                        <i class="fas fa-calendar"></i> <?php echo date('l, F j, Y'); ?>
                    </div>
                </div>
            </div>
            
            <!-- Content Section -->
            <div class="content-section">
                <!-- Welcome Banner -->
                <div class="welcome-banner animate-fade-up">
                    <div class="welcome-content">
                        <h2 style="font-size: 2rem; margin-bottom: var(--space-3); font-family: var(--font-display);">
                            Welcome to VIVO United Football Manager
                        </h2>
                        <p style="font-size: 1.1rem; opacity: 0.9;">
                            Your professional football management system. Manage teams, track players, organize events, and monitor attendance all in one place.
                        </p>
                    </div>
                </div>

                <!-- Statistics Grid -->
                <div class="stats-grid">
                    <!-- Debug: Links should be clickable -->
                    <a href="teams.php" class="stat-card fc-card animate-fade-up" style="text-decoration: none; color: inherit;" title="Click to view teams">
                        <div class="stat-number num"><?php echo $total_teams; ?></div>
                        <div class="stat-label">Teams</div>
                    </a>
                    <a href="players.php" class="stat-card fc-card animate-fade-up" style="animation-delay: 0.1s; text-decoration: none; color: inherit;" title="Click to view players">
                        <div class="stat-number num"><?php echo $total_players; ?></div>
                        <div class="stat-label">Players</div>
                    </a>
                    <a href="events.php" class="stat-card fc-card animate-fade-up" style="animation-delay: 0.2s; text-decoration: none; color: inherit;" title="Click to view events">
                        <div class="stat-number num"><?php echo $total_events; ?></div>
                        <div class="stat-label">Events</div>
                    </a>
                    <a href="players.php?status=active" class="stat-card fc-card animate-fade-up" style="animation-delay: 0.3s; text-decoration: none; color: inherit;" title="Click to view active players">
                        <div class="stat-number num"><?php echo $active_players; ?></div>
                        <div class="stat-label">Active Players</div>
                    </a>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions animate-fade-up">
                    <!-- Left stacked sidebar (compact widgets) -->
                    <aside class="left-stack" aria-label="Quick widgets">
                        <div class="stack-card">
                            <div class="stack-card-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="stack-card-body">
                                <h5>Upcoming Matches</h5>
                                <?php if (!empty($upcoming_matches)): ?>
                                    <small><?php echo htmlspecialchars($upcoming_matches[0]['team_name'] ?? 'Club'); ?> — <?php echo date('M j', strtotime($upcoming_matches[0]['date'])); ?></small>
                                <?php else: ?>
                                    <small>No upcoming matches scheduled.</small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="stack-card">
                            <div class="stack-card-icon"><i class="fas fa-user"></i></div>
                            <div class="stack-card-body">
                                <h5>Top Scorers</h5>
                                <?php if (!empty($top_scorers)): ?>
                                    <small><?php echo htmlspecialchars($top_scorers[0]['first_name'] . ' ' . $top_scorers[0]['last_name']); ?> — <?php echo (int)$top_scorers[0]['goals']; ?> goals</small>
                                <?php else: ?>
                                    <small>No scoring data available yet.</small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="stack-card">
                            <div class="stack-card-icon"><i class="fas fa-newspaper"></i></div>
                            <div class="stack-card-body">
                                <h5>Latest News</h5>
                                <?php if (!empty($recent_events)): ?>
                                    <small><?php echo htmlspecialchars($recent_events[0]['title'] ?? ''); ?></small>
                                <?php else: ?>
                                    <small>No news available.</small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="stack-card">
                            <div class="stack-card-icon"><i class="fas fa-plus"></i></div>
                            <div class="stack-card-body">
                                <h5>Add New Team</h5>
                                <small>Create and configure a new team</small>
                            </div>
                        </div>
                    </aside>
                        <!-- Compact widgets are in the left stacked pane -->
                    <!-- Main large actions area (three tall columns) -->
                    <div class="main-actions" role="region" aria-label="Primary actions">
                        <a href="player_edit.php?action=add" class="action-card action-card--tall fc-card">
                            <div class="action-icon"><i class="fas fa-user-plus"></i></div>
                            <h3>Add New Player</h3>
                            <p>Register a new player</p>
                        </a>
                        <a href="event_edit.php?action=add" class="action-card action-card--tall fc-card">
                            <div class="action-icon"><i class="fas fa-calendar-plus"></i></div>
                            <h3>Schedule Event</h3>
                            <p>Create a new match or training</p>
                        </a>
                        <a href="attendance.php" class="action-card action-card--tall fc-card">
                            <div class="action-icon"><i class="fas fa-clipboard-check"></i></div>
                            <h3>Take Attendance</h3>
                            <p>Record player attendance</p>
                        </a>
                    </div>
                    <a href="event_edit.php?action=add" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <h4>Schedule Event</h4>
                        <p>Create a new match or training</p>
                    </a>
                    <a href="attendance.php" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <h4>Take Attendance</h4>
                        <p>Record player attendance</p>
                    </a>
                </div>

                <!-- Dashboard Grid (main body) -->
                <div class="dashboard-grid">
                    
                        <!-- Main Left Column: Fixtures / Results / Standings / Player Gallery -->
                        <div>
                            <div class="scoreboard-row animate-fade-up" style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-4);">
                                <!-- Fixtures -->
                                <div class="card fixtures-card" style="padding: var(--space-4);">
                                    <h4 class="card-title" style="margin-bottom: var(--space-3);">
                                        <i class="fas fa-calendar-day" style="color: var(--secondary-orange); margin-right:8px"></i>
                                        Fixtures
                                    </h4>
                                    <?php if (!empty($upcoming_matches)): ?>
                                        <?php foreach ($upcoming_matches as $match): ?>
                                            <div style="display:flex; justify-content:space-between; padding:8px 6px; border-bottom:1px solid var(--border-light);">
                                                <div>
                                                    <div style="font-weight:700"><?php echo htmlspecialchars($match['team_name'] ?: 'Club'); ?></div>
                                                    <div style="font-size:0.9rem; color:var(--text-secondary);"><?php echo date('M j, Y', strtotime($match['date'])); ?> • <?php echo htmlspecialchars($match['time'] ?? 'TBA'); ?></div>
                                                </div>
                                                <div style="text-align:right; font-size:0.85rem; color:var(--text-secondary);">
                                                    <?php echo htmlspecialchars($match['opponent'] ?? 'Opponent'); ?><br/>
                                                    <span style="font-weight:600; color:var(--primary-green);"><?php echo !empty($match['is_home_game']) ? 'Home' : 'Away'; ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center" style="padding: var(--space-6); color:var(--text-secondary);">
                                            No upcoming matches.
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Results -->
                                <div class="card results-card" style="padding: var(--space-4);">
                                    <h4 class="card-title" style="margin-bottom: var(--space-3);">
                                        <i class="fas fa-flag-checkered" style="color: var(--danger); margin-right:8px"></i>
                                        Results
                                    </h4>
                                    <?php if (!empty($recent_results)): ?>
                                        <?php foreach ($recent_results as $res): ?>
                                            <div style="display:flex; justify-content:space-between; padding:8px 6px; border-bottom:1px solid var(--border-light);">
                                                <div>
                                                    <div style="font-weight:700"><?php echo htmlspecialchars($res['title']); ?></div>
                                                    <div style="font-size:0.9rem; color:var(--text-secondary);"><?php echo date('M j, Y', strtotime($res['date'])); ?></div>
                                                </div>
                                                <div style="text-align:right; font-size:1.0rem; font-weight:700; color:var(--text-primary);">
                                                    <?php if (isset($res['score_home']) && isset($res['score_away'])): ?>
                                                        <?php echo htmlspecialchars($res['score_home']) . ' - ' . htmlspecialchars($res['score_away']); ?>
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center" style="padding: var(--space-6); color:var(--text-secondary);">
                                            No recent results found.
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Standings (approximation) -->
                                <div class="card standings-card" style="padding: var(--space-4);">
                                    <h4 class="card-title" style="margin-bottom: var(--space-3);">
                                        <i class="fas fa-list-ol" style="color: var(--info); margin-right:8px"></i>
                                        Primary League Standings
                                    </h4>
                                    <?php if (!empty($standings)): ?>
                                        <table style="width:100%; border-collapse: collapse; font-size:0.9rem;">
                                            <thead>
                                                <tr style="text-align:left; color:var(--text-secondary); font-weight:700; font-size:0.8rem;">
                                                    <th style="width:32px">#</th>
                                                    <th>Club</th>
                                                    <th style="width:70px; text-align:right">Players</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($standings as $i => $row): ?>
                                                <tr style="border-top:1px solid var(--border-light);">
                                                    <td style="padding:8px 6px; font-weight:700"><?php echo $i + 1; ?></td>
                                                    <td style="padding:8px 6px;"><?php echo htmlspecialchars($row['name']); ?></td>
                                                    <td style="padding:8px 6px; text-align:right; color:var(--text-secondary);"><?php echo (int)$row['players_count']; ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <div class="text-center" style="padding: var(--space-6); color:var(--text-secondary);">
                                            No standings available.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Player Gallery -->
                            <div class="player-gallery card fc-card animate-fade-up" style="margin-top: var(--space-6); padding: var(--space-4);">
                                <h4 class="card-title" style="margin-bottom: var(--space-3);"><i class="fas fa-images" style="color:var(--primary-green); margin-right:8px"></i>Player Gallery</h4>
                                <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap: var(--space-3);">
                                    <?php if (!empty($players_gallery)): ?>
                                        <?php foreach ($players_gallery as $p): ?>
                                            <div style="text-align:center; background:var(--white); padding:8px; border-radius:6px; box-shadow:var(--shadow-sm);">
                                                <?php
                                                // Try to detect a player image file in uploads/players/player_<id>_profile.png or jpg
                                                $img1 = 'uploads/players/player_' . intval($p['id']) . '_profile.png';
                                                $img2 = 'uploads/players/player_' . intval($p['id']) . '_profile.jpg';
                                                $profileImg = file_exists($img1) ? $img1 : (file_exists($img2) ? $img2 : 'uploads/testlogo.png');
                                                ?>
                                                <img src="<?php echo $profileImg; ?>" alt="<?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?>" style="width:100%; height:90px; object-fit:cover; border-radius:6px; margin-bottom:6px;"/>
                                                <div style="font-weight:700; font-size:0.9rem;"><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></div>
                                                <div style="font-size:0.8rem; color:var(--text-secondary);">#<?php echo htmlspecialchars($p['jersey_number'] ?? '—'); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="grid-column: 1 / -1; text-align:center; color:var(--text-secondary); padding:12px;">No players to display</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    <!-- Top Teams -->
                    <div class="animate-fade-up">
                        <!-- Right Column - Next Match + Widgets -->
                        <div class="card" style="margin-bottom: var(--space-4); padding: var(--space-4);">
                            <h3 class="card-title" style="margin-bottom: 0.5rem;"><i class="fas fa-bullseye" style="color:var(--primary-green); margin-right:8px"></i>Next Match</h3>
                            <?php if (!empty($upcoming_matches)): $nextMatch = $upcoming_matches[0]; ?>
                                <div style="display:flex; align-items:center; gap: var(--space-4); padding-top: 10px;">
                                    <div style="flex: 1;">
                                        <div style="font-weight:700; font-size:1rem"><?php echo htmlspecialchars($nextMatch['team_name'] ?: 'Club'); ?> v <?php echo htmlspecialchars($nextMatch['opponent'] ?? 'Opp'); ?></div>
                                        <div style="color:var(--text-secondary); font-size:0.9rem; margin-top:6px"><?php echo date('D, M j, Y', strtotime($nextMatch['date'])); ?> • <?php echo htmlspecialchars($nextMatch['time'] ?? 'TBA'); ?></div>
                                        <div style="margin-top:8px; font-weight:600; color:var(--text-primary)" id="next-match-countdown">Loading...</div>
                                    </div>
                                    <div style="width:84px; height:84px; background:var(--light-gray); border-radius:8px; display:flex; align-items:center; justify-content:center; font-weight:700; color:var(--primary-green);">
                                        <?php echo date('d', strtotime($nextMatch['date'])); ?><br/><small style="font-weight:500; color:var(--text-secondary);"><?php echo date('M', strtotime($nextMatch['date'])); ?></small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div style="padding-top:10px; color:var(--text-secondary);">No scheduled upcoming matches.</div>
                            <?php endif; ?>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-trophy" style="color: var(--secondary-orange); margin-right: 8px;"></i>
                                    Top Teams
                                </h3>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($top_teams)): ?>
                                    <?php foreach ($top_teams as $index => $team): ?>
                                        <div class="team-card">
                                            <div class="d-flex justify-between align-center">
                                                <div>
                                                    <h5 style="margin: 0; color: var(--text-primary);">
                                                        #<?php echo $index + 1; ?> <?php echo htmlspecialchars($team['name']); ?>
                                                    </h5>
                                                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;">
                                                        <?php echo htmlspecialchars($team['age_group'] ?? 'No age group'); ?>
                                                    </p>
                                                </div>
                                                <div class="text-center">
                                                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-green);">
                                                        <?php echo $team['player_count']; ?>
                                                    </div>
                                                    <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                                        Players
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center" style="color: var(--text-secondary); padding: var(--space-6);">
                                        <i class="fas fa-users" style="font-size: 2rem; margin-bottom: var(--space-3); opacity: 0.5;"></i>
                                        <p>No teams found.</p>
                                        <a href="team_edit.php?action=add" class="btn btn-primary mt-3">
                                            <i class="fas fa-plus"></i> Add Team
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Age Groups Distribution -->
                        <?php if (!empty($age_groups)): ?>
                            <div class="card mt-6">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-chart-pie" style="color: var(--info); margin-right: 8px;"></i>
                                        Age Groups
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($age_groups as $group): ?>
                                        <div class="d-flex justify-between align-center mb-2">
                                            <span style="font-weight: 500;">
                                                <?php echo htmlspecialchars($group['age_group']); ?>
                                            </span>
                                            <span class="item-badge">
                                                <?php echo $group['count']; ?> team<?php echo $group['count'] != 1 ? 's' : ''; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div> <!-- /content-main -->

    <script>
        // Dashboard animations and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stats on load
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                const number = card.querySelector('.stat-number');
                const target = parseInt(number.textContent.replace('%', ''));
                let current = 0;
                const increment = target / 50;
                
                setTimeout(() => {
                    const counter = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            current = target;
                            clearInterval(counter);
                        }
                        number.textContent = Math.floor(current) + (number.textContent.includes('%') ? '%' : '');
                    }, 20);
                }, index * 200);
            });
            
            // Add hover effects to quick action cards
            const quickActions = document.querySelectorAll('.quick-action-card');
            quickActions.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.querySelector('.quick-action-icon').style.transform = 'scale(1.1) rotate(5deg)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.querySelector('.quick-action-icon').style.transform = 'scale(1) rotate(0deg)';
                });
            });
            
            // Force stat card clicks to work - debugging
            const statCardsClickable = document.querySelectorAll('.stat-card');
            console.log('Found stat cards:', statCardsClickable.length);
            statCardsClickable.forEach(card => {
                card.style.cursor = 'pointer';
                card.style.display = 'block';
                card.addEventListener('click', function(e) {
                    console.log('Stat card clicked:', this.href);
                    if (this.href) {
                        window.location.href = this.href;
                    }
                });
            });

            // Next match countdown (if next match exists)
            <?php if (!empty($nextMatch ?? null)): ?>
            (function() {
                const countdownEl = document.getElementById('next-match-countdown');
                if (!countdownEl) return;

                const nextDate = new Date('<?php echo date('c', strtotime(($nextMatch['date'] ?? '') . ' ' . ($nextMatch['time'] ?? '00:00:00'))); ?>').getTime();

                function updateCountdown() {
                    const now = Date.now();
                    const diff = nextDate - now;
                    if (diff <= 0) {
                        countdownEl.textContent = 'Match starting — check Events';
                        return;
                    }
                    const days = Math.floor(diff / (1000*60*60*24));
                    const hours = Math.floor((diff / (1000*60*60)) % 24);
                    const mins = Math.floor((diff / (1000*60)) % 60);
                    const secs = Math.floor((diff/1000) % 60);
                    countdownEl.textContent = days + 'd ' + hours + 'h ' + mins + 'm ' + secs + 's';
                }

                updateCountdown();
                setInterval(updateCountdown, 1000);
            })();
            <?php endif; ?>
        });
    </script>
</body>
</html>
