<?php
// Admin helper: Sync/Create teams from player data
// Usage: upload to live server temporarily, run, then remove when done.
require_once __DIR__ . '/database_config.php';

header('Content-Type: text/html; charset=utf-8');

$db = DatabaseConfigSQLite::getConnection();

function getPlayerColumns($db) {
    $stmt = $db->query("PRAGMA table_info(players)");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return array_column($cols, 'name');
}

function getDistinctValues($db, $col) {
    $safe = preg_replace('/[^a-z0-9_]/i','', $col);
    $q = $db->prepare("SELECT DISTINCT TRIM(COALESCE($safe, '')) AS val FROM players WHERE $safe IS NOT NULL AND TRIM($safe) != '' ORDER BY val");
    $q->execute();
    return array_column($q->fetchAll(PDO::FETCH_ASSOC), 'val');
}

function teamsByName($db) {
    $stmt = $db->query("SELECT name FROM teams");
    return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
}

// POST handler: create teams for selected names
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['create_names'])) {
    $toCreate = $_POST['create_names'];
    $inserted = [];
    $stmt = $db->prepare("INSERT INTO teams (name, created_at) VALUES (:name, datetime('now'))");
    foreach ($toCreate as $name) {
        $name = trim($name);
        if ($name === '') continue;
        try {
            $stmt->execute([':name' => $name]);
            $inserted[] = $name;
        } catch (Exception $e) {
            // ignore duplicates or errors
        }
    }
}

$playerCols = getPlayerColumns($db);
$teamNameCols = array_filter($playerCols, function($c){ return stripos($c,'team') !== false; });
$existingTeams = teamsByName($db);

// Find missing team_id references
$missingTeamIds = [];
try {
    $mStmt = $db->query("SELECT DISTINCT p.team_id FROM players p LEFT JOIN teams t ON p.team_id = t.id WHERE p.team_id IS NOT NULL AND t.id IS NULL");
    $missing = $mStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($missing as $r) {
        if ($r['team_id'] !== null && $r['team_id'] !== '') $missingTeamIds[] = (int)$r['team_id'];
    }
} catch (Exception $e) {
    $missingTeamIds = [];
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Sync Teams</title>
  <style>body{font-family:Inter,Arial,sans-serif;padding:18px;color:#111}h1{margin-top:0}table{border-collapse:collapse;margin:8px 0}td,th{border:1px solid #e6e6ee;padding:6px}</style>
</head>
<body>
  <h1>Sync Teams from Player Data</h1>
  <p>Use this admin helper to inspect player columns for team names and create missing teams in the <code>teams</code> table. Remove when finished.</p>

  <h3>Database summary</h3>
  <ul>
    <li>Player columns: <?php echo htmlspecialchars(implode(', ', $playerCols)); ?></li>
    <li>Detected team-name columns: <?php echo htmlspecialchars(implode(', ', $teamNameCols) ?: 'none'); ?></li>
  </ul>

  <?php if (!empty($missingTeamIds)): ?>
    <h3>Players reference missing team IDs</h3>
    <p>The following team_id values are referenced by players but there is no matching row in <code>teams</code>:</p>
    <pre><?php echo htmlspecialchars(implode(', ', $missingTeamIds)); ?></pre>
    <form method="post">
      <input type="hidden" name="create_names[]" value="" />
      <p>Click the button below to create placeholder teams for each missing numeric id (names will be <code>Imported Team &lt;id&gt;</code>).</p>
      <?php foreach ($missingTeamIds as $mid): ?>
        <input type="hidden" name="create_names[]" value="<?php echo htmlspecialchars('Imported Team '.$mid); ?>">
      <?php endforeach; ?>
      <button type="submit">Create placeholder teams for missing IDs</button>
    </form>
  <?php else: ?>
    <h3>No missing numeric team_id references found</h3>
  <?php endif; ?>

  <?php if (!empty($teamNameCols)): ?>
    <h3>Candidate team name columns</h3>
    <?php foreach ($teamNameCols as $col):
        $vals = getDistinctValues($db, $col);
        // filter out those already in teams
        $new = array_filter($vals, function($v) use ($existingTeams){ return $v !== '' && !in_array($v, $existingTeams); });
        if (empty($new)) continue;
    ?>
      <h4>Column: <?php echo htmlspecialchars($col); ?></h4>
      <form method="post">
        <p>Select names to create as teams:</p>
        <?php foreach ($new as $name): ?>
          <label style="display:block;margin:4px 0"><input type="checkbox" name="create_names[]" value="<?php echo htmlspecialchars($name); ?>"> <?php echo htmlspecialchars($name); ?></label>
        <?php endforeach; ?>
        <p><button type="submit">Create selected teams</button></p>
      </form>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if (!empty($inserted)): ?>
    <h3>Created teams</h3>
    <ul><?php foreach ($inserted as $n) { echo '<li>'.htmlspecialchars($n).'</li>'; } ?></ul>
  <?php endif; ?>

  <h3>Existing teams sample</h3>
  <?php
    $s = $db->query("SELECT id,name,created_at FROM teams ORDER BY id DESC LIMIT 20");
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) { echo '<p>No teams in DB.</p>'; } else {
  ?>
    <table><thead><tr><th>ID</th><th>Name</th><th>Created</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
      <tr><td><?php echo htmlspecialchars($r['id']); ?></td><td><?php echo htmlspecialchars($r['name']); ?></td><td><?php echo htmlspecialchars($r['created_at']); ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
  <?php } ?>

  <p style="margin-top:1.5rem;color:#666">After you finish, delete this file to avoid exposing internal data.</p>
</body>
</html>
