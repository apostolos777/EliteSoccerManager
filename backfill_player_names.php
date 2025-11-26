<?php
/**
 * Player Name Backfill Script
 *
 * Purpose: Populate missing first_name / last_name values in the players table using a CSV file.
 *
 * Usage:
 * 1. Upload this script to the vivoapp directory.
 * 2. Access via browser: /vivoapp/backfill_player_names.php
 * 3. Upload a CSV with headers. Supported header names (case-insensitive):
 *    - unique_player_id OR id (at least one identifier required)
 *    - first_name / firstname / first
 *    - last_name / lastname / last
 *    - name (full name; will be split into first + last at first space)
 *    - jersey_number / jersey (fallback identifier if no unique_player_id)
 *    - team_id (optional, can help disambiguate)
 *    - date_of_birth / dob (optional)
 *
 * 4. Dry Run (default): the script shows what it WOULD update.
 *    Pass ?commit=1 to actually apply updates.
 *
 * Safety: Will only update rows where first_name AND last_name are NULL or empty.
 */
require_once 'wp-config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$db = get_db_connection();
$commit = isset($_REQUEST['commit']) && $_REQUEST['commit'] == '1';
$message = '';
$results = [];
$error = '';

function norm($s){ return strtolower(trim($s)); }

// Fetch existing players keyed by identifiers for fast lookup
$playersRaw = $db->query("SELECT id, unique_player_id, first_name, last_name, jersey_number, team_id FROM players")->fetchAll();
$byUnique = [];
$byId = [];
$byComposite = []; // jersey+team
foreach ($playersRaw as $pr) {
    if (!empty($pr['unique_player_id'])) { $byUnique[norm($pr['unique_player_id'])] = $pr; }
    $byId[$pr['id']] = $pr;
    $compKey = norm(($pr['jersey_number'] !== null ? $pr['jersey_number'] : '')) . '|' . norm(($pr['team_id'] !== null ? $pr['team_id'] : ''));
    if ($compKey !== '|') { $byComposite[$compKey] = $pr; }
}

