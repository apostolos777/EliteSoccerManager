<?php
/**
 * Player profile (read-only) — mirrors edit_player.php layout but with disabled inputs.
 * Supports fragment mode (fragment=1) which returns a compact modal fragment.
 */
require_once 'includes/color_system.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentPage = 'players';

// Allow a quick override for local testing: ?use_db=kickcv will switch to database_kickcv.db
$dbPath = 'database.db';
if (isset($_GET['use_db']) && $_GET['use_db'] === 'kickcv' && is_file(__DIR__ . '/database_kickcv.db')) {
    $dbPath = 'database_kickcv.db';
}
try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Make DB available to auth helpers which may prefer a global PDO connection
    $GLOBALS['db'] = $db;
} catch (Exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}

$player_id = $_GET['id'] ?? null;
if (!$player_id) {
    header('Location: players.php');
    exit;
}

$fragment = isset($_GET['fragment']) && $_GET['fragment'] == 1;

// schema
$schemaColumns = [];
try {
    $res = $db->query("PRAGMA table_info(players)");
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) $schemaColumns[] = $r['name'];
} catch (Exception $e) { /* ignore */ }

$hasFirstLastName = in_array('first_name', $schemaColumns) && in_array('last_name', $schemaColumns);
$hasNameSurname = in_array('name', $schemaColumns) && in_array('surname', $schemaColumns);

