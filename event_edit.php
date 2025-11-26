<?php
// VIVO United Football Manager - Event Edit/Add/Delete (Clean)

require_once 'includes/auth.php';
@require_once __DIR__ . '/includes/color_system.php';
require_once 'functions.php'; // flash helpers

// auth.php starts session when needed; avoid calling session_start() here to prevent notices

if (!isLoggedIn()) { header('Location: login.php'); exit; }
$currentPage = 'events';

require_once __DIR__ . '/wp-config.php';
try { $db = get_db_connection(); } catch (Throwable $e) { die('Database connection failed: '.$e->getMessage()); }

$message=''; $messageType=''; $event=null; $flash = null; $eventNotFound = false;
$action = $_GET['action'] ?? 'edit';
$eventId = $_GET['id'] ?? null;

// Redirect any accidental 'add' usage to dedicated add_event page
if ($action === 'add') {
    header('Location: add_event.php');
    exit;
}

// GET delete (direct link)
if ($action === 'delete' && $eventId && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $db->prepare('DELETE FROM attendance WHERE event_id = ?')->execute([$eventId]);
        $db->prepare('DELETE FROM events WHERE id = ?')->execute([$eventId]);
        header('Location: events.php?message=' . urlencode('Event deleted successfully!') . '&type=success');
        exit;
    } catch (Exception $e) {
        header('Location: events.php?message=' . urlencode('Error deleting event: '.$e->getMessage()) . '&type=error');
        exit;
    }
}

// POST handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('VIVO Event POST: '.json_encode($_POST));
    $postedAction = $_POST['action'] ?? '';
    $postedId = $_POST['id'] ?? null;
    if (!$eventId && $postedId) { $eventId = $postedId; }

    // Normalize incoming values
    $normType = strtolower(trim($_POST['event_type'] ?? ''));
    $normStatus = strtolower(trim($_POST['status'] ?? 'scheduled'));
    if ($normStatus === '') { $normStatus = 'scheduled'; }
    $isHomeGame = isset($_POST['is_home_game']) ? 1 : 0;

    switch ($postedAction) {
        case 'edit':
            try {
                $idForUpdate = $eventId ?: $postedId;
                if (!$idForUpdate) throw new Exception('Missing event ID for update');
                $stmt = $db->prepare('UPDATE events SET title=?, description=?, event_type=?, date=?, time=?, location=?, team_id=?, opponent=?, is_home_game=?, status=? WHERE id=?');
                $params = [trim($_POST['title']), trim($_POST['description']), $normType, $_POST['date'], $_POST['time'] ?: null, trim($_POST['location']), $_POST['team_id'] ?: null, trim($_POST['opponent']), $isHomeGame, $normStatus, $idForUpdate];
                error_log('VIVO Event UPDATE params: '.json_encode($params));
                $stmt->execute($params);
                set_flash('Event updated successfully!','success');
                header('Location: events.php');
                exit;
            } catch (Exception $e) { $message='Error updating event: '.$e->getMessage(); $messageType='error'; }
            break;
        case 'delete':
            try {
                $idForDelete = $eventId ?: $postedId; if (!$idForDelete) throw new Exception('Missing event ID for delete');
                $db->prepare('DELETE FROM attendance WHERE event_id = ?')->execute([$idForDelete]);
                $db->prepare('DELETE FROM events WHERE id = ?')->execute([$idForDelete]);
                // Respond differently for XHR (AJAX) requests
                $isXhr = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
                if ($isXhr) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'id' => $idForDelete]);
                    exit;
                }

                set_flash('Event deleted successfully!','success');
                header('Location: events.php');
                exit;
            } catch (Exception $e) { $message='Error deleting event: '.$e->getMessage(); $messageType='error'; }
            break;
    }
}

// Load event if editing/deleting
if (($action === 'edit' || $action === 'delete') && $eventId) {
    $stmt = $db->prepare('SELECT e.*, t.name as team_name FROM events e LEFT JOIN teams t ON e.team_id = t.id WHERE e.id = ?');
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
    if (!$event) {
        // Don't redirect here; show a friendly inline error and avoid PHP notices in the template
        $message = 'Event not found';
        $messageType = 'error';
        $eventNotFound = true;
    }
}

// Data for form
$teams = $db->query('SELECT id, name FROM teams ORDER BY name')->fetchAll();
$eventTypes = ['training','match','meeting','tournament','camp','other'];
$eventStatuses = ['scheduled','ongoing','completed','cancelled'];

