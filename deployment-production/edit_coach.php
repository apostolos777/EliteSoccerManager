<?php
require_once 'includes/color_system.php';
require_once 'includes/auth.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$currentPage = 'coaches';

// Database connection - use same as main app
try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) { 
    die("Database connection failed: " . $e->getMessage()); 
}

// Load club settings for dynamic colors
$clubSettings = [];
try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM club_settings");
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['setting_key'])) {
                $clubSettings[$row['setting_key']] = $row['setting_value'] ?? '';
            }
        }
    }
} catch (Exception $e) {
    error_log("Edit Coach - Failed to load club settings: " . $e->getMessage());
}

$message = '';
$error = '';

// Get coach ID from URL
$coachId = $_GET['id'] ?? null;
$viewMode = $_GET['view'] ?? 'edit'; // 'edit' or 'profile'
$isProfileView = ($viewMode === 'profile');

if (!$coachId) {
    header('Location: coaches.php?message=Coach ID not provided');
    exit();
}

// Get coach data
try {
    $stmt = $db->prepare("SELECT * FROM coaches WHERE id = ?");
    $stmt->execute([$coachId]);
    $coach = $stmt->fetch();

    if (!$coach) {
        header('Location: coaches.php?message=Coach not found');
        exit();
    }
} catch (Exception $e) {
    header('Location: coaches.php?message=Error loading coach: ' . $e->getMessage());
    exit();
}

// Get all teams for dropdown
try {
    $stmt = $db->prepare("SELECT id, name, age_group FROM teams ORDER BY age_group, name");
    $stmt->execute();
    $teams = $stmt->fetchAll();
} catch (Exception $e) {
    $teams = [];
}

// Get current team assignments
try {
    $stmt = $db->prepare("SELECT team_id FROM team_coaches WHERE coach_id = ?");
    $stmt->execute([$coachId]);
    $assignedTeams = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $assignedTeams = [];
}

// Handle form submission (only for edit mode, not profile view)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isProfileView) {
    try {
        // Validate required fields
        if (empty($_POST['name'])) {
            throw new Exception("Coach name is required.");
        }

        $stmt = $db->prepare("UPDATE coaches SET name = ?, email = ?, phone = ?, role = ?, qualifications = ?, certifications = ?, bio = ?, photo_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([
            $_POST['name'],
            $_POST['email'] ?? '',
            $_POST['phone'] ?? '',
            $_POST['role'] ?? 'coach',
            $_POST['qualifications'] ?? '',
            $_POST['certifications'] ?? '',
            $_POST['bio'] ?? '',
            $_POST['photo_url'] ?? '',
            $coachId
        ]);

        // Handle team assignments - first remove all existing assignments
        $stmt = $db->prepare("DELETE FROM team_coaches WHERE coach_id = ?");
        $stmt->execute([$coachId]);

        // Then add new assignments - only for valid team IDs
        if (!empty($_POST['teams']) && is_array($_POST['teams'])) {
            // Get valid team IDs
            $validTeamIds = array_column($teams, 'id');
            
            $stmt = $db->prepare("INSERT INTO team_coaches (team_id, coach_id) VALUES (?, ?)");
            foreach ($_POST['teams'] as $teamId) {
                if (!empty($teamId) && in_array($teamId, $validTeamIds)) {
                    $stmt->execute([$teamId, $coachId]);
                }
            }
        }

        header('Location: coaches.php?message=Coach updated successfully');
        exit();
    } catch (Exception $e) {
        $error = "Error updating coach: " . $e->getMessage();
    }
}

