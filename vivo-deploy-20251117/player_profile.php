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

try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
    $stmt = $db->prepare("SELECT p.*, t.name as team_name FROM players p LEFT JOIN teams t ON p.team_id = t.id WHERE p.id = ?");
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
    
    // Fetch all teams from team_ids
    $allTeams = [];
    if (!empty($player['team_ids'])) {
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
    // Fallback to primary team if no team_ids
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
$positions = [ 'Goalkeeper','Defender','Midfielder','Forward','Left Wing','Right Wing','Centre-Back','Left-Back','Right-Back','Striker' ];
$ageGroups = [ 'U6','U7','U8','U9','U10','U11','U12','U13','U14','U15','U16','U17','U18','U19','U20','U21','Senior' ];

// fragment modal
if ($fragment) {
    require_once 'includes/css_helper.php';
    vivo_include_head_css($db);
    ?>
    <div class="player-profile-modal p-3">
        <div class="text-center mb-3">
            <?php if (!empty($player['profile_image'])): ?>
                <img src="<?= htmlspecialchars($player['profile_image']) ?>" alt="<?= htmlspecialchars($playerName) ?>" class="player-photo-large mb-2" style="max-width:120px;border-radius:6px">
            <?php else: ?>
                <div class="player-placeholder-large mb-2"><i class="fas fa-user fa-3x"></i></div>
            <?php endif; ?>
            <h4 class="mb-0"><?= htmlspecialchars($playerName) ?></h4>
            <?php if (!empty($player['nickname'])): ?><div class="text-muted">"<?= htmlspecialchars($player['nickname']) ?>"</div><?php endif; ?>
        </div>
        <div class="player-meta small">
            <div><strong>Teams:</strong> <?= !empty($allTeams) ? htmlspecialchars(implode(', ', array_column($allTeams, 'name'))) : 'Not assigned' ?></div>
            <div><strong>Number:</strong> <?= htmlspecialchars($player['jersey_number'] ?? '—') ?></div>
            <div><strong>Primary:</strong> <?= htmlspecialchars($player['primary_position'] ?? '—') ?></div>
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
        .form-grid .form-group {
            margin-bottom: 0.45rem; /* reduced row padding */
        }
        /* Slightly reduce card body padding for denser layout */
        .card.form-section .card-body { padding: 0.9rem; }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
        }
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
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <?php if (!empty($player['profile_image'])): ?>
                            <img src="<?= htmlspecialchars($player['profile_image']) ?>" alt="<?= htmlspecialchars($playerName) ?>" style="width:72px;height:72px;object-fit:cover;border-radius:6px">
                        <?php else: ?>
                            <div style="width:72px;height:72px;display:flex;align-items:center;justify-content:center;border-radius:6px;background:#f2f2f2"><i class="fas fa-user fa-2x"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold"><?= htmlspecialchars($playerName) ?></div>
                        <?php if (!empty($player['nickname'])): ?><div class="text-muted">"<?= htmlspecialchars($player['nickname']) ?>"</div><?php endif; ?>
                        <div class="small"><strong>Teams:</strong> <?= !empty($allTeams) ? htmlspecialchars(implode(', ', array_column($allTeams, 'name'))) : 'Not assigned' ?> &nbsp; <strong>#</strong><?= htmlspecialchars($player['jersey_number'] ?? '—') ?></div>
                    </div>
                </div>
            </div>
            <div class="page-actions">
                <a href="players.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back to Players</a>
                <a href="edit_player.php?id=<?= $player_id ?>" class="btn-secondary"><i class="fas fa-edit"></i> Edit</a>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card form-section">
                        <div class="card-header"><h5 class="card-title">Basic Information</h5></div>
                        <div class="card-body">
                            <form>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Nickname</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($player['nickname'] ?? '') ?>" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Date of Birth</label>
                                        <input type="date" class="form-control" value="<?= htmlspecialchars($player['date_of_birth'] ?? '') ?>" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Age</label>
                                        <input type="number" class="form-control" value="<?= htmlspecialchars($player['age'] ?? '') ?>" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Nationality</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($player['nationality'] ?? '') ?>" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Contact Number</label>
                                        <input type="tel" class="form-control" value="<?= htmlspecialchars($player['contact_number'] ?? '') ?>" disabled>
                                    </div>
                                    <?php if (in_array('identity_number', $schemaColumns)): ?>
                                    <div class="form-group">
                                        <label>ID / Passport Number</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($player['identity_number'] ?? '') ?>" disabled>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card form-section">
                        <div class="card-header"><h5 class="card-title">Football Information</h5></div>
                        <div class="card-body">
                            <form>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Primary Position</label>
                                        <select class="form-select" disabled>
                                            <option value="">Select Position</option>
                                            <?php foreach ($positions as $pos): ?>
                                            <option <?= (isset($player['primary_position']) && $player['primary_position'] == $pos) ? 'selected' : '' ?>><?= htmlspecialchars($pos) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php if (in_array('age_group', $schemaColumns)): ?>
                                    <div class="form-group">
                                        <label>Age Group</label>
                                        <select class="form-select" disabled>
                                            <option value="">Select Age Group</option>
                                            <?php foreach ($ageGroups as $ag): ?>
                                                <option <?= (isset($player['age_group']) && $player['age_group'] === $ag) ? 'selected' : '' ?>><?= htmlspecialchars($ag) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php endif; ?>
                                    <div class="form-group">
                                        <label>Secondary Position</label>
                                        <select class="form-select" disabled>
                                            <option value="">Select Position</option>
                                            <?php foreach ($positions as $pos): ?>
                                            <option <?= (isset($player['secondary_position']) && $player['secondary_position'] == $pos) ? 'selected' : '' ?>><?= htmlspecialchars($pos) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

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

                    <div class="card form-section">
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

                    <div class="card form-section">
                        <div class="card-header"><h5 class="card-title">Player Story</h5></div>
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

                    <div class="card form-section">
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

                <!-- right column profile card removed to avoid duplication; header shows image/name -->
                    <?php if (in_array('why_started_playing', $schemaColumns) || in_array('personal_talents', $schemaColumns) || in_array('off_field_interests', $schemaColumns)): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header"><h6 class="card-title mb-0">Background & Interests</h6></div>
                        <div class="card-body">
                            <?php if (in_array('why_started_playing', $schemaColumns) && !empty($player['why_started_playing'])): ?><div class="mb-2"><strong>Why started</strong><div><?= nl2br(htmlspecialchars($player['why_started_playing'])) ?></div></div><?php endif; ?>
                            <?php if (in_array('personal_talents', $schemaColumns) && !empty($player['personal_talents'])): ?><div class="mb-2"><strong>Talents</strong><div><?= nl2br(htmlspecialchars($player['personal_talents'])) ?></div></div><?php endif; ?>
                            <?php if (in_array('off_field_interests', $schemaColumns) && !empty($player['off_field_interests'])): ?><div class="mb-2"><strong>Interests</strong><div><?= nl2br(htmlspecialchars($player['off_field_interests'])) ?></div></div><?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (in_array('height', $schemaColumns) || in_array('weight', $schemaColumns) || in_array('preferred_foot', $schemaColumns) || in_array('medical_conditions', $schemaColumns) || in_array('emergency_contact', $schemaColumns)): ?>
                    <div class="card shadow-sm mb-4">
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

                </div>
            </div>

            <?php include 'includes/layout_end.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