// fetch player
try {
    // Build a safe query: some DBs (e.g. the kickcv sample) may not have players.team_id
    if (in_array('team_id', $schemaColumns)) {
        $stmt = $db->prepare("SELECT p.*, t.name as team_name FROM players p LEFT JOIN teams t ON p.team_id = t.id WHERE p.id = ?");
    } else {
        // team_id isn't present on this schema; fetch player without join and resolve teams separately
        $stmt = $db->prepare("SELECT p.* FROM players p WHERE p.id = ?");
    }
    $stmt->execute([$player_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$player) {
        if ($fragment) {
            echo '<div class="alert alert-danger">Player not found.</div>';
            exit;
        }
        header('Location: players.php');
        exit;
    }
    // Enforce player ownership: only owners (linked user) or admins can view arbitrary profiles
    if (!isAdmin()) {
        // If the users/players link exists, ensure current user owns the requested profile
        if (function_exists('currentUserOwnsPlayer')) {
            if (!currentUserOwnsPlayer($player_id)) {
                if ($fragment) {
                    echo '<div class="alert alert-danger">Access denied.</div>';
                    exit;
                }
                header('Location: players.php?error=' . urlencode('Access denied. You may only view your own profile.'));
                exit;
            }
        }
    }
    
    // Fetch all teams from team_ids OR join table player_teams
    $allTeams = [];

    // If player_teams join table exists, prefer it for authoritative list of teams
    try {
        $r = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='player_teams'")->fetch(PDO::FETCH_ASSOC);
        if ($r) {
            $stmtPt = $db->prepare('SELECT t.id, t.name FROM player_teams pt JOIN teams t ON pt.team_id = t.id WHERE pt.player_id = ? ORDER BY t.name');
            $stmtPt->execute([$player_id]);
            $allTeams = $stmtPt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) { /* ignore */ }

    // Fallback to CSV column on players.team_ids if no rows were returned from player_teams
    if (empty($allTeams) && !empty($player['team_ids'])) {
        $teamIds = array_map('trim', explode(',', $player['team_ids']));
        $teamIds = array_filter($teamIds, function($id) { return !empty($id); });
        
        if (!empty($teamIds)) {
            $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
            $teamsQuery = "SELECT id, name FROM teams WHERE id IN ($placeholders) ORDER BY name";
            $stmtTeams = $db->prepare($teamsQuery);
            $stmtTeams->execute(array_values($teamIds));
            $allTeams = $stmtTeams->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    // Fallback to primary team if no team_ids / no player_teams entries
    if (empty($allTeams) && !empty($player['team_id'])) {
        $allTeams = [['id' => $player['team_id'], 'name' => $player['team_name']]];
    }
    
    // display name
    if ($hasFirstLastName && !empty($player['first_name'])) {
        $playerName = trim($player['first_name'] . ' ' . ($player['last_name'] ?? ''));
    } elseif ($hasNameSurname && !empty($player['name'])) {
        $playerName = trim(($player['name'] ?? '') . ' ' . ($player['surname'] ?? ''));
    } elseif (!empty($player['name'])) {
        $playerName = $player['name'];
    } else {
        $playerName = 'Player ' . $player['id'];
    }
} catch (Exception $e) {
    if ($fragment) {
        echo '<div class="alert alert-danger">Error loading player.</div>';
        exit;
    }
    die('Error fetching player: ' . $e->getMessage());
}

// lookups
$teams = [];
try { $teams = $db->query("SELECT * FROM teams ORDER BY name")->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) { }
$positionOptions = [
    'GK' => 'Goalkeeper',
    'SW' => 'Sweeper',
    'RB' => 'Right Back',
    'LB' => 'Left Back',
    'CB' => 'Centre Back',
    'LCB' => 'Left Centre Back',
    'RCB' => 'Right Centre Back',
    'RWB' => 'Right Wing Back',
    'LWB' => 'Left Wing Back',
    'CDM' => 'Central Defensive Midfielder',
    'CM' => 'Central Midfielder',
    'LCM' => 'Left Central Midfielder',
    'RCM' => 'Right Central Midfielder',
    'CAM' => 'Central Attacking Midfielder',
    'RM' => 'Right Midfielder',
    'LM' => 'Left Midfielder',
    'RW' => 'Right Winger',
    'LW' => 'Left Winger',
    'SS' => 'Second Striker',
    'CF' => 'Centre Forward',
    'ST' => 'Striker',
];
$ageGroups = [ 'U6','U7','U8','U9','U10','U11','U12','U13','U14','U15','U16','U17','U18','U19','U20','U21','Senior' ];

// Country flag helper (used in header and listings)

require_once 'includes/country_helper.php';
$playerFlagImg = !empty($player['nationality']) ? vivo_flag_img_tag($player['nationality'], 20, $player['nationality']) : '';

// fragment modal
if ($fragment) {
    require_once 'includes/css_helper.php';
    vivo_include_head_css($db);
    ?>
    <?php
    // compute flag image if nationality present
    require_once 'includes/country_helper.php';
    $playerFlagImg = !empty($player['nationality']) ? vivo_flag_img_tag($player['nationality'], 20, $player['nationality']) : '';
    ?>
    <div class="player-profile-modal p-3">
        <div class="text-center mb-3">
            <?php if (!empty($player['profile_image'])): ?>
                <img src="<?= htmlspecialchars($player['profile_image']) ?>" alt="<?= htmlspecialchars($playerName) ?>" class="player-photo-large mb-2" style="max-width:120px;border-radius:6px">
            <?php else: ?>
                <div class="player-placeholder-large mb-2"><i class="fas fa-user fa-3x"></i></div>
            <?php endif; ?>
            <h4 class="mb-0"><?= ($playerFlagImg ? $playerFlagImg : '') . htmlspecialchars($playerName) ?></h4>
            <?php if (!empty($player['nickname'])): ?><div class="text-muted">"<?= htmlspecialchars($player['nickname']) ?>"</div><?php endif; ?>
        </div>
            <div class="player-meta small">
            <div><strong>Teams:</strong> <?= !empty($allTeams) ? htmlspecialchars(implode(', ', array_column($allTeams, 'name'))) : 'Not assigned' ?></div>
            <div><strong>Number:</strong> <?= htmlspecialchars($player['jersey_number'] ?? '—') ?></div>
            <div><strong>Primary:</strong>
                <?php
                    $pp = $player['primary_position'] ?? '';
                    if ($pp === '') echo '—';
                    else echo htmlspecialchars(isset($positionOptions[$pp]) ? $positionOptions[$pp] . " ($pp)" : $pp);
                ?>
            </div>
            <div><strong>Age Group:</strong> <?= htmlspecialchars($player['age_group'] ?? '—') ?></div>
        </div>
    </div>
    <?php
    exit;
}

// full page read-only
require_once 'includes/css_helper.php';
vivo_include_head_css($db);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Profile - <?= htmlspecialchars($playerName) ?></title>
    <style>
        /* Ensure form-grid becomes a two-column layout on wide screens */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem; /* tightened vertical and horizontal spacing */
        }
        .form-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.6rem;
        }
        .form-grid .form-group {
            margin-bottom: 0.45rem; /* reduced row padding */
        }
        /* Slightly reduce card body padding for denser layout */
        .card.form-section .card-body { padding: 0.9rem; }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-grid-3 { grid-template-columns: 1fr; }
        }

        /* Player CV specific styling (KickCV-like hero + two-column layout) */
        .player-cv { gap: 2rem; margin-top: 1.2rem; }
        .main-cv { padding-right: 1rem; }
        .side-cv { padding-left: 1rem; }

        /* Hero: use app color system (primary/secondary) so it matches the rest of the app */
        .cv-hero { display:flex; gap:1.2rem; align-items:center; padding:1.6rem;border-radius:12px;margin-bottom:1rem;color:var(--hero-text,#fff);position:relative;overflow:visible; border:1px solid rgba(0,0,0,0.06); box-shadow: 0 8px 24px rgba(17,24,39,0.06); background: linear-gradient(135deg, var(--primary), var(--secondary)); }
        .cv-hero::after { content:""; position:absolute; inset:auto -120px 0 auto; width:420px; height:420px; background: radial-gradient(circle at 30% 30%, rgba(200,230,255,0.08), rgba(240,240,240,0.02)); border-radius:50%; transform:translateY(12%); pointer-events:none; opacity:0.9 }
        .cv-hero .photo { flex: 0 0 140px; width:140px; height:140px; border-radius:999px; overflow:hidden; background:linear-gradient(180deg,#ffffff,#f6fafc); display:flex; align-items:center; justify-content:center; box-shadow: 0 8px 30px rgba(15,23,42,0.08); border:6px solid rgba(237,242,247,0.8); margin-left:12px }
        .cv-hero .photo img { width:100%; height:100%; object-fit:cover }
        .cv-hero .info { flex:1; display:flex; flex-direction:column; gap:0.2rem }
        .cv-hero .info h2, .cv-name { margin:0; font-size:2.2rem; letter-spacing:0.2px; font-weight:800; color: #fff }
        .cv-hero .meta { font-size:1rem; color:rgba(255,255,255,0.95); display:flex; gap:0.8rem; align-items:center; flex-wrap:wrap }
        .cv-hero .badge { display:inline-block; padding:.28rem .65rem; border-radius:999px; font-weight:700; background:#f6f7fb; color:#1b2b34; border:1px solid rgba(17,24,39,0.04); }
        .cv-hero .actions { display:flex; gap:.6rem; align-items:center }
        /* Prefer the application's color system for primary buttons so they remain readable on the hero */
        .btn { display:inline-flex; align-items:center; gap:0.45rem; padding:.6rem .9rem; border-radius:999px; border:1px solid rgba(0,0,0,0.06); cursor:pointer; font-weight:700; text-decoration:none; background:transparent; color:var(--text, #0f1724) }
        .btn-primary { background: var(--primary) !important; color: white !important; border: 1px solid rgba(0,0,0,0.06); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
        .btn-outline { background:transparent; color:var(--primary) !important; border:1px solid rgba(0,0,0,0.06); }

        /* Hero-specific button overrides - ensure CTAs remain visible on the colored hero background */
        .cv-hero .btn-primary { background: #fff !important; color: var(--primary) !important; border-color: rgba(0,0,0,0.06) !important; box-shadow: 0 10px 30px rgba(0,0,0,0.06) !important; }
        .cv-hero .btn-outline { color: #fff !important; border-color: rgba(255,255,255,0.14) !important; background: transparent !important; }
        .btn-download { background:transparent; color:var(--primary) !important; border:1px solid rgba(0,0,0,0.06); }
        .cv-hero .meta .badge { background:transparent; border:1px solid rgba(255,255,255,0.12); padding:0.25rem .6rem; border-radius:6px; font-weight:700; color:rgba(255,255,255,0.95) }
        .cv-side-section.profile-card .card-body { display:flex; flex-direction:column; gap:.6rem }
        .cv-side-section.stats-card .card-body { display:flex; flex-direction:column; gap:.5rem; font-size:0.95rem }

        /* compact readable sections like KickCV */
        .card.form-section { border-radius:12px; overflow:hidden; }
            /* Stats tiles below hero */
            .cv-stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin:1rem 0 1.6rem }
            .cv-stat { background:#fff;border-radius:12px;padding:1.4rem; box-shadow: 0 10px 30px rgba(2,8,23,0.06); text-align:center; border:1px solid rgba(0,0,0,0.04) }
            .cv-stat .icon { font-size:1.8rem; color:#0b6cff; margin-bottom:.4rem }
            .cv-stat .value { font-weight:800; color:#0f6f4b; font-size:1.8rem }
            .cv-stat .label { text-transform:uppercase; font-size:.78rem; color:#6b7785; letter-spacing:.7px; margin-top:.45rem }

            @media (max-width: 1000px) { .cv-stats-grid { grid-template-columns:repeat(2,1fr); } }
            @media (max-width: 520px) { .cv-stats-grid { grid-template-columns:1fr; } }
        .card.form-section .card-header { background:linear-gradient(90deg,rgba(0,0,0,0.02),rgba(0,0,0,0.01)); padding:.8rem 1rem }
        .card.form-section .card-title { margin:0; font-size:1rem }

        @media (max-width: 991px) {
            .cv-hero { flex-direction:column; align-items:flex-start }
            .cv-hero .photo { width:100%; height:220px }
        }

        /* map card tweaks */
        .map-card .card-body { padding: .6rem; }
        .map-card iframe { display:block;border:0;border-radius:8px; }

        /* Achievements and season stat cards on right */
        .achievements-block { margin-bottom:1rem }
        .achievement-card { transition: transform .16s ease, box-shadow .16s ease }
        .achievement-card:hover { transform: translateY(-4px); box-shadow: 0 18px 30px rgba(2,8,23,0.08) }
        .season-stats .cv-stat { display:flex; gap:12px; align-items:center }

        /* skills progress */
        .skills-grid { display:grid; grid-template-columns: repeat(2,1fr); gap:.75rem }
        .skills-grid .skill { display:block }
        .skills-grid .skill .label { font-weight:700; color:#0f2b34; margin-bottom:.35rem }
        .skills-grid .skill .bar { height:10px;background:#eef2f4;border-radius:999px;overflow:hidden }
        .skills-grid .skill .bar .fill { height:100%; background:linear-gradient(90deg,#0f6f4b,#ff7a3a); }

        @media (max-width: 780px) { .skills-grid { grid-template-columns:1fr } }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="content-main">
        <div class="page-header">
            <div class="page-header-content d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">Player Profile</h1>
                </div>
                <!-- Header: removed small photo and name; CV hero below contains primary identity info -->
                <div style="display:flex;align-items:center;gap:8px;">
                    <div class="text-muted small">Profile preview is in the CV header below</div>
                </div>
            </div>
            <div class="page-actions">
                <a href="players.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back to Players</a>
                <a href="edit_player.php?id=<?= $player_id ?>" class="btn-secondary"><i class="fas fa-edit"></i> Edit</a>
            </div>
        </div>

        <div class="container-fluid">
            <!-- CV Hero (large header like a CV) -->
            <div class="cv-hero cv-hero-card">
                <div class="hero-left">
                    <div class="hero-name-wrap">
                        <h1 class="cv-name"><?= ($playerFlagImg ? $playerFlagImg : '') . htmlspecialchars($playerName) ?></h1>
                        <?php if (!empty($player['nickname'])): ?><div class="cv-sub muted">"<?= htmlspecialchars($player['nickname']) ?>"</div><?php endif; ?>
                        <div class="hero-meta muted" style="margin-top:.6rem;"><?= (!empty($player['primary_position']) ? htmlspecialchars($player['primary_position']) : '—') ?> · <?= (!empty($player['age']) ? htmlspecialchars($player['age']) . ' years' : (!empty($player['date_of_birth']) ? htmlspecialchars((new DateTime())->diff(new DateTime($player['date_of_birth']))->y) . ' years' : '—')) ?></div>
                        <div class="hero-meta" style="margin-top:.4rem;color:var(--muted-strong,#8aa0b7)"><?= htmlspecialchars($player['nationality'] ?? '—') ?></div>
                    </div>
                    <div class="hero-actions" style="margin-top:18px;">
                        <?php if (!empty($player['email'])): ?><a class="btn btn-primary" href="mailto:<?= htmlspecialchars($player['email']) ?>"><i class="fas fa-envelope"></i> Contact</a><?php endif; ?>
                        <a class="btn btn-outline" href="player_card_print.php?id=<?= $player_id ?>" target="_blank"><i class="fas fa-print"></i> Print</a>
                        <a class="btn btn-outline" href="#" onclick="window.print();return false;"><i class="fas fa-download"></i> Download</a>
                    </div>
                </div>
                    <div class="photo">
                    <?php if (!empty($player['profile_image'])): ?>
                        <img src="<?= htmlspecialchars($player['profile_image']) ?>" alt="<?= htmlspecialchars($playerName) ?>">
                    <?php else: ?>
                        <div style="width:64px;height:64px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:#e6eef9;color:#2a64b8"><i class="fas fa-user fa-2x"></i></div>
                    <?php endif; ?>
                </div>
                    <div class="info">
                        <div style="display:flex;justify-content:space-between;align-items:start;gap:1rem">
                        <div>
                            <!-- name is shown once in the main hero header (h1). avoid repeating the name here -->
                            <?php if (!empty($player['nickname'])): ?><div class="text-muted">"<?= htmlspecialchars($player['nickname']) ?>"</div><?php endif; ?>
                        </div>
                        <div style="text-align:right">
                            <div class="meta">
                                <?php if (!empty($player['primary_position'])): ?><span class="badge"><?= htmlspecialchars($player['primary_position']) ?></span><?php endif; ?>
                                <?php if (!empty($player['age_group'])): ?><span class="badge"><?= htmlspecialchars($player['age_group']) ?></span><?php endif; ?>
                                <?php if (!empty($player['jersey_number'])): ?><span class="badge">#<?= htmlspecialchars($player['jersey_number']) ?></span><?php endif; ?>
                                <?php // Add a status badge if available
                                if (!empty($player['status'])): ?><span class="badge" style="background:#0b6cff;color:white;"><?= htmlspecialchars(ucfirst($player['status'])) ?></span><?php endif; ?>
                            </div>
                            <div class="meta" style="margin-top:.4rem">
                                <span><?= !empty($allTeams) ? htmlspecialchars(implode(', ', array_column($allTeams, 'name'))) : 'Not assigned' ?></span>
                                <?php if (!empty($player['nationality'])): ?><span><?= htmlspecialchars($player['nationality']) ?></span><?php endif; ?>
                            </div>
                            <div style="margin-top:.6rem;display:flex;gap:.5rem;align-items:center">
                                <?php if (!empty($player['email'])): ?><a href="mailto:<?= htmlspecialchars($player['email']) ?>" class="btn btn-primary" style="padding:.45rem .8rem;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:.45rem"><i class="fas fa-envelope"></i> Contact</a><?php endif; ?>
                                <a href="player_card_print.php?id=<?= $player_id ?>" target="_blank" class="btn btn-outline" style="padding:.35rem .6rem;border-radius:8px;text-decoration:none"><i class="fas fa-print"></i> Print</a>
                                <a href="#" onclick="window.print();return false;" class="btn btn-outline" style="padding:.35rem .6rem;border-radius:8px;text-decoration:none"><i class="fas fa-download"></i> Download</a>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($player['playing_style'])): ?><div style="margin-top:.6rem;color:#0b6cff;font-weight:700"><?= htmlspecialchars(substr($player['playing_style'],0,220)) ?><?= (strlen($player['playing_style'])>220 ? '...' : '') ?></div><?php endif; ?>

                    <?php
                        // small stats row similar to KickCV if any stats columns exist
                        $statKeys = ['career_goals','goals','assists','matches','caps','years_experience'];
                        $presentStats = [];
                        foreach ($statKeys as $k) {
                            if (!empty($player[$k])) $presentStats[$k] = $player[$k];
                        }
                        if (!empty($presentStats)):
                    ?>
                        <div style="margin-top:.8rem;display:flex;gap:.6rem;flex-wrap:wrap;align-items:center">
                            <?php foreach ($presentStats as $k=>$v): ?>
                                <div style="background:linear-gradient(180deg,#fff,#f3f7ff);padding:.45rem .6rem;border-radius:8px;border:1px solid rgba(0,0,0,0.04);font-weight:700;font-size:.9rem;color:#0b6cff;min-width:88px;text-align:center;">
                                    <div style="font-size:1rem"><?= htmlspecialchars($v) ?></div>
                                    <div style="font-size:0.7rem;color:rgba(0,0,0,0.6);text-transform:uppercase;letter-spacing:.6px;">
                                        <?= htmlspecialchars(ucwords(str_replace(['_','years'],' ', $k))) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row player-cv">
                <div class="col-lg-8 main-cv">
                    <div class="card form-section cv-about">
                        <div class="card-header"><h5 class="card-title">About <?= htmlspecialchars($playerName) ?></h5></div>
                        <div class="card-body">
                            <div class="mb-3" style="font-size:1.03rem;color:#374151;line-height:1.6">
                                <?php if (!empty($player['playing_style'])): ?>
                                    <?= nl2br(htmlspecialchars($player['playing_style'])) ?>
                                <?php else: ?>
                                    <em>No biography provided. Add a short summary in the edit form to show here.</em>
                                <?php endif; ?>
                            </div>

                            <div class="details-two-col" style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;align-items:start;margin-top:1rem">
                                <div class="personal-details">
                                    <h6 style="font-weight:700;color:#0b6cff;margin-bottom:.6rem">Personal Details</h6>
                                    <ul style="list-style:none;padding:0;margin:0;display:grid;grid-template-columns:repeat(1,1fr);gap:.6rem;color:#374151">
                                        <li><i class="fas fa-calendar" style="color:#0b66ff;margin-right:.6rem"></i><strong>Age:</strong> <?= (!empty($player['age']) ? htmlspecialchars($player['age']) . ' years' : ( !empty($player['date_of_birth']) ? htmlspecialchars((new DateTime())->diff(new DateTime($player['date_of_birth']))->y) . ' years' : '—')) ?></li>
                                        <li><i class="fas fa-ruler-vertical" style="color:#0b66ff;margin-right:.6rem"></i><strong>Height:</strong> <?= !empty($player['height']) ? htmlspecialchars($player['height']) . ' cm' : '—' ?></li>
                                        <li><i class="fas fa-weight" style="color:#0b66ff;margin-right:.6rem"></i><strong>Weight:</strong> <?= !empty($player['weight']) ? htmlspecialchars($player['weight']) . ' kg' : '—' ?></li>
                                    </ul>
                                </div>

                                <div class="playing-details">
                                    <h6 style="font-weight:700;color:#0f6f4b;margin-bottom:.6rem">Playing Details</h6>
                                    <ul style="list-style:none;padding:0;margin:0;display:grid;grid-template-columns:repeat(1,1fr);gap:.6rem;color:#374151">
                                        <li><i class="fas fa-shoe-prints" style="color:#0f6f4b;margin-right:.6rem"></i><strong>Preferred Foot:</strong> <?= htmlspecialchars($player['preferred_foot'] ?? '—') ?></li>
                                        <li><i class="fas fa-user-friends" style="color:#0f6f4b;margin-right:.6rem"></i><strong>Position:</strong> <?= htmlspecialchars($player['primary_position'] ?? ($player['position'] ?? '—')) ?></li>
                                        <li><i class="fas fa-flag" style="color:#0f6f4b;margin-right:.6rem"></i><strong>Nationality:</strong> <?= htmlspecialchars($player['nationality'] ?? '—') ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card form-section cv-playing-info" style="display:none;">
                        <div class="card-header"><h5 class="card-title">Football information</h5></div>
                        <div class="card-body">
                            <form>
                                <div class="form-grid-3">
                                    <div class="form-group">
                                        <label>Primary Position</label>
                                        <input type="text" class="form-control" disabled value="<?= htmlspecialchars(isset($positionOptions[$player['primary_position'] ?? '']) ? $positionOptions[$player['primary_position']] . ' (' . ($player['primary_position'] ?? '') . ')' : ($player['primary_position'] ?? '')) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Secondary Position</label>
                                        <input type="text" class="form-control" disabled value="<?= htmlspecialchars(isset($positionOptions[$player['secondary_position'] ?? '']) ? $positionOptions[$player['secondary_position']] . ' (' . ($player['secondary_position'] ?? '') . ')' : ($player['secondary_position'] ?? '')) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Third Position</label>
                                        <input type="text" class="form-control" disabled value="<?= htmlspecialchars(isset($positionOptions[$player['third_position'] ?? '']) ? $positionOptions[$player['third_position']] . ' (' . ($player['third_position'] ?? '') . ')' : ($player['third_position'] ?? '')) ?>">
                                    </div>
                                </div>
                                <?php if (in_array('age_group', $schemaColumns)): ?>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Age Group</label>
                                        <select class="form-select" disabled>
                                            <option value="">Select Age Group</option>
                                            <?php foreach ($ageGroups as $ag): ?>
                                                <option <?= (isset($player['age_group']) && $player['age_group'] === $ag) ? 'selected' : '' ?>><?= htmlspecialchars($ag) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <?php endif; ?>

                                    <div class="form-group">
                                        <label>Preferred Foot</label>
                                        <select class="form-select" disabled>
                                            <option value="">Select Foot</option>
                                            <option <?= (isset($player['preferred_foot']) && $player['preferred_foot'] === 'Left') ? 'selected' : '' ?>>Left</option>
                                            <option <?= (isset($player['preferred_foot']) && $player['preferred_foot'] === 'Right') ? 'selected' : '' ?>>Right</option>
                                            <option <?= (isset($player['preferred_foot']) && $player['preferred_foot'] === 'Both') ? 'selected' : '' ?>>Both</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Jersey Number</label>
                                        <input type="number" class="form-control" value="<?= htmlspecialchars($player['jersey_number'] ?? '') ?>" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Teams</label>
                                        <?php if (!empty($allTeams)): ?>
                                            <div class="form-control" style="background:#f8f9fa;padding:0.5rem 0;display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center">
                                                <?php foreach ($allTeams as $team): ?>
                                                    <span class="badge bg-primary"><?= htmlspecialchars($team['name']) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="form-control" style="background:#f8f9fa">Not assigned</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group">
                                        <label>Favorite Player</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($player['favorite_player'] ?? '') ?>" disabled>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card form-section cv-story">
                        <div class="card-header"><h5 class="card-title">Personal Details</h5></div>
                        <div class="card-body">
                            <form>
                                <div class="form-grid">
                                    <div class="form-group"><label>Height (cm)</label><input class="form-control" value="<?= htmlspecialchars($player['height'] ?? '') ?>" disabled></div>
                                    <div class="form-group"><label>Weight (kg)</label><input class="form-control" value="<?= htmlspecialchars($player['weight'] ?? '') ?>" disabled></div>
                                    <div class="form-group"><label>Nickname</label><input class="form-control" value="<?= htmlspecialchars($player['nickname'] ?? '') ?>" disabled></div>
                                    <div class="form-group"><label>ID / Passport</label><input class="form-control" value="<?= htmlspecialchars($player['identity_number'] ?? '') ?>" disabled></div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Career History -->
                    <div class="card form-section">
                        <div class="card-header"><h5 class="card-title">Career History</h5></div>
                        <div class="card-body">
                            <?php
                                // If there's a dedicated career history structure in JSON use it; otherwise use team join table rows
                                $timeline = [];
                                if (!empty($player['career_history_json'])) {
                                    $tmp = json_decode($player['career_history_json'], true);
                                    if (is_array($tmp)) $timeline = $tmp;
                                }
                                if (empty($timeline) && !empty($allTeams)) {
                                    // Convert teams to simple timeline items
                                    foreach ($allTeams as $t) {
                                        $timeline[] = ['title' => $t['name'], 'period' => '', 'desc' => 'Played for ' . $t['name']];
                                    }
                                }
                            ?>
                            <?php if (!empty($timeline)): ?>
                                <div class="timeline" style="display:flex;flex-direction:column;gap:1rem">
                                    <?php foreach ($timeline as $item): ?>
                                        <div style="display:flex;gap:12px;align-items:center">
                                            <div style="width:46px;height:46px;border-radius:50%;background:#f4f6f8;display:flex;align-items:center;justify-content:center;border:1px solid rgba(0,0,0,0.02)"><i class="fas fa-futbol" style="color:#0b66ff"></i></div>
                                            <div style="flex:1">
                                                <div style="font-weight:800;color:#0f1724"><?= htmlspecialchars($item['title'] ?? 'Team') ?></div>
                                                <div class="small muted"><?= htmlspecialchars($item['period'] ?? '') ?></div>
                                                <?php if (!empty($item['desc'])): ?><div style="margin-top:.4rem;color:#374151;"><?= nl2br(htmlspecialchars($item['desc'])) ?></div><?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="small muted">No career history recorded. Add entries to the player's Career History in edit mode.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Skills & Attributes -->
                    <div class="card form-section">
                        <div class="card-header"><h5 class="card-title">Skills & Attributes</h5></div>
                        <div class="card-body">
                            <?php
                                $skills = [];
                                if (!empty($player['skills_json'])) {
                                    $tmp = json_decode($player['skills_json'], true);
                                    if (is_array($tmp)) $skills = $tmp;
                                }
                                // fallback to parsing personal_talents as soft tags (not scored)
                                if (empty($skills) && !empty($player['personal_talents'])) {
                                    $tags = array_filter(array_map('trim', explode(',', $player['personal_talents'])));
                                    foreach ($tags as $t) $skills[$t] = 65; // arbitrary default
                                }
                            ?>
                            <?php if (!empty($skills)): ?>
                                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:.8rem">
                                    <?php foreach ($skills as $k=>$v): $pct = intval($v); ?>
                                    <div>
                                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem"><div style="font-weight:700;color:#0f2b34"><?= htmlspecialchars($k) ?></div><div style="font-weight:800;color:#0f6f4b"><?= htmlspecialchars($pct) ?>%</div></div>
                                        <div style="height:10px;background:#eef2f4;border-radius:999px;overflow:hidden"><div style="width:<?= $pct ?>%;height:100%;background:linear-gradient(90deg,#0f6f4b,#ff7a3a);"></div></div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="small muted">No skill scores available.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card form-section">
                        <div class="card-header"><h5 class="card-title">Player story</h5></div>
                        <div class="card-body">
                            <?php if (in_array('playing_style', $schemaColumns) && !empty($player['playing_style'])): ?>
                                <div class="mb-3"><strong>Playing Style</strong><div><?= nl2br(htmlspecialchars($player['playing_style'])) ?></div></div>
                            <?php endif; ?>
                            <?php if (in_array('why_started_playing', $schemaColumns) && !empty($player['why_started_playing'])): ?>
                                <div class="mb-3"><strong>Why started playing</strong><div><?= nl2br(htmlspecialchars($player['why_started_playing'])) ?></div></div>
                            <?php endif; ?>
                            <?php if (in_array('why_like_football', $schemaColumns) && !empty($player['why_like_football'])): ?>
                                <div class="mb-3"><strong>Why they like football</strong><div><?= nl2br(htmlspecialchars($player['why_like_football'])) ?></div></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card form-section cv-media">
                        <div class="card-header"><h5 class="card-title">Social Media</h5></div>
                        <div class="card-body">
                            <div class="form-grid">
                                <?php if (in_array('facebook_url', $schemaColumns)): ?><div class="form-group"><label>Facebook URL</label><input class="form-control" value="<?= htmlspecialchars($player['facebook_url'] ?? '') ?>" disabled></div><?php endif; ?>
                                <?php if (in_array('instagram_url', $schemaColumns)): ?><div class="form-group"><label>Instagram URL</label><input class="form-control" value="<?= htmlspecialchars($player['instagram_url'] ?? '') ?>" disabled></div><?php endif; ?>
                                <?php if (in_array('twitter_url', $schemaColumns)): ?><div class="form-group"><label>Twitter URL</label><input class="form-control" value="<?= htmlspecialchars($player['twitter_url'] ?? '') ?>" disabled></div><?php endif; ?>
                                <?php if (in_array('tiktok_url', $schemaColumns)): ?><div class="form-group"><label>TikTok URL</label><input class="form-control" value="<?= htmlspecialchars($player['tiktok_url'] ?? '') ?>" disabled></div><?php endif; ?>
                                <?php if (in_array('youtube_url', $schemaColumns)): ?><div class="form-group"><label>YouTube URL</label><input class="form-control" value="<?= htmlspecialchars($player['youtube_url'] ?? '') ?>" disabled></div><?php endif; ?>
                                <?php if (in_array('linkedin_url', $schemaColumns)): ?><div class="form-group"><label>LinkedIn URL</label><input class="form-control" value="<?= htmlspecialchars($player['linkedin_url'] ?? '') ?>" disabled></div><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card form-section">
                        <div class="card-header"><h5 class="card-title">Media</h5></div>
                        <div class="card-body">
                            <?php if (!empty($player['profile_image'])): ?><div class="mb-3"><strong>Profile Image</strong><div><img src="<?= htmlspecialchars($player['profile_image']) ?>" style="max-width:220px"></div></div><?php endif; ?>
                            <?php if (!empty($player['introduction_video_url'])): ?><div class="mb-3"><strong>Introduction Video</strong><div><?= htmlspecialchars(basename($player['introduction_video_url'])) ?></div></div><?php endif; ?>
                        </div>
                    </div>

                </div>

                </div>
                <div class="col-lg-4 side-cv">
                <!-- right column (narrower) - profile header shows image/name -->
                    <?php if (in_array('why_started_playing', $schemaColumns) || in_array('personal_talents', $schemaColumns) || in_array('off_field_interests', $schemaColumns)): ?>
                    <div class="card shadow-sm mb-4 cv-side-section profile-card">
                        <div class="card-header"><h6 class="card-title mb-0">Background & Interests</h6></div>
                        <div class="card-body">
                            <?php if (in_array('why_started_playing', $schemaColumns) && !empty($player['why_started_playing'])): ?><div class="mb-2"><strong>Why started</strong><div><?= nl2br(htmlspecialchars($player['why_started_playing'])) ?></div></div><?php endif; ?>
                            <?php if (in_array('personal_talents', $schemaColumns) && !empty($player['personal_talents'])): ?><div class="mb-2"><strong>Talents</strong><div><?= nl2br(htmlspecialchars($player['personal_talents'])) ?></div></div><?php endif; ?>
                            <?php if (in_array('off_field_interests', $schemaColumns) && !empty($player['off_field_interests'])): ?><div class="mb-2"><strong>Interests</strong><div><?= nl2br(htmlspecialchars($player['off_field_interests'])) ?></div></div><?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (in_array('height', $schemaColumns) || in_array('weight', $schemaColumns) || in_array('preferred_foot', $schemaColumns) || in_array('medical_conditions', $schemaColumns) || in_array('emergency_contact', $schemaColumns)): ?>
                    <div class="card shadow-sm mb-4 cv-side-section stats-card">
                        <div class="card-header"><h6 class="card-title mb-0">Enhanced Details</h6></div>
                        <div class="card-body">
                            <?php if (in_array('height', $schemaColumns) && !empty($player['height'])): ?><div class="mb-2"><strong>Height</strong><div><?= htmlspecialchars($player['height']) ?> cm</div></div><?php endif; ?>
                            <?php if (in_array('weight', $schemaColumns) && !empty($player['weight'])): ?><div class="mb-2"><strong>Weight</strong><div><?= htmlspecialchars($player['weight']) ?> kg</div></div><?php endif; ?>
                            <?php if (in_array('preferred_foot', $schemaColumns) && !empty($player['preferred_foot'])): ?><div class="mb-2"><strong>Preferred Foot</strong><div><?= htmlspecialchars($player['preferred_foot']) ?></div></div><?php endif; ?>
                            <?php if (in_array('medical_conditions', $schemaColumns) && !empty($player['medical_conditions'])): ?><div class="mb-2"><strong>Medical</strong><div><?= nl2br(htmlspecialchars($player['medical_conditions'])) ?></div></div><?php endif; ?>
                            <?php if (in_array('emergency_contact', $schemaColumns) && !empty($player['emergency_contact'])): ?><div class="mb-2"><strong>Emergency Contact</strong><div><?= nl2br(htmlspecialchars($player['emergency_contact'])) ?></div></div><?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                        <!-- Achievements stack (right column visual cards) -->
                        <?php
                            // Try to load achievements as JSON or comma-separated
                            $achievements = [];
                            if (!empty($player['achievements_json'])) {
                                $tmp = json_decode($player['achievements_json'], true);
                                if (is_array($tmp)) $achievements = $tmp;
                            } elseif (!empty($player['achievements'])) {
                                $achievements = array_filter(array_map('trim', explode('|', $player['achievements'])));
                            }
                        ?>
                        <?php if (!empty($achievements)): ?>
                            <div class="achievements-block" style="display:flex;flex-direction:column;gap:.8rem;margin-bottom:1rem">
                                <?php foreach ($achievements as $a): ?>
                                    <div class="achievement-card" style="display:flex;align-items:center;gap:.9rem;background:#fff;border-radius:12px;padding:.8rem;border:1px solid rgba(0,0,0,0.04);box-shadow:0 10px 30px rgba(2,8,23,0.04);">
                                        <div style="width:56px;height:56px;border-radius:10px;background:linear-gradient(180deg,#f0fbf6,#e9f7f2);display:flex;align-items:center;justify-content:center;color:#0f6f4b;font-weight:800;font-size:20px;">🏆</div>
                                        <div style="flex:1">
                                            <div style="font-weight:800;color:#0f2b34;"><?= htmlspecialchars($a['title'] ?? (is_string($a) ? $a : 'Achievement')) ?></div>
                                            <?php if (!empty($a['sub'])): ?><div class="small muted"><?= htmlspecialchars($a['sub']) ?></div><?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Season statistics tiles (compact stacked cards) -->
                        <div class="season-stats" style="display:grid;grid-template-columns:1fr;gap:.8rem;margin-bottom:1rem">
                            <?php if (!empty($player['season_goals']) || !empty($player['season_assists'])): ?>
                                <div class="cv-stat" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;">
                                    <div style="flex:1;text-align:center;border-radius:10px;background:#fff;padding:14px;border:1px solid rgba(0,0,0,0.03);box-shadow:0 10px 30px rgba(2,8,23,0.03)">
                                        <div class="value" style="font-size:26px;color:#0f6f4b;font-weight:800"><?= htmlspecialchars($player['season_goals'] ?? $player['goals'] ?? '0') ?></div>
                                        <div class="label small muted">GOALS</div>
                                    </div>
                                    <div style="width:12px"></div>
                                    <div style="flex:1;text-align:center;border-radius:10px;background:#fff;padding:14px;border:1px solid rgba(0,0,0,0.03);box-shadow:0 10px 30px rgba(2,8,23,0.03)">
                                        <div class="value" style="font-size:26px;color:#0f6f4b;font-weight:800"><?= htmlspecialchars($player['season_assists'] ?? $player['assists'] ?? '0') ?></div>
                                        <div class="label small muted">ASSISTS</div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($player['season_matches']) || !empty($player['pass_accuracy']) || !isset($player['season_matches'])): ?>
                                <div class="cv-stat" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;">
                                    <div style="flex:1;text-align:center;border-radius:10px;background:#fff;padding:14px;border:1px solid rgba(0,0,0,0.03);box-shadow:0 10px 30px rgba(2,8,23,0.03)">
                                        <div class="value" style="font-size:26px;color:#0f6f4b;font-weight:800"><?= htmlspecialchars($player['season_matches'] ?? $player['matches'] ?? '0') ?></div>
                                        <div class="label small muted">MATCHES</div>
                                    </div>
                                    <div style="width:12px"></div>
                                    <div style="flex:1;text-align:center;border-radius:10px;background:#fff;padding:14px;border:1px solid rgba(0,0,0,0.03);box-shadow:0 10px 30px rgba(2,8,23,0.03)">
                                        <div class="value" style="font-size:26px;color:#0f6f4b;font-weight:800"><?= htmlspecialchars(($player['pass_accuracy'] ?? '') !== '' ? htmlspecialchars($player['pass_accuracy']) . '%' : ($player['pass_accuracy'] ?? '—')) ?></div>
                                        <div class="label small muted">PASS ACC.</div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($player['goals_per_90']) || !empty($player['shots_on_target'])): ?>
                                <div class="cv-stat" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;">
                                    <div style="flex:1;text-align:center;border-radius:10px;background:#fff;padding:14px;border:1px solid rgba(0,0,0,0.03);box-shadow:0 10px 30px rgba(2,8,23,0.03)">
                                        <div class="value" style="font-size:26px;color:#0f6f4b;font-weight:800"><?= htmlspecialchars($player['goals_per_90'] ?? '0') ?></div>
                                        <div class="label small muted">GOALS/90MIN</div>
                                    </div>
                                    <div style="width:12px"></div>
                                    <div style="flex:1;text-align:center;border-radius:10px;background:#fff;padding:14px;border:1px solid rgba(0,0,0,0.03);box-shadow:0 10px 30px rgba(2,8,23,0.03)">
                                        <div class="value" style="font-size:26px;color:#0f6f4b;font-weight:800"><?= htmlspecialchars($player['shots_on_target'] ?? '0') ?></div>
                                        <div class="label small muted">SHOTS ON TARGET</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php
                            // Try to show a map. Prefer city/home_town/location columns, fallback to nationality
                            $mapQuery = '';
                            if (!empty($player['location'])) $mapQuery = $player['location'];
                            if (!$mapQuery && !empty($player['city'])) $mapQuery = $player['city'];
                            if (!$mapQuery && !empty($player['home_town'])) $mapQuery = $player['home_town'];
                            if (!$mapQuery && !empty($player['nationality'])) $mapQuery = $player['nationality'];

                            if (!empty($mapQuery)): 
                                $mapSrc = 'https://www.google.com/maps?q=' . rawurlencode($mapQuery) . '&output=embed';
                        ?>
                        <div class="card shadow-sm mb-4 cv-side-section map-card">
                            <div class="card-header"><h6 class="card-title mb-0">Location map</h6></div>
                            <div class="card-body">
                                <div style="width:100%;height:220px;border-radius:8px;overflow:hidden;border:1px solid rgba(0,0,0,0.06)">
                                    <iframe src="<?= htmlspecialchars($mapSrc) ?>" width="100%" height="220" style="border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                                </div>
                                <?php if (trim((string)$mapQuery) !== trim((string)$playerName)): ?>
                                    <div class="small text-muted mt-2"><?= htmlspecialchars($mapQuery) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                </div>
            </div>

            <?php include 'includes/layout_end.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