// Available roles
$roles = ['coach', 'assistant_coach', 'manager', 'volunteer', 'physio', 'kit_manager', 'other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Coach - VIVO United Manager</title>
    <?php
    // Include dynamic CSS system
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
                <i class="fas fa-<?php echo $isProfileView ? 'user' : 'user-edit'; ?>"></i>
                <?php echo $isProfileView ? 'Coach Profile' : 'Edit Coach'; ?>
            </h1>
            <p class="page-subtitle"><?php echo $isProfileView ? 'View coach information and profile details' : 'Update coach information and profile details'; ?></p>

            <div class="page-actions">
                <?php if ($isProfileView): ?>
                    <a href="edit_coach.php?id=<?php echo $coachId; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i>
                        Edit Coach
                    </a>
                <?php endif; ?>
                <a href="coaches.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Coaches
                </a>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="coach-form">
                <!-- Basic Information -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-user"></i>
                        Basic Information
                    </h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" id="name" name="name" class="form-control"
                                   value="<?php echo htmlspecialchars($coach['name']); ?>" 
                                   <?php echo $isProfileView ? 'readonly' : 'required'; ?>>
                        </div>

                        <div class="form-group">
                            <label for="role" class="form-label">Role</label>
                            <select id="role" name="role" class="form-control" <?php echo $isProfileView ? 'disabled' : ''; ?>>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role; ?>" <?php echo ($coach['role'] === $role) ? 'selected' : ''; ?>>
                                        <?php echo ucwords(str_replace('_', ' ', $role)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-address-book"></i>
                        Contact Information
                    </h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control"
                                   value="<?php echo htmlspecialchars($coach['email'] ?? ''); ?>" 
                                   <?php echo $isProfileView ? 'readonly' : ''; ?>>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                   value="<?php echo htmlspecialchars($coach['phone'] ?? ''); ?>" 
                                   <?php echo $isProfileView ? 'readonly' : ''; ?>>
                        </div>
                    </div>
                </div>

                <!-- Professional Information -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-graduation-cap"></i>
                        Professional Information
                    </h3>

                    <div class="form-group">
                        <label for="qualifications" class="form-label">Qualifications</label>
                        <textarea id="qualifications" name="qualifications" class="form-control" rows="3"
                                  placeholder="e.g., UEFA License, First Aid Certificate, etc." 
                                  <?php echo $isProfileView ? 'readonly' : ''; ?>><?php echo htmlspecialchars($coach['qualifications'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="certifications" class="form-label">Certifications</label>
                        <textarea id="certifications" name="certifications" class="form-control" rows="3"
                                  placeholder="e.g., FA Level 1, Safeguarding Certificate, etc." 
                                  <?php echo $isProfileView ? 'readonly' : ''; ?>><?php echo htmlspecialchars($coach['certifications'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Additional Information
                    </h3>

                    <div class="form-group">
                        <label for="bio" class="form-label">Biography</label>
                        <textarea id="bio" name="bio" class="form-control" rows="4"
                                  placeholder="Brief biography or background information" 
                                  <?php echo $isProfileView ? 'readonly' : ''; ?>><?php echo htmlspecialchars($coach['bio'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="photo_url" class="form-label">Photo URL</label>
                        <input type="text" id="photo_url" name="photo_url" class="form-control"
                               value="<?php echo htmlspecialchars($coach['photo_url'] ?? ''); ?>"
                               placeholder="https://example.com/photo.jpg (optional - Font Awesome icons used as fallback)" 
                               <?php echo $isProfileView ? 'readonly' : ''; ?>>
                    </div>
                </div>

                <!-- Team Assignment -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-users"></i>
                        Team Assignment
                    </h3>

                    <div class="form-group">
                        <label for="teams" class="form-label">Assign to Teams</label>
                        <select id="teams" name="teams[]" class="form-control" multiple size="6" <?php echo $isProfileView ? 'disabled' : ''; ?>>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>" <?php echo in_array($team['id'], $assignedTeams) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($team['name'] . ' (' . $team['age_group'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!$isProfileView): ?>
                            <small class="form-text text-muted">Hold Ctrl (Cmd on Mac) to select multiple teams</small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Form Actions -->
                <?php if (!$isProfileView): ?>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i>
                            Update Coach
                        </button>
                        <a href="coaches.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                    </div>
                <?php else: ?>
                    <div class="form-actions profile-actions">
                        <a href="edit_coach.php?id=<?php echo $coachId; ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-edit"></i>
                            Edit Coach
                        </a>
                        <a href="coaches.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-arrow-left"></i>
                            Back to Coaches
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<style>
        
.form-container {
    max-width: 800px;
    margin: 0 auto;
}

.coach-form {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.form-section {
    padding: var(--space-6);
    border-bottom: 1px solid var(--border-color);
}

.form-section:last-child {
    border-bottom: none;
}

.section-title {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    margin: 0 0 var(--space-5) 0;
    color: var(--primary-green);
    font-size: 1.2rem;
    font-weight: 600;
}

.section-title i {
    font-size: 1.1rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-5);
    margin-bottom: var(--space-5);
}

.form-group {
    margin-bottom: var(--space-5);
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-label {
    display: block;
    margin-bottom: var(--space-2);
    font-weight: 600;
    color: var(--text-primary);
}

.form-control {
    width: 100%;
    padding: var(--space-3) var(--space-4);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    font-size: 1rem;
    transition: all var(--transition-fast);
    background: var(--white);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
}

.form-control[required] {
    border-color: var(--warning);
}

.form-control[required]:focus {
    border-color: var(--primary-green);
}

textarea.form-control {
    resize: vertical;
    min-height: 80px;
}

.form-actions {
    padding: var(--space-6);
    background: var(--gray-50);
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: var(--space-4);
    justify-content: center;
}

/* btn-lg now uses global styles from vivo-style.css */

.form-control[readonly] {
    background: var(--gray-50);
    color: var(--text-primary);
    border-color: var(--gray-200);
    cursor: default;
}

.form-control[readonly]:focus {
    border-color: var(--gray-200);
    box-shadow: none;
}

.form-control[disabled] {
    background: var(--gray-50);
    color: var(--text-primary);
    border-color: var(--gray-200);
    cursor: default;
}

.profile-actions {
    background: var(--primary-light, #f0f9ff);
}

.alert {
    padding: var(--space-4);
    border-radius: var(--radius-md);
    margin-bottom: var(--space-6);
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.alert-error {
    background: var(--error-light);
    color: var(--error);
    border: 1px solid var(--error);
}

.alert i {
    font-size: 1.1rem;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: var(--space-4);
    }

    .form-section {
        padding: var(--space-4);
    }

    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }

    /* Mobile button styles handled by global CSS */
}

/* Multi-select styling */
select[multiple] {
    min-height: 120px;
    padding: var(--space-3);
}

.form-text {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-top: var(--space-2);
}
</style>

</body>
</html>
