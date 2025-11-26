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

    // Check if assignment already exists
    $stmt = $db->prepare("SELECT id FROM team_coaches WHERE team_id = ? AND coach_id = ?");
    $stmt->execute([$teamId, $coachId]);
    $existing = $stmt->fetch();

    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'Coach is already assigned to this team']);
        exit;
    }

    // Create the assignment
    $stmt = $db->prepare("INSERT INTO team_coaches (team_id, coach_id) VALUES (?, ?)");
    $result = $stmt->execute([$teamId, $coachId]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Coach assigned successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to assign coach']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
