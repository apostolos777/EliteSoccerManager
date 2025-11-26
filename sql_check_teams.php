<?php
// Diagnostic: Run quick teams queries against the app database
// Usage: drop this file on the live server temporarily, visit in a browser, then remove it.
// WARNING: remove this file after use to avoid exposing data.

require_once __DIR__ . '/database_config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Teams - Quick SQL Check</title>
  <style>body{font-family:Inter,system-ui,Arial,sans-serif;padding:20px;color:#111}table{border-collapse:collapse;width:100%;max-width:900px}td,th{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f4f4f6}</style>
</head>
<body>
  <h2>Teams - Quick SQL Check</h2>
  <p>This temporary diagnostic page runs two queries against the app database and shows results. Remove this file from the live server when finished.</p>

<?php
try {
    $db = DatabaseConfigSQLite::getConnection();

    // Query 1: teams count
    $countStmt = $db->query("SELECT COUNT(*) AS teams_count FROM teams;");
    $count = $countStmt->fetch(PDO::FETCH_ASSOC);

    // Query 2: sample rows
    $rowsStmt = $db->query("SELECT id, name, created_at FROM teams ORDER BY id DESC LIMIT 10;");
    $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    echo "<div style='color:#900;font-weight:700'>Error running queries: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</body></html>";
    exit(1);
}

?>

  <h3>Teams count</h3>
  <div style="margin-bottom:1rem;font-weight:700"><?php echo isset($count['teams_count']) ? (int)$count['teams_count'] : '0'; ?></div>

  <h3>Latest teams (up to 10)</h3>
  <?php if (empty($rows)): ?>
    <p>No rows returned.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr><th>ID</th><th>Name</th><th>Created At</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['id']); ?></td>
            <td><?php echo htmlspecialchars($r['name']); ?></td>
            <td><?php echo htmlspecialchars($r['created_at']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <h3>Raw SQL (copy to sqlite3)</h3>
  <pre style="background:#f7f7fb;padding:10px;border:1px solid #e2e2ea;max-width:900px">SELECT COUNT(*) AS teams_count FROM teams;
SELECT id, name, created_at FROM teams ORDER BY id DESC LIMIT 10;</pre>

  <p style="margin-top:1.5rem;color:#666">Remember to remove this file from the server when done.</p>
</body>
</html>