// If existing event has capitalized or legacy values, normalize for select prefill
if ($event) {
    if (!in_array(strtolower($event['event_type']), $eventTypes)) {
        $event['event_type'] = strtolower($event['event_type']);
    } else { $event['event_type'] = strtolower($event['event_type']); }
    if (!in_array(strtolower($event['status']), $eventStatuses)) {
        $event['status'] = strtolower($event['status']);
    } else { $event['status'] = strtolower($event['status']); }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $action === 'delete' ? 'Delete' : 'Edit' ?> Event - VIVO United Manager</title>
    <link rel="stylesheet" href="css/vivo-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php // Dynamic club color CSS
    require_once 'includes/css_helper.php';
    vivo_include_head_css();
    ?>
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-header h1 {
            color: var(--primary);
            margin: 0;
            font-size: 2.5em;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary);
            color: #fff;
        }
        
        .btn-primary:hover {
            background: var(--secondary);
        }
        
        .btn-secondary {
            background: var(--secondary);
            color: #fff;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .btn-danger {
            background: var(--danger, #dc2626);
            color: #fff;
        }
        
        .btn-danger:hover {
            background: var(--primary);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .delete-confirmation {
            background: #fef2f2;
            border: 2px solid #fecaca;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .delete-confirmation h3 {
            color: var(--primary);
            margin: 0 0 10px 0;
        }
        
        .delete-confirmation p {
            color: #4b5563;
            margin: 0 0 15px 0;
        }
        
        .event-type-match #opponent-group {
            display: block;
        }
        
        #opponent-group {
            display: none;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="content-main">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <div class="form-header">
                <h1>
                    <i class="fas fa-calendar-<?= $action === 'delete' ? 'times' : 'edit' ?>"></i>
                    <?= $action === 'delete' ? 'Delete' : 'Edit' ?> Event
                </h1>
            </div>

            <?php if ($eventNotFound): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
                <div style="text-align:center; margin:20px 0;">
                    <a href="events.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Events</a>
                </div>
            <?php elseif ($action === 'delete'): ?>
                <div class="delete-confirmation">
                    <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
                    <p>Are you sure you want to delete the event <strong><?= htmlspecialchars($event['title']) ?></strong>?</p>
                    <p>Scheduled for: <strong><?= date('F j, Y', strtotime($event['date'])) ?><?= $event['time'] ? ' at ' . date('g:i A', strtotime($event['time'])) : '' ?></strong></p>
                    <p>This action will also delete all associated attendance records and cannot be undone.</p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <div class="form-actions">
                        <a href="events.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Event
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <form method="POST" id="event-form">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= $event['id'] ?>">
                    
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="title">Event Title *</label>
                            <input type="text" id="title" name="title" required
                                   value="<?= htmlspecialchars($event['title'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="event_type">Event Type *</label>
                            <select id="event_type" name="event_type" required>
                                <option value="">Select Type</option>
                                <?php foreach ($eventTypes as $type): ?>
                                    <option value="<?= $type ?>" <?= ($event['event_type'] ?? '') === $type ? 'selected' : '' ?>>
                                        <?= ucfirst($type) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <?php foreach ($eventStatuses as $status): ?>
                                    <option value="<?= $status ?>" <?= ($event['status'] ?? 'scheduled') === $status ? 'selected' : '' ?>>
                                        <?= ucfirst($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date">Date *</label>
                            <input type="date" id="date" name="date" required
                                   value="<?= htmlspecialchars($event['date'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="time">Time</label>
                            <input type="time" id="time" name="time"
                                   value="<?= htmlspecialchars($event['time'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="team_id">Team</label>
                            <select id="team_id" name="team_id">
                                <option value="">Select Team</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= $team['id'] ?>" <?= ($event['team_id'] ?? '') == $team['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($team['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location"
                                   value="<?= htmlspecialchars($event['location'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group full-width" id="opponent-group" <?= strtolower($event['event_type'] ?? '') === 'match' ? 'style="display: block;"' : '' ?>>
                            <label for="opponent">Opponent (for matches)</label>
                            <input type="text" id="opponent" name="opponent"
                                   value="<?= htmlspecialchars($event['opponent'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="is_home_game">Home Game</label>
                            <input type="checkbox" id="is_home_game" name="is_home_game" value="1"
                                   <?= ($event['is_home_game'] ?? 1) ? 'checked' : '' ?>>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" 
                                      placeholder="Event details, objectives, special instructions..."><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
                        </div>
                        
                        <!-- Notes field removed: column not present in current schema -->
                    </div>
                    
                    <div class="form-actions">
                        <a href="events.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Events
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Event
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="js/sidebar.js"></script>
    <script>
    // Show/hide opponent field based on event type (lowercase comparison)
    const etSel = document.getElementById('event_type');
    const opponentGroup = document.getElementById('opponent-group');
    function toggleOpponent(){ opponentGroup.style.display = (etSel.value.toLowerCase() === 'match') ? 'block' : 'none'; }
    etSel.addEventListener('change', toggleOpponent); toggleOpponent();
    </script>
</body>
</html>
