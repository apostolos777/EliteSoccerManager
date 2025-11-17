<?php
header('Content-Type: application/json');
require_once __DIR__.'/database_factory.php';
session_start();
function db(){ try { return DatabaseFactory::getConnection(); } catch(Throwable $e){ return null; }}
$pdo = db();
if(!$pdo){ http_response_code(500); echo json_encode(['success'=>false,'error'=>'DB unavailable']); exit; }
$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
if($eventId<=0){ http_response_code(400); echo json_encode(['success'=>false,'error'=>'Invalid event id']); exit; }
try {
    $stmt = $pdo->prepare("SELECT p.id, p.name AS full_name, p.position, p.jersey_number, t.name AS team_name, a.status
                            FROM players p
                            LEFT JOIN teams t ON p.team_id = t.id
                            LEFT JOIN attendance a ON a.player_id = p.id AND a.event_id = ?
                            WHERE p.status='active'
                            ORDER BY p.name");
    $stmt->execute([$eventId]);
    $players = $stmt->fetchAll();
    echo json_encode(['success'=>true,'players'=>$players]);
} catch(Throwable $e){ http_response_code(500); echo json_encode(['success'=>false,'error'=>'Query failed']); }
?>
