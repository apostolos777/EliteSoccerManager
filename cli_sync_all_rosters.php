#!/usr/bin/env php
<?php
/**
 * CLI tool to sync all teams (safe, intended to run on server as admin)
 */
require_once 'database_config.php';
require_once 'database_factory.php';

try {
    $db = DatabaseFactory::getConnection();
} catch (Exception $e) {
    echo "DB connection failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

$r = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='player_teams'")->fetch(PDO::FETCH_ASSOC);
$hasPlayerTeams = (bool)$r;

$teams = $db->query('SELECT id, name FROM teams ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
if (!$teams) {
    echo "No teams found\n";
    exit(0);
}

$totalInserted = 0;
$totalUpdated = 0;

foreach ($teams as $team) {
    $teamId = (int)$team['id'];
    $teamName = $team['name'] ?? '';
    echo "Syncing team {$teamName} (ID: {$teamId})...\n";
    if ($hasPlayerTeams) {
        $stmt1 = $db->prepare("INSERT INTO player_teams (player_id, team_id) SELECT p.id, ? FROM players p WHERE p.team_id = ? AND NOT EXISTS (SELECT 1 FROM player_teams pt WHERE pt.player_id = p.id AND pt.team_id = ?)");
        $stmt1->execute([$teamId, $teamId, $teamId]);
        $inserted = $stmt1->rowCount();

        $stmt2 = $db->prepare("INSERT INTO player_teams (player_id, team_id) SELECT p.id, ? FROM players p WHERE p.team_ids IS NOT NULL AND p.team_ids <> '' AND (',' || p.team_ids || ',') LIKE ('%,' || ? || ',%') AND NOT EXISTS (SELECT 1 FROM player_teams pt WHERE pt.player_id = p.id AND pt.team_id = ?)");
        $stmt2->execute([$teamId, $teamId, $teamId]);
        $inserted2 = $stmt2->rowCount();
        $totalInserted += ($inserted + $inserted2);
        echo "  Inserted: " . ($inserted + $inserted2) . " rows\n";
    } else {
        $stmtu = $db->prepare("UPDATE players SET team_id = ? WHERE (team_id IS NULL OR team_id = '') AND team_ids IS NOT NULL AND team_ids <> '' AND (',' || team_ids || ',') LIKE ('%,' || ? || ',%')");
        $stmtu->execute([$teamId, $teamId]);
        $u = $stmtu->rowCount();
        $totalUpdated += $u;
        echo "  Updated players.team_id: $u\n";
    }
}

echo "Done. Total inserted: $totalInserted; Total updated: $totalUpdated\n";
exit(0);
