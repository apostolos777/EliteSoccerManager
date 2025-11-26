<?php
// Edit Event - clean layout following event_details.php structure
require_once 'includes/auth.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
requireLogin();

// DB connection
try { require_once __DIR__.'/wp-config.php'; $db = get_db_connection(); } catch (Throwable $e) {
    require_once 'database_factory.php';
    try { $db = DatabaseFactory::getConnection(); } catch (Throwable $_) { $db = new PDO('sqlite:'.__DIR__.'/database.db'); $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); }
}

$event_id = $_GET['id'] ?? $_POST['event_id'] ?? null;
if (!$event_id) { header('Location: events.php'); exit; }

// Load helpers/data
$availableAgeGroups = [];
if (file_exists(__DIR__.'/includes/age_groups.php')) $availableAgeGroups = require __DIR__.'/includes/age_groups.php';

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // minimal validation + update
    $title = trim($_POST['title'] ?? '');
    $date = trim($_POST['event_date'] ?? '');
    $time = trim($_POST['event_time'] ?? '');
    $age_groups = $_POST['age_groups'] ?? [];
    if (!is_array($age_groups)) $age_groups = explode(',', (string)$age_groups);
    $age_groups = array_filter(array_map('trim',$age_groups));
    $age_groups_str = implode(',', $age_groups);

    if ($title === '') $error = 'Title is required.';
    if ($date === '') $error = 'Date is required.';

    if ($error === '') {
        try {
            $sql = "UPDATE events SET title=:title, description=:description, event_type=:event_type, date=:date, time=:time, location=:location, team_id=:team_id, opponent=:opponent, max_participants=:max_participants, cost=:cost, equipment_needed=:equipment_needed, notes=:notes, is_mandatory=:is_mandatory, age_groups=:age_groups, event_date=:event_date, updated_at=datetime('now') WHERE id=:id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':title'=>$title,
                ':description'=>trim($_POST['description'] ?? '') ?: null,
                ':event_type'=>trim($_POST['event_type'] ?? '') ?: null,
                ':date'=>$date ?: null,
                ':time'=>$time ?: null,
                ':location'=>trim($_POST['location'] ?? '') ?: null,
                ':team_id'=>trim($_POST['team_id'] ?? '') ?: null,
                ':opponent'=>trim($_POST['opponent'] ?? '') ?: null,
                ':max_participants'=>trim($_POST['max_participants'] ?? '') ?: null,
                ':cost'=>trim($_POST['cost'] ?? '') ?: null,
                ':equipment_needed'=>trim($_POST['equipment_needed'] ?? '') ?: null,
                ':notes'=>trim($_POST['notes'] ?? '') ?: null,
                ':is_mandatory'=>isset($_POST['is_mandatory'])?1:0,
                ':age_groups'=>$age_groups_str ?: null,
                ':event_date'=>$date . (!empty($time)? ' '.$time : ''),
                ':id'=>$event_id
            ]);
            $_SESSION['event_update_success'] = 'Event updated.';
            header('Location: edit_event.php?id='.urlencode($event_id).'&success=1');
            exit;
        } catch (Throwable $e) {
            if (file_exists(__DIR__.'/includes/db_logger.php')) require_once __DIR__.'/includes/db_logger.php';
            if (function_exists('log_db_error')) log_db_error($e,'edit_event_post');
            $error = 'DB error: '.$e->getMessage();
        }
    }
}