if (!empty($_FILES['csv_file']['tmp_name'])) {
    $tmp = $_FILES['csv_file']['tmp_name'];
    $rows = [];
    $h = fopen($tmp, 'r');
    if ($h) {
        $header = fgetcsv($h);
        if ($header === false) {
            $error = 'Empty CSV file.';
        } else {
            $map = [];
            foreach ($header as $i => $col) {
                $c = norm($col);
                if (in_array($c, ['unique_player_id','unique id','player_uid','uid'])) $map['unique_player_id'] = $i;
                if (in_array($c, ['id','player_id'])) $map['id'] = $i;
                if (in_array($c, ['first_name','firstname','first'])) $map['first_name'] = $i;
                if (in_array($c, ['last_name','lastname','last','surname'])) $map['last_name'] = $i;
                if (in_array($c, ['name','full_name','player_name'])) $map['name'] = $i;
                if (in_array($c, ['jersey','jersey_number','number'])) $map['jersey_number'] = $i;
                if (in_array($c, ['team_id','team'])) $map['team_id'] = $i;
                if (in_array($c, ['dob','date_of_birth','birthdate'])) $map['date_of_birth'] = $i; // optional
            }
            $lineNum = 1;
            while(($data = fgetcsv($h)) !== false) {
                $lineNum++;
                $row = ['_line' => $lineNum];
                foreach ($map as $field => $idx) {
                    $row[$field] = isset($data[$idx]) ? trim($data[$idx]) : null;
                }
                $rows[] = $row;
            }
            fclose($h);

            foreach ($rows as $r) {
                $identifier = null; $source = '';
                $player = null;

                if (!empty($r['unique_player_id'])) {
                    $uidKey = norm($r['unique_player_id']);
                    if (isset($byUnique[$uidKey])) { $player = $byUnique[$uidKey]; $identifier = $r['unique_player_id']; $source='unique_player_id'; }
                }
                if (!$player && !empty($r['id']) && ctype_digit(preg_replace('/[^0-9]/','',$r['id']))) {
                    $pid = (int)$r['id'];
                    if (isset($byId[$pid])) { $player = $byId[$pid]; $identifier = 'ID '.$pid; $source='id'; }
                }
                if (!$player && (!empty($r['jersey_number']) || $r['jersey_number']==='0')) {
                    $comp = norm($r['jersey_number']) . '|' . norm($r['team_id'] ?? '');
                    if (isset($byComposite[$comp])) { $player = $byComposite[$comp]; $identifier = 'Jersey '.$r['jersey_number'].' Team '.($r['team_id']??''); $source='jersey+team'; }
                }

                // Derive first/last from provided fields
                $first = $r['first_name'] ?? '';
                $last  = $r['last_name'] ?? '';
                if ((!$first && !$last) && !empty($r['name'])) {
                    $parts = preg_split('/\s+/', trim($r['name']));
                    $first = array_shift($parts);
                    $last = count($parts) ? implode(' ', $parts) : '';
                }
                $first = trim($first); $last = trim($last);

                $action = 'skip'; $reason = '';
                if (!$player) {
                    $reason = 'Player not found (identifier mismatch)';
                } elseif (!$first && !$last) {
                    $reason = 'No name data in row';
                } elseif (!empty($player['first_name']) || !empty($player['last_name'])) {
                    $reason = 'Already has name';
                } else {
                    $action = 'update';
                }

                $results[] = [
                    'line' => $r['_line'],
                    'identifier' => $identifier ?? 'UNKNOWN',
                    'source' => $source,
                    'first' => $first,
                    'last' => $last,
                    'action' => $action,
                    'reason' => $reason,
                    'player_id' => $player['id'] ?? null
                ];
            }

            if ($commit) {
                $updated = 0;
                $stmt = $db->prepare("UPDATE players SET first_name = :f, last_name = :l, updated_at = datetime('now') WHERE id = :id");
                foreach ($results as $row) {
                    if ($row['action']==='update') {
                        $stmt->execute([':f'=>$row['first'] ?: null, ':l'=>$row['last'] ?: null, ':id'=>$row['player_id']]);
                        $updated++;
                    }
                }
                $message = "Applied updates: $updated player records updated.";
            } else {
                $message = 'Dry run complete. No changes applied. Add ?commit=1 to apply updates.';
            }
        }
    } else {
        $error = 'Failed to open uploaded file.';
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Backfill Player Names</title>
<style>body{font-family:system-ui,Arial,sans-serif;margin:30px;line-height:1.4;}table{border-collapse:collapse;width:100%;margin-top:1rem;font-size:.85rem;}th,td{border:1px solid #ccc;padding:6px 8px;text-align:left;}th{background:#f5f7fa;}tr.update{background:#ecfdf5;}tr.skip{color:#64748b;}code{background:#f1f5f9;padding:2px 4px;border-radius:4px;font-size:.75rem;} .msg{padding:10px 14px;border-radius:6px;margin:15px 0;font-weight:600;} .ok{background:#ecfdf5;color:#065f46;} .warn{background:#fff7ed;color:#9a3412;} .err{background:#fef2f2;color:#991b1b;} .badge{display:inline-block;background:#e2e8f0;color:#334155;padding:2px 6px;border-radius:4px;font-size:.65rem;margin-left:4px;font-weight:600;}</style>
</head><body>
<h1>Player Name Backfill</h1>
<p>Upload a CSV to populate missing <code>first_name</code> / <code>last_name</code>. This tool only updates players that currently have both empty.</p>
<ul>
 <li>Dry run by default (shows planned updates).</li>
 <li>Add <code>?commit=1</code> to apply changes after verifying.</li>
 <li>Identifier priority: unique_player_id -> id -> jersey+team.</li>
</ul>
<form method="POST" enctype="multipart/form-data">
    <label><strong>Select CSV:</strong> <input type="file" name="csv_file" accept=".csv" required></label>
    <button type="submit">Process CSV (Dry Run)</button>
    <button type="submit" name="commit" value="1" formaction="?commit=1" style="background:#065f46;color:#fff;margin-left:8px;">Apply Updates (Commit)</button>
</form>
<?php if($message): ?><div class="msg ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($results): ?>
<table>
    <thead><tr><th>#</th><th>Identifier</th><th>Src</th><th>First</th><th>Last</th><th>Action</th><th>Reason</th></tr></thead>
    <tbody>
    <?php foreach ($results as $r): ?>
        <tr class="<?= htmlspecialchars($r['action']) ?>">
            <td><?= (int)$r['line'] ?></td>
            <td><?= htmlspecialchars($r['identifier']) ?></td>
            <td><?= htmlspecialchars($r['source'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['first']) ?></td>
            <td><?= htmlspecialchars($r['last']) ?></td>
            <td><?= htmlspecialchars(strtoupper($r['action'])) ?></td>
            <td><?= htmlspecialchars($r['reason']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</body></html>
