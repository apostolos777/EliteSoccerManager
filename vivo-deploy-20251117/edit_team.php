<?php
require_once 'includes/color_system.php';
require_once 'includes/auth.php';

requireLogin();
$currentPage = 'teams';

// Database connection
try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get team ID from URL
$team_id = $_GET['id'] ?? null;
if (!$team_id) {
    header('Location: teams.php');
    exit;
}

$message = '';
$error = '';

// Fetch current team data
$stmt = $db->prepare("SELECT * FROM teams WHERE id = ?");
$stmt->execute([$team_id]);
$team = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$team) {
    header('Location: teams.php');
    exit;
}

// Get currently assigned coaches for this team
$stmt = $db->prepare("
    SELECT c.id, c.name, c.role, c.email, c.phone
    FROM coaches c
    JOIN team_coaches tc ON c.id = tc.coach_id
    WHERE tc.team_id = ?
    ORDER BY c.name
");
$stmt->execute([$team_id]);
$assigned_coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine current head coach and assistant coach IDs
$current_head_coach_id = null;
$current_assistant_coach_id = null;

foreach ($assigned_coaches as $coach) {
    if ($coach['role'] === 'Head Coach') {
        $current_head_coach_id = $coach['id'];
    } elseif ($coach['role'] === 'Assistant Coach') {
        $current_assistant_coach_id = $coach['id'];
    }
}

// Get all available coaches (not assigned to this team)
$stmt = $db->prepare("
    SELECT c.id, c.name, c.role, c.email, c.phone
    FROM coaches c
    WHERE c.id NOT IN (
        SELECT coach_id FROM team_coaches WHERE team_id = ?
    )
    ORDER BY c.name
");
$stmt->execute([$team_id]);
$available_coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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

        // Collect form data - only use existing database columns
        $updateData = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'coach_name' => $head_coach_name, // Use coach name from dropdown
            'assistant_coach' => $assistant_coach_name, // Use assistant coach name from dropdown
            'age_group' => trim($_POST['age_group'] ?? '')
        ];

        // Only include photo_url if provided
        if (isset($_POST['photo_url']) && !empty(trim($_POST['photo_url']))) {
            $updateData['photo_url'] = trim($_POST['photo_url']);
        }

        // Validate required fields
        if (empty($updateData['name']) || empty($updateData['age_group'])) {
            throw new Exception("Team name and age group are required.");
        }

        // Validate email format if provided
        if (!empty($updateData['contact_email']) && !filter_var($updateData['contact_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }

        // Use proper prepared statements for safety
        $db = get_db_connection();

        // Add updated timestamp
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        // Build update query with placeholders
        $set_clauses = [];
        $params = [];
        foreach ($updateData as $field => $value) {
            $set_clauses[] = "$field = ?";
            $params[] = empty($value) ? null : $value;
        }
        $params[] = $team_id; // For WHERE clause

        $query = "UPDATE teams SET " . implode(', ', $set_clauses) . " WHERE id = ?";
        $stmt = $db->prepare($query);
        $result = $stmt->execute($params);

        if ($result) {
            $affected_rows = $stmt->rowCount();
            if ($affected_rows > 0) {
                $message = "Team information updated successfully!";
            } else {
                $message = "No changes were made to the team information.";
            }

            // Handle coach assignments - remove all existing and add new ones
            $db->prepare("DELETE FROM team_coaches WHERE team_id = ?")->execute([$team_id]);

            if (!empty($_POST['head_coach_id'])) {
                $coach_stmt = $db->prepare("INSERT INTO team_coaches (team_id, coach_id) VALUES (?, ?)");
                $coach_stmt->execute([$team_id, $_POST['head_coach_id']]);
            }

            if (!empty($_POST['assistant_coach_id'])) {
                $coach_stmt = $db->prepare("INSERT INTO team_coaches (team_id, coach_id) VALUES (?, ?)");
                $coach_stmt->execute([$team_id, $_POST['assistant_coach_id']]);
            }

            // Refresh team data
            $stmt = $db->prepare("SELECT * FROM teams WHERE id = ?");
            $stmt->execute([$team_id]);
            $team = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Refresh coach data
            $stmt = $db->prepare("
                SELECT c.id, c.name, c.role, c.email, c.phone
                FROM coaches c
                JOIN team_coaches tc ON c.id = tc.coach_id
                WHERE tc.team_id = ?
                ORDER BY c.name
            ");
            $stmt->execute([$team_id]);
            $assigned_coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "Failed to update team information. Please try again.";
        }

    } catch (Exception $e) {
        $error = "Error updating team: " . $e->getMessage();
        // Log the error for debugging
        error_log("Team update error for team ID $team_id: " . $e->getMessage());
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        error_log("Team update PDO error for team ID $team_id: " . $e->getMessage());
    }
}

// Age groups
$ageGroups = require_once __DIR__ . '/includes/age_groups.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Team - <?php echo htmlspecialchars($team['name']); ?> - VIVO United Manager</title>
    
    <?php 
    // Include dynamic CSS system
    require_once 'includes/css_helper.php';
    vivo_include_head_css($db);
    ?>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="content-main">
    <div class="page-header">
        <div class="page-header-content">
            <h1 class="page-title">
                <i class="fas fa-edit"></i> 
                Edit Team - <?php echo htmlspecialchars($team['name']); ?>
            </h1>
            <p class="page-subtitle">Update team information and details</p>
        </div>
        <div class="page-actions">
            <a href="team_details.php?id=<?= $team['id'] ?>" class="btn btn-secondary">
                <i class="fas fa-eye"></i> View Team
            </a>
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

    <!-- Edit Form -->
    <form method="POST" class="form-container">
        <!-- Basic Information -->
        <div class="card mb-3">
            <div class="card-header">
                <h2><i class="fas fa-shield-alt"></i> Team Information</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Team Name *</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?= htmlspecialchars($team['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="age_group" class="form-label">Age Group *</label>
                        <select id="age_group" name="age_group" class="form-control" required>
                            <option value="">Select Age Group</option>
                            <?php foreach ($ageGroups as $age): ?>
                                <option value="<?= $age ?>" <?= ($team['age_group'] ?? '') === $age ? 'selected' : '' ?>>
                                    <?= $age ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"><?= htmlspecialchars($team['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coaching Staff -->
        <div class="card mb-3">
            <div class="card-header">
                <h2><i class="fas fa-users"></i> Coaching Staff</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="head_coach_id" class="form-label">Head Coach</label>
                        <select id="head_coach_id" name="head_coach_id" class="form-control">
                            <option value="">Select Head Coach</option>
                            <?php
                            // Combine assigned and available coaches for the dropdown
                            $all_coaches = array_merge($assigned_coaches, $available_coaches);
                            // Remove duplicates based on ID
                            $unique_coaches = [];
                            foreach ($all_coaches as $coach) {
                                $unique_coaches[$coach['id']] = $coach;
                            }
                            foreach ($unique_coaches as $coach): ?>
                                <option value="<?= $coach['id'] ?>" <?= $current_head_coach_id == $coach['id'] ? 'selected' : '' ?>>
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
                            <?php foreach ($unique_coaches as $coach): ?>
                                <option value="<?= $coach['id'] ?>" <?= $current_assistant_coach_id == $coach['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($coach['name']) ?>
                                    <?php if (!empty($coach['role'])): ?>
                                        (<?= ucwords(str_replace('_', ' ', $coach['role'])) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Currently Assigned Coaches Display -->
                <?php if (!empty($assigned_coaches)): ?>
                <div class="mb-3">
                    <label class="form-label">Currently Assigned Coaches</label>
                    <div class="border rounded p-3 bg-light">
                        <div class="row">
                            <?php foreach ($assigned_coaches as $coach): ?>
                            <div class="col-md-6 col-lg-4 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-check text-success mr-2"></i>
                                    <div>
                                        <strong><?= htmlspecialchars($coach['name']) ?></strong>
                                        <?php if ($coach['role'] === 'Head Coach'): ?>
                                            <span class="badge badge-primary ml-1">Head Coach</span>
                                        <?php elseif ($coach['role'] === 'Assistant Coach'): ?>
                                            <span class="badge badge-info ml-1">Assistant Coach</span>
                                        <?php endif; ?>
                                        <?php if (!empty($coach['role'])): ?>
                                            <br><small class="text-muted">
                                                <i class="fas fa-tag"></i> <?= ucwords(str_replace('_', ' ', $coach['role'])) ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if (!empty($coach['email'])): ?>
                                            <br><small class="text-muted">
                                                <i class="fas fa-envelope"></i> <?= htmlspecialchars($coach['email']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (empty($unique_coaches)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>No coaches available</strong><br>
                    No coaches have been added to the system yet. You can still add custom coach names above, or add coaches through the Coaches page.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Note: Contact information and assistant coach fields removed as columns don't exist in database -->

        <!-- Form Actions -->
        <div class="text-center">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Team
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
