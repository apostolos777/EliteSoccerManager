<?php
/**
 * VIVO United - Team Details with Player Management
 */

// Load color system and auth
require_once 'includes/color_system.php';
require_once 'includes/auth.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Set current page for navigation
$currentPage = 'teams';

// Database connection - use unified config like other pages
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
    if ($team) {
        // Get player count and average age separately. Prefer join table if present.
        try {
            $r = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='player_teams'")->fetch(PDO::FETCH_ASSOC);
            $hasPlayerTeams = (bool)$r;
        } catch (Exception $e) {
            $hasPlayerTeams = false;
        }

        if ($hasPlayerTeams) {
            $stmt = $db->prepare("\n                SELECT COUNT(DISTINCT p.id) as player_count, ROUND(AVG(CASE WHEN p.age IS NOT NULL THEN p.age END),1) as avg_age\n                FROM players p\n                JOIN player_teams pt ON p.id = pt.player_id\n                WHERE pt.team_id = ? AND p.status = 'active'\n            ");
            // If multi-team join table exists, use it for listing; otherwise fall back to players.team_id
            $r = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='player_teams'")->fetch(PDO::FETCH_ASSOC);

            $stmt->execute([$team_id]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $db->prepare("\n                SELECT COUNT(id) as player_count, ROUND(AVG(CASE WHEN age IS NOT NULL THEN age END), 1) as avg_age\n                FROM players \n                WHERE team_id = ? AND status = 'active'\n            ");
            $stmt->execute([$team_id]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Add stats to team array
        $team['player_count'] = $stats['player_count'] ?? 0;
        $team['avg_age'] = $stats['avg_age'];
    }
    header('Location: teams.php');
    exit;
}

// Handle player assignment/removal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $player_id = $_POST['player_id'] ?? null;
        // detect if the player_teams join table exists (multi-team support)
        try {
            $r = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='player_teams'")->fetch(PDO::FETCH_ASSOC);
            $hasPlayerTeams = (bool)$r;
        } catch (Exception $e) {
            $hasPlayerTeams = false;
        }
        
        if ($_POST['action'] === 'add_player' && $player_id) {
            // Add player to team. If multi-team support exists, use the join table; otherwise fall back to legacy single team column.
            try {
                if ($hasPlayerTeams) {
                    $stmt = $db->prepare("INSERT OR IGNORE INTO player_teams (player_id, team_id) VALUES (?, ?)");
                    $result = $stmt->execute([$player_id, $team_id]);
                    if ($result) {
                        $success = "Player successfully added to team!";
                    } else {
                        $error = "Error adding player to team.";
                    }
                } else {
                    $stmt = $db->prepare("UPDATE players SET team_id = ? WHERE id = ?");
                    $result = $stmt->execute([$team_id, $player_id]);
                    if ($result) {
                        $success = "Player successfully added to team!";
                    } else {
                        $error = "Error adding player to team.";
                    }
                }
            } catch (Exception $e) {
                $error = "Error adding player to team: " . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'remove_player' && $player_id) {
            // Remove player from team (respect join table if present)
            try {
                if ($hasPlayerTeams) {
                    $stmt = $db->prepare("DELETE FROM player_teams WHERE player_id = ? AND team_id = ?");
                    $result = $stmt->execute([$player_id, $team_id]);
                    if ($result) {
                        $success = "Player successfully removed from team!";
                    } else {
                        $error = "Error removing player from team.";
                    }
                } else {
                    $stmt = $db->prepare("UPDATE players SET team_id = NULL WHERE id = ?");
                    $result = $stmt->execute([$player_id]);
                    if ($result) {
                        $success = "Player successfully removed from team!";
                    } else {
                        $error = "Error removing player from team.";
                    }
                }
            } catch (Exception $e) {
                $error = "Error removing player from team: " . $e->getMessage();
            }
        }
    }
}

// Get comprehensive team information
try {
    // First get basic team info
    $stmt = $db->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$team_id]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($team) {
        // Get player count and average age separately
        $stmt = $db->prepare("
            SELECT COUNT(id) as player_count,
                   ROUND(AVG(CASE WHEN age IS NOT NULL THEN age END), 1) as avg_age
            FROM players 
            WHERE team_id = ? AND status = 'active'
        ");
        $stmt->execute([$team_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Add stats to team array
        $team['player_count'] = $stats['player_count'] ?? 0;
        $team['avg_age'] = $stats['avg_age'];
    }
} catch (Exception $e) {
    error_log("Error loading team data: " . $e->getMessage());
    $team = null;
}

if (!$team) {
    header('Location: teams.php');
    exit;
}

// Get team players
try {
    // If multi-team join table exists, use it for listing; otherwise fall back to players.team_id
    $r = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='player_teams'")->fetch(PDO::FETCH_ASSOC);
    $hasPlayerTeams = (bool)$r;
    if ($hasPlayerTeams) {
        $stmt = $db->prepare("
            SELECT p.*, p.age as age 
            FROM players p 
            JOIN player_teams pt ON p.id = pt.player_id 
            WHERE pt.team_id = ? AND p.status = 'active'
            ORDER BY p.position, p.name
        ");
        $stmt->execute([$team_id]);
        $team_players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $db->prepare("
            SELECT p.*, p.age as age
            FROM players p
            WHERE p.team_id = ? AND p.status = 'active'
            ORDER BY p.position, p.name
        ");
        $stmt->execute([$team_id]);
        $team_players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $clubSettings = [];
    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM club_settings");
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['setting_key'])) {
                    $clubSettings[$row['setting_key']] = $row['setting_value'] ?? '';
                }
            }
        }
    } catch (Exception $e) {
        error_log("Team Details - Failed to load club settings: " . $e->getMessage());
    }
} catch (Exception $e) {
    $available_players = [];
    error_log("Error loading available players: " . $e->getMessage());
}

// Get team stats (using SQLite-compatible query)
try {
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT e.id) as total_events,
            COUNT(DISTINCT CASE WHEN e.date >= DATE('now') THEN e.id END) as upcoming_events,
            COUNT(DISTINCT CASE WHEN e.date < DATE('now') THEN e.id END) as past_events
        FROM events e
    ");
    $stmt->execute();
    $team_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $team_stats = ['total_events' => 0, 'upcoming_events' => 0, 'past_events' => 0];
    error_log("Error loading team stats: " . $e->getMessage());
}

