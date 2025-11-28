<?php
/**
 * VIVO United - Players Management (WordPress Style)
 */

// Load configuration and authentication
require_once 'includes/color_system.php';
require_once 'includes/auth.php';
require_once 'includes/country_helper.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// auth.php starts session when needed; avoid calling session_start() here to prevent notices
$currentPage = 'players';

// Database connection - use unified config
require_once 'database_config.php';
try {
    $db = DatabaseConfigSQLite::getConnection();
} catch (Throwable $e) { 
    die("Database connection failed: " . $e->getMessage()); 
}

// Clear color cache to ensure fresh data
if (class_exists('VIVOColorSystem')) {
    VIVOColorSystem::clearCache();
}

// Use predefined position list from add player page
$allPositions = [
    'Goalkeeper',
    'Centre-Back',
    'Left-Back',
    'Right-Back',
    'Defensive Midfielder',
    'Central Midfielder',
    'Attacking Midfielder',
    'Left Winger',
    'Right Winger',
    'Striker',
    'Centre-Forward'
];

// Flash support
$flashMsg = null;
if (!function_exists('get_flash')) { require_once 'functions.php'; }
if (function_exists('get_flash')) { $flashMsg = get_flash(); }
// If a create flow stored the created player's name in session, attach it to the flash for modal display
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['flash_created_name'])) {
    if (!is_array($flashMsg)) $flashMsg = ['t' => $_SESSION['flash_type'] ?? 'success', 'm' => $_SESSION['flash_message'] ?? ''];
    $flashMsg['name'] = $_SESSION['flash_created_name'];
    unset($_SESSION['flash_created_name']);
}

// Get players data - using PDO
try {
    // Detect available columns in players table (legacy schemas may lack first_name/last_name/status)
    $cols = [];
    $colStmt = $db->query("PRAGMA table_info(players)");
    foreach ($colStmt->fetchAll() as $c) { $cols[] = $c['name']; }

    $hasFirst = in_array('first_name', $cols);
    $hasLast = in_array('last_name', $cols);
    $hasStatus = in_array('status', $cols);
    $hasNameCol = in_array('name', $cols); // some older schemas used a single name column
    $hasUniqueId = in_array('unique_player_id', $cols);
    $hasJersey = in_array('jersey_number', $cols);

    $hasSurname = in_array('surname', $cols);

    // Build a safe base expression that only references existing columns
    if ($hasFirst && $hasLast) {
        $baseExpr = "TRIM(COALESCE(p.first_name,'') || ' ' || COALESCE(p.last_name,''))";
    } elseif ($hasNameCol && $hasSurname) {
        $baseExpr = "TRIM(COALESCE(p.name,'') || ' ' || COALESCE(p.surname,''))";
    } elseif ($hasFirst) {
        $baseExpr = "p.first_name";
    } elseif ($hasLast) {
        $baseExpr = "p.last_name";
    } elseif ($hasNameCol) {
        $baseExpr = "p.name";
    } else {
        $baseExpr = "''"; // will fall back further below
    }

    // Additional fallbacks chain (only use columns if they exist)
    $fallbacks = [];
    if ($hasUniqueId) { $fallbacks[] = "(p.unique_player_id IS NOT NULL AND p.unique_player_id <> '') THEN p.unique_player_id"; }
    if ($hasJersey) { $fallbacks[] = "(p.jersey_number IS NOT NULL AND p.jersey_number <> '') THEN 'Player #' || p.jersey_number"; }

    $cases = [];
    $cases[] = "(" . $baseExpr . " IS NOT NULL AND " . $baseExpr . " <> '') THEN " . $baseExpr;
    $cases = array_merge($cases, $fallbacks);
    $caseBody = '';
    foreach ($cases as $c) { $caseBody .= "WHEN " . $c . " "; }
    $nameExpr = "CASE $caseBody ELSE 'Player ' || p.id END";

    $whereActive = $hasStatus ? "WHERE p.status = 'active'" : ""; // only filter if status column exists

    $playersQuery = "SELECT p.*, t.name AS team_name, $nameExpr AS name
        FROM players p
        LEFT JOIN teams t ON p.team_id = t.id
        $whereActive
        ORDER BY name";

    $stmt = $db->prepare($playersQuery);
    $stmt->execute();
    $players = $stmt->fetchAll();
    
    // Fetch team names for team_ids (comma-separated) for each player
    foreach ($players as &$p) {
        $p['all_teams'] = []; // Initialize
        
        // Prefer a modern join-table mapping (player_teams) if present — this is authoritative
        $allTeamsFromJoin = [];
        try {
            $r = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='player_teams'")->fetch(PDO::FETCH_ASSOC);
            if ($r) {
                $ptStmt = $db->prepare('SELECT t.name FROM player_teams pt JOIN teams t ON pt.team_id = t.id WHERE pt.player_id = ? ORDER BY t.name');
                $ptStmt->execute([$p['id']]);
                $allTeamsFromJoin = $ptStmt->fetchAll(PDO::FETCH_COLUMN, 0);
            }
        } catch (Exception $e) { /* ignore */ }

        if (!empty($allTeamsFromJoin)) {
            $p['all_teams'] = $allTeamsFromJoin;
        } elseif (!empty($p['team_ids'])) {
            $teamIds = array_map('trim', explode(',', $p['team_ids']));
            $teamIds = array_filter($teamIds, function($id) { return !empty($id); });
            
            if (!empty($teamIds)) {
                $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
                $teamsQuery = "SELECT name FROM teams WHERE id IN ($placeholders) ORDER BY name";
                $stmtTeams = $db->prepare($teamsQuery);
                $stmtTeams->execute(array_values($teamIds));
                $teamNames = $stmtTeams->fetchAll(PDO::FETCH_COLUMN, 0);
                $p['all_teams'] = $teamNames; // Array of all team names
            }
        }
        
        // Fallback to primary team if no team_ids
        if (empty($p['all_teams']) && !empty($p['team_name'])) {
            $p['all_teams'] = [$p['team_name']];
        }
    }
    unset($p); // Break reference
} catch (Exception $e) {
    $players = [];
    $error = 'Database error: ' . $e->getMessage();
}

