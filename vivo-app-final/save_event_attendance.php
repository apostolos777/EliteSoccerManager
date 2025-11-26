<?php
header('Content-Type: application/json');
require_once __DIR__.'/database_factory.php';
session_start();
if($_SERVER['REQUEST_METHOD']!=='POST'){ http_response_code(405); echo json_encode(['success'=>false,'error'=>'Method not allowed']); exit; }
function db(){ try { return DatabaseFactory::getConnection(); } catch(Throwable $e){ return null; }}
$pdo = db();
if(!$pdo){ http_response_code(500); echo json_encode(['success'=>false,'error'=>'DB unavailable']); exit; }
$eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$attendance = $_POST['attendance'] ?? [];
if($eventId<=0){ http_response_code(400); echo json_encode(['success'=>false,'error'=>'Invalid event id']); exit; }
try {
    $pdo->beginTransaction();
    $check = $pdo->prepare('SELECT id FROM attendance WHERE player_id=? AND event_id=?');
    $insert = $pdo->prepare('INSERT INTO attendance (player_id,event_id,status,recorded_at) VALUES (?,?,?,CURRENT_TIMESTAMP)');
    $update = $pdo->prepare('UPDATE attendance SET status=?, recorded_at=CURRENT_TIMESTAMP WHERE player_id=? AND event_id=?');
    $count=0;
    foreach($attendance as $pid=>$status){
        $pid=(int)$pid; $status=trim($status);
        if(!$pid || $status==='') continue;
        $check->execute([$pid,$eventId]);
        if($check->fetch()){ $update->execute([$status,$pid,$eventId]); } else { $insert->execute([$pid,$eventId,$status]); }
        $count++;
    }
    $pdo->commit();
    echo json_encode(['success'=>true,'updated'=>$count]);
} catch(Throwable $e){ $pdo->rollBack(); http_response_code(500); echo json_encode(['success'=>false,'error'=>'Save failed']); }
?>
