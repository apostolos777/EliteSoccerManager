<?php
require_once 'includes/color_system.php';
require_once 'database_factory.php';
require_once 'includes/auth.php';

requireLogin();
$currentPage = 'coaches';

$db = DatabaseFactory::getConnection();
$message = '';
$error = '';

// Available roles
$roles = ['coach', 'assistant_coach', 'manager', 'volunteer', 'physio', 'kit_manager', 'other'];

// Get all teams for dropdown - Load this BEFORE form processing
try {
    $stmt = $db->prepare("SELECT id, name, age_group FROM teams ORDER BY age_group, name");
    $stmt->execute();
    $teams = $stmt->fetchAll();
} catch (Exception $e) {
    $teams = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['name'])) {
            throw new Exception("Coach name is required.");
        }

        $stmt = $db->prepare("INSERT INTO coaches (name, email, phone, role, qualifications, certifications, bio, photo_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['name'],
            $_POST['email'] ?? '',
            $_POST['phone'] ?? '',
            $_POST['role'] ?? 'coach',
            $_POST['qualifications'] ?? '',
            $_POST['certifications'] ?? '',
            $_POST['bio'] ?? '',
            $_POST['photo_url'] ?? ''
        ]);

        $coachId = $db->lastInsertId();

        // Handle team assignments - only for valid team IDs
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

        // Set success message and prepare for confirmation
        $message = "Coach '" . htmlspecialchars($_POST['name']) . "' has been successfully added to the system!";
        
        // Set a flash message for the coaches page
        if (function_exists('set_flash')) {
            set_flash('Coach added successfully', 'success');
            $_SESSION['flash_created_name'] = $_POST['name'] ?? 'New Coach';
        }
        
        // Clear form data after successful submission
        $_POST = [];

        // Redirect to edit page for the newly created coach so downstream flows which expect an ID work.
        if (!empty($coachId)) {
            header('Location: edit_coach.php?id=' . (int)$coachId);
            exit;
        }
    } catch (Exception $e) {
        $error = "Error adding coach: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Coach - VIVO United Manager</title>
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
                <i class="fas fa-user-plus"></i>
                Add New Coach
            </h1>
            <p class="page-subtitle">Create a profile for a coach, staff member, or volunteer</p>

            <div class="page-actions">
                <a href="coaches.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Coaches
                </a>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="form-container">
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                    <div class="success-actions">
                        <a href="coaches.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-users"></i>
                            View All Coaches
                        </a>
                        <a href="add_coach.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-plus"></i>
                            Add Another Coach
                        </a>
                    </div>
                </div>
            <?php endif; ?>

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
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="role" class="form-label">Role</label>
                            <select id="role" name="role" class="form-control">
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role; ?>" <?php echo (($_POST['role'] ?? 'coach') === $role) ? 'selected' : ''; ?>>
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
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
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
                                  placeholder="e.g., UEFA License, First Aid Certificate, etc."><?php echo htmlspecialchars($_POST['qualifications'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="certifications" class="form-label">Certifications</label>
                        <textarea id="certifications" name="certifications" class="form-control" rows="3"
                                  placeholder="e.g., FA Level 1, Safeguarding Certificate, etc."><?php echo htmlspecialchars($_POST['certifications'] ?? ''); ?></textarea>
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
                                  placeholder="Brief biography or background information"><?php echo htmlspecialchars($_POST['bio'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="photo_url" class="form-label">Photo URL</label>
                        <input type="text" id="photo_url" name="photo_url" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['photo_url'] ?? ''); ?>"
                               placeholder="https://example.com/photo.jpg (optional - Font Awesome icons used as fallback)">
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
                        <select id="teams" name="teams[]" class="form-control" multiple size="6">
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>" <?php echo (isset($_POST['teams']) && in_array($team['id'], $_POST['teams'])) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($team['name'] . ' (' . $team['age_group'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Hold Ctrl (Cmd on Mac) to select multiple teams</small>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i>
                        Add Coach
                    </button>
                    <a href="coaches.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
        <?php echo VIVOColorSystem::generateDynamicCSS(); ?>
        
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

.btn-lg {
    padding: var(--space-4) var(--space-6);
    font-size: 1.1rem;
    font-weight: 600;
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

.alert-success {
    background: var(--success-light, #dcfce7);
    color: var(--success, #15803d);
    border: 1px solid var(--success, #15803d);
}

.success-actions {
    margin-top: var(--space-4);
    display: flex;
    gap: var(--space-3);
    flex-wrap: wrap;
}

.btn-sm {
    padding: var(--space-2) var(--space-3);
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: var(--space-4);
    }
    
    .btn-lg {
        width: 100%;
        text-align: center;
        padding: var(--space-4);
    }
    
    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }
}
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
