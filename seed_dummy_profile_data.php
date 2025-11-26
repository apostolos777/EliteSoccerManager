<?php
/**
 * seed_dummy_profile_data.php
 *
 * Populate (or backfill) dummy extended profile data for ALL players.
 * Safe: Only updates fields that are NULL, empty string, or zero (for numeric ratings) unless force=1.
 *
 * Usage:
 *  - Browser: /vivoapp/seed_dummy_profile_data.php (dry run by default)
 *  - Add ?apply=1 to actually write changes
 *  - Add &force=1 to overwrite even non-empty values
 *  - Add &player_id=123 to limit to a single player (optional)
 *
 * Works with SQLite (PRAGMA) or MySQL (INFORMATION_SCHEMA fallback not required if PDO->getAttribute shows driver).
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Bootstrap database (try common config files if function missing)
if (!function_exists('get_db_connection')) {
    foreach (['wp-config.php','config.php','config_production.php','database.php','database_factory.php'] as $cfg) {
        $path = __DIR__ . '/' . $cfg;
        if (is_file($path)) { require_once $path; }
        if (function_exists('get_db_connection') || class_exists('DatabaseFactory')) break;
    }
}
if (!function_exists('get_db_connection') && class_exists('DatabaseFactory')) {
    try { $GLOBALS['__seed_db'] = DatabaseFactory::getConnection(); function get_db_connection(){ return $GLOBALS['__seed_db']; } } catch(Throwable $e) {}
}
if (!function_exists('get_db_connection')) { die('Cannot proceed: get_db_connection() not available.'); }

try { $db = get_db_connection(); } catch (Throwable $e) { die('DB connect failed: '. htmlspecialchars($e->getMessage())); }

$apply   = isset($_GET['apply']);
$force   = isset($_GET['force']);
$limitId = isset($_GET['player_id']) ? (int)$_GET['player_id'] : null;

$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

// Gather existing columns in players table
$playerCols = [];
if ($driver === 'sqlite') {
    $cols = $db->query("PRAGMA table_info(players)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) { $playerCols[$c['name']] = true; }
} else { // assume MySQL
    $cols = $db->query("SHOW COLUMNS FROM players")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) { $playerCols[$c['Field']] = true; }
}

// Target numeric rating/score fields
$numericFields = [
    // Physical
    'stamina_rating','agility_rating','flexibility_rating','strength_rating',
    // Mentality
    'goal_setting_score','resilience_score','teamwork_score','focus_score','leadership_score','professional_thinking_score',
    // Technical
    'dribbling_rating','passing_rating','shooting_rating','crossing_rating','heading_rating','tackling_rating','ball_control_rating',
    // Tactical
    'positioning_rating','game_reading_rating','decision_making_rating','formation_understanding_rating','tactical_flexibility_rating'
];

// Time metrics
$timeMetrics = ['sprint_20m','sprint_40m'];

// Simple textual fields
$textFields = [
    'nickname','favorite_player','playing_style','why_started_playing','why_love_football','off_field_interests','personal_talents','nationality','preferred_foot','secondary_position'
];

// Height/weight etc
$physicalSingles = ['height','weight'];

$exists = function($col) use ($playerCols){ return isset($playerCols[$col]); };

// Precompute field sets actually present
$presentNumeric = array_values(array_filter($numericFields,$exists));
$presentText    = array_values(array_filter($textFields,$exists));
$presentTime    = array_values(array_filter($timeMetrics,$exists));
$presentPhysicalSingles = array_values(array_filter($physicalSingles,$exists));
$hasFoot = $exists('preferred_foot');

// Value pools
$nicknames = ['Flash','Rocky','Wizard','Sniper','Ironfoot','Ace','Shadow','Blaze','Titan','Maverick'];
$favPlayers = ['Messi','Ronaldo','Mbappé','Haaland','Modrić','De Bruyne','Salah','Kane','Vinicius Jr','Bellingham'];
$playingStyles = ['Aggressive winger','Playmaking midfielder','Ball-winning defender','Box-to-box engine','Creative forward','Sweeper keeper'];
$whyStarted = ['Friends played','Saw World Cup','Family influence','School program','Loved the game on TV'];
$whyLove = ['Team spirit','Competition','Self improvement','Community','Fitness','Strategy'];
$interests = ['Reading','Gaming','Swimming','Cycling','Music','Art','Coding','Cooking'];
$talents = ['Speed','Vision','Leadership','Composure','Dribbling','Finishing','Interceptions'];
$feet = ['Left','Right','Both'];
$secondaryPositions = ['Left Wing','Right Wing','Center Mid','Attacking Mid','Defensive Mid','Full Back','Center Back'];
$nationalities = ['South Africa','Nigeria','Ghana','Kenya','Brazil','Spain','England'];

// Build players list
$sql = 'SELECT * FROM players';
$params = [];
if ($limitId) { $sql .= ' WHERE id = ?'; $params[] = $limitId; }
$players = $db->prepare($sql); $players->execute($params); $players = $players->fetchAll(PDO::FETCH_ASSOC);

$updatesPlanned = [];

foreach ($players as $p) {
    $set = [];
    // Numeric ratings 55-95
    foreach ($presentNumeric as $nf) {
        $current = $p[$nf] ?? null;
        if ($force || $current === null || $current === '' || (is_numeric($current) && (int)$current === 0)) {
            $set[$nf] = rand(55,95);
        }
    }
    // Time metrics if exist (random plausible seconds)
    foreach ($presentTime as $tf) {
        $current = $p[$tf] ?? null;
        if ($force || $current === null || $current === '' || (float)$current == 0.0) {
            $set[$tf] = number_format(mt_rand(320, 520)/100,2); // 3.20 - 5.20
        }
    }
    // Height (cm) 120-190, weight (kg) 30-90
    foreach ($presentPhysicalSingles as $ps) {
        $current = $p[$ps] ?? null;
        if ($force || $current === null || $current === '' || (int)$current === 0) {
            $set[$ps] = $ps==='height'? rand(140,185): rand(40,85);
        }
    }
    // Textual
    foreach ($presentText as $tf) {
        $current = trim((string)($p[$tf] ?? ''));
        if (!$force && $current !== '') continue;
        switch ($tf) {
            case 'nickname': $set[$tf] = $nicknames[array_rand($nicknames)]; break;
            case 'favorite_player': $set[$tf] = $favPlayers[array_rand($favPlayers)]; break;
            case 'playing_style': $set[$tf] = $playingStyles[array_rand($playingStyles)]; break;
            case 'why_started_playing': $set[$tf] = $whyStarted[array_rand($whyStarted)]; break;
            case 'why_love_football': $set[$tf] = $whyLove[array_rand($whyLove)]; break;
            case 'off_field_interests': $set[$tf] = $interests[array_rand($interests)]; break;
            case 'personal_talents': $set[$tf] = $talents[array_rand($talents)]; break;
            case 'preferred_foot': $set[$tf] = $feet[array_rand($feet)]; break;
            case 'secondary_position': $set[$tf] = $secondaryPositions[array_rand($secondaryPositions)]; break;
            case 'nationality': $set[$tf] = $nationalities[array_rand($nationalities)]; break;
        }
    }
    // unique_id fallback if column exists & empty
    if ($exists('unique_id')) {
        $uid = $p['unique_id'] ?? '';
        if ($force || $uid === '' || $uid === null) {
            $set['unique_id'] = 'VIVO-' . str_pad($p['id'],6,'0',STR_PAD_LEFT);
        }
    }
    if ($set) {
        $updatesPlanned[] = ['id'=>$p['id'],'set'=>$set];
    }
}

$affected = count($updatesPlanned);
$fieldsTouched = [];
foreach ($updatesPlanned as $u) { foreach(array_keys($u['set']) as $k) { $fieldsTouched[$k]=true; } }

if ($apply && $updatesPlanned) {
    $db->beginTransaction();
    foreach ($updatesPlanned as $u) {
        $parts=[]; $vals=[];
        foreach ($u['set'] as $col=>$val) { $parts[] = "$col = :$col"; $vals[":$col"] = $val; }
        $vals[':id']=$u['id'];
        $sql = 'UPDATE players SET ' . implode(', ',$parts) . ", updated_at = datetime('now') WHERE id = :id";
        $stmt=$db->prepare($sql); $stmt->execute($vals);
    }
    $db->commit();
}

?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Seed Dummy Player Profile Data</title>
<style>body{font-family:system-ui,Arial,sans-serif;margin:32px;max-width:1000px}h1{margin-top:0}code{background:#f1f5f9;padding:2px 5px;border-radius:4px}table{border-collapse:collapse;width:100%;font-size:12px;margin-top:16px}th,td{border:1px solid #e2e8f0;padding:4px 6px;text-align:left}th{background:#f8fafc}tr:nth-child(even){background:#f9fafb}.bar{display:inline-block;height:6px;background:#3b82f6;border-radius:3px}</style>
</head><body>
<h1>Dummy Player Profile Seeder</h1>
<p>This tool <?= $apply?'<strong>applied</strong>':'will apply (dry run)' ?> random but plausible extended profile values to all players<?= $limitId? ' (filtered to player ID '.(int)$limitId.')':''; ?>.</p>
<ul>
 <li>Total players scanned: <?= count($players) ?></li>
 <li>Players with pending/updated changes: <?= $affected ?></li>
 <li>Fields considered: <?= count($fieldsTouched) ? implode(', ', array_keys($fieldsTouched)) : 'None (no eligible columns present)'; ?></li>
 <li>Mode: <?= $apply? 'WRITE (changes saved)':'DRY RUN (no database writes)' ?></li>
 <li>Force overwrite: <?= $force? 'Yes':'No (only empty / zero values touched)' ?></li>
</ul>
<div style="margin:12px 0;">
    <a href="?">Dry Run</a> |
    <a href="?apply=1" onclick="return confirm('Apply dummy data to all players?');">Apply Now</a> |
    <a href="?apply=1&force=1" onclick="return confirm('Force overwrite existing values for all players?');">Force Apply</a>
    <?php if(!$apply): ?>| <a href="?player_id=1">Dry run single player (ID 1)</a><?php endif; ?>
</div>
<?php if($updatesPlanned): ?>
<table>
 <thead><tr><th>Player ID</th><th>Fields (new values)</th></tr></thead>
 <tbody>
 <?php foreach ($updatesPlanned as $u): ?>
  <tr><td><?= (int)$u['id'] ?></td><td><?php foreach($u['set'] as $k=>$v){ echo '<strong>'.htmlspecialchars($k).'</strong>: '.htmlspecialchars($v).' | '; } ?></td></tr>
 <?php endforeach; ?>
 </tbody>
</table>
<?php else: ?>
<p>No changes queued. (Either all data already populated or no extended columns exist.)</p>
<?php endif; ?>
</body></html>
