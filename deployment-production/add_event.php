<?php
// VIVO United - Add Event
session_start();

// Auto login for development
if (!isset($_SESSION['user_logged_in'])) {
    $_SESSION['user_logged_in'] = true;
    $_SESSION['username'] = 'admin';
    $_SESSION['user_role'] = 'admin';
}

// Include color system
require_once 'includes/color_system.php';

// Use the project's DatabaseFactory if available so events land in the correct DB
require_once 'database_factory.php';
function set_flash($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

try {
    $db = DatabaseFactory::getConnection();
} catch (Throwable $e) {
    // fallback to local file DB (legacy)
    $db_path = __DIR__ . '/database.db';
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
$error = '';

// Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $event_type = strtolower(trim($_POST['event_type'] ?? ''));
    // Accept both legacy 'date'/'time' and unified 'event_date'/'event_time' form names
    $date = trim($_POST['date'] ?? ($_POST['event_date'] ?? ''));
    $time = trim($_POST['time'] ?? ($_POST['event_time'] ?? ''));
    $location = trim($_POST['location'] ?? '');
        $team_id = ($_POST['team_id'] ?? '') !== '' ? (int)$_POST['team_id'] : null;
    // Handle multiple age groups (allow selecting which age groups participate)
    $age_groups = $_POST['age_groups'] ?? [];
    if (is_array($age_groups)) {
        $age_groups_str = implode(',', $age_groups);
    } else {
        $age_groups_str = $age_groups;
    }
    $opponent = trim($_POST['opponent'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = 'scheduled';
    $is_home_game = 1;

    if ($title === '' || $event_type === '' || $date === '') {
        $error = 'Title, Type and Date are required.';
    } elseif (empty($age_groups_str)) {
        // Require explicit age group selection so 'General' doesn't silently include everyone
        $error = 'Please select at least one age group for this event.';
    } else {
        try {
            // Save age_groups as a comma-separated string
            // Also set unified event_date column for compatibility with other pages
            $event_datetime = $date;
            if (!empty($time)) { $event_datetime = $date . ' ' . $time; }

            $stmt = $db->prepare("INSERT INTO events (title, description, event_type, date, time, location, team_id, opponent, is_home_game, status, age_groups, event_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $result = $stmt->execute([$title, $description, $event_type, $date, ($time ?: null), $location, $team_id, $opponent, $is_home_game, $status, $age_groups_str, $event_datetime]);

            if ($result) {
                $newId = $db->lastInsertId();
                // If this is an XHR/Fetch request, return JSON
                $isXhr = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
                if ($isXhr) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'id' => $newId]);
                    exit;
                }

                // Include the created event title in the flash so the events list can show a confirmation modal
                if (function_exists('set_flash')) {
                    set_flash('Event created successfully!', 'success');
                    $_SESSION['flash_created_name'] = $title;
                }
                header('Location: events.php');
                exit;
            } else {
                $error = 'Database insert failed';
            }
        } catch (Exception $e) {
            require_once __DIR__ . '/includes/db_logger.php';
            log_db_error($e, 'add_event');
            $error = 'Save failed: ' . $e->getMessage();
            // If XHR, return JSON error
            $isXhr = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
            if ($isXhr) {
                header('Content-Type: application/json', true, 500);
                echo json_encode(['success' => false, 'message' => $error]);
                exit;
            }
        }
    }
}

// Get teams for dropdown
$teams = [];
try {
    $teams = $db->query("SELECT id, name FROM teams ORDER BY name")->fetchAll();
} catch (Throwable $e) {
    // Ignore if teams table doesn't exist
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event - VIVO United</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        <?php echo VIVOColorSystem::generateDynamicCSS(); ?>
        
        * { box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f8fafc; 
            margin: 0; 
            padding: 1rem;
            color: #1f2937;
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
        }
        .back-btn { 
            display: inline-flex; 
            align-items: center; 
            gap: 0.5rem; 
            color: var(--primary); 
            text-decoration: none; 
            font-weight: 500;
            margin-bottom: 1.5rem;
            padding: 0.5rem 1rem;
            border: 2px solid var(--primary);
            border-radius: 8px;
            transition: all 0.2s;
        }
        .back-btn:hover { 
            background: var(--primary);
            color: white;
        }
        .form-card { 
            background: #fff; 
            padding: 2rem; 
            border-radius: 12px; 
            box-shadow: 0 4px 16px rgba(0,0,0,0.1); 
        }
        .page-header { 
            margin-bottom: 2rem; 
            text-align: center;
        }
        .page-title { 
            font-size: 2rem; 
            font-weight: 700; 
            color: #1f2937; 
            margin: 0 0 0.5rem; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            gap: 0.75rem; 
        }
        .page-title i {
            color: var(--primary);
        }
        .page-sub { 
            color: #6b7280; 
            margin: 0; 
            font-size: 1rem;
        }
        .form-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 1.5rem; 
        }
        .fg-full { 
            grid-column: 1 / -1; 
        }
        .fg label { 
            display: block; 
            font-weight: 600; 
            color: #374151; 
            margin-bottom: 0.5rem; 
            font-size: 0.875rem; 
        }
        .fg input, .fg select, .fg textarea { 
            width: 100%; 
            padding: 0.75rem; 
            border: 2px solid #e5e7eb; 
            border-radius: 8px; 
            font-size: 0.875rem; 
            background: #fff; 
            transition: border-color 0.2s;
        }
        .fg input:focus, .fg select:focus, .fg textarea:focus { 
            outline: none; 
            border-color: var(--primary); 
        }
        textarea { 
            resize: vertical; 
            min-height: 120px; 
            font-family: inherit;
        }
        .actions { 
            display: flex; 
            gap: 1rem; 
            justify-content: center; 
            margin-top: 2rem; 
        }
        .btn { 
            padding: 0.75rem 2rem; 
            border: none; 
            border-radius: 8px; 
            font-weight: 600; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.5rem; 
            cursor: pointer; 
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        .btn-primary { 
            background: var(--primary); 
            color: white; 
        }
        .btn-primary:hover { 
            background: var(--secondary); 
            transform: translateY(-1px);
        }
        .btn-secondary { 
            background: #6b7280; 
            color: white; 
        }
        .btn-secondary:hover { 
            background: #4b5563; 
        }
        .alert { 
            padding: 1rem; 
            border-radius: 8px; 
            margin-bottom: 1.5rem; 
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-error { 
            background: #fef2f2; 
            color: #b91c1c; 
            border: 1px solid #fecaca; 
        }
        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="events.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> 
            Back to Events
        </a>
        <div class="form-card">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-calendar-plus"></i>
                    Add New Event
                </h1>
                <p class="page-sub">Create a new training session, match, or club event</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-grid">
                    <div class="fg fg-full">
                        <label for="title">Event Title *</label>
                        <input type="text" id="title" name="title" required 
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                               placeholder="e.g., U12 Training Session">
                    </div>

                    <div class="fg">
                        <label for="event_type">Event Type *</label>
                        <select id="event_type" name="event_type" required>
                            <option value="">Select Type</option>
                            <?php 
                            $types = ['training', 'match', 'meeting', 'tournament', 'camp', 'other'];
                            $selected_type = $_POST['event_type'] ?? '';
                            foreach ($types as $type): 
                            ?>
                                <option value="<?php echo $type; ?>" <?php echo $selected_type === $type ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="fg">
                        <label for="date">Date *</label>
                        <input type="date" id="date" name="date" required 
                               value="<?php echo htmlspecialchars($_POST['date'] ?? ''); ?>">
                    </div>

                    <div class="fg">
                        <label for="time">Time</label>
                        <input type="time" id="time" name="time" 
                               value="<?php echo htmlspecialchars($_POST['time'] ?? ''); ?>">
                    </div>

                    <div class="fg">
                        <label for="team_id">Team</label>
                        <select id="team_id" name="team_id">
                            <option value="">All Teams / General</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>" 
                                        <?php echo ($_POST['team_id'] ?? '') == $team['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($team['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="fg">
                        <label>Age Groups *</label>
                        <div class="age-groups-controls" style="margin-bottom:.5rem;">
                            <button type="button" id="selectAllAges" class="btn-control">Select All</button>
                            <button type="button" id="clearAllAges" class="btn-control">Clear All</button>
                            <button type="button" id="selectYouthAges" class="btn-control">Youth Only (U6-U18)</button>
                        </div>
                        <div class="age-groups-container" style="margin-bottom:.5rem; padding:12px;">
                                <?php 
                                $availableAgeGroups = require_once __DIR__ . '/includes/age_groups.php';
                                $selectedAgeGroups = !empty($_POST['age_groups']) ? (is_array($_POST['age_groups']) ? $_POST['age_groups'] : explode(',', $_POST['age_groups'])) : [];
                                foreach ($availableAgeGroups as $ageGroup):
                                ?>
                                <div class="age-group-checkbox" style="display:inline-flex;align-items:center;gap:.5rem;margin:.25rem;padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:6px;background:#fff;">
                                    <input type="checkbox" 
                                           id="age_group_<?php echo str_replace(' ', '_', strtolower($ageGroup)); ?>" 
                                           name="age_groups[]" 
                                           value="<?php echo $ageGroup; ?>"
                                           <?php echo in_array($ageGroup, $selectedAgeGroups) ? 'checked' : ''; ?> />
                                    <label for="age_group_<?php echo str_replace(' ', '_', strtolower($ageGroup)); ?>"><?php echo $ageGroup; ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="form-help">Select all age groups that can participate in this event</small>
                    </div>

                    <div class="fg">
                        <label for="opponent">Opponent</label>
                        <input type="text" id="opponent" name="opponent" 
                               value="<?php echo htmlspecialchars($_POST['opponent'] ?? ''); ?>" 
                               placeholder="For matches only">
                    </div>

                    <div class="fg fg-full">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" 
                               value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" 
                               placeholder="Ground, facility, or venue">
                    </div>

                    <div class="fg fg-full">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" 
                                  placeholder="Event details, objectives, or special instructions..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="actions">
                    <a href="events.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Event
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Set minimum date to today and auto-focus
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('date');
            dateInput.min = new Date().toISOString().split('T')[0];
            document.getElementById('title').focus();
        });
        // Age group controls
        document.addEventListener('DOMContentLoaded', function(){
            const selectAllBtn = document.getElementById('selectAllAges');
            const clearAllBtn = document.getElementById('clearAllAges');
            const youthBtn = document.getElementById('selectYouthAges');
            const checkboxes = Array.from(document.querySelectorAll('input[name="age_groups[]"]'));

            if(selectAllBtn){ selectAllBtn.addEventListener('click', function(){ checkboxes.forEach(c=>c.checked=true); }); }
            if(clearAllBtn){ clearAllBtn.addEventListener('click', function(){ checkboxes.forEach(c=>c.checked=false); }); }
            if(youthBtn){ youthBtn.addEventListener('click', function(){
                checkboxes.forEach(function(cb){
                    const val = cb.value.toLowerCase();
                    cb.checked = /under\s?\d+/i.test(val);
                });
            }); }
        });
    </script>
</body>
</html>