// Load event and related data
try { $stmt = $db->prepare('SELECT * FROM events WHERE id = ? LIMIT 1'); $stmt->execute([$event_id]); $event = $stmt->fetch(PDO::FETCH_ASSOC); if (!$event) { header('Location: events.php'); exit; } } catch (Throwable $e) { die('Failed to load event: '.$e->getMessage()); }
try { $teams = $db->query('SELECT id,name,age_group FROM teams ORDER BY name')->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $_) { $teams = []; }
try { $att = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present FROM attendance WHERE event_id = ?"); $att->execute([$event_id]); $attendance = $att->fetch(PDO::FETCH_ASSOC); } catch (Throwable $_) { $attendance = ['total'=>0,'present'=>0]; }

$selectedAgeGroups = !empty($event['age_groups']) ? array_map('trim', explode(',',$event['age_groups'])) : [];
if (isset($_GET['success']) && isset($_SESSION['event_update_success'])) { $message = $_SESSION['event_update_success']; unset($_SESSION['event_update_success']); }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Event - <?php echo htmlspecialchars($event['title'] ?? 'Event'); ?></title>
    <?php if (file_exists('includes/css_helper.php')) { require_once 'includes/css_helper.php'; if (function_exists('vivo_include_head_css')) vivo_include_head_css(); } ?>
    <style>
        /* keep styles compact and aligned with event_details.php */
        .event-details-container { max-width:1200px; margin:0 auto; padding:18px; }
        .event-header-card { background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; border-radius:12px; padding:20px; margin-bottom:18px; }
        .event-title-main { font-size:1.6rem; font-weight:700; }
        .event-meta { display:flex; gap:12px; margin-top:8px; }
        .content-grid { display:grid; grid-template-columns:2fr 1fr; gap:18px; }
        .main-content-card, .sidebar-card { background:#fff; padding:16px; border-radius:10px; box-shadow:0 6px 18px rgba(0,0,0,0.06); }
        .section-title { font-weight:700; font-size:1.05rem; margin-bottom:12px; border-bottom:3px solid var(--primary); padding-bottom:8px; }
        .age-groups-container{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:8px}
        .age-group-checkbox{display:flex;align-items:center;gap:8px;padding:8px;border:1px solid #eee;border-radius:6px}
        .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px}
        .stat-card{background:#f9fafb;border-radius:8px;padding:12px;text-align:center;border-top:3px solid var(--primary)}
        .stat-number{font-size:1.4rem;font-weight:700;color:var(--primary)}
    </style>
</head>
<body>
<?php include 'includes/layout_start.php'; ?>
<div class="event-details-container">
    <div class="event-header-card">
        <div class="event-title-main"><?php echo htmlspecialchars($event['title'] ?? 'Event'); ?></div>
        <div class="event-meta">
            <div><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars(substr($event['event_date'] ?? $event['date'] ?? '',0,16)); ?></div>
            <div><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location'] ?? ''); ?></div>
            <div><i class="fas fa-users"></i> Team: <?php echo $event['team_id'] ? htmlspecialchars($event['team_id']) : 'All'; ?></div>
        </div>
    </div>

    <div class="content-grid">
        <main class="main-content-card">
            <div class="section-title">Edit Event</div>
            <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <form method="POST">
                <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event_id); ?>">
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">Title</label><input name="title" class="form-input" required value="<?php echo htmlspecialchars($event['title'] ?? ''); ?>"></div>
                    <div class="form-group"><label class="form-label">Type</label>
                        <select id="event_type" name="event_type" class="form-select"><option value="">Select</option>
                            <?php foreach (['Match','Training','Meeting','Tournament','Social','Other'] as $t): ?>
                                <option value="<?php echo $t ?>" <?php echo ($event['event_type'] ?? '')===$t? 'selected':''; ?>><?php echo $t ?></option>
                            <?php endforeach; ?></select>
                    </div>

                    <div class="form-group"><label class="form-label">Date</label><input type="date" name="event_date" class="form-input" value="<?php echo htmlspecialchars(substr($event['event_date'] ?? $event['date'] ?? '',0,10)); ?>" required></div>
                    <div class="form-group"><label class="form-label">Time</label><input type="time" name="event_time" class="form-input" value="<?php echo htmlspecialchars(substr($event['event_date'] ?? $event['time'] ?? '',11,5)); ?>"></div>
                    <div class="form-group"><label class="form-label">Location</label><input name="location" class="form-input" value="<?php echo htmlspecialchars($event['location'] ?? ''); ?>"></div>

                    <div class="form-group"><label class="form-label">Team</label>
                        <select id="team_id" name="team_id" class="form-select">
                            <option value="">All Teams</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>" data-age-group="<?php echo htmlspecialchars($team['age_group'] ?? ''); ?>" <?php echo ($event['team_id'] == $team['id']) ? 'selected':''; ?>><?php echo htmlspecialchars($team['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Age Groups</label>
                        <div style="display:flex;gap:8px;margin-bottom:8px;align-items:center;">
                            <select id="age_group_preset" class="form-select" style="max-width:260px;"><option value="">Preset</option><option value="youth">Youth (Under)</option><option value="all">All</option><option value="first_team">First Team</option></select>
                            <small class="form-help">Or pick a team to auto-apply its age groups</small>
                        </div>
                        <div class="age-groups-container">
                            <?php foreach ($availableAgeGroups as $ag): $id = 'ag_'.preg_replace('/[^a-z0-9_]/','_',strtolower($ag)); ?>
                                <label class="age-group-checkbox" for="<?php echo $id; ?>">
                                    <input id="<?php echo $id; ?>" type="checkbox" name="age_groups[]" value="<?php echo htmlspecialchars($ag); ?>" <?php echo in_array($ag,$selectedAgeGroups)?'checked':''; ?>>
                                    <span><?php echo htmlspecialchars($ag); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group form-group-full"><label class="form-label">Description</label><textarea name="description" class="form-textarea"><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea></div>
                    <div class="form-group"><label class="form-label">Max participants</label><input type="number" name="max_participants" class="form-input" min="1" value="<?php echo htmlspecialchars($event['max_participants'] ?? ''); ?>"></div>
                    <div class="form-group"><label class="form-label">Cost</label><input type="number" step="0.01" name="cost" class="form-input" value="<?php echo htmlspecialchars($event['cost'] ?? ''); ?>"></div>
                    <div class="form-group form-group-full"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Event</button> <a href="events.php" class="btn btn-secondary">Cancel</a></div>
                </div>
            </form>
        </main>

        <aside class="sidebar-card">
            <div class="section-title">Attendance</div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:12px;">
                <div class="stat-card"><div class="stat-number"><?php echo (int)($attendance['present'] ?? 0); ?></div><div class="stat-label">Present</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo max(0,(int)($attendance['total'] ?? 0) - (int)($attendance['present'] ?? 0)); ?></div><div class="stat-label">Absent</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo (int)($attendance['total'] ?? 0); ?></div><div class="stat-label">Registered</div></div>
            </div>
            <div style="margin-bottom:12px;"><a href="attendance.php?event_id=<?php echo urlencode($event_id); ?>" class="btn btn-primary" style="display:block;text-align:center;">Manage Attendance</a></div>
            <div class="section-title">Quick Actions</div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <a href="delete_event.php?id=<?php echo $event_id; ?>" class="btn btn-secondary" onclick="return confirm('Delete event?')">Delete Event</a>
                <a href="events.php" class="btn btn-secondary">Back to events</a>
            </div>
        </aside>
    </div>
</div>
</div>
<?php include 'includes/layout_end.php'; ?>

<script>
function setAgeGroups(list){
    document.querySelectorAll('input[name="age_groups[]"]').forEach(function(cb){ cb.checked = list.indexOf(cb.value) !== -1; });
}

document.addEventListener('DOMContentLoaded', function(){
    var preset = document.getElementById('age_group_preset');
    var team = document.getElementById('team_id');

    if (preset){
        preset.addEventListener('change', function(){
            var v = this.value;
            if (v === 'youth'){
                var youth = Array.from(document.querySelectorAll('input[name="age_groups[]"]'))
                    .map(function(i){ return i.value; })
                    .filter(function(s){ return /under|u[0-9]+/i.test(s); });
                setAgeGroups(youth);
            } else if (v === 'all'){
                setAgeGroups(Array.from(document.querySelectorAll('input[name="age_groups[]"]')).map(function(i){ return i.value; }));
            } else if (v === 'first_team'){
                setAgeGroups(['First Team']);
            } else {
                setAgeGroups([]);
            }
        });
    }

    if (team){
        team.addEventListener('change', function(){
            var opt = this.options[this.selectedIndex];
            var ag = opt ? (opt.getAttribute('data-age-group')||'') : '';
            if (!ag){ setAgeGroups([]); return; }
            var parts = ag.split(',').map(function(s){ return s.trim(); }).filter(function(s){ return s; });
            var available = Array.from(document.querySelectorAll('input[name="age_groups[]"]')).map(function(i){ return i.value; });
            var matched = [];
            parts.forEach(function(p){
                available.forEach(function(a){
                    if (a.toLowerCase() === p.toLowerCase() || a.toLowerCase().replace(/\s+/g,'') === p.toLowerCase().replace(/\s+/g,'') || a.toLowerCase().startsWith(p.toLowerCase())) matched.push(a);
                });
            });
            setAgeGroups(Array.from(new Set(matched)));
        });

        if (team.value) team.dispatchEvent(new Event('change'));
    }
});
</script>
</body>
</html>
