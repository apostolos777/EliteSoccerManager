<?php
// Script to reset events table with sample events
require_once __DIR__ . '/../database_factory.php'; // adjust if necessary
// If running from repo root, use database_factory.php directly
require_once __DIR__ . '/../includes/auth.php';
$db = DatabaseFactory::getConnection();

try {
    $db->beginTransaction();
    $db->exec("DELETE FROM events;");

    $insert = $db->prepare("INSERT INTO events (title, description, date, time, location, event_type, team_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))");

    $today = new DateTime();

    $samples = [
        ['Team Training', 'Weekly team training session', $today->modify('+1 day')->format('Y-m-d'), '18:00:00', 'Main Field', 'training', 1],
        ['Match vs Rivals', 'Home match against Rivals FC', $today->modify('+2 day')->format('Y-m-d'), '15:00:00', 'Stadium', 'match', 1],
        ['Coaches Meeting', 'Monthly coaches strategy meeting', $today->modify('+3 day')->format('Y-m-d'), '20:00:00', 'Clubhouse', 'meeting', 1],
        ['Social Night', 'Team social event for all players and families', $today->modify('+4 day')->format('Y-m-d'), '19:00:00', 'Community Hall', 'social', 1],
        ['Tournament', 'Regional tournament opening match', $today->modify('+5 day')->format('Y-m-d'), '09:00:00', 'Regional Stadium', 'tournament', 1],
    ];

    foreach ($samples as $s) {
        $insert->execute($s);
    }

    $db->commit();
    echo "OK\n";
} catch (Exception $e) {
    $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
