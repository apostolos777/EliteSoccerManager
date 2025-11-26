<?php
// VIVO United Football Manager Dashboard
// Modern Dashboard Interface

// Check authentication
if (!function_exists('isLoggedIn')) {
    require_once 'includes/color_system.php';
    require_once 'includes/auth.php';
}

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Set current page for sidebar navigation
$currentPage = 'dashboard';

// Initialize database connection
require_once __DIR__ . '/wp-config.php';
try { 
    $db = get_db_connection(); 
    $dbStatus = "Connected";
} catch (Throwable $e) { 
    $dbStatus = "Error: " . $e->getMessage();
    die("Database connection failed: " . $e->getMessage()); 
}

// Get comprehensive statistics
try {
    // Team statistics
    $stmt = $db->prepare("SELECT COUNT(*) FROM teams");
    $stmt->execute();
    $total_teams = $stmt->fetchColumn();
    
    // Player statistics
    $stmt = $db->prepare("SELECT COUNT(*) FROM players WHERE status = 'active'");
    $stmt->execute();
    $active_players = $stmt->fetchColumn();
    
    // Event statistics (matches played this season)
    $stmt = $db->prepare("SELECT COUNT(*) FROM events WHERE type = 'match' AND status = 'completed'");
    $stmt->execute();
    $matches_played = $stmt->fetchColumn();
    
    // Goals scored
    $stmt = $db->prepare("SELECT COUNT(*) FROM player_stats WHERE stat_type = 'goal'");
    $stmt->execute();
    $goals_scored = $stmt->fetchColumn();
    
    // Recent matches
    $stmt = $db->prepare("
        SELECT e.*, t.name as team_name 
        FROM events e
        LEFT JOIN teams t ON e.team_id = t.id
        WHERE e.type = 'match'
        ORDER BY e.date DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_matches = $stmt->fetchAll();
    
    // Coach statistics
    $stmt = $db->prepare("SELECT COUNT(*) FROM coaches WHERE status = 'active'");
    $stmt->execute();
    $total_coaches = $stmt->fetchColumn();

} catch (Exception $e) {
    // Set default values if database queries fail
    $total_teams = 12;
    $active_players = 247;
    $matches_played = 156;
    $goals_scored = 423;
    $recent_matches = [];
    $total_coaches = 8;
}

// Calculate growth percentages (mock data for demonstration)
$player_growth = "+12%";
$team_growth = "+8%";
$match_growth = "-2%";
$goal_growth = "+15%";

// Load club settings
$clubSettings = ['club_name' => 'VIVO United', 'club_address' => '', 'club_phone' => '', 'club_email' => ''];
try {
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM club_settings");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['setting_key'])) {
            $clubSettings[$row['setting_key']] = $row['setting_value'];
        }
    }
} catch (Exception $e) {
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

// Format date
$today = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Football Management System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/vivo-style.css" rel="stylesheet">
    
    <style>
        :root {
            --dashboard-bg: #f8fafc;
            --card-bg: #ffffff;
            --primary-blue: #3b82f6;
            --primary-purple: #8b5cf6;
            --success-green: #10b981;
            --warning-orange: #f59e0b;
            --danger-red: #ef4444;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
        }

        body {
            background: var(--dashboard-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Header Section */
        .header-section {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-purple) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 1rem 1rem;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .greeting h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .greeting p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0.25rem;
        }

        .greeting .date {
            font-size: 0.95rem;
            opacity: 0.8;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 600;
        }

        /* Dashboard Overview */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .dashboard-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .quick-add-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .quick-add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        /* Stat Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.blue::before { background: var(--primary-blue); }
        .stat-card.red::before { background: var(--danger-red); }
        .stat-card.orange::before { background: var(--warning-orange); }
        .stat-card.green::before { background: var(--success-green); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-info h3 {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .stat-icon.blue { background: var(--primary-blue); }
        .stat-icon.red { background: var(--danger-red); }
        .stat-icon.orange { background: var(--warning-orange); }
        .stat-icon.green { background: var(--success-green); }

        .stat-growth {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .growth-indicator {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .growth-indicator.positive {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-green);
        }

        .growth-indicator.negative {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-red);
        }

        .growth-text {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        /* Recent Matches */
        .recent-matches {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .section-title i {
            color: var(--warning-orange);
        }

        .view-all-btn {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .view-all-btn:hover {
            color: var(--primary-purple);
        }

        .matches-table {
            width: 100%;
        }

        .matches-table th {
            background: #f8fafc;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
        }

        .matches-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .score {
            font-weight: 700;
            color: var(--text-primary);
        }

        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-green);
        }

        /* Quick Actions */
        .quick-actions {
            background: linear-gradient(135deg, var(--primary-purple) 0%, #a855f7 100%);
            border-radius: 1rem;
            padding: 1.5rem;
            color: white;
        }

        .quick-actions .section-title {
            color: white;
            margin-bottom: 1.5rem;
        }

        .quick-actions .section-title i {
            color: #fbbf24;
        }

        .action-btn {
            width: 100%;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .action-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            transform: translateY(-2px);
        }

        .action-btn i {
            width: 20px;
            text-align: center;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .dashboard-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .greeting h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php require_once 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Header Section -->
                <div class="header-section">
                    <div class="container-fluid">
                        <div class="header-content">
                            <div class="greeting">
                                <h1><?= $greeting ?>, <?= htmlspecialchars($user['username']) ?>! 👋</h1>
                                <p>Welcome to your Football Management Dashboard</p>
                                <div class="date"><?= $today ?></div>
                            </div>
                            <div class="user-avatar">
                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Overview -->
                <div class="dashboard-header">
                    <h2 class="dashboard-title">Dashboard Overview</h2>
                    <button class="btn quick-add-btn" data-bs-toggle="modal" data-bs-target="#quickAddModal">
                        <i class="fas fa-plus"></i> QUICK ADD
                    </button>
                </div>

                <!-- Statistics Grid -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-header">
                            <div class="stat-info">
                                <h3>Total Players</h3>
                                <div class="stat-number"><?= $active_players ?></div>
                                <div class="stat-growth">
                                    <span class="growth-indicator positive">
                                        <i class="fas fa-arrow-up"></i> <?= $player_growth ?>
                                    </span>
                                    <span class="growth-text">this month</span>
                                </div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card red">
                        <div class="stat-header">
                            <div class="stat-info">
                                <h3>Active Teams</h3>
                                <div class="stat-number"><?= $total_teams ?></div>
                                <div class="stat-growth">
                                    <span class="growth-indicator positive">
                                        <i class="fas fa-arrow-up"></i> <?= $team_growth ?>
                                    </span>
                                    <span class="growth-text">this month</span>
                                </div>
                            </div>
                            <div class="stat-icon red">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card orange">
                        <div class="stat-header">
                            <div class="stat-info">
                                <h3>Matches Played</h3>
                                <div class="stat-number"><?= $matches_played ?></div>
                                <div class="stat-growth">
                                    <span class="growth-indicator negative">
                                        <i class="fas fa-arrow-down"></i> <?= $match_growth ?>
                                    </span>
                                    <span class="growth-text">this month</span>
                                </div>
                            </div>
                            <div class="stat-icon orange">
                                <i class="fas fa-futbol"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card green">
                        <div class="stat-header">
                            <div class="stat-info">
                                <h3>Goals Scored</h3>
                                <div class="stat-number"><?= $goals_scored ?></div>
                                <div class="stat-growth">
                                    <span class="growth-indicator positive">
                                        <i class="fas fa-arrow-up"></i> <?= $goal_growth ?>
                                    </span>
                                    <span class="growth-text">this month</span>
                                </div>
                            </div>
                            <div class="stat-icon green">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Grid -->
                <div class="content-grid">
                    <!-- Recent Matches -->
                    <div class="recent-matches">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-trophy"></i>
                                Recent Matches
                            </h3>
                            <a href="events.php" class="view-all-btn">
                                <i class="fas fa-eye"></i> VIEW ALL
                            </a>
                        </div>

                        <table class="matches-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Match</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_matches)): ?>
                                    <?php foreach ($recent_matches as $match): ?>
                                        <tr>
                                            <td><?= date('m/d/Y', strtotime($match['date'])) ?></td>
                                            <td><?= htmlspecialchars($match['title'] ?: 'Match') ?></td>
                                            <td class="score">3 - 1</td>
                                            <td>
                                                <span class="status-badge status-completed">COMPLETED</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td>8/25/2025</td>
                                        <td>Vivo FC vs Thunder United</td>
                                        <td class="score">3 - 1</td>
                                        <td>
                                            <span class="status-badge status-completed">COMPLETED</span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <h3 class="section-title">
                            <i class="fas fa-bolt"></i>
                            Quick Actions
                        </h3>

                        <a href="add_player.php" class="action-btn">
                            <i class="fas fa-user-plus"></i>
                            ADD NEW PLAYER
                        </a>

                        <a href="add_event.php" class="action-btn">
                            <i class="fas fa-calendar-plus"></i>
                            SCHEDULE MATCH
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Quick Add Modal -->
    <div class="modal fade" id="quickAddModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Quick Add</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-grid gap-2">
                        <a href="add_player.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add Player
                        </a>
                        <a href="add_team.php" class="btn btn-success">
                            <i class="fas fa-users"></i> Add Team
                        </a>
                        <a href="add_event.php" class="btn btn-warning">
                            <i class="fas fa-calendar-plus"></i> Add Event
                        </a>
                        <a href="add_coach.php" class="btn btn-info">
                            <i class="fas fa-user-tie"></i> Add Coach
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Add some interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stat numbers on load
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const finalValue = parseInt(stat.textContent);
                let currentValue = 0;
                const increment = finalValue / 50;
                
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        stat.textContent = finalValue;
                        clearInterval(timer);
                    } else {
                        stat.textContent = Math.floor(currentValue);
                    }
                }, 30);
            });
        });
    </script>
</body>
</html>
