<?php
require_once 'includes/auth.php';
require_once 'functions.php';
require_once 'includes/color_system.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// If logged in, redirect root index to the dashboard page (single source of truth)
header('Location: dashboard.php');
exit();

// Database connection
try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Load club color system
$clubColorCSS = '';
if (class_exists('VIVOColorSystem')) {
    VIVOColorSystem::clearCache();
    $clubColorCSS = VIVOColorSystem::generateDynamicCSS($db);
}

// Load club settings for colors and stats
try {
    // Load club settings for colors
    $clubSettings = [];
    $stmt = $db->query("SELECT setting_key, setting_value FROM club_settings");
    if ($stmt) {
        while ($row = $stmt->fetch()) {
            if (!empty($row['setting_key'])) {
                $clubSettings[$row['setting_key']] = $row['setting_value'] ?? '';
            }
        }
    }
    error_log("Club settings loaded: " . json_encode($clubSettings));
} catch (Exception $e) {
    $clubSettings = [];
    error_log("Failed to load club settings: " . $e->getMessage());
}

// Statistics and data loading
try {
    // Team statistics
    $stmt = $db->prepare("SELECT COUNT(*) FROM teams");
    $stmt->execute();
    $total_teams = $stmt->fetchColumn();
    
    // Player statistics - count actual registered players
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM players 
        WHERE status = 'active'
    ");
    $stmt->execute();
    $active_players = $stmt->fetchColumn();
    
    // Match statistics - only count actual matches (since there are no matches, use events)
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM events 
        WHERE event_type LIKE '%match%' OR event_type LIKE '%Match%' OR title LIKE '%match%' OR title LIKE '%vs%'
    ");
    $stmt->execute();
    $matches_played = $stmt->fetchColumn();
    
    // If no matches found, show total events as placeholder
    if ($matches_played == 0) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM events");
        $stmt->execute();
        $matches_played = $stmt->fetchColumn();
    }
    
    // Goals from player stats
    $stmt = $db->prepare("SELECT SUM(goals) FROM player_stats");
    $stmt->execute();
    $goals_scored = $stmt->fetchColumn() ?: 0;
    
    // Recent events
    $stmt = $db->prepare("
        SELECT e.*, t.name as team_name 
        FROM events e
        LEFT JOIN teams t ON e.team_id = t.id
        ORDER BY e.date DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_events = $stmt->fetchAll();
    
    // Coach statistics - handle missing coaches table
    $total_coaches = 0;
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM coaches WHERE status = 'active'");
        $stmt->execute();
        $total_coaches = $stmt->fetchColumn();
    } catch (Exception $e) {
        // Coaches table doesn't exist, use 0
        error_log("Coaches table not found: " . $e->getMessage());
        $total_coaches = 0;
    }
    
    // Additional stats for cards
    $stmt = $db->prepare("SELECT COUNT(*) FROM events WHERE date >= date('now')");
    $stmt->execute();
    $upcoming_events = $stmt->fetchColumn();
    
    // Top teams by player count
    $stmt = $db->prepare("
        SELECT t.*, COUNT(p.id) as player_count
        FROM teams t
        LEFT JOIN players p ON t.id = p.team_id AND p.status = 'active'
        GROUP BY t.id
        ORDER BY player_count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $top_teams = $stmt->fetchAll();

    // Debug output for statistics
    error_log("Dashboard Stats: Teams=$total_teams, Players=$active_players, Events=$matches_played, Goals=$goals_scored, Coaches=$total_coaches");

} catch (Exception $e) {
    // Set default values if database queries fail and log error
    error_log("Dashboard Database Error: " . $e->getMessage());
    $total_teams = 0;
    $active_players = 0;
    $matches_played = 0;
    $goals_scored = 0;
    $recent_events = [];
    $total_coaches = 0;
    $upcoming_events = 0;
    $top_teams = [];
}

// Load club settings including colors using the same database connection
$clubSettings = [
    'club_name' => 'VIVO United', 
    'club_address' => '', 
    'club_phone' => '', 
    'club_email' => '',
    'color_primary' => '#2563eb',
    'color_secondary' => '#1d4ed8', 
    'color_accent' => '#eff6ff',
    'color_success' => '#059669',
    'color_warning' => '#d97706',
    'color_danger' => '#dc2626'
];

try {
    if (isset($db)) {
        $stmt = $db->prepare("SELECT setting_key, setting_value FROM club_settings");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['setting_key'])) {
                $clubSettings[$row['setting_key']] = $row['setting_value'];
            }
        }
        error_log("Dashboard Colors: Primary=" . $clubSettings['color_primary'] . ", Secondary=" . $clubSettings['color_secondary']);
    }
} catch (Exception $e) {
    error_log("Dashboard Color Settings Error: " . $e->getMessage());
    // Use defaults
}

// Get current user info
$user = getCurrentUser();
$currentTime = new DateTime();
$hour = (int)$currentTime->format('H');

