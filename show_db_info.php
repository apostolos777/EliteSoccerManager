<?php
// Debug helper: show which database file is used and a quick teams count
require_once __DIR__ . '/database_config.php';
header('Content-Type: text/plain; charset=utf-8');

try {
    $dbClass = new ReflectionClass('DatabaseConfigSQLite');
    // Attempt to infer DB path from DatabaseConfigSQLite implementation
    $dbPath = __DIR__ . '/database.db';
    if (method_exists('DatabaseConfigSQLite', 'getConnection')) {
        $db = DatabaseConfigSQLite::getConnection();
        // Try to read the filename via PRAGMA if sqlite
        $res = $db->query("PRAGMA database_list;")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($res) && isset($res[0]['file'])) {
            $dbPath = $res[0]['file'];
        }
        // Get teams count
        $count = $db->query("SELECT COUNT(*) AS c FROM teams;")->fetch(PDO::FETCH_ASSOC);
        $teamsCount = $count['c'] ?? '(unknown)';
    } else {
        $db = null;
        $teamsCount = '(no connection)';
    }
} catch (Throwable $e) {
    $dbPath = '(error) ' . $e->getMessage();
    $teamsCount = '(error)';
}

echo "Database file: " . $dbPath . "\n";
echo "Teams count: " . $teamsCount . "\n";
echo "Current working dir: " . __DIR__ . "\n";
echo "PHP version: " . phpversion() . "\n";

// Show a small sample of teams if available
if (isset($db) && $db) {
    echo "\nSample teams:\n";
    $s = $db->query("SELECT id,name,created_at FROM teams ORDER BY id DESC LIMIT 10");
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
        echo sprintf("%s | %s | %s\n", $r['id'], $r['name'], $r['created_at']);
    }
}

echo "\nTo debug further: visit /sql_check_teams.php and /sync_teams.php on the live server. Remove these debug files after use.";
