<?php
require_once 'database_factory.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$teamId = (int)($_POST['team_id'] ?? 0);
$coachId = (int)($_POST['coach_id'] ?? 0);

if (!$teamId || !$coachId) {
    echo json_encode(['success' => false, 'message' => 'Missing team or coach ID']);
    exit;
}

try {
    $db = DatabaseFactory::getConnection();

    // Remove the assignment
    $stmt = $db->prepare("DELETE FROM team_coaches WHERE team_id = ? AND coach_id = ?");
    $result = $stmt->execute([$teamId, $coachId]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Coach removed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove coach']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
