<?php
/**
 * VIVO United - Delete Team
 * Safe team deletion with player reassignment
 */

require_once 'database_factory.php';
require_once 'includes/auth.php';
require_once 'functions.php';

requireLogin();

$db = DatabaseFactory::getConnection();

// Use GET to show confirmation page; POST handles actual deletion with CSRF
$team_id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$team_id) {
    header('Location: teams.php');
    exit;
}

// Verify team exists and get details
$stmt = $db->prepare("SELECT * FROM teams WHERE id = ?");
$stmt->execute([$team_id]);
$team = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$team) {
    header('Location: teams.php');
    exit;
}

// Handle POST deletion with CSRF validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        try {
            // Get player count for feedback
            $stmt = $db->prepare("SELECT COUNT(*) FROM players WHERE team_id = ?");
            $stmt->execute([$team_id]);
            $playerCount = $stmt->fetchColumn();

            $db->beginTransaction();
            // Unassign players
            $stmt = $db->prepare("UPDATE players SET team_id = NULL, updated_at = datetime('now') WHERE team_id = ?");
            $stmt->execute([$team_id]);
            // Remove team association from events
            $stmt = $db->prepare("UPDATE events SET team_id = NULL, updated_at = datetime('now') WHERE team_id = ?");
            $stmt->execute([$team_id]);
            // Delete team
            $stmt = $db->prepare("DELETE FROM teams WHERE id = ?");
            $stmt->execute([$team_id]);
            $db->commit();

            if (function_exists('set_flash')) { set_flash("Team '{$team['name']}' has been successfully deleted. {$playerCount} player(s) have been unassigned.", 'success'); }
            header('Location: teams.php');
            exit;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = 'Failed to delete team: ' . $e->getMessage();
        }
    }
}

// Get related data for confirmation (for GET display)
$stmt = $db->prepare("SELECT COUNT(*) FROM players WHERE team_id = ?");
$stmt->execute([$team_id]);
$playerCount = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM events WHERE team_id = ?");
$stmt->execute([$team_id]);
$eventCount = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Team - VIVO United Manager</title>
    <?php 
    require_once 'includes/css_helper.php';
    vivo_include_head_css($db);
    ?>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="content-main">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <div class="page-header-content">
            <h1 class="page-title text-danger">
                <i class="fas fa-trash-alt"></i>
                Delete Team
            </h1>
            <p class="page-subtitle">Permanently remove team from the system</p>
        </div>
        <div class="page-actions">
            <a href="teams.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Teams
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h2><i class="fas fa-exclamation-triangle"></i> Confirm Team Deletion</h2>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-warning"></i> 
                        <strong>Warning:</strong> This action cannot be undone. Please review the information below carefully.
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5><i class="fas fa-shield-alt text-primary"></i> Team Information</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td><?= htmlspecialchars($team['name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Age Group:</strong></td>
                                    <td><?= htmlspecialchars($team['age_group']) ?></td>
                                </tr>
                                <?php if ($team['coach']): ?>
                                <tr>
                                    <td><strong>Coach:</strong></td>
                                    <td><?= htmlspecialchars($team['coach']) ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($team['founded_date']): ?>
                                <tr>
                                    <td><strong>Founded:</strong></td>
                                    <td><?= date('M j, Y', strtotime($team['founded_date'])) ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h5><i class="fas fa-chart-bar text-info"></i> Impact Assessment</h5>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body py-2">
                                            <h4><?= $playerCount ?></h4>
                                            <small>Players</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card bg-info text-white">
                                        <div class="card-body py-2">
                                            <h4><?= $eventCount ?></h4>
                                            <small>Events</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> What will happen:</h6>
                        <ul class="mb-0">
                            <li><strong>Players:</strong> <?= $playerCount ?> player(s) will be unassigned and available for other teams</li>
                            <li><strong>Events:</strong> <?= $eventCount ?> event(s) will remain but team association will be removed</li>
                            <li><strong>Team:</strong> All team data will be permanently deleted</li>
                        </ul>
                    </div>

                    <form method="POST" action="" onsubmit="return confirm('Are you absolutely sure you want to delete this team? This action cannot be undone!')">
                        <?php echo csrf_input_field(); ?>
                        <input type="hidden" name="id" value="<?= htmlspecialchars($team['id']) ?>">
                        <div class="mt-4">
                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="confirmCheckbox" required>
                                <label class="custom-control-label" for="confirmCheckbox">
                                    I understand that this action cannot be undone and I want to delete the team <strong>"<?= htmlspecialchars($team['name']) ?>"</strong>
                                </label>
                            </div>

                            <button type="submit" name="confirm_delete" class="btn btn-danger" id="deleteButton" disabled>
                                <i class="fas fa-trash-alt"></i> Delete Team Permanently
                            </button>
                            <a href="teams.php" class="btn btn-secondary ml-2">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-lightbulb text-warning"></i> Important Notes</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6><i class="fas fa-users text-primary"></i> Player Safety</h6>
                        <p class="small text-muted">Players will not be deleted, only unassigned from this team. You can reassign them to other teams later.</p>
                    </div>

                    <div class="mb-3">
                        <h6><i class="fas fa-calendar text-info"></i> Event Preservation</h6>
                        <p class="small text-muted">Events associated with this team will be preserved for historical records, but the team association will be removed.</p>
                    </div>

                    <div>
                        <h6><i class="fas fa-undo text-success"></i> Alternative Actions</h6>
                        <p class="small text-muted">Consider editing the team instead of deleting if you just need to update information.</p>
                        <a href="edit_team.php?id=<?= $team['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i> Edit Team Instead
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Enable delete button only when checkbox is checked
document.getElementById('confirmCheckbox').addEventListener('change', function() {
    document.getElementById('deleteButton').disabled = !this.checked;
});
</script>

<!-- Mobile Navigation JavaScript -->
<script src="js/mobile-navigation.js"></script>
</body>
</html>