// Get team coaches
try {
    $stmt = $db->prepare("
        SELECT c.id, c.name, c.role, c.email, c.phone
        FROM coaches c
        JOIN team_coaches tc ON c.id = tc.coach_id
        WHERE tc.team_id = ?
        ORDER BY c.name
    ");
    $stmt->execute([$team_id]);
    $team_coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $clubSettings = [];
    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM club_settings");
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['setting_key'])) {
                    $clubSettings[$row['setting_key']] = $row['setting_value'] ?? '';
                }
            }
        }
    } catch (Exception $e) {
        error_log("Team Details - Failed to load club settings: " . $e->getMessage());
    }
        }
        .btn-outline-primary:focus {
            box-shadow: 0 0 0 0.2rem rgba(<?= hexdec(substr($clubSettings['color_primary'] ?? '#2563eb', 1, 2)) ?>, <?= hexdec(substr($clubSettings['color_primary'] ?? '#2563eb', 3, 2)) ?>, <?= hexdec(substr($clubSettings['color_primary'] ?? '#2563eb', 5, 2)) ?>, 0.25) !important;
        }
        
        /* Dynamic team switch button colors using club settings */
        .team-switch-btn {
            background-color: <?= $clubSettings['color_primary'] ?? '#2563eb' ?> !important;
            border: 2px solid <?= $clubSettings['color_primary'] ?? '#2563eb' ?> !important;
            color: white !important;
            transition: all 0.3s ease !important;
        }
        .team-switch-btn:hover {
            background-color: <?= $clubSettings['color_secondary'] ?? '#1d4ed8' ?> !important;
            border-color: <?= $clubSettings['color_secondary'] ?? '#1d4ed8' ?> !important;
            color: white !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .team-switch-btn.active {
            background-color: <?= $clubSettings['color_secondary'] ?? '#1d4ed8' ?> !important;
            border-color: <?= $clubSettings['color_secondary'] ?? '#1d4ed8' ?> !important;
            color: white !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 767px) {
            .col-md-3 {
                margin-top: 1rem;
            }
        }
        
        .coach-row-container {
            overflow-x: auto;
            overflow-y: hidden;
            white-space: nowrap;
            padding-bottom: 10px;
            -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
        }
        
        .coach-row-container::-webkit-scrollbar {
            height: 6px;
        }
        
        .coach-row-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .coach-row-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .coach-row-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        @media (max-width: 768px) {
            .coach-card {
                width: 250px !important;
                margin-right: 0.75rem;
            }
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-card.primary { border-left: 4px solid #007bff; }
        .stat-card.info { border-left: 4px solid #17a2b8; }
        .stat-card.success { border-left: 4px solid #28a745; }
        .stat-card.warning { border-left: 4px solid #ffc107; }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .stat-card.primary .stat-icon { background: rgba(0,123,255,0.1); color: #007bff; }
        .stat-card.info .stat-icon { background: rgba(23,162,184,0.1); color: #17a2b8; }
        .stat-card.success .stat-icon { background: rgba(40,167,69,0.1); color: #28a745; }
        .stat-card.warning .stat-icon { background: rgba(255,193,7,0.1); color: #ffc107; }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="content-main">
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success ?? '') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error ?? '') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <div class="page-header-content">
            <h1 class="page-title">
                <i class="fas fa-shield-alt"></i> 
                <?= htmlspecialchars($team['name'] ?? 'Unknown Team') ?>
            </h1>
            <p class="page-subtitle">Team Details & Squad Management</p>
        </div>
        <div class="page-actions">
            <a href="edit_team.php?id=<?= $team['id'] ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Team
            </a>
            <a href="#" class="btn btn-success" onclick="openCoachModal()">
                <i class="fas fa-user-plus"></i> Manage Coaches
            </a>
            <a href="delete_team.php?id=<?= $team['id'] ?>" class="btn btn-danger" 
               onclick="return confirm('Are you sure you want to delete this team?')">
                <i class="fas fa-trash-alt"></i> Delete Team
            </a>
            <a href="teams.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Teams
            </a>
        </div>
    </div>

    <!-- Team Overview & Quick Navigation Row -->
    <div class="row mb-2">
        <!-- Quick Team Navigation -->
        <div class="col-md-3">
            <div class="card h-100" style="background-color: #f8f9fa;">
                <div class="card-header">
                    <h2><i class="fas fa-exchange-alt"></i> Quick Team Switch</h2>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-2">
                        <?php 
                        try {
                            $stmt = $db->prepare("SELECT id, name FROM teams ORDER BY name");
                            $stmt->execute();
                            $all_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) {
                            $all_teams = [];
                            error_log("Error loading teams list: " . $e->getMessage());
                        }
                        foreach ($all_teams as $t): 
                        ?>
                            <a href="team_details.php?id=<?= $t['id'] ?>#squad-list" 
                               class="btn team-switch-btn <?= $t['id'] == $team_id ? 'active' : '' ?> btn-sm mb-1">
                                <?= htmlspecialchars($t['name'] ?? '') ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Team Overview -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-info-circle"></i> Team Overview</h2>
                </div>
                <div class="card-body">
                    <!-- Dashboard-style Stats Grid -->
                    <div class="stats-grid">
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?= htmlspecialchars($team['name'] ?? '') ?></div>
                                <div class="stat-label">Team Name</div>
                            </div>
                        </div>
                        
                        <div class="stat-card info">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?= htmlspecialchars($team['age_group'] ?? 'Not set') ?></div>
                                <div class="stat-label">Age Group</div>
                            </div>
                        </div>
                        
                        <div class="stat-card success">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?= $team['player_count'] ?></div>
                                <div class="stat-label">Players</div>
                            </div>
                        </div>
                        
                        <div class="stat-card warning">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?= $team['avg_age'] ? round($team['avg_age'], 1) : 'N/A' ?></div>
                                <div class="stat-label">Avg Age</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Coach Information Section -->
                    <?php if (!empty($team_coaches)): ?>
                        <div class="mt-3">
                            <h5 class="mb-3">
                                <i class="fas fa-user-tie"></i> Team Coaches (<?= count($team_coaches) ?>)
                            </h5>
                            <div class="coach-row-container" style="overflow-x: auto; overflow-y: hidden; white-space: nowrap; padding-bottom: 10px;">
                                <div class="d-inline-flex" style="gap: 1rem; min-width: max-content;">
                                    <?php foreach ($team_coaches as $coach): ?>
                                        <div class="coach-card" style="display: inline-block; vertical-align: top; width: 280px; flex-shrink: 0;">
                                            <div class="card border-primary h-100">
                                                <div class="card-body p-3">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <div class="coach-avatar mr-3">
                                                            <i class="fas fa-user-tie fa-2x text-primary"></i>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-0">
                                                                <strong><?= htmlspecialchars($coach['name'] ?? '') ?></strong>
                                                            </h6>
                                                            <small class="text-muted">
                                                                <i class="fas fa-tag"></i> <?= ucwords(str_replace('_', ' ', $coach['role'])) ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($coach['email']) || !empty($coach['phone'])): ?>
                                                        <div class="coach-contact">
                                                            <?php if (!empty($coach['email'])): ?>
                                                                <small class="d-block">
                                                                    <i class="fas fa-envelope text-muted"></i>
                                                                    <a href="mailto:<?= htmlspecialchars($coach['email'] ?? '') ?>" class="text-decoration-none">
                                                                        <?= htmlspecialchars($coach['email'] ?? '') ?>
                                                                    </a>
                                                                </small>
                                                            <?php endif; ?>
                                                            <?php if (!empty($coach['phone'])): ?>
                                                                <small class="d-block">
                                                                    <i class="fas fa-phone text-muted"></i>
                                                                    <a href="tel:<?= htmlspecialchars($coach['phone'] ?? '') ?>" class="text-decoration-none">
                                                                        <?= htmlspecialchars($coach['phone'] ?? '') ?>
                                                                    </a>
                                                                </small>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-3">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>No Coaches Assigned</strong><br>
                                This team doesn't have any coaches assigned yet. 
                                <a href="teams.php" class="alert-link">Go to Teams page</a> to assign coaches.
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($team['description'])): ?>
                        <div class="mt-2">
                            <div class="alert alert-info">
                                <strong><i class="fas fa-info-circle"></i> Description:</strong> 
                                <?= htmlspecialchars($team['description'] ?? 'No description available') ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Squad List -->
    <div id="squad-list" class="card mb-2">
        <div class="card-header">
            <h2><i class="fas fa-users"></i> Squad (<?= count($team_players) ?> Players)</h2>
        </div>
        <div class="card-body">
            <?php if (empty($team_players)): ?>
                <div class="text-center py-2">
                    <i class="fas fa-users fa-3x text-muted mb-2"></i>
                    <h5>No Players Added</h5>
                    <p class="text-muted">This team doesn't have any players yet.</p>
                    <?php if (!empty($available_players)): ?>
                        <p class="text-info">Use the "Add Players to Team" section below to assign players.</p>
                    <?php else: ?>
                        <p class="text-warning">All players are already assigned to this team or no players exist.</p>
                        <a href="players.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Manage Players
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Jersey #</th>
                                <th>Age</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($team_players as $player): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($player['name'] ?? '') ?> <?= htmlspecialchars($player['surname'] ?? '') ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($player['position'] ?? '') ?></td>
                                    <td>
                                        <?php if ($player['jersey_number']): ?>
                                            <span class="badge badge-primary">#<?= $player['jersey_number'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $player['age'] ? $player['age'] . ' years' : 'N/A' ?></td>
                                    <td>
                                        <a href="player_profile.php?id=<?= $player['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="edit_player.php?id=<?= $player['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" action="" style="display: inline-block;" 
                                              onsubmit="return confirm('Are you sure you want to remove this player from the team?')">
                                            <input type="hidden" name="action" value="remove_player">
                                            <input type="hidden" name="player_id" value="<?= $player['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-times"></i> Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Player Management Section -->
    <?php if (!empty($available_players)): ?>
    <div class="card mb-2">
        <div class="card-header">
            <h2><i class="fas fa-user-plus"></i> Add Players to Team</h2>
        </div>
        <div class="card-body">
            <p class="text-muted mb-2">Select players to add to this team. Players from other teams will be transferred.</p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_player">
                <div class="row">
                    <div class="col-md-8">
                        <select name="player_id" class="form-control" required>
                            <option value="">Select a player to add...</option>
                            <?php foreach ($available_players as $player): ?>
                                <option value="<?= $player['id'] ?>">
                                    <?= htmlspecialchars($player['name'] ?? '') ?>
                                    <?php if ($player['age']): ?>
                                        (<?= $player['age'] ?> years)
                                    <?php endif; ?>
                                    <?php if ($player['position']): ?>
                                        - <?= htmlspecialchars($player['position'] ?? '') ?>
                                    <?php endif; ?>
                                    <?php if ($player['current_team_name']): ?>
                                        - Currently: <?= htmlspecialchars($player['current_team_name'] ?? '') ?>
                                    <?php else: ?>
                                        - Unassigned
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Player
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Team Statistics -->
    <div class="card mb-2">
        <div class="card-header">
            <h2><i class="fas fa-chart-bar"></i> Team Statistics</h2>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-4">
                    <h4><?= $team_stats['total_events'] ?? 0 ?></h4>
                    <small class="text-muted">Total Events</small>
                </div>
                <div class="col-4">
                    <h4><?= $team_stats['upcoming_events'] ?? 0 ?></h4>
                    <small class="text-muted">Upcoming</small>
                </div>
                <div class="col-4">
                    <h4><?= $team_stats['past_events'] ?? 0 ?></h4>
                    <small class="text-muted">Completed</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Events -->
    <?php if (!empty($recent_events)): ?>
        <div class="card mb-2">
            <div class="card-header">
                <h2><i class="fas fa-calendar"></i> Recent Events</h2>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php foreach ($recent_events as $event): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($event['title'] ?? '') ?></strong><br>
                                <small class="text-muted">
                                    <?= date('M j, Y', strtotime($event['event_date'])) ?> • 
                                    <?= ucfirst($event['event_type']) ?>
                                </small>
                            </div>
                            <span class="badge badge-<?= strtotime($event['event_date']) >= time() ? 'primary' : 'secondary' ?>">
                                <?= strtotime($event['event_date']) >= time() ? 'Upcoming' : 'Past' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Mobile Navigation JavaScript -->
<script src="js/mobile-navigation.js"></script>

<!-- Team Switch Smooth Scrolling -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if there's a hash in the URL (e.g., #squad-list)
    if (window.location.hash) {
        // Small delay to ensure page is fully loaded
        setTimeout(function() {
            const target = document.querySelector(window.location.hash);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }, 100);
    }
});

function openCoachModal() {
    document.getElementById('coachModal').style.display = 'block';
    loadTeamCoaches();
    loadAvailableCoaches();
}

function closeCoachModal() {
    document.getElementById('coachModal').style.display = 'none';
}

function loadTeamCoaches() {
    fetch(`load_team_coaches.php?team_id=<?= $team_id ?>`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('currentCoachesList');
            container.innerHTML = '';

            if (data.length === 0) {
                container.innerHTML = '<p class="text-muted">No coaches assigned to this team.</p>';
            } else {
                data.forEach(coach => {
                    const coachDiv = document.createElement('div');
                    coachDiv.className = 'coach-item d-flex justify-content-between align-items-center p-2 border rounded mb-2';
                    coachDiv.innerHTML = `
                        <div>
                            <strong>${coach.name}</strong> 
                            <small class="text-muted">(${coach.role})</small>
                        </div>
                        <button type="button" onclick="removeCoach(${coach.id})" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    `;
                    container.appendChild(coachDiv);
                });
            }
        })
        .catch(error => console.error('Error loading coaches:', error));
}

function loadAvailableCoaches() {
    fetch(`load_available_coaches.php?team_id=<?= $team_id ?>`)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('availableCoachesSelect');
            select.innerHTML = '<option value="">Select a coach to add...</option>';

            data.forEach(coach => {
                const option = document.createElement('option');
                option.value = coach.id;
                option.textContent = `${coach.name} (${coach.role})`;
                select.appendChild(option);
            });
        })
        .catch(error => console.error('Error loading available coaches:', error));
}

function addCoach() {
    const coachId = document.getElementById('availableCoachesSelect').value;

    if (!coachId) {
        alert('Please select a coach to add.');
        return;
    }

    fetch('assign_coach_to_team.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `team_id=<?= $team_id ?>&coach_id=${coachId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadTeamCoaches();
            loadAvailableCoaches();
            // Reload the page to update the coach display
            setTimeout(() => location.reload(), 500);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

function removeCoach(coachId) {
    if (!confirm('Are you sure you want to remove this coach from the team?')) {
        return;
    }

    fetch('remove_coach_from_team.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `team_id=<?= $team_id ?>&coach_id=${coachId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadTeamCoaches();
            loadAvailableCoaches();
            // Reload the page to update the coach display
            setTimeout(() => location.reload(), 500);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('coachModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>

<!-- Coach Management Modal -->
<div id="coachModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-tie"></i> Manage Coaches - <?= htmlspecialchars($team['name'] ?? '') ?>
                </h5>
                <button type="button" class="close" onclick="closeCoachModal()">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Current Coaches</h6>
                        <div id="currentCoachesList" class="border p-3" style="min-height: 200px;">
                            <p class="text-muted">Loading...</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Add Coach</h6>
                        <div class="border p-3">
                            <div class="form-group">
                                <label for="availableCoachesSelect">Available Coaches</label>
                                <select id="availableCoachesSelect" class="form-control">
                                    <option value="">Loading coaches...</option>
                                </select>
                            </div>
                            <button type="button" onclick="addCoach()" class="btn btn-primary btn-block">
                                <i class="fas fa-plus"></i> Add Coach
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCoachModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
        <?php echo VIVOColorSystem::generateDynamicCSS(); ?>
        
.coach-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
}

.coach-contact small {
    display: block;
    margin-bottom: 2px;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1050;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal.fade .modal-dialog {
    transform: translate(0, -50px);
    transition: transform 0.3s ease-out;
}

.modal.show .modal-dialog {
    transform: translate(0, 0);
}

.modal-dialog {
    max-width: 800px;
    margin: 1.75rem auto;
}

@media (max-width: 768px) {
    .coach-contact small {
        font-size: 11px;
    }
}
</style>
</body>
</html>
