<?php
/**
 * Temporary admin helper: fix duplicate player names by editing surname/name
 * Usage: upload to live site and visit while logged in. Remove after use.
 */
require_once 'includes/auth.php';
require_once 'database_config.php';
if (!isLoggedIn()) { header('Location: login.php'); exit(); }

try { $db = DatabaseConfigSQLite::getConnection(); } catch (Throwable $e) { die('DB error: '.$e->getMessage()); }

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    try {
        $stmt = $db->prepare('UPDATE players SET name = ?, surname = ? WHERE id = ?');
        $stmt->execute([$name, $surname, $id]);
        $message = "Updated player ID $id";
    } catch (Exception $e) { $message = 'Update failed: '.$e->getMessage(); }
}

$dupes = $db->query("SELECT name, COUNT(*) as cnt FROM players GROUP BY name HAVING cnt>1 ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);

$rows = [];
foreach ($dupes as $d) {
    $name = $d['name'];
    $stmt = $db->prepare('SELECT id,name,surname,nickname,team_id,(SELECT name FROM teams WHERE id=players.team_id) as team_name,date_of_birth,contact_number FROM players WHERE name = ? ORDER BY id');
    $stmt->execute([$name]);
    $rows[$name] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Fix Duplicate Players</title>
<style>body{font-family:Arial,Helvetica,sans-serif;padding:18px} table{border-collapse:collapse;width:100%} th,td{border:1px solid #ddd;padding:8px} th{background:#f4f4f4}</style>
</head><body>
<h1>Duplicate Player Names</h1>
<?php if ($message): ?><div style="padding:8px;background:#e6ffed;border:1px solid #c6f0d6;margin-bottom:12px"><?=htmlspecialchars($message)?></div><?php endif; ?>
<?php if (empty($rows)): ?>
    <p>No duplicate player names found.</p>
<?php else: ?>
    <?php foreach ($rows as $name => $items): ?>
        <h2><?=htmlspecialchars($name)?> (<?=count($items)?>)</h2>
        <table>
            <thead><tr><th>ID</th><th>Name</th><th>Surname</th><th>Nickname</th><th>Team</th><th>DOB</th><th>Contact</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($items as $r): ?>
                <tr>
                    <td><?=intval($r['id'])?></td>
                    <td>
                        <form method="POST" style="display:flex;gap:8px;align-items:center">
                            <input type="hidden" name="id" value="<?=intval($r['id'])?>">
                            <input type="text" name="name" value="<?=htmlspecialchars($r['name'])?>" style="min-width:220px">
                    </td>
                    <td><input type="text" name="surname" value="<?=htmlspecialchars($r['surname'])?>" style="width:140px"></td>
                    <td><?=htmlspecialchars($r['nickname'] ?? '')?></td>
                    <td><?=htmlspecialchars($r['team_name'] ?? '')?></td>
                    <td><?=htmlspecialchars($r['date_of_birth'] ?? '')?></td>
                    <td><?=htmlspecialchars($r['contact_number'] ?? '')?></td>
                    <td><button type="submit">Save</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
<?php endif; ?>
<p style="margin-top:14px;color:#666">Note: This is a temporary admin helper. Remove after use.</p>
</body></html>
