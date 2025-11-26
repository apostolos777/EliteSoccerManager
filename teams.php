<?php
/**
 * VIVO United - Teams Management
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

// Database connection - use unified config
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
} catch (Exception $e) {
    error_log("Teams - Failed to load club settings: " . $e->getMessage());
}

// Clear color cache to ensure fresh data
if (class_exists('VIVOColorSystem')) {
    VIVOColorSystem::clearCache();
}

// Flash support
$flashMsg = null;
if (!function_exists('get_flash')) { require_once 'functions.php'; }
if (function_exists('get_flash')) { $flashMsg = get_flash(); }

// Get teams with player counts, statistics, and coach information - WordPress style
// Determine if players.status exists to allow filtering active players safely
try {
    $playersColsStmt = $db->query("PRAGMA table_info(players)");
    $playersCols = array_column($playersColsStmt->fetchAll(), 'name');
} catch (Exception $e) {
    $playersCols = [];
}
$playerJoinCondition = in_array('status', $playersCols) ? "t.id = p.team_id AND p.status = 'active'" : "t.id = p.team_id";

$teamsQuery = "
    SELECT t.*,
           COUNT(DISTINCT p.id) as player_count,
    (
        SELECT COUNT(*)
        FROM attendance a
        JOIN players p2 ON a.player_id = p2.id
        WHERE p2.team_id = t.id AND a.status = 'present' AND datetime(a.created_at) >= datetime('now', '-30 days')
    ) as recent_attendance,
    (
        SELECT COUNT(*)
        FROM attendance a
        JOIN players p3 ON a.player_id = p3.id
        WHERE p3.team_id = t.id AND datetime(a.created_at) >= datetime('now', '-30 days')
    ) as total_sessions,
    GROUP_CONCAT(DISTINCT c.name) as coach_names,
    COUNT(DISTINCT tc.coach_id) as coach_count,
    (
        SELECT c2.name FROM coaches c2
        JOIN team_coaches tc2 ON c2.id = tc2.coach_id
        WHERE tc2.team_id = t.id AND tc2.role_in_team = 'head_coach'
        LIMIT 1
    ) as head_coach,
    (
        SELECT c3.name FROM coaches c3
        JOIN team_coaches tc3 ON c3.id = tc3.coach_id
        WHERE tc3.team_id = t.id AND tc3.role_in_team = 'assistant_coach'
        LIMIT 1
    ) as assistant_coach
    FROM teams t
    LEFT JOIN players p ON {$playerJoinCondition}
    LEFT JOIN team_coaches tc ON t.id = tc.team_id
    LEFT JOIN coaches c ON tc.coach_id = c.id
    GROUP BY t.id
    ORDER BY t.name";

try {
    $stmt = $db->prepare($teamsQuery);
    $stmt->execute();
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $teams = [];
    $error = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams - VIVO United</title>
    <?php 
    // Include dynamic CSS system
    require_once 'includes/css_helper.php';
    vivo_include_head_css($db);
    vivo_include_react_scripts();
    ?>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="content-main">
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title">
                    <i class="fas fa-shield-alt"></i>
                    Team Management
                </h1>
                <p class="page-subtitle">Organize squads and track team performance</p>
                
                <div class="page-actions">
                    <a href="team_edit.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i>
                        Add New Team
                    </a>
                    <!-- Team Reports button removed per UX update -->
                </div>
            </div>
        </div>

        <style>
        <?php echo VIVOColorSystem::generateDynamicCSS(); ?>
        
            .alert {
                padding: 1rem;
                margin: 1rem 0;
                border: 1px solid transparent;
                border-radius: 8px;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .alert-success {
                color: #155724;
                background-color: #d4edda;
                border-color: #c3e6cb;
            }
            .alert-error {
                color: #721c24;
                background-color: #f8d7da;
                border-color: #f5c6cb;
            }
            /* Compact team card grid like players */
            .team-filter-bar { display:flex; flex-wrap:wrap; gap:.75rem; margin:0 0 1.2rem; }
            .team-filter-bar input { padding:.55rem .7rem; border:1px solid #d9dde2; border-radius:6px; font-size:.85rem; }
            .team-filter-bar input:focus { outline:none; border-color: var(--primary); box-shadow:0 0 0 2px var(--primary-light); }
            .team-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:1rem; }
            .team-card { background:#fff; border:1px solid #e2e6ea; border-radius:10px; display:flex; flex-direction:column; position:relative; overflow:hidden; box-shadow:0 2px 4px rgba(0,0,0,.04); transition:transform .18s ease, box-shadow .18s ease; }
            .team-card:hover { transform:translateY(-3px); box-shadow:0 6px 14px -4px rgba(0,0,0,.15); }
            .tc-header { display:flex; align-items:center; gap:.7rem; padding:.75rem .8rem .55rem; }
            .tc-icon { width:48px; height:48px; background:#eef0f3; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#374151; font-size:1.2rem; position:relative; }
            .tc-body { padding:0 .8rem .75rem; flex:1; display:flex; flex-direction:column; }
            .tc-name { font-size:.95rem; font-weight:600; margin:0 .2rem .15rem 0; display:flex; justify-content:space-between; gap:.5rem; }
            .tc-created { font-size:.55rem; text-transform:uppercase; letter-spacing:.5px; color:#6b7785; margin-bottom:.5rem; }
            .tc-description { font-size:.65rem; line-height:1.2; color:#4b5563; margin:0 0 .6rem; max-height:3.2em; overflow:hidden; }
            .tc-meta { display:grid; grid-template-columns:repeat(3,1fr); gap:.35rem; margin-bottom:.55rem; }
            .tc-stat { background:#f3f4f6; border-radius:4px; padding:.35rem .25rem; text-align:center; }
            .tc-stat span { display:block; line-height:1.05; }
            .tc-stat .l { font-size:.5rem; font-weight:600; color:#6b7785; letter-spacing:.5px; }
            .tc-stat .v { font-size:.7rem; font-weight:700; color:#1f2933; }
            .tc-actions { display:flex; gap:.4rem; margin-top:auto; }
            .tc-actions a { flex:1; text-align:center; font-size:.6rem; padding:.4rem .35rem; border-radius:6px; font-weight:600; letter-spacing:.4px; display:inline-flex; align-items:center; justify-content:center; gap:.25rem; }
            .teams-empty { text-align:center; padding:2.25rem 1rem; border:2px dashed #d5dae0; border-radius:12px; background:#fff; }
            .teams-empty h3 { margin:.35rem 0; font-size:1.1rem; }
            @media (max-width:520px){ .team-grid { grid-template-columns:repeat(auto-fill,minmax(170px,1fr)); } }
        </style>

        <?php
        $flashMsg = $flashMsg ?? null;
        if ($flashMsg && is_array($flashMsg) && isset($flashMsg['t']) && isset($flashMsg['m'])) {
            $flashType = $flashMsg['t'] ?? 'info';
            $flashMessage = $flashMsg['m'] ?? '';
            if ($flashType && $flashMessage) {
        ?>
            <div class="alert alert-<?= htmlspecialchars($flashType) ?>" style="margin-bottom:1rem;">
                <i class="fas fa-<?= $flashType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                <?= htmlspecialchars($flashMessage) ?>
            </div>
        <?php
            }
        }
        ?>

        <div class="team-filter-bar">
            <input type="text" id="teamSearch" placeholder="Search teams" onkeyup="filterTeams()">
        </div>

        <?php if (empty($teams)): ?>
            <div class="teams-empty">
                <i class="fas fa-shield-alt fa-3x" style="color:#94a3b8"></i>
                <h3>No Teams Yet</h3>
                <p>Create a team to get started</p>
                <a href="team_edit.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Team</a>
            </div>
        <?php else: ?>
            <div id="teamsGrid" class="team-grid">
                <?php foreach ($teams as $team):
                    $attendanceRate = $team['total_sessions'] > 0 ? round(($team['recent_attendance'] / $team['total_sessions']) * 100) : 0;
                ?>
                <div class="team-card team-item" data-team-name="<?= strtolower($team['name'] ?: '') ?>">
                    <div class="tc-header">
                        <div class="tc-icon"><i class="fas fa-shield-alt"></i></div>
                        <div class="tc-head-text">
                            <h2 class="tc-name"><?= htmlspecialchars($team['name'] ?: 'Unknown Team') ?></h2>
                        </div>
                    </div>
                    <div class="tc-body">
                        <div class="tc-created">CREATED <?= strtoupper(date('M d, Y', strtotime($team['created_at'] ?? 'now'))) ?></div>
                        <?php if (!empty($team['description'])): ?><p class="tc-description"><?= htmlspecialchars($team['description']) ?></p><?php endif; ?>
                        <div class="tc-meta">
                            <div class="tc-stat"><span class="l">PLY</span><span class="v"><?= $team['player_count'] ?? 0 ?></span></div>
                            <div class="tc-stat"><span class="l">COA</span><span class="v"><?= $team['coach_count'] ?? 0 ?></span></div>
                            <div class="tc-stat"><span class="l">ATT</span><span class="v"><?= $attendanceRate ?>%</span></div>
                        </div>

                        <?php if (!empty($team['head_coach']) || !empty($team['assistant_coach'])): ?>
                        <div class="tc-coaches">
                            <small style="color:#6b7785; font-weight:600;">COACHES:</small><br>
                            <?php if (!empty($team['head_coach'])): ?>
                                <small style="color:#374151; display:block;">Head: <?= htmlspecialchars($team['head_coach']) ?></small>
                            <?php endif; ?>
                            <?php if (!empty($team['assistant_coach'])): ?>
                                <small style="color:#374151; display:block;">Assistant: <?= htmlspecialchars($team['assistant_coach']) ?></small>
                            <?php endif; ?>
                        </div>
                        <?php elseif (!empty($team['coach_names'])): ?>
                        <div class="tc-coaches">
                            <small style="color:#6b7785; font-weight:600;">COACHES:</small><br>
                            <small style="color:#374151;"><?= htmlspecialchars(substr($team['coach_names'], 0, 30)) ?><?php if (strlen($team['coach_names']) > 30) echo '...'; ?></small>
                        </div>
                        <?php endif; ?>

                        <div class="tc-actions">
                            <a href="team_details.php?id=<?= $team['id'] ?? '' ?>" class="view"><i class="fas fa-eye"></i> View</a>
                            <a href="team_edit.php?id=<?= $team['id'] ?? '' ?>&action=edit" class="edit"><i class="fas fa-edit"></i> Edit</a>
                            <a href="#" class="assign-coach-btn" onclick="openCoachAssignment(<?= $team['id'] ?>, '<?= htmlspecialchars(addslashes($team['name'])) ?>')"><i class="fas fa-user-plus"></i> Coach</a>
                            <a href="delete_team.php?id=<?= $team['id'] ?? '' ?>" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <script>
            function filterTeams(){
                const s=document.getElementById('teamSearch').value.toLowerCase();
                document.querySelectorAll('.team-item').forEach(card=>{
                    const n=card.getAttribute('data-team-name');
                    card.style.display = !s || n.includes(s) ? 'flex':'none';
                });
            }

            function openCoachAssignment(teamId, teamName) {
                document.getElementById('assignmentTeamId').value = teamId;
                document.getElementById('assignmentTeamName').textContent = teamName;
                document.getElementById('coachAssignmentModal').style.display = 'block';
                loadTeamCoaches(teamId);
                loadAvailableCoaches(teamId);
            }

            function closeCoachAssignment() {
                document.getElementById('coachAssignmentModal').style.display = 'none';
            }

            function loadTeamCoaches(teamId) {
                fetch(`load_team_coaches.php?team_id=${teamId}`)
                    .then(response => response.json())
                    .then(data => {
                        const container = document.getElementById('currentCoaches');
                        container.innerHTML = '';

                        if (data.length === 0) {
                            container.innerHTML = '<p style="color:#6b7785; font-style:italic;">No coaches assigned</p>';
                        } else {
                            data.forEach(coach => {
                                const coachDiv = document.createElement('div');
                                coachDiv.className = 'coach-item';
                                coachDiv.innerHTML = `
                                    <span>${coach.name} (${coach.role})</span>
                                    <button type="button" onclick="removeCoach(${teamId}, ${coach.id})" class="btn-remove">
                                        <i class="fas fa-times"></i>
                                    </button>
                                `;
                                container.appendChild(coachDiv);
                            });
                        }
                    })
                    .catch(error => console.error('Error loading coaches:', error));
            }

            function loadAvailableCoaches(teamId) {
                fetch(`load_available_coaches.php?team_id=${teamId}`)
                    .then(response => response.json())
                    .then(data => {
                        const select = document.getElementById('availableCoaches');
                        select.innerHTML = '<option value="">Select a coach...</option>';

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
                const teamId = document.getElementById('assignmentTeamId').value;
                const coachId = document.getElementById('availableCoaches').value;

                if (!coachId) {
                    alert('Please select a coach to add.');
                    return;
                }

                fetch('assign_coach_to_team.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `team_id=${teamId}&coach_id=${coachId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadTeamCoaches(teamId);
                        loadAvailableCoaches(teamId);
                        // Refresh the page to update coach count
                        setTimeout(() => location.reload(), 500);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }

            function removeCoach(teamId, coachId) {
                if (!confirm('Are you sure you want to remove this coach from the team?')) {
                    return;
                }

                fetch('remove_coach_from_team.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `team_id=${teamId}&coach_id=${coachId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadTeamCoaches(teamId);
                        loadAvailableCoaches(teamId);
                        // Refresh the page to update coach count
                        setTimeout(() => location.reload(), 500);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('coachAssignmentModal');
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            }
        </script>
    </div>

    <!-- Coach Assignment Modal -->
    <div id="coachAssignmentModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Assign Coaches to <span id="assignmentTeamName"></span></h3>
                <span class="close" onclick="closeCoachAssignment()">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assignmentTeamId">

                <div class="assignment-section">
                    <h4>Current Coaches</h4>
                    <div id="currentCoaches" class="coach-list">
                        <p style="color:#6b7785; font-style:italic;">Loading...</p>
                    </div>
                </div>

                <div class="assignment-section">
                    <h4>Add Coach</h4>
                    <div class="add-coach-form">
                        <select id="availableCoaches" class="form-control">
                            <option value="">Loading coaches...</option>
                        </select>
                        <button type="button" onclick="addCoach()" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Coach
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        <?php echo VIVOColorSystem::generateDynamicCSS(); ?>
        
        .tc-coaches {
            margin-bottom: 0.5rem;
            padding: 0.25rem 0;
            border-top: 1px solid #e5e7eb;
        }

        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            color: #1f2937;
        }

        .close {
            color: #6b7280;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #374151;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .assignment-section {
            margin-bottom: 1.5rem;
        }

        .assignment-section h4 {
            margin: 0 0 0.5rem 0;
            color: #374151;
            font-size: 1rem;
        }

        .coach-list {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 0.75rem;
            min-height: 60px;
            background: #f9fafb;
        }

        .coach-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            background: white;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            border: 1px solid #e5e7eb;
        }

        .coach-item:last-child {
            margin-bottom: 0;
        }

        /* btn-remove now uses global styles from vivo-style.css */

        .add-coach-form {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .add-coach-form .form-control {
            flex: 1;
        }

        /* btn-sm now uses global styles from vivo-style.css */

        @media (max-width: 640px) {
            .modal-content {
                margin: 2% auto;
                width: 95%;
            }

            .add-coach-form {
                flex-direction: column;
                align-items: stretch;
            }

            /* Button alignment uses global button styles */
        }
    </style>
</body>
</html>
