<?php
require_once 'database_factory.php';
require_once 'includes/auth.php';

requireLogin();
$currentPage = 'teams';

 $db = DatabaseFactory::getConnection();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['name']) || empty($_POST['age_group'])) {
            throw new Exception("Team name and age group are required.");
        }

        $stmt = $db->prepare("INSERT INTO teams (name, description, coach_name, assistant_coach, age_group, contact_email, contact_phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['name'],
            $_POST['description'] ?? '',
            $_POST['coach'] ?? '',
            $_POST['assistant_coach'] ?? '',
            $_POST['age_group'],
            $_POST['contact_email'] ?? '',
            $_POST['contact_phone'] ?? ''
        ]);

        $message = "Team '" . htmlspecialchars($_POST['name']) . "' added successfully!";

        // Clear form data after successful submission
        $_POST = array();

    } catch (Exception $e) {
        $error = "Error adding team: " . $e->getMessage();
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
    <title>Add New Team - VIVO United Manager</title>
    <?php 
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
                        <label for="coach" class="form-label">Head Coach</label>
                        <input type="text" id="coach" name="coach" class="form-control"
                               value="<?= htmlspecialchars($_POST['coach'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="assistant_coach" class="form-label">Assistant Coach</label>
                        <input type="text" id="assistant_coach" name="assistant_coach" class="form-control"
                               value="<?= htmlspecialchars($_POST['assistant_coach'] ?? '') ?>">
                    </div>
                </div>
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