if ($hour < 12) {
    $greeting = "Good Morning";
} elseif ($hour < 17) {
    $greeting = "Good Afternoon";
} else {
    $greeting = "Good Evening";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - VIVO United</title>
    <?php 
    // Include dynamic CSS system
    require_once 'includes/css_helper.php';
    vivo_include_head_css($db);
    ?>
    <style>
        /* Load dynamic club colors */
        <?php echo $clubColorCSS; ?>
        
        :root {
            --club-primary: <?php echo $clubSettings['color_primary'] ?? '#2563eb'; ?>;
            --club-secondary: <?php echo $clubSettings['color_secondary'] ?? '#1d4ed8'; ?>;
            --club-accent: <?php echo $clubSettings['color_accent'] ?? '#f59e0b'; ?>;
            --club-success: <?php echo $clubSettings['color_success'] ?? '#10b981'; ?>;
            --club-warning: <?php echo $clubSettings['color_warning'] ?? '#f59e0b'; ?>;
            --club-danger: <?php echo $clubSettings['color_danger'] ?? '#ef4444'; ?>;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, var(--club-primary) 0%, var(--club-secondary) 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-top: 4px solid var(--club-primary);
            transition: all 0.3s ease;
            text-decoration: none !important;
            cursor: pointer;
            display: block;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            text-decoration: none !important;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--club-primary);
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .quick-action-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: #374151;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-top: 4px solid var(--club-success);
        }
        
        .quick-action-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            text-decoration: none;
            color: #374151;
        }
        
        .quick-action-icon {
            font-size: 2.5rem;
            color: var(--club-success);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .recent-activity {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: #f9fafb;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--club-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
        
        .team-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid var(--club-warning);
        }
        
        .team-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(4px);
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            .quick-actions {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/layout_start.php'; ?>
    
    <div class="container-fluid">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-content">
                <h1 class="mb-2"><?php echo $greeting; ?>, <?php echo htmlspecialchars($user['username'] ?? 'Coach'); ?>!</h1>
                <p class="mb-0" style="opacity: 0.9;">Welcome back to <?php echo htmlspecialchars($clubSettings['club_name']); ?> Football Manager</p>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <a href="teams.php" class="stat-card">
                <div class="stat-number"><?php echo $total_teams; ?></div>
                <div class="stat-label">Teams</div>
            </a>
            <a href="players.php" class="stat-card">
                <div class="stat-number"><?php echo $active_players; ?></div>
                <div class="stat-label">Active Players</div>
            </a>
            <a href="events.php" class="stat-card">
                <div class="stat-number"><?php echo $matches_played; ?></div>
                <div class="stat-label">Events Scheduled</div>
            </a>
            <a href="coaches.php" class="stat-card">
                <div class="stat-number"><?php echo $total_coaches; ?></div>
                <div class="stat-label">Coaches</div>
            </a>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="add_team.php" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h4>Add New Team</h4>
                <p>Create and configure a new team</p>
            </a>
            <a href="add_coach.php" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h4>Add New Coach</h4>
                <p>Register a coach or staff member</p>
            </a>
            <a href="add_player.php" class="quick-action-card">
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
            <div class="recent-activity">
                <h3 class="mb-3">
                    <i class="fas fa-clock" style="color: var(--club-primary); margin-right: 8px;"></i>
                    Recent Events
                </h3>
                
                <?php if (!empty($recent_events)): ?>
                    <?php foreach ($recent_events as $event): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php echo $event['event_type'] === 'Match' ? 'futbol' : 'dumbbell'; ?>"></i>
                            </div>
                            <div>
                                <h5 style="margin: 0; color: #374151;">
                                    <?php echo htmlspecialchars($event['title']); ?>
                                </h5>
                                <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">
                                    <?php echo $event['team_name'] ? htmlspecialchars($event['team_name']) : 'No team assigned'; ?> • 
                                    <?php echo date('M j, Y', strtotime($event['date'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center" style="color: #6b7280; padding: 2rem;">
                        <i class="fas fa-calendar" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No recent events found.</p>
                        <a href="event_edit.php?action=add" class="btn btn-primary mt-3">
                            <i class="fas fa-plus"></i> Add Event
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Top Teams -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-trophy" style="color: var(--club-warning); margin-right: 8px;"></i>
                            Top Teams
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($top_teams)): ?>
                            <?php foreach ($top_teams as $index => $team): ?>
                                <div class="team-card">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 style="margin: 0; color: #374151;">
                                                #<?php echo $index + 1; ?> <?php echo htmlspecialchars($team['name']); ?>
                                            </h5>
                                            <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">
                                                <?php echo htmlspecialchars($team['age_group'] ?? 'No age group'); ?>
                                            </p>
                                        </div>
                                        <div class="text-center">
                                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--club-primary);">
                                                <?php echo $team['player_count']; ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: #6b7280; margin-bottom: 6px;">
                                                Players
                                            </div>
                                            <div>
                                                <a href="team_details.php?id=<?php echo $team['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                <a href="delete_team.php?id=<?php echo $team['id']; ?>" class="btn btn-sm btn-danger">Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center" style="color: #6b7280; padding: 1.5rem;">
                                <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                <p>No teams found.</p>
                                <a href="add_team.php" class="btn btn-primary mt-3">
                                    <i class="fas fa-plus"></i> Add Team
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/layout_end.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate stats on load
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        const number = card.querySelector('.stat-number');
        const target = parseInt(number.textContent);
        let current = 0;
        const increment = target / 50;
        
        setTimeout(() => {
            const counter = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(counter);
                }
                number.textContent = Math.floor(current);
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
});
</script>

</body>
</html>