// Handle URL error messages (e.g., access denied)
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Players - VIVO United Manager</title>
    <?php 
    // Include dynamic CSS system
    require_once 'includes/css_helper.php';
    vivo_include_head_css($db);
    vivo_include_react_scripts();
    ?>
    <style>
    /* Compact card grid layout; force 4 columns on desktop and constrain image height */
    /* Use 3 columns per row as requested */
    .player-list-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; }
    @media (max-width:1100px){ .player-list-grid { grid-template-columns:repeat(2,1fr); } }
    @media (max-width:800px){ .player-list-grid { grid-template-columns:repeat(2,1fr); } }
    @media (max-width:520px){ .player-list-grid { grid-template-columns:1fr; } }

     /* Constrain player photo height so images don't dominate the card
         Use a tighter height so 4 cards comfortably fit per row on desktop */
    .pcm-photo { width:90px; height:125px; max-height:140px; background:#f0f2f5; display:flex; align-items:center; justify-content:center; position:relative; overflow:hidden; flex-shrink:0; }
    .pcm-photo img { width:100%; height:100%; object-fit:cover; display:block; max-width:none; }
        .pcm-photo .placeholder { font-size:3rem; color:#94a3b8; }
        .pcm-number { position:absolute; left:0; bottom:0; background:#1f2933; color:#fff; padding:.3rem .6rem; font-size:.75rem; font-weight:600; border-top-right-radius:6px; letter-spacing:.5px; }
        .pcm-body { padding:.7rem .75rem .75rem; display:flex; flex-direction:column; flex:1; }
        .pcm-name { font-size:.95rem; font-weight:600; margin:0 0 .15rem; display:flex; flex-wrap:wrap; gap:.35rem; align-items:center; }
        .pcm-pos { background:#1f9d94; color:#fff; font-size:.55rem; padding:.2rem .4rem; border-radius:4px; font-weight:600; letter-spacing:.5px; }
        .pcm-team { font-size:.65rem; font-weight:500; color:#64748b; margin:0 0 .4rem; text-transform:uppercase; letter-spacing:.6px; }
        .pcm-meta { display:grid; grid-template-columns:repeat(3,1fr); gap:.35rem; margin-bottom:.55rem; }
        .pcm-stat { background:#f3f4f6; border-radius:4px; padding:.35rem .25rem; text-align:center; }
        .pcm-stat span { display:block; line-height:1.05; }
        .pcm-stat .l { font-size:.5rem; font-weight:600; color:#6b7785; letter-spacing:.5px; }
        .pcm-stat .v { font-size:.7rem; font-weight:700; color:#1f2933; }
        .pcm-actions { margin-top:auto; display:flex; gap:.4rem; }
        .pcm-actions a { flex:1; text-align:center; font-size:.65rem; padding:.45rem .4rem; border-radius:6px; font-weight:600; letter-spacing:.4px; display:inline-flex; align-items:center; justify-content:center; gap:.25rem; }
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
        .pp-empty { text-align:center; padding:2.25rem 1rem; border:2px dashed #d5dae0; border-radius:12px; background:#fff; }
        .pp-empty h3 { margin:.35rem 0; font-size:1.1rem; }
    @media (max-width:520px){ .player-list-grid { grid-template-columns:1fr; } .pcm-photo { height:125px; } }

    /* Make player items use the same card styling as teams (team-card) */
    .team-card {
        background:#fff; border:1px solid var(--border-light); border-radius:12px; display:flex; flex-direction:column; overflow:hidden; transition: transform .18s ease, box-shadow .18s ease; box-shadow:var(--shadow-sm);
    }
    .team-card:hover { transform:translateY(-4px); box-shadow:var(--shadow-md); }
    .team-card .pcm-photo { width:100%; height:140px; object-fit:cover; border-bottom:1px solid rgba(0,0,0,0.04); }
    .team-card .pcm-body { padding: .9rem; }
    @media (max-width:520px){ .player-list-grid { grid-template-columns: 1fr !important; } }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="content-main">
    <div class="page-header">
        <div class="page-header-content">
            <h1 class="page-title"><i class="fas fa-users"></i> Players</h1>
            <p class="page-subtitle">Squad overview and player profiles</p>
        </div>
        <div class="page-actions">
            <a href="add_player.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add Player</a>
            <button id="bulkDeleteBtn" class="btn btn-danger" style="margin-left:.5rem; display:none;" onclick="bulkDeleteSelected()"><i class="fas fa-trash"></i> Delete Selected</button>
        </div>
    </div>

    <?php if ($flashMsg && isset($flashMsg['t']) && isset($flashMsg['m']) && $flashMsg['t'] !== null && $flashMsg['m'] !== null): ?>
        <div class="alert alert-<?= htmlspecialchars($flashMsg['t']) ?>">
            <i class="fas fa-<?= $flashMsg['t']==='success'?'check-circle':'exclamation-triangle' ?>"></i>
            <?= htmlspecialchars($flashMsg['m']) ?>
        </div>
    <?php endif; ?>

    <?php if ($flashMsg && is_array($flashMsg) && isset($flashMsg['name']) && $flashMsg['name']): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function(){
            try {
                var createdName = <?= json_encode($flashMsg['name']) ?>;
                var content = '<div style="padding:1rem;font-size:1rem;">' +
                              '<p style="margin:0 0 .5rem;">Successfully added: <strong>' + createdName + '</strong></p>' +
                              '<div style="text-align:right;margin-top:.75rem;">' +
                              '<button onclick="closeInlineModal()" style="background:var(--primary);color:#fff;border:none;padding:.5rem .75rem;border-radius:6px;">OK</button>' +
                              '</div></div>';
                showInlineModal(content, 'Created');
            } catch (e) { console.warn('Could not show creation modal', e); }
        });
        </script>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- React-powered Players List -->
    <div id="react-players-list"></div>

    <!-- Legacy PHP content hidden when React loads -->
    <div id="legacy-players-content" style="display:none;">
    <div class="player-filter-bar">
        <input type="text" id="playerSearch" placeholder="Search name / team / position" onkeyup="filterPlayers()">
        <select id="teamFilter" onchange="filterPlayers()">
            <option value="">All Teams</option>
            <?php foreach (array_unique(array_filter(array_column($players,'team_name'))) as $team): ?>
                <option value="<?= htmlspecialchars($team) ?>"><?= htmlspecialchars($team) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="ageGroupFilter" onchange="filterPlayers()">
            <option value="">All Age Groups</option>
            <?php
                $ageGroupsPresent = array_filter(array_unique(array_map(function($x){ return $x['age_group'] ?? ''; }, $players)));
                $order = ['U6','U7','U8','U9','U10','U11','U12','U13','U14','U15','U16','U17','U18','U19','U20','U21','Senior','Veterans'];
                usort($ageGroupsPresent, function($a,$b) use ($order){ $ia = array_search($a,$order); $ib = array_search($b,$order); if($ia === false) $ia = 999; if($ib === false) $ib = 999; return $ia - $ib; });
                foreach ($ageGroupsPresent as $ag): if(trim($ag)==='') continue; ?>
                    <option value="<?= htmlspecialchars($ag) ?>"><?= htmlspecialchars($ag) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="positionFilter" onchange="filterPlayers()">
            <option value="">All Positions</option>
            <?php foreach ($allPositions as $pos): ?>
                <option value="<?= htmlspecialchars($pos) ?>"><?= htmlspecialchars($pos) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if (empty($players)): ?>
        <div class="pp-empty">
            <i class="fas fa-users fa-3x" style="color:#94a3b8"></i>
            <h3>No Players Yet</h3>
            <p>Add your first player to get started</p>
            <a href="add_player.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Player</a>
                        <a href="add_player.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Player</a>
        </div>
    <?php else: ?>
        <div style="margin:0 0 1rem; display:flex; gap:.5rem; align-items:center;">
            <label style="display:flex;align-items:center;gap:.4rem"><input type="checkbox" id="selectAllPlayers" onchange="toggleSelectAll(this)"> Select all</label>
        </div>
        <div id="playersGrid" class="player-list-grid">
            <?php foreach ($players as $p):
                // Build full name properly, handling both naming conventions
                $fullName = '';
                if (!empty($p['surname'])) {
                    // Database has separate name and surname columns
                    $name = trim($p['name'] ?? '');
                    $surname = trim($p['surname']);
                    
                    // Check if name already ends with surname to avoid duplication
                    if ($name && !str_ends_with($name, $surname)) {
                        $fullName = trim($name . ' ' . $surname);
                    } else {
                        // Name already contains surname or is empty
                        $fullName = $name ?: $surname;
                    }
                } else {
                    // Database has single name column
                    $fullName = trim($p['name'] ?? '');
                }
                // Ensure fullName is never empty
                $fullName = $fullName ?: 'Unknown Player';
                $age = $p['age'] ?? null;
                if (!$age && !empty($p['date_of_birth']) && $p['date_of_birth'] !== '0000-00-00') {
                    try { $dobDt = new DateTime($p['date_of_birth']); $age = (new DateTime())->diff($dobDt)->y; } catch (Exception $ex) { $age = null; }
                }
                $heightDisplay = !empty($p['height']) ? (is_numeric($p['height']) ? number_format($p['height']/100,2).'m' : htmlspecialchars($p['height'])) : '-';
                $weightDisplay = !empty($p['weight']) ? (is_numeric($p['weight']) ? intval($p['weight']).'kg' : htmlspecialchars($p['weight'])) : '-';
            ?>
            <div class="team-card player-item" tabindex="0" aria-expanded="false" data-player-id="<?= $p['id'] ?>" data-player-name="<?= strtolower($fullName) ?>" data-team="<?= strtolower($p['team_name'] ?? '') ?>" data-position="<?= strtolower($p['position'] ?? '') ?>" data-age-group="<?= htmlspecialchars($p['age_group'] ?? '') ?>" onclick="window.location='player_profile.php?id=<?= $p['id'] ?>'">
                <input type="checkbox" class="player-select-checkbox" value="<?= $p['id'] ?>" style="position:absolute;left:.6rem;top:.6rem;z-index:5;" onclick="event.stopPropagation();updateBulkUI();">
                <div class="pcm-photo">
                    <?php if (!empty($p['profile_image']) && file_exists($p['profile_image'])): ?>
                        <img src="<?= htmlspecialchars($p['profile_image'] ?: '') ?>" alt="<?= htmlspecialchars($fullName) ?>">
                    <?php else: ?>
                        <?php 
                        // Use non-gender specific placeholders (5 variants available)
                        $placeholderNum = (($p['id'] ?? 1) % 5) + 1; // 5 variants: player-placeholder-1.svg through player-placeholder-5.svg
                        $placeholderFile = "player-placeholder-{$placeholderNum}.svg";
                        ?>
                        <img src="images/placeholders/<?= $placeholderFile ?>" alt="<?= htmlspecialchars($fullName) ?> placeholder">
                    <?php endif; ?>
                    <?php if (!empty($p['jersey_number'])): ?><div class="pcm-number">#<?= htmlspecialchars($p['jersey_number'] ?: '') ?></div><?php endif; ?>
                </div>
                <div class="pcm-body">
                    <?php $flag = !empty($p['nationality']) ? vivo_get_flag_for_nationality($p['nationality']) : ''; ?>
                    <h2 class="pcm-name"><?= ($flag ? htmlspecialchars($flag) . ' ' : '') . htmlspecialchars($fullName) ?><?php if(!empty($p['position'])): ?><span class="pcm-pos"><?= htmlspecialchars(strtoupper($p['position'] ?: '')) ?></span><?php endif; ?></h2>
                    <p class="pcm-team"><?= !empty($p['all_teams']) ? htmlspecialchars(implode(', ', $p['all_teams'])) : 'No Team' ?></p>
                    <div class="pcm-meta">
                        <div class="pcm-stat"><span class="l">AGE</span><span class="v"><?= $age !== null ? $age : '-' ?></span></div>
                        <div class="pcm-stat"><span class="l">HT</span><span class="v"><?= $heightDisplay ?></span></div>
                        <div class="pcm-stat"><span class="l">WT</span><span class="v"><?= $weightDisplay ?></span></div>
                        <div class="pcm-stat"><span class="l">POS</span><span class="v"><?= htmlspecialchars(substr($p['position'] ?? '-',0,3)) ?></span></div>
                        <div class="pcm-stat"><span class="l">GRP</span><span class="v"><?= htmlspecialchars($p['age_group'] ?? '-') ?></span></div>
                        <div class="pcm-stat"><span class="l">ID</span><span class="v"><?= !empty($p['vivo_id']) ? htmlspecialchars(substr($p['vivo_id'],-4)) : htmlspecialchars($p['id']) ?></span></div>
                    </div>
                    <div class="pcm-actions">
                        <a href="player_profile.php?id=<?= $p['id'] ?>" class="btn-view" onclick="event.stopPropagation();"><i class="fas fa-eye"></i> View</a>
                        <a href="player_profile.php?id=<?= $p['id'] ?>&action=edit" class="btn-edit" onclick="event.stopPropagation();"><i class="fas fa-edit"></i> Edit</a>
                        <?php if (function_exists('isAdmin') && isAdmin()): ?>
                        <a href="delete_player.php?id=<?= $p['id'] ?>" class="btn-delete" onclick="event.stopPropagation(); return confirm('Are you sure you want to permanently delete this player? This action cannot be undone and will remove all player data including attendance records.');" style="background:var(--primary);color:white !important;"><i class="fas fa-trash"></i> Delete</a>
                        <?php endif; ?>
                        <a href="player_card_print.php?id=<?= $p['id'] ?>" class="btn-secondary" target="_blank" style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:.25rem;font-size:.65rem;background:#6c757d;color:white !important;text-decoration:none;border-radius:6px;padding:.45rem .4rem;font-weight:600;" onclick="event.stopPropagation();"><i class="fas fa-print"></i> Card</a>
                    </div>
                    <div class="pcm-details-panel" aria-hidden="true">
                        <div class="pcm-tags">
                            <?php if(!empty($p['secondary_position'])): ?><span class="pcm-tag">2ND POS: <?= htmlspecialchars(strtoupper($p['secondary_position'] ?: '')) ?></span><?php endif; ?>
                            <?php if(!empty($p['preferred_foot'])): ?><span class="pcm-tag">FOOT: <?= htmlspecialchars(strtoupper($p['preferred_foot'] ?: '')) ?></span><?php endif; ?>
                            <?php if(!empty($p['nationality'])): ?><span class="pcm-tag">NAT: <?= htmlspecialchars(strtoupper(substr($p['nationality'] ?: '',0,3))) ?></span><?php endif; ?>
                            <?php if(!empty($p['age_group'])): ?><span class="pcm-tag">GROUP: <?= htmlspecialchars(strtoupper($p['age_group'] ?: '')) ?></span><?php endif; ?>
                        </div>
                        
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script>
function filterPlayers(){
    const s=document.getElementById('playerSearch').value.toLowerCase();
    const t=document.getElementById('teamFilter').value.toLowerCase();
    const p=document.getElementById('positionFilter').value.toLowerCase();
    const gElem = document.getElementById('ageGroupFilter');
    const g = gElem ? gElem.value.toLowerCase() : '';
    document.querySelectorAll('.player-card').forEach(card=>{
        const n=card.getAttribute('data-player-name');
        const tm=card.getAttribute('data-team');
        const pos=card.getAttribute('data-position');
        const ag = (card.getAttribute('data-age-group')||'').toLowerCase();
        const ok=(n.includes(s)||tm.includes(s)||pos.includes(s)) && (!t||tm.includes(t)) && (!p||pos.includes(p));
        const ageOk = !g || (ag === g);
        card.style.display= (ok && ageOk) ? 'flex':'none';
    });
}
function deletePlayer(id, el){
    if(!confirm('Delete this player? (Marks inactive)')) return;
    const btn=el; if(btn){ btn.dataset.originalText=btn.innerHTML; btn.innerHTML='...'; btn.disabled=true; }
    fetch('delete_player.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ ids: [id] })
    }).then(r=>r.json()).then(function(res){
        if(res && res.success){
            if(res.deleted && res.deleted.length){
                res.deleted.forEach(function(d){
                    const card=document.querySelector('[data-player-id="'+d.id+'"]'); if(card){ card.style.transition='opacity .28s, transform .28s'; card.style.opacity='0.35'; card.style.transform='scale(.96)'; setTimeout(()=>card.remove(),300); }
                });
            }
            updateBulkUI();
        } else {
            alert(res.error || 'Delete failed');
            if(btn){ btn.disabled=false; btn.innerHTML=btn.dataset.originalText; }
        }
    }).catch(function(err){ alert('Delete failed: '+err); if(btn){ btn.disabled=false; btn.innerHTML=btn.dataset.originalText; } });
}
function toggleSelectAll(cb){
    var checkboxes = document.querySelectorAll('.player-select-checkbox');
    checkboxes.forEach(function(ch){ ch.checked = cb.checked; });
    updateBulkUI();
}

function updateBulkUI(){
    var any = Array.from(document.querySelectorAll('.player-select-checkbox')).some(ch=>ch.checked);
    var btn = document.getElementById('bulkDeleteBtn');
    if(btn) btn.style.display = any ? 'inline-block' : 'none';
    var selectAll = document.getElementById('selectAllPlayers');
    if(selectAll){
        var all = Array.from(document.querySelectorAll('.player-select-checkbox')).every(ch=>ch.checked);
        selectAll.checked = all && document.querySelectorAll('.player-select-checkbox').length>0;
    }
}

function bulkDeleteSelected(){
    if(!confirm('Delete selected players? This cannot be undone.')) return;
    var ids = Array.from(document.querySelectorAll('.player-select-checkbox:checked')).map(function(ch){ return parseInt(ch.value,10); });
    if(!ids.length) return;
    fetch('delete_player.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ ids: ids })
    }).then(r=>r.json()).then(function(res){
        if(res && res.success){
            if(res.deleted && res.deleted.length){
                res.deleted.forEach(function(d){
                    const card=document.querySelector('[data-player-id="'+d.id+'"]'); if(card){ card.style.transition='opacity .28s, transform .28s'; card.style.opacity='0.35'; card.style.transform='scale(.96)'; setTimeout(()=>card.remove(),300); }
                });
            }
            updateBulkUI();
        } else {
            alert(res.error || 'Bulk delete failed');
        }
    }).catch(function(err){ alert('Bulk delete failed: '+err); });
}
function togglePlayerCard(card,e){
    const tag=e.target.tagName.toLowerCase();
    if(['a','button','i','svg','path'].includes(tag)) return; // don't toggle when clicking action controls
    const expanded=card.classList.toggle('expanded');
    card.setAttribute('aria-expanded', expanded?'true':'false');
    const panel = card.querySelector('.pcm-details-panel'); if(panel) panel.setAttribute('aria-hidden', expanded?'false':'true');
}
function quickViewProfile(id){
    fetch('player_profile.php?id='+id+'&fragment=1')
        .then(r=>r.text())
        .then(html=>showInlineModal(html,'Player Profile'))
        .catch(()=>alert('Could not load profile'));
}
function showInlineModal(content,title){
    let modal=document.getElementById('inline-modal');
    if(!modal){
        modal=document.createElement('div'); modal.id='inline-modal';
        modal.innerHTML=`<div class="im-backdrop" onclick="closeInlineModal()"></div><div class="im-dialog" role="dialog" aria-modal="true" aria-labelledby="im-title"><div class="im-header"><h3 id="im-title"></h3><button class="im-close" onclick="closeInlineModal()" aria-label="Close">×</button></div><div class="im-body"></div></div>`;
        document.body.appendChild(modal);
        const style=document.createElement('style');
        style.textContent=`#inline-modal{position:fixed;inset:0;z-index:1200;display:flex;align-items:flex-start;justify-content:center;padding:4vh 1rem;font-family:inherit;}#inline-modal .im-backdrop{position:absolute;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px);}#inline-modal .im-dialog{position:relative;background:#fff;border-radius:18px;max-width:680px;width:100%;box-shadow:0 25px 60px -12px rgba(0,0,0,.4);display:flex;flex-direction:column;max-height:90vh;overflow:hidden;animation:imPop .28s cubic-bezier(.16,.8,.3,1);}#inline-modal .im-header{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.15rem;border-bottom:1px solid #e5e7eb;background:#f8fafc;}#inline-modal .im-header h3{margin:0;font-size:1.05rem;font-weight:600;letter-spacing:.5px;}#inline-modal .im-close{background:none;border:none;font-size:1.4rem;cursor:pointer;line-height:1;color:#334155;padding:.2rem;border-radius:6px;}#inline-modal .im-close:hover{background:#f1f5f9;color:#d62828;}#inline-modal .im-body{padding:1rem 1.15rem;overflow:auto;font-size:.85rem;line-height:1.45;color:#334155;}@keyframes imPop{from{opacity:0;transform:translateY(14px) scale(.96);}to{opacity:1;transform:translateY(0) scale(1);}}`; document.head.appendChild(style);
    }
    modal.querySelector('#im-title').textContent=title||'Details';
    modal.querySelector('.im-body').innerHTML=content;
    modal.style.display='flex';
    document.body.style.overflow='hidden';
}
function closeInlineModal(){ const modal=document.getElementById('inline-modal'); if(modal){ modal.style.display='none'; document.body.style.overflow=''; } }
</script>
</div><!-- Close legacy-players-content -->
</div><!-- Close content-main -->
</body>
</html>
