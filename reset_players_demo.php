<?php
/**
 * reset_players_demo.php
 *
 * DANGER: Deletes ALL player-related data and seeds demo teams & players.
 * Default mode = safety page. Must pass ?action=run&confirm=1 to execute.
 * Optional dry run: ?action=run&dry=1 (shows planned operations, no DB writes)
 * Optional CSV export of current players before deletion: ?export=1
 *
 * Workflow:
 * 1. (Optional) Export existing players as CSV (retain for backup) via export link.
 * 2. Run dry run to view what will happen.
 * 3. Execute destructive reset.
 * 4. Redirects to players.php with flash message upon success.
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/functions.php';
// Ensure database helper loaded (get_db_connection lives in wp-config.php)
if (!function_exists('get_db_connection')) {
    $cfgTried = false;
    $paths = [__DIR__ . '/wp-config.php', __DIR__ . '/config.php', __DIR__ . '/config_production.php'];
    foreach ($paths as $p) {
        if (is_file($p)) { require_once $p; $cfgTried = true; if (function_exists('get_db_connection')) break; }
    }
    if (!function_exists('get_db_connection')) {
        die('DB bootstrap failed: get_db_connection() not found after attempting wp-config/config includes.');
    }
}

try { $db = get_db_connection(); } catch (Throwable $e) { die('DB connect failed: ' . htmlspecialchars($e->getMessage())); }

function table_exists($db, $name) {
    $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = ? LIMIT 1");
    $stmt->execute([$name]);
    return (bool)$stmt->fetchColumn();
}

// CSV Export of current players (non-destructive)
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="players_export_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    $rows = $db->query("SELECT * FROM players ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $r) { fputcsv($out, $r); }
    }
    fclose($out);
    exit;
}

$action = $_GET['action'] ?? null;
$dry = isset($_GET['dry']);
$confirm = isset($_GET['confirm']);

$relatedTables = [
    'attendance',
    'player_skill_progress',
    'player_scenario_history',
    'player_assessments',
    'player_mentality_metrics',
    'player_badges'
];

// Collect counts
$counts = [];
$playerCount = (int)$db->query("SELECT COUNT(*) FROM players")->fetchColumn();
$counts['players'] = $playerCount;
foreach ($relatedTables as $t) {
    if (table_exists($db, $t)) {
        $counts[$t] = (int)$db->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    }
}
$teamCount = table_exists($db, 'teams') ? (int)$db->query("SELECT COUNT(*) FROM teams")->fetchColumn() : 0;

$log = [];
$executed = false;

function add_log(&$log, $msg) { $log[] = $msg; }

$demoTeams = [
    ['name' => 'Lions'],
    ['name' => 'Tigers'],
    ['name' => 'Bears']
];

$demoPlayers = [
    ['first_name'=>'Alice','last_name'=>'Swift','position'=>'Forward','jersey_number'=>9,'team'=>'Lions'],
    ['first_name'=>'Ben','last_name'=>'Carter','position'=>'Midfielder','jersey_number'=>8,'team'=>'Lions'],
    ['first_name'=>'Cara','last_name'=>'Lopez','position'=>'Defender','jersey_number'=>4,'team'=>'Lions'],
    ['first_name'=>'Dylan','last_name'=>'Ng','position'=>'Goalkeeper','jersey_number'=>1,'team'=>'Tigers'],
    ['first_name'=>'Elena','last_name'=>'Stone','position'=>'Forward','jersey_number'=>11,'team'=>'Tigers'],
    ['first_name'=>'Finn','last_name'=>'Waters','position'=>'Midfielder','jersey_number'=>6,'team'=>'Tigers'],
    ['first_name'=>'Gina','last_name'=>'Reid','position'=>'Defender','jersey_number'=>3,'team'=>'Bears'],
    ['first_name'=>'Hugo','last_name'=>'Price','position'=>'Forward','jersey_number'=>10,'team'=>'Bears'],
    ['first_name'=>'Isla','last_name'=>'Meier','position'=>'Midfielder','jersey_number'=>7,'team'=>'Bears'],
    ['first_name'=>'Jon','last_name'=>'Khan','position'=>'Defender','jersey_number'=>2,'team'=>'Bears']
];

if ($action === 'run') {
    if (!$confirm) {
        add_log($log, 'Missing confirm=1; aborting.');
    } else {
        if ($dry) {
            add_log($log, 'Dry run enabled – no deletions or inserts performed.');
        } else {
            $db->beginTransaction();
        }

        // Delete related tables first
        foreach ($relatedTables as $t) {
            if (table_exists($db, $t)) {
                $sql = "DELETE FROM $t";
                if ($dry) { add_log($log, "Would execute: $sql (rows: {$counts[$t]})"); }
                else { $db->exec($sql); add_log($log, "Deleted table $t"); }
            }
        }
        // Delete players
        if ($dry) { add_log($log, "Would execute: DELETE FROM players (rows: $playerCount)"); }
        else { $db->exec("DELETE FROM players"); add_log($log, 'Deleted all players'); }

        // Optionally reset autoincrement (SQLite)
        if (table_exists($db, 'sqlite_sequence') && !$dry) {
            $db->exec("DELETE FROM sqlite_sequence WHERE name='players'");
            add_log($log, 'Reset players autoincrement sequence');
        } elseif ($dry) {
            add_log($log, 'Would reset autoincrement for players');
        }

        // Ensure teams exist
        $teamNameToId = [];
        if (table_exists($db, 'teams')) {
            $existing = $db->query("SELECT id, name FROM teams")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($existing as $row) { $teamNameToId[strtolower($row['name'])] = $row['id']; }

            foreach ($demoTeams as $t) {
                $key = strtolower($t['name']);
                if (!isset($teamNameToId[$key])) {
                    if ($dry) { add_log($log, "Would insert team: {$t['name']}"); }
                    else {
                        $stmt = $db->prepare("INSERT INTO teams (name, created_at, updated_at) VALUES (?, datetime('now'), datetime('now'))");
                        $stmt->execute([$t['name']]);
                        $teamNameToId[$key] = $db->lastInsertId();
                        add_log($log, 'Inserted team ' . $t['name']);
                    }
                }
            }
        } else {
            add_log($log, 'Teams table not found; skipping team creation.');
        }

        // Insert demo players
        $playerInserts = 0;
        foreach ($demoPlayers as $p) {
            $team_id = $teamNameToId[strtolower($p['team'])] ?? null;
            $unique_player_id = 'DEMO_' . strtoupper(substr($p['first_name'],0,1) . substr($p['last_name'],0,1)) . '_' . str_pad(($playerInserts+1), 3, '0', STR_PAD_LEFT);
            if ($dry) {
                add_log($log, "Would insert player {$p['first_name']} {$p['last_name']} (Team: {$p['team']} -> ID $team_id)");
            } else {
                $stmt = $db->prepare("INSERT INTO players (first_name, last_name, name, position, jersey_number, team_id, unique_player_id, is_active, created_at, updated_at) VALUES (:f,:l,:name,:pos,:jersey,:team,:uid,1, datetime('now'), datetime('now'))");
                $stmt->execute([
                    ':f'=>$p['first_name'], ':l'=>$p['last_name'], ':name'=>$p['first_name'] . ' ' . $p['last_name'], ':pos'=>$p['position'], ':jersey'=>$p['jersey_number'], ':team'=>$team_id, ':uid'=>$unique_player_id
                ]);
                $playerInserts++;
            }
        }
        if ($dry) add_log($log, 'Would insert ' . count($demoPlayers) . ' demo players.');
        else add_log($log, 'Inserted ' . $playerInserts . ' demo players.');

        if (!$dry) {
            $db->commit();
            $executed = true;
            if (!function_exists('set_flash')) {
                // Minimal flash fallback
                $_SESSION['flash_success'] = 'Players reset with demo data.';
            } else {
                set_flash('success', 'Players reset with demo data.');
            }
            header('Location: players.php');
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Reset Players (Demo Seed)</title>
<style>body{font-family:system-ui,Arial,sans-serif;margin:32px;max-width:900px}h1{margin-top:0}.warn{background:#fff7ed;padding:12px 16px;border:1px solid #fdba74;border-radius:8px}.danger{background:#fef2f2;padding:12px 16px;border:1px solid #fca5a5;border-radius:8px;margin-top:18px}.btns a{display:inline-block;margin:6px 8px 0 0;padding:10px 16px;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px}a.export{background:#2563eb;color:#fff}a.dry{background:#334155;color:#fff}a.run{background:#dc2626;color:#fff}code{background:#f1f5f9;padding:2px 5px;border-radius:4px;font-size:13px}ul.stats{columns:2;list-style:none;padding:0;margin:8px 0 0}ul.stats li{padding:2px 0;font-size:14px}</style>
</head><body>
<h1>Reset Players & Seed Demo Data</h1>
<p class="warn"><strong>Purpose:</strong> Completely clear existing player-related data and insert a small, clean demo dataset for testing.</p>
<div class="danger"><strong>IRREVERSIBLE (without backup):</strong> Export current data first if you may need it again.</div>
<h2>Current Data Counts</h2>
<ul class="stats">
    <li>Players: <?= (int)$counts['players'] ?></li>
    <?php foreach($relatedTables as $t): if(isset($counts[$t])): ?>
        <li><?= htmlspecialchars($t) ?>: <?= (int)$counts[$t] ?></li>
    <?php endif; endforeach; ?>
    <li>Teams: <?= (int)$teamCount ?></li>
</ul>

<h2>Actions</h2>
<div class="btns">
    <a class="export" href="?export=1">1. Export Players CSV</a>
    <a class="dry" href="?action=run&confirm=1&dry=1">2. Dry Run Reset</a>
    <a class="run" href="?action=run&confirm=1" onclick="return confirm('Really DELETE all players and related data?');">3. Execute Reset</a>
</div>

<?php if($action==='run'): ?>
    <h2>Operation Log (<?= $dry ? 'Dry Run' : ($executed ? 'Completed' : 'Attempt') ?>)</h2>
    <pre><?php foreach($log as $l){ echo htmlspecialchars($l) . "\n"; } ?></pre>
<?php endif; ?>

<h2>Demo Dataset Overview</h2>
<p>Teams to ensure: Lions, Tigers, Bears. Inserts 10 demo players distributed across teams with positions & jersey numbers.</p>
<p>Unique IDs prefixed with <code>DEMO_</code>.</p>

</body></html>
