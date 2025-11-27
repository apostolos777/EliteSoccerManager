<?php
require_once 'includes/color_system.php';
require_once 'includes/auth.php';
require_once 'database_factory.php';

requireLogin();
$currentPage = 'coaches';

// Use the application's DatabaseFactory so all pages share the same SQLite file
$db = DatabaseFactory::getConnection();

// Clear color cache to ensure fresh data
if (class_exists('VIVOColorSystem')) {
    VIVOColorSystem::clearCache();
}

// Flash support
$flashMsg = null;
if (!function_exists('get_flash')) { require_once 'functions.php'; }
if (function_exists('get_flash')) { $flashMsg = get_flash(); }

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $db->prepare("DELETE FROM coaches WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        header('Location: coaches.php?message=Coach deleted successfully');
        exit();
    } catch (Exception $e) {
        $error = "Error deleting coach: " . $e->getMessage();
    }
}

// Get all coaches
try {
    $stmt = $db->prepare("
        SELECT c.*,
               COUNT(tc.team_id) as team_count
        FROM coaches c
        LEFT JOIN team_coaches tc ON c.id = tc.coach_id
        GROUP BY c.id
        ORDER BY c.name
    ");
    $stmt->execute();
    $coaches = $stmt->fetchAll();
} catch (Exception $e) {
    $coaches = [];
    $error = "Error loading coaches: " . $e->getMessage();
}

$message = $_GET['message'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coach Management - VIVO United Manager</title>
    <?php
    // Include dynamic CSS system
    require_once 'includes/css_helper.php';
    vivo_include_head_css($db);
    ?>
    <!-- Cache busting timestamp: <?php echo date('Y-m-d H:i:s'); ?> -->
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
    <?php if ($flashMsg && is_array($flashMsg) && isset($flashMsg['name'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                try {
                    var name = <?= json_encode($flashMsg['name']) ?>;
                    var content = '<div style="padding:1rem;font-size:1rem;">' +
                                  '<p style="margin:0 0 .5rem;">Coach added: <strong>' + name + '</strong></p>' +
                                  '<div style="text-align:right;margin-top:.75rem;"><button onclick="closeInlineModal()" style="background:var(--primary);color:#fff;border:none;padding:.5rem .75rem;border-radius:6px;">OK</button></div>' +
                                  '</div>';
                    showInlineModal(content, 'Coach Created');
                } catch(e) { console.warn(e); }
            });
        </script>
    <?php endif; ?>
<div class="content-main">
    <div class="page-header">
        <div class="page-header-content">
            <h1 class="page-title">
                <i class="fas fa-users-cog"></i>
                Coach Management
            </h1>
            <p class="page-subtitle">Manage coaches, staff, and volunteers</p>

            <div class="page-actions">
                <a href="add_coach.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i>
                    Add New Coach
                </a>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($coaches)): ?>
            <div class="empty-state">
                <i class="fas fa-users-cog fa-3x" style="color: var(--text-secondary); margin-bottom: 1rem;"></i>
                <h3>No Coaches Yet</h3>
                <p>Get started by adding your first coach or staff member</p>
                <a href="add_coach.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus"></i> Add First Coach
                </a>
            </div>
        <?php else: ?>
            <div class="coaches-grid">
                <?php foreach ($coaches as $coach): ?>
                    <div class="team-card coach-item" onclick="window.location='edit_coach.php?id=<?php echo $coach['id']; ?>&view=profile'">
                                <div class="tc-header coach-card-header">
                            <?php if ($coach['photo_url']): ?>
                                <img src="<?php echo htmlspecialchars($coach['photo_url']); ?>" alt="<?php echo htmlspecialchars($coach['name']); ?>" class="coach-photo">
                            <?php else: ?>
                                <?php
                                // Use Font Awesome icons as placeholders with variety based on coach ID
                                $iconVariants = [
                                    'fas fa-user-tie',
                                    'fas fa-user-graduate',
                                    'fas fa-user-cog',
                                    'fas fa-chalkboard-teacher',
                                    'fas fa-user-friends'
                                ];
                                $iconIndex = $coach['id'] % count($iconVariants);
                                $selectedIcon = $iconVariants[$iconIndex];
                                ?>
                                <div class="coach-photo coach-icon-placeholder">
                                    <i class="<?php echo $selectedIcon; ?>"></i>
                                </div>
                            <?php endif; ?>
                            <div class="tc-head-text coach-info">
                                <h3 class="tc-name">
                                    <?php echo htmlspecialchars($coach['name']); ?>
                                </h3>
                                <span class="coach-role tc-created"><?php echo htmlspecialchars($coach['role']); ?></span>
                            </div>
                        </div>

                        <div class="tc-body coach-card-body">
                            <?php if ($coach['email']): ?>
                                <div class="coach-detail">
                                    <i class="fas fa-envelope"></i>
                                    <a href="mailto:<?php echo htmlspecialchars($coach['email']); ?>"><?php echo htmlspecialchars($coach['email']); ?></a>
                                </div>
                            <?php endif; ?>

                            <?php if ($coach['phone']): ?>
                                <div class="coach-detail">
                                    <i class="fas fa-phone"></i>
                                    <a href="tel:<?php echo htmlspecialchars($coach['phone']); ?>"><?php echo htmlspecialchars($coach['phone']); ?></a>
                                </div>
                            <?php endif; ?>

                            <div class="coach-detail">
                                <i class="fas fa-users"></i>
                                <?php echo $coach['team_count']; ?> team<?php echo $coach['team_count'] !== 1 ? 's' : ''; ?>
                            </div>
                        </div>

                        <div class="tc-actions coach-card-actions">
                            <a href="edit_coach.php?id=<?php echo $coach['id']; ?>&view=profile" class="btn btn-primary btn-sm" onclick="event.stopPropagation();">
                                <i class="fas fa-user"></i> View Profile
                            </a>
                            <a href="edit_coach.php?id=<?php echo $coach['id']; ?>" class="btn btn-secondary btn-sm" onclick="event.stopPropagation();">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="coaches.php?action=delete&id=<?php echo $coach['id']; ?>" class="btn btn-danger btn-sm" onclick="event.stopPropagation(); return confirm('Are you sure you want to delete this coach?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
        <?php echo VIVOColorSystem::generateDynamicCSS($db); ?>
        
.coaches-grid {
    display: grid;
    /* 4 columns on desktop, 2 on mobile */
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    margin-top: 1.5rem;
}

@media (max-width: 768px) {
    .coaches-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

.team-card, .coach-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    transition: all var(--transition-fast);
    border: 1px solid var(--border-color);
}

.team-card:hover, .coach-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.coach-card-header {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-dark) 100%);
    color: var(--white);
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* team-card (shared card style with teams.php) */
.team-card {
    background:#fff;
    border:1px solid var(--border-color);
    border-radius:12px;
    display:flex;
    flex-direction:column;
    position:relative;
    overflow:hidden;
    transition:transform .18s ease, box-shadow .18s ease;
}
.team-card .tc-header { display:flex; align-items:center; gap:.7rem; padding:.9rem .9rem .7rem; }
.team-card .tc-icon { width:56px; height:56px; background:#eef0f3; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#374151; font-size:1.2rem; }
.team-card .tc-body { padding:0 .9rem .9rem; flex:1; display:flex; flex-direction:column; }
.team-card .tc-name { font-size:1rem; font-weight:700; margin:0 0 .1rem; }
.team-card .tc-created { font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-bottom:.5rem; }
.team-card .tc-description { font-size:.9rem; color:#4b5563; margin:0 0 .6rem; }
.team-card .tc-meta { display:grid; grid-template-columns:repeat(3,1fr); gap:.5rem; margin-bottom:.6rem; }
.team-card .tc-stat { background:#f3f4f6; border-radius:6px; padding:.5rem; text-align:center; }
.team-card .tc-actions { display:flex; gap:.4rem; margin-top:auto; }

@media (max-width:520px) {
    .coaches-grid { grid-template-columns: 1fr !important; }
}

.coach-photo {
    width: 100%;
    height: 110px;
    object-fit: cover;
    display:block;
    border-bottom: 1px solid rgba(0,0,0,0.04);
}

.coach-icon-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary-color, #2563eb) 0%, var(--secondary-color, #1d4ed8) 100%);
    color: white;
    font-size: 1.8rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.coach-icon-placeholder i {
    display: block;
}

.coach-info h3 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
    color: #000000;
}

.coach-name-link {
    color: inherit;
    text-decoration: none;
    transition: all var(--transition-fast);
}

.coach-name-link:hover {
    color: var(--white);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.coach-role {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-sm);
    font-size: 0.85rem;
    font-weight: 500;
    text-transform: capitalize;
    color: #000000;
}

.coach-card-body {
    padding: 1rem;
}

.coach-detail {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    color: var(--text-secondary);
}

.coach-detail:last-child {
    margin-bottom: 0;
}

.coach-detail i {
    width: 16px;
    color: var(--primary-green);
}

.coach-detail a {
    color: var(--primary-green);
    text-decoration: none;
}

.coach-detail a:hover {
    text-decoration: underline;
}

.coach-card-actions {
    padding: 0.75rem 1rem;
    background: var(--gray-50);
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: var(--space-3);
}

.empty-state {
    text-align: center;
    padding: var(--space-12) var(--space-6);
    color: var(--text-secondary);
}

.empty-state h3 {
    margin: var(--space-4) 0;
    color: var(--text-primary);
}

.empty-state p {
    margin-bottom: var(--space-6);
    font-size: 1.1rem;
}
/* btn-lg now uses global styles from vivo-style.css */
</style>

</body>
</html>
