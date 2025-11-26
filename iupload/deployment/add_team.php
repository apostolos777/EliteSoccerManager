<?php
require_once 'includes/color_system.php';
require_once 'database_factory.php';
require_once 'includes/auth.php';

requireLogin();
$currentPage = 'teams';

$db = DatabaseFactory::getConnection();
$message = '';
$error = '';

// Get all available coaches (use PDO via DatabaseFactory)
try {
    $coachStmt = $db->query("SELECT id, name, role, email, phone FROM coaches ORDER BY name");
    $available_coaches = $coachStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $available_coaches = [];
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['name']) || empty($_POST['age_group'])) {
            throw new Exception("Team name and age group are required.");
        }

        // Get coach names from IDs if selected
        $head_coach_name = '';
        $assistant_coach_name = '';

        if (!empty($_POST['head_coach_id'])) {
            $coach_query = $db->prepare("SELECT name FROM coaches WHERE id = ?");
            $coach_query->execute([$_POST['head_coach_id']]);
            $head_coach = $coach_query->fetch(PDO::FETCH_ASSOC);
            $head_coach_name = $head_coach ? $head_coach['name'] : '';
        }

        if (!empty($_POST['assistant_coach_id'])) {
            $coach_query = $db->prepare("SELECT name FROM coaches WHERE id = ?");
            $coach_query->execute([$_POST['assistant_coach_id']]);
            $assistant_coach = $coach_query->fetch(PDO::FETCH_ASSOC);
            $assistant_coach_name = $assistant_coach ? $assistant_coach['name'] : '';
        }

        // Determine existing columns in the teams table so we only insert columns that actually exist
        $existingCols = [];
        try {
            $colStmt = $db->query("PRAGMA table_info(teams)");
            $cols = $colStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $c) { $existingCols[] = $c['name']; }
        } catch (Exception $e) {
            // If PRAGMA fails, fall back to best-effort minimal insert
            $existingCols = ['name','description','coach_name','age_group','photo_url'];
        }

        // Map potential input fields to table columns and values
        $fieldMap = [
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? '',
            'coach_name' => $head_coach_name,
            'assistant_coach' => $assistant_coach_name,
            'age_group' => $_POST['age_group'],
            'contact_email' => $_POST['contact_email'] ?? null,
            'contact_phone' => $_POST['contact_phone'] ?? null,
            'photo_url' => $_POST['photo_url'] ?? null,
        ];

        $insertCols = [];
        $placeholders = [];
        $values = [];
        foreach ($fieldMap as $col => $val) {
            if (in_array($col, $existingCols)) {
                $insertCols[] = $col;
                $placeholders[] = '?';
                $values[] = $val;
            }
        }

        if (empty($insertCols)) {
            throw new Exception('No valid columns to insert for teams table.');
        }

        $sql = "INSERT INTO teams (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute($values);

        $team_id = $db->lastInsertId();

        // Handle coach assignments to team_coaches table
        if (!empty($_POST['head_coach_id'])) {
            $coach_stmt = $db->prepare("INSERT OR IGNORE INTO team_coaches (team_id, coach_id, role_in_team, is_primary) VALUES (?, ?, ?, ?)");
            $coach_stmt->execute([$team_id, $_POST['head_coach_id'], 'head_coach', 1]);
        }

        if (!empty($_POST['assistant_coach_id'])) {
            $coach_stmt = $db->prepare("INSERT OR IGNORE INTO team_coaches (team_id, coach_id, role_in_team, is_primary) VALUES (?, ?, ?, ?)");
            $coach_stmt->execute([$team_id, $_POST['assistant_coach_id'], 'assistant_coach', 0]);
        }

        $message = "Team '" . htmlspecialchars($_POST['name']) . "' added successfully!";

        // Clear form data after successful submission
        $_POST = array();

    } catch (Exception $e) {
        $error = "Error adding team: " . $e->getMessage();
    }
}

// Age groups (centralized)
$ageGroups = require_once __DIR__ . '/includes/age_groups.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Team - VIVO United Manager</title>
    
    <?php 
    // Include dynamic CSS system and base styles (pass DB so colors read from the same DB)
    require_once 'includes/css_helper.php';
    vivo_include_head_css($db);
    ?>
    <!-- Cache busting timestamp: <?php echo date('Y-m-d H:i:s'); ?> -->
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="content-main">
    <div class="page-header">
        <div class="page-header-content">
            <h1 class="page-title">
                <i class="fas fa-plus-circle"></i>
                Add New Team
            </h1>
            <p class="page-subtitle">Create a new team squad with complete details</p>
        </div>
        <div class="page-actions">
            <a href="teams.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Teams
            </a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Add Form -->
    <form method="POST" class="form-container">
        <!-- Basic Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h2><i class="fas fa-shield-alt"></i> Team Information</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Team Name *</label>
                        <input type="text" id="name" name="name" class="form-control"
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="age_group" class="form-label">Age Group *</label>
                        <select id="age_group" name="age_group" class="form-control" required>
                            <option value="">Select Age Group</option>
                            <?php foreach ($ageGroups as $age): ?>
                                <option value="<?= $age ?>" <?= ($_POST['age_group'] ?? '') === $age ? 'selected' : '' ?>>
                                    <?= $age ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coaching Staff -->
        <div class="card mb-4">
            <div class="card-header">
                <h2><i class="fas fa-users"></i> Coaching Staff</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="head_coach_id" class="form-label">Head Coach</label>
                        <select id="head_coach_id" name="head_coach_id" class="form-control">
                            <option value="">Select Head Coach</option>
                            <?php foreach ($available_coaches as $coach): ?>
                                <option value="<?= $coach['id'] ?>" <?= ($_POST['head_coach_id'] ?? '') == $coach['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($coach['name']) ?>
                                    <?php if (!empty($coach['role'])): ?>
                                        (<?= ucwords(str_replace('_', ' ', $coach['role'])) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="assistant_coach_id" class="form-label">Assistant Coach</label>
                        <select id="assistant_coach_id" name="assistant_coach_id" class="form-control">
                            <option value="">Select Assistant Coach</option>
                            <?php foreach ($available_coaches as $coach): ?>
                                <option value="<?= $coach['id'] ?>" <?= ($_POST['assistant_coach_id'] ?? '') == $coach['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($coach['name']) ?>
                                    <?php if (!empty($coach['role'])): ?>
                                        (<?= ucwords(str_replace('_', ' ', $coach['role'])) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php if (empty($available_coaches)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>No coaches available</strong><br>
                    No coaches have been added to the system yet. You can still add custom coach names above, or add coaches through the Coaches page.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h2><i class="fas fa-address-book"></i> Contact Information</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="contact_email" class="form-label">Contact Email</label>
                        <input type="email" id="contact_email" name="contact_email" class="form-control"
                               value="<?= htmlspecialchars($_POST['contact_email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="contact_phone" class="form-label">Contact Phone</label>
                        <input type="tel" id="contact_phone" name="contact_phone" class="form-control"
                               value="<?= htmlspecialchars($_POST['contact_phone'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="text-center">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Add Team
            </button>
            <a href="teams.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>

</div>

<!-- Mobile Navigation JavaScript -->
<script src="js/mobile-navigation.js"></script>
</body>
</html>