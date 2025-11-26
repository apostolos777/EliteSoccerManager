<?php
require_once 'database_factory.php';

header('Content-Type: application/json');

if (!isset($_GET['team_id'])) {
    echo json_encode([]);
    exit;
}

$teamId = (int)$_GET['team_id'];

try {
    $db = DatabaseFactory::getConnection();

    // Get coaches not already assigned to this team
    $stmt = $db->prepare("
        SELECT c.id, c.name, c.role
        FROM coaches c
        WHERE c.id NOT IN (
            SELECT coach_id FROM team_coaches WHERE team_id = ?
        )
        ORDER BY c.name
    ");
    $stmt->execute([$teamId]);
    $coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($coaches);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
