<?php
require_once 'includes/auth.php';
require_once 'functions.php';
require_once 'database_factory.php';

requireLogin();

// Database connection
try {
    $db = DatabaseFactory::getConnection();
} catch (Exception $e) {
    $db = new PDO('sqlite:' . __DIR__ . '/database.db');
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
    $ts = $raw ? strtotime($raw) : $today;
    if ($ts >= $today) $upcomingEvents[] = $ev; else $completedEvents[] = $ev;
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
        .card { background:#fff;border-radius:8px;padding:.75rem;margin-bottom:.5rem;border:1px solid #e5e7eb }
        .card-body{padding:0}
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="content-main">
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title"><i class="fas fa-calendar-alt"></i> Event Management</h1>
                <p class="page-subtitle">Schedule and manage events</p>
            </div>
            <div class="page-actions">
                <!-- Link directly to add_event.php so the Add New Event action opens even when event_edit.php requires authentication -->
                    <a href="edit_event.php?action=add" class="btn btn-primary">Add New Event</a>
                <a href="calendar.php" class="btn btn-secondary" style="margin-left:1rem;"><i class="fas fa-calendar"></i> Full Calendar View</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div style="display:flex;gap:1rem;">
            <div style="flex:1;">
                <h3>Upcoming</h3>
                <?php if (empty($upcomingEvents)): ?>
                    <div class="card"><div class="card-body">No upcoming events</div></div>
                <?php endif; ?>
                <?php foreach ($upcomingEvents as $event):
                    $raw = $event['event_date'] ?? $event['date'] ?? null;
                    $dt = $raw ? date('M j, Y g:i A', strtotime($raw)) : 'TBD';
                ?>
                    <div class="card">
                        <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                <div style="font-weight:700;"><?= htmlspecialchars($event['title']) ?></div>
                                <div style="color:#6b7280;font-size:.9rem;"><?= htmlspecialchars($event['location'] ?? '') ?> • <?= $dt ?></div>
                                <!-- age groups badges shown above -->
                                    <?php if (!empty($event['age_groups'])): ?>
                                        <div style="margin-top:.5rem;display:flex;gap:.4rem;flex-wrap:wrap;">
                                            <?php foreach (explode(',', $event['age_groups']) as $ag): $ag = trim($ag); if ($ag==='') continue; ?>
                                                <span style="background:#eef2ff;color:#3730a3;padding:.25rem .5rem;border-radius:999px;font-size:.75rem;font-weight:600;border:1px solid #c7d2fe"><?= htmlspecialchars($ag) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:.9rem;">Present: <?= (int)($event['present_count'] ?? 0) ?> / <?= (int)($event['total_attendance'] ?? 0) ?></div>
                                <div style="margin-top:.5rem;"><a href="event_details.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-primary">Details</a>
                                <a href="delete_event.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-danger">Delete</a></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="flex:1;">
                <h3>Completed</h3>
                <?php if (empty($completedEvents)): ?>
                    <div class="card"><div class="card-body">No past events</div></div>
                <?php endif; ?>
                <?php foreach ($completedEvents as $event):
                    $raw = $event['event_date'] ?? $event['date'] ?? null;
                    $dt = $raw ? date('M j, Y g:i A', strtotime($raw)) : 'TBD';
                ?>
                    <div class="card">
                        <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                <div style="font-weight:700;"><?= htmlspecialchars($event['title']) ?></div>
                                <div style="color:#6b7280;font-size:.9rem;"><?= htmlspecialchars($event['location'] ?? '') ?> • <?= $dt ?></div>
                                <?php if (!empty($event['age_groups'])): ?>
                                    <div style="margin-top:.5rem;display:flex;gap:.4rem;flex-wrap:wrap;">
                                        <?php foreach (explode(',', $event['age_groups']) as $ag): $ag = trim($ag); if ($ag==='') continue; ?>
                                            <span style="background:#eef2ff;color:#3730a3;padding:.25rem .5rem;border-radius:999px;font-size:.75rem;font-weight:600;border:1px solid #c7d2fe"><?= htmlspecialchars($ag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:.9rem;">Present: <?= (int)($event['present_count'] ?? 0) ?> / <?= (int)($event['total_attendance'] ?? 0) ?></div>
                                <div style="margin-top:.5rem;"><a href="event_details.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-primary">Details</a>
                                <a href="delete_event.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-danger">Delete</a></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
</body>
</html>
