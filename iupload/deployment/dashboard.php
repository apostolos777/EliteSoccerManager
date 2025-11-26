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
    
    // Recent events
    $recent_events = $db->query("
        SELECT e.*, t.name as team_name 
        FROM events e
        LEFT JOIN teams t ON e.team_id = t.id
        ORDER BY e.created_at DESC
        LIMIT 5
    ")->fetchAll();
    
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
                    <a href="teams.php" class="stat-card animate-fade-up" style="text-decoration: none; color: inherit;" title="Click to view teams">
                        <div class="stat-number"><?php echo $total_teams; ?></div>
                        <div class="stat-label">Teams</div>
                    </a>
                    <a href="players.php" class="stat-card animate-fade-up" style="animation-delay: 0.1s; text-decoration: none; color: inherit;" title="Click to view players">
                        <div class="stat-number"><?php echo $total_players; ?></div>
                        <div class="stat-label">Players</div>
                    </a>
                    <a href="events.php" class="stat-card animate-fade-up" style="animation-delay: 0.2s; text-decoration: none; color: inherit;" title="Click to view events">
                        <div class="stat-number"><?php echo $total_events; ?></div>
                        <div class="stat-label">Events</div>
                    </a>
                    <a href="players.php?status=active" class="stat-card animate-fade-up" style="animation-delay: 0.3s; text-decoration: none; color: inherit;" title="Click to view active players">
                        <div class="stat-number"><?php echo $active_players; ?></div>
                        <div class="stat-label">Active Players</div>
                    </a>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions animate-fade-up">
                    <a href="team_edit.php?action=add" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <h4>Add New Team</h4>
                        <p>Create and configure a new team</p>
                    </a>
                    <a href="player_edit.php?action=add" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h4>Add New Player</h4>
                        <p>Register a new player</p>
                    </a>
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

                <!-- Dashboard Grid -->
                <div class="dashboard-grid">
                    <!-- Recent Activity -->
                    <div class="recent-activity animate-fade-up">
                        <h3 class="mb-3">
                            <i class="fas fa-clock" style="color: var(--primary-green); margin-right: 8px;"></i>
                            Recent Events
                        </h3>
                        
                        <?php if (!empty($recent_events)): ?>
                            <?php foreach ($recent_events as $event): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-<?php echo $event['event_type'] === 'Match' ? 'futbol' : 'dumbbell'; ?>"></i>
                                    </div>
                                    <div>
                                        <h5 style="margin: 0; color: var(--text-primary);">
                                            <?php echo htmlspecialchars($event['title']); ?>
                                        </h5>
                                        <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;">
                                            <?php echo $event['team_name'] ? htmlspecialchars($event['team_name']) : 'No team assigned'; ?> • 
                                            <?php echo date('M j, Y', strtotime($event['date'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center" style="color: var(--text-secondary); padding: var(--space-8);">
                                <i class="fas fa-calendar" style="font-size: 2rem; margin-bottom: var(--space-3); opacity: 0.5;"></i>
                                <p>No recent events found.</p>
                                <a href="event_edit.php?action=add" class="btn btn-primary mt-3">
                                    <i class="fas fa-plus"></i> Create Event
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Top Teams -->
                    <div class="animate-fade-up">
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
        });
    </script>
</body>
</html>
