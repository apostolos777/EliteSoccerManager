<?php
require_once 'includes/auth.php';
require_once 'functions.php';
require_once 'database_factory.php';

requireLogin();

// Database connection
try {
    $db = DatabaseFactory::getConnection();
} catch (Exception $e) {
    $db_file = defined('DB_FILE') ? DB_FILE : (__DIR__ . '/database.db');
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

// Fetch events with attendance stats
$eventsSql = "SELECT e.*, COUNT(a.id) as total_attendance, SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count FROM events e LEFT JOIN attendance a ON e.id = a.event_id GROUP BY e.id ORDER BY COALESCE(e.event_date, e.date) DESC";
$stmt = $db->query($eventsSql);
$allEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$upcomingEvents = [];
$completedEvents = [];
$today = strtotime(date('Y-m-d'));
foreach ($allEvents as $ev) {
    $raw = $ev['event_date'] ?? $ev['date'] ?? null;
    // Use a safe parse that treats invalid dates as 'TBD' (no future timestamp)
    try {
        $ts = $raw ? strtotime($raw) : $today;
        if ($ts === false || $ts === -1) $ts = $today;
    } catch (Exception $e) {
        $ts = $today;
    }
    if ($ts >= $today) $upcomingEvents[] = $ev; else $completedEvents[] = $ev;
}

// Helper: format event date consistently and safely
function formatEventDate($raw) {
    if (empty($raw)) return 'TBD';
    try {
        $d = new DateTime($raw);
        return $d->format('M j, Y g:i A');
    } catch (Exception $e) {
        return 'TBD';
    }
}

$flash = get_flash();
$message = '';
$messageType = '';
if ($flash) { $message = $flash['m']; $messageType = $flash['t']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Events - VIVO United</title>
    <?php require_once 'includes/css_helper.php'; vivo_include_head_css($db); ?>
    <style>
        .card { background:#fff;border-radius:12px;padding:1rem;margin-bottom:0.75rem;border:1px solid rgba(0,0,0,0.04);box-shadow:0 10px 30px rgba(2,8,23,0.06) }
        .card-body{padding:0}
        .events-container { display: flex; gap: 2rem; margin-top: 1.5rem; }
        .events-column { flex: 1; }
        .events-column h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: #0f1724; }
        .event-card-inner { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; }
        .event-info { flex: 1; }
        .event-title { font-weight: 700; color: #0f1724; margin-bottom: 0.25rem; }
        .event-meta { color: #6b7280; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .event-badges { display: flex; gap: 0.4rem; flex-wrap: wrap; margin-top: 0.5rem; }
        .event-badge { background: #eef2ff; color: #3730a3; padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; border: 1px solid #c7d2fe; }
        .event-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end; }
        @media (max-width: 1024px) {
            .events-container { flex-direction: column; gap: 1.5rem; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="content-main">
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title">
                    <i class="fas fa-calendar-alt"></i>
                    Event Management
                </h1>
                <p class="page-subtitle">Schedule and manage events and fixtures</p>
            </div>
            <div class="page-actions">
                <a href="add_event.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i>
                    Add New Event
                </a>
                <a href="calendar.php" class="btn btn-secondary">
                    <i class="fas fa-calendar"></i>
                    Full Calendar
                </a>
            </div>
        </div>

        <div class="content-wrapper">
            <?php if ($message): ?>
                <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
                    <i class="fas fa-info-circle"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="events-container">
                <div class="events-column">
                    <h3><i class="fas fa-arrow-right" style="color: #0b6cff; margin-right: 0.5rem;"></i>Upcoming Events</h3>
                    <?php if (empty($upcomingEvents)): ?>
                        <div class="card">
                            <div class="card-body">
                                <div style="text-align: center; padding: 2rem; color: #6b7280;">
                                    <i class="fas fa-calendar-times fa-2x" style="margin-bottom: 1rem; opacity: 0.5;"></i>
                                    <p>No upcoming events scheduled</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($upcomingEvents as $event):
                        $raw = $event['event_date'] ?? $event['date'] ?? null;
                        $dt = formatEventDate($raw);
                    ?>
                        <div class="card">
                            <div class="card-body">
                                <div class="event-card-inner">
                                    <div class="event-info">
                                        <div class="event-title"><?= htmlspecialchars($event['title']) ?></div>
                                        <div class="event-meta">
                                            <i class="fas fa-map-marker-alt" style="color: #9ca3af; margin-right: 0.25rem;"></i><?= htmlspecialchars($event['location'] ?? 'TBD') ?> 
                                            <span style="margin: 0 0.5rem;">•</span>
                                            <i class="fas fa-clock" style="color: #9ca3af; margin-right: 0.25rem;"></i><?= $dt ?>
                                        </div>
                                        <?php if (!empty($event['age_groups'])): ?>
                                            <div class="event-badges">
                                                <?php foreach (explode(',', $event['age_groups']) as $ag): $ag = trim($ag); if ($ag==='') continue; ?>
                                                    <span class="event-badge"><?= htmlspecialchars($ag) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="flex-shrink: 0;">
                                        <div style="font-size: 0.9rem; text-align: right; margin-bottom: 0.5rem; color: #0f6f4b; font-weight: 600;">
                                            <i class="fas fa-users" style="margin-right: 0.25rem;"></i><?= (int)($event['present_count'] ?? 0) ?>/<?= (int)($event['total_attendance'] ?? 0) ?>
                                        </div>
                                        <div class="event-actions">
                                            <a href="event_details.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-primary">View</a>
                                            <?php if (function_exists('isAdmin') && isAdmin()): ?><a href="delete_event.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-danger">Delete</a><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="events-column">
                    <h3><i class="fas fa-history" style="color: #9ca3af; margin-right: 0.5rem;"></i>Completed Events</h3>
                    <?php if (empty($completedEvents)): ?>
                        <div class="card">
                            <div class="card-body">
                                <div style="text-align: center; padding: 2rem; color: #6b7280;">
                                    <i class="fas fa-inbox fa-2x" style="margin-bottom: 1rem; opacity: 0.5;"></i>
                                    <p>No past events to display</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($completedEvents as $event):
                        $raw = $event['event_date'] ?? $event['date'] ?? null;
                        $dt = formatEventDate($raw);
                    ?>
                        <div class="card">
                            <div class="card-body">
                                <div class="event-card-inner">
                                    <div class="event-info">
                                        <div class="event-title"><?= htmlspecialchars($event['title']) ?></div>
                                        <div class="event-meta">
                                            <i class="fas fa-map-marker-alt" style="color: #9ca3af; margin-right: 0.25rem;"></i><?= htmlspecialchars($event['location'] ?? 'TBD') ?> 
                                            <span style="margin: 0 0.5rem;">•</span>
                                            <i class="fas fa-clock" style="color: #9ca3af; margin-right: 0.25rem;"></i><?= $dt ?>
                                        </div>
                                        <?php if (!empty($event['age_groups'])): ?>
                                            <div class="event-badges">
                                                <?php foreach (explode(',', $event['age_groups']) as $ag): $ag = trim($ag); if ($ag==='') continue; ?>
                                                    <span class="event-badge"><?= htmlspecialchars($ag) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="flex-shrink: 0;">
                                        <div style="font-size: 0.9rem; text-align: right; margin-bottom: 0.5rem; color: #0f6f4b; font-weight: 600;">
                                            <i class="fas fa-users" style="margin-right: 0.25rem;"></i><?= (int)($event['present_count'] ?? 0) ?>/<?= (int)($event['total_attendance'] ?? 0) ?>
                                        </div>
                                        <div class="event-actions">
                                            <a href="event_details.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-primary">View</a>
                                            <?php if (function_exists('isAdmin') && isAdmin()): ?><a href="delete_event.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-danger">Delete</a><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
