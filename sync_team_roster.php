<?php
/**
 * Sync team roster from player assignments
 * - If `player_teams` join table exists, insert missing entries for players whose `team_id` or `team_ids` reference the team
 * - If `player_teams` table does not exist, update players.team_id using team_ids CSV when appropriate
 */

require_once 'includes/auth.php';
require_once 'database_config.php';
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

header('Content-Type: application/json');

try {
    $db = DatabaseConfigSQLite::getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

$teamId = intval($_POST['team_id'] ?? 0);
if ($teamId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid team_id']);
    exit;
}

// Validate team exists
$tstmt = $db->prepare('SELECT id FROM teams WHERE id = ? LIMIT 1');
$tstmt->execute([$teamId]);
if (!$tstmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Team not found']);
    exit;
}

// Do we have player_teams join table?
$r = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='player_teams'")->fetch(PDO::FETCH_ASSOC);
$hasPlayerTeams = (bool)$r;

$inserted = 0;
$updated = 0;
$errors = [];

try {
    if ($hasPlayerTeams) {
        // Insert players who have team_id = teamId but not in player_teams
        $sql1 = "INSERT INTO player_teams (player_id, team_id)
            SELECT p.id, ? FROM players p
            WHERE p.team_id = ? AND NOT EXISTS (SELECT 1 FROM player_teams pt WHERE pt.player_id = p.id AND pt.team_id = ?)
        ";
        $stmt1 = $db->prepare($sql1);
        $stmt1->execute([$teamId, $teamId, $teamId]);
        $inserted += $stmt1->rowCount();

        // Insert players who have team_ids CSV containing the team id
        $sql2 = "INSERT INTO player_teams (player_id, team_id)
            SELECT p.id, ? FROM players p
            WHERE p.team_ids IS NOT NULL AND p.team_ids <> '' AND (',' || p.team_ids || ',') LIKE ('%,' || ? || ',%')
                AND NOT EXISTS (SELECT 1 FROM player_teams pt WHERE pt.player_id = p.id AND pt.team_id = ?)
        ";
        $stmt2 = $db->prepare($sql2);
        $stmt2->execute([$teamId, $teamId, $teamId]);
        $inserted += $stmt2->rowCount();
    } else {
        // No join table - update players.team_id from team_ids CSV (where team_id is NULL)
        $sqlu = "UPDATE players SET team_id = ? WHERE (team_id IS NULL OR team_id = '') AND team_ids IS NOT NULL AND team_ids <> '' AND (',' || team_ids || ',') LIKE ('%,' || ? || ',%')";
        $stmtu = $db->prepare($sqlu);
        $stmtu->execute([$teamId, $teamId]);
        $updated += $stmtu->rowCount();
    }
} catch (Exception $e) {
    $errors[] = $e->getMessage();
}

echo json_encode(['success' => empty($errors), 'inserted' => $inserted, 'updated' => $updated, 'errors' => $errors]);
exit;

?>
