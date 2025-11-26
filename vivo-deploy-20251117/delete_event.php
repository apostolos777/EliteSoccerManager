<?php
// VIVO United Football Manager - Delete Event
// Safe event deletion with attendance cleanup
// Created: July 26, 2025

require_once 'wp-config.php';
require_once 'includes/auth.php';
require_once 'functions.php';

requireLogin();

// Initialize database connection
$db = get_db_connection();

$event_id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$event_id) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Event ID is required']);
        exit;
    }
    header('Location: events.php');
    exit;
}

// Verify event exists
$stmt = $db->prepare("SELECT title FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Event not found']);
        exit;
    }
    header('Location: events.php');
    exit;
}

// Require POST for deletion and validate CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        // If this was a form POST confirmation, redirect back with error
        if (isset($_POST['confirm_delete'])) {
            header('Location: events.php?error=' . urlencode('Invalid CSRF token'));
            exit;
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }

    $wasConfirm = isset($_POST['confirm_delete']);
    $acceptsJson = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    try {
        $db->beginTransaction();
        // Delete attendance records first
        $deleteAttendanceStmt = $db->prepare("DELETE FROM attendance WHERE event_id = ?");
        $deleteAttendanceStmt->execute([$event_id]);
        // Delete the event
        $deleteEventStmt = $db->prepare("DELETE FROM events WHERE id = ?");
        $deleteEventStmt->execute([$event_id]);
        $db->commit();

        // If this was a normal browser form confirmation, redirect back to events list
        if ($wasConfirm) {
            if (function_exists('set_flash')) { set_flash("Event '{$event['title']}' has been deleted.", 'success'); }
            header('Location: events.php?deleted=1&event_name=' . urlencode($event['title']));
            exit;
        }

        // Otherwise return JSON for API/AJAX callers
        if ($acceptsJson || $isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
            exit;
        }

        // Fallback to redirect
        header('Location: events.php?deleted=1&event_name=' . urlencode($event['title']));
        exit;
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        require_once __DIR__ . '/includes/db_logger.php';
        log_db_error($e, 'delete_event');
        if ($wasConfirm) {
            header('Location: events.php?error=' . urlencode('Failed to delete event: ' . $e->getMessage()));
            exit;
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Failed to delete event: ' . $e->getMessage()]);
        exit;
    }
}

// If GET, render a confirmation page with a CSRF-protected form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Confirm Delete Event - <?= htmlspecialchars($event['title']) ?></title>
        <?php require_once 'includes/css_helper.php'; vivo_include_head_css($db); ?>
    </head>
    <body>
        <?php include 'includes/sidebar.php'; ?>
        <div class="content-main">
            <div class="page-header">
                <div class="page-header-content">
                    <h1 class="page-title text-danger"><i class="fas fa-trash-alt"></i> Delete Event</h1>
                    <p class="page-subtitle">Permanently remove event and attendance records</p>
                </div>
                <div class="page-actions">
                    <a href="events.php" class="btn btn-secondary">&larr; Back to Events</a>
                </div>
            </div>

            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h2><i class="fas fa-exclamation-triangle"></i> Confirm Event Deletion</h2>
                </div>
                <div class="card-body">
                    <p>This will permanently delete the event <strong><?= htmlspecialchars($event['title']) ?></strong> and all associated attendance records. This action cannot be undone.</p>
                    <form method="POST" action="">
                        <?= csrf_input_field() ?>
                        <input type="hidden" name="id" value="<?= htmlspecialchars($event_id) ?>">
                        <button type="submit" name="confirm_delete" class="btn btn-danger"><i class="fas fa-trash"></i> Delete Event Permanently</button>
                        <a href="events.php" class="btn btn-secondary ml-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
        <script src="js/mobile-navigation.js"></script>
    </body>
    </html>
    <?php
    exit;
}
?>
