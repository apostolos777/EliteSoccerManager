<?php
require_once 'database_factory.php';

session_start();
$currentPage = 'settings';

$message = '';
$messageType = '';

// Handle form submission
if ($_POST && isset($_POST['save_settings'])) {
    try {
        $db = DatabaseFactory::getConnection();
        if ($db) {
            $settings = [
                'club_name' => $_POST['club_name'] ?? '',
                'primary_color' => $_POST['primary_color'] ?? '#FF0000',
                'secondary_color' => $_POST['secondary_color'] ?? '#FFFFFF',
                'founded_year' => $_POST['founded_year'] ?? '',
                'home_ground' => $_POST['home_ground'] ?? '',
                'contact_email' => $_POST['contact_email'] ?? '',
                'contact_phone' => $_POST['contact_phone'] ?? ''
            ];
            
            $stmt = $db->prepare("
                INSERT INTO club_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            
            foreach ($settings as $key => $value) {
                $stmt->execute([$key, $value]);
            }
            
            $message = 'Settings saved successfully!';
            $messageType = 'success';
        }
    } catch (Exception $e) {
        $message = 'Error saving settings: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get current settings
$settings = [];
try {
    $db = DatabaseFactory::getConnection();
    if ($db) {
        $result = $db->query("SELECT setting_key, setting_value FROM club_settings");
        while ($row = $result->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
} catch (Exception $e) {
    // Use defaults if database fails
}

// Default values
$defaults = [
    'club_name' => 'VIVO United FC',
    'primary_color' => '#FF0000',
    'secondary_color' => '#FFFFFF',
    'founded_year' => '2025',
    'home_ground' => '',
    'contact_email' => '',
    'contact_phone' => ''
];

foreach ($defaults as $key => $default) {
    if (!isset($settings[$key])) {
        $settings[$key] = $default;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - VIVO United FC</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php 
    $db = DatabaseFactory::getConnection();
    require_once 'includes/css_helper.php';
    vivo_include_head_css($db);
    ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-futbol"></i> VIVO United FC
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="teams.php">
                            <i class="fas fa-users"></i> Teams
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="players.php">
                            <i class="fas fa-user-friends"></i> Players
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">
                            <i class="fas fa-calendar"></i> Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="attendance.php">
                            <i class="fas fa-check-square"></i> Attendance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active fw-bold" href="settings.php">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h2 text-danger mb-2">
                            <i class="fas fa-cog"></i> Club Settings
                        </h1>
                        <p class="text-muted mb-0">Manage your club's information and preferences</p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Settings Form -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    <i class="fas fa-edit"></i> Club Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <!-- Club Name -->
                        <div class="col-md-6 mb-3">
                            <label for="club_name" class="form-label">Club Name</label>
                            <input type="text" class="form-control" id="club_name" name="club_name" 
                                   value="<?php echo htmlspecialchars($settings['club_name']); ?>" required>
                        </div>

                        <!-- Founded Year -->
                        <div class="col-md-6 mb-3">
                            <label for="founded_year" class="form-label">Founded Year</label>
                            <input type="number" class="form-control" id="founded_year" name="founded_year" 
                                   value="<?php echo htmlspecialchars($settings['founded_year']); ?>" 
                                   min="1800" max="<?php echo date('Y'); ?>">
                        </div>

                        <!-- Primary Color -->
                        <div class="col-md-6 mb-3">
                            <label for="primary_color" class="form-label">Primary Color</label>
                            <input type="color" class="form-control form-control-color" id="primary_color" 
                                   name="primary_color" value="<?php echo htmlspecialchars($settings['primary_color']); ?>">
                        </div>

                        <!-- Secondary Color -->
                        <div class="col-md-6 mb-3">
                            <label for="secondary_color" class="form-label">Secondary Color</label>
                            <input type="color" class="form-control form-control-color" id="secondary_color" 
                                   name="secondary_color" value="<?php echo htmlspecialchars($settings['secondary_color']); ?>">
                        </div>

                        <!-- Home Ground -->
                        <div class="col-12 mb-3">
                            <label for="home_ground" class="form-label">Home Ground</label>
                            <input type="text" class="form-control" id="home_ground" name="home_ground" 
                                   value="<?php echo htmlspecialchars($settings['home_ground']); ?>" 
                                   placeholder="Stadium or field name">
                        </div>

                        <!-- Contact Email -->
                        <div class="col-md-6 mb-3">
                            <label for="contact_email" class="form-label">Contact Email</label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                   value="<?php echo htmlspecialchars($settings['contact_email']); ?>" 
                                   placeholder="club@example.com">
                        </div>

                        <!-- Contact Phone -->
                        <div class="col-md-6 mb-3">
                            <label for="contact_phone" class="form-label">Contact Phone</label>
                            <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                                   value="<?php echo htmlspecialchars($settings['contact_phone']); ?>" 
                                   placeholder="+27 11 123 4567">
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" name="save_settings" class="btn btn-danger">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- System Information -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle"></i> System Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Application Version:</strong><br>
                        <span class="text-muted">VIVO United v2.0</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Database Status:</strong><br>
                        <?php
                        try {
                            $db = DatabaseFactory::getConnection();
                            echo $db ? '<span class="text-success">Connected</span>' : '<span class="text-danger">Disconnected</span>';
                        } catch (Exception $e) {
                            echo '<span class="text-danger">Error</span>';
                        }
                        ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Last Updated:</strong><br>
                        <span class="text-muted"><?php echo date('Y-m-d H:i:s'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
