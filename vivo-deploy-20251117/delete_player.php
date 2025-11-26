<?php
/**
 * VIVO United - Delete Player
 * Safe player deletion with admin authentication
 */

require_once 'database_factory.php';
require_once 'includes/auth.php';

requireLogin();
requireAdmin(); // Only admins can delete players


$db = DatabaseFactory::getConnection();

// Accept id from POST (AJAX) or GET (normal form links)
$player_id = $_POST['id'] ?? $_GET['id'] ?? null;

// detect AJAX / fetch requests
$isXhr = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

if (!$player_id) {
    if ($isXhr) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Missing id']);
        exit;
    }
    header('Location: players.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM players WHERE id = ?");
$stmt->execute([$player_id]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    header('Location: players.php');
    exit;
}

try {
    $db->beginTransaction();
    
    // Delete attendance records first (cascade cleanup)
    $deleteAttendanceStmt = $db->prepare("DELETE FROM attendance WHERE player_id = ?");
    $deleteAttendanceStmt->execute([$player_id]);
    
    // Delete the player
    $deletePlayerStmt = $db->prepare("DELETE FROM players WHERE id = ?");
    $deletePlayerStmt->execute([$player_id]);
    
    $db->commit();

    // Build player name for feedback
    $playerName = '';
    if (!empty($player['first_name']) && !empty($player['last_name'])) {
        $playerName = trim($player['first_name'] . ' ' . $player['last_name']);
    } elseif (!empty($player['name']) && !empty($player['surname'])) {
        $playerName = trim($player['name'] . ' ' . $player['surname']);
    } elseif (!empty($player['first_name'])) {
        $playerName = $player['first_name'];
    } elseif (!empty($player['name'])) {
        $playerName = $player['name'];
    } else {
        $playerName = 'Player #' . $player['id'];
    }
    
    // Success response for AJAX vs normal
    if ($isXhr) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'id' => (int)$player_id, 'name' => $playerName]);
        exit;
    }

    if (!function_exists('set_flash')) { require_once 'functions.php'; }
    if (function_exists('set_flash')) {
        set_flash("Player '{$playerName}' has been permanently deleted along with all attendance records.", 'success');
    }
    header('Location: players.php');
    exit;
    
} catch (Exception $e) {
    $db->rollback();
    if ($isXhr) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    
    // Set error message for non-AJAX
    if (!function_exists('set_flash')) { require_once 'functions.php'; }
    if (function_exists('set_flash')) {
        set_flash('Error deleting player: ' . $e->getMessage(), 'error');
    }
    header('Location: players.php');
    exit;
}
?>
