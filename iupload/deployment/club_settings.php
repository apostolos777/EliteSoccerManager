<?php
/**
 * VIVO United - Club Settings (Enhanced with Dynamic Color System)
 * Comprehensive club management with dynamic color system
 */

// Load color system and auth
require_once 'includes/color_system.php';
require_once 'includes/auth.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// auth.php starts session when needed; avoid calling session_start() here to prevent notices
$currentPage = 'settings';

// Database connection
try {
    // Use the application's DatabaseFactory so club settings are stored in the canonical DB file
    require_once 'database_factory.php';
    $db = DatabaseFactory::getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission
$message = '';
$messageType = '';

// Handle color form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_colors') {
    try {
        // Use the new color system to save colors
        $colors_to_save = [
            'color_primary' => $_POST['color_primary'] ?? 'var(--primary)',
            'color_secondary' => $_POST['color_secondary'] ?? 'var(--secondary)',
            'color_accent' => $_POST['color_accent'] ?? '#fef2f2',
            'color_success' => $_POST['color_success'] ?? 'var(--success)',
            'color_warning' => $_POST['color_warning'] ?? 'var(--warning)',
            'color_danger' => $_POST['color_danger'] ?? 'var(--primary)'
        ];
        
        if (VIVOColorSystem::saveColors($colors_to_save)) {
            $message = 'Club colors updated successfully! Changes will apply across the entire application.';
            $messageType = 'success';
            
            // Add auto-refresh flag for JavaScript
            $autoRefresh = true;
        } else {
            $message = 'Error saving colors. Please try again.';
            $messageType = 'error';
        }
        
    } catch (Exception $e) {
        $message = 'Error saving colors: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Function to generate custom CSS
function generateCustomCSS($default_colors, $post_data) {
    $css_content = "/* Custom Club Colors - Auto Generated */\n";
    $css_content .= ":root {\n";
    
    foreach ($default_colors as $key => $default_value) {
        $color_value = $post_data[$key] ?? $default_value;
        $css_var_name = str_replace('color_', '', $key);
        $css_content .= "    --color-{$css_var_name}: {$color_value};\n";
    }
    
    $css_content .= "}\n\n";
    
    // Comprehensive styling for all app components
    $css_content .= "
/* ===== SIDEBAR & NAVIGATION ===== */
.sidebar {
    background-color: var(--color-primary) !important;
}

.sidebar-header, .main-nav {
    background-color: var(--color-primary) !important;
}

.sidebar .logo-container {
    background-color: var(--color-primary) !important;
}

.sidebar .logo-title {
    color: white !important;
}

.sidebar .logo-subtitle {
    color: rgba(255, 255, 255, 0.8) !important;
}

.sidebar .logo-text {
    color: white !important;
}

.sidebar-header .logo-container {
    background-color: var(--color-primary) !important;
    border-bottom: 1px solid var(--color-secondary) !important;
}

.sidebar-header .logo-title {
    color: white !important;
    font-weight: 600 !important;
}

.sidebar-header .logo-subtitle {
    color: rgba(255, 255, 255, 0.9) !important;
}

/* Club logo styling */
.club-logo-img {
    border: 2px solid rgba(255, 255, 255, 0.3) !important;
    border-radius: 8px !important;
}

/* Navigation Links */
.nav-link {
    color: rgba(255, 255, 255, 0.9) !important;
    transition: all 0.3s ease !important;
}

.nav-link:hover {
    background-color: var(--color-secondary) !important;
    color: white !important;
}

.nav-link.active {
    background-color: var(--color-secondary) !important;
    color: white !important;
    border-left: 4px solid white !important;
}

.nav-link i {
    color: rgba(255, 255, 255, 0.9) !important;
}

.nav-link:hover i,
.nav-link.active i {
    color: white !important;
}

/* Mobile menu toggle */
.mobile-menu-toggle {
    background-color: var(--color-primary) !important;
    color: white !important;
}

/* ===== HEADER ===== */
.header {
    background-color: var(--color-primary) !important;
}

.header-title {
    color: white !important;
}

.header .breadcrumb a {
    color: rgba(255, 255, 255, 0.8) !important;
}

/* ===== BUTTONS ===== */
.btn-primary, .bg-primary {
    background-color: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
    color: white !important;
}

.btn-primary:hover, .btn-primary:focus {
    background-color: var(--color-secondary) !important;
    border-color: var(--color-secondary) !important;
    color: white !important;
}

.btn-secondary {
    background-color: var(--color-secondary) !important;
    border-color: var(--color-secondary) !important;
    color: white !important;
}

.btn-secondary:hover, .btn-secondary:focus {
    background-color: rgba(153, 27, 27, 0.8) !important;
    border-color: rgba(153, 27, 27, 0.8) !important;
}

.btn-success {
    background-color: var(--color-success) !important;
    border-color: var(--color-success) !important;
}

.btn-success:hover, .btn-success:focus {
    background-color: rgba(5, 150, 105, 0.8) !important;
    border-color: rgba(5, 150, 105, 0.8) !important;
}

.btn-warning {
    background-color: var(--color-warning) !important;
    border-color: var(--color-warning) !important;
}

.btn-warning:hover, .btn-warning:focus {
    background-color: rgba(217, 119, 6, 0.8) !important;
    border-color: rgba(217, 119, 6, 0.8) !important;
}

.btn-danger {
    background-color: var(--color-danger) !important;
    border-color: var(--color-danger) !important;
}

.btn-danger:hover, .btn-danger:focus {
    background-color: rgba(220, 38, 38, 0.8) !important;
    border-color: rgba(220, 38, 38, 0.8) !important;
}

/* Action buttons */
.action-btn {
    background-color: var(--color-primary) !important;
    color: white !important;
}

.action-btn:hover {
    background-color: var(--color-secondary) !important;
}

/* ===== LINKS ===== */
.text-primary, a.text-primary {
    color: var(--color-primary) !important;
}

a.text-primary:hover {
    color: var(--color-secondary) !important;
}

/* ===== CARDS & COMPONENTS ===== */
.card-header {
    background-color: var(--color-accent) !important;
    border-bottom: 1px solid var(--color-primary) !important;
}

.card-header h3 {
    color: var(--color-primary) !important;
}

/* Player cards */
.player-card {
    border-left: 4px solid var(--color-primary) !important;
}

.player-card-header {
    color: var(--color-primary) !important;
}

/* Team cards */
.team-card {
    border-left: 4px solid var(--color-primary) !important;
}

.team-card-header {
    color: var(--color-primary) !important;
}

/* Event cards */
.event-card {
    border-left: 4px solid var(--color-primary) !important;
}

/* ===== FORMS ===== */
.form-control:focus {
    border-color: var(--color-primary) !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25) !important;
}

.form-group label {
    color: var(--color-primary) !important;
}

/* Checkboxes */
input[type=\"checkbox\"]:checked {
    background-color: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
}

/* ===== ALERTS ===== */
.alert-success {
    background-color: var(--color-success) !important;
    border-color: var(--color-success) !important;
    color: white !important;
}

.alert-warning {
    background-color: var(--color-warning) !important;
    border-color: var(--color-warning) !important;
    color: white !important;
}

.alert-danger, .alert-error {
    background-color: var(--color-danger) !important;
    border-color: var(--color-danger) !important;
    color: white !important;
}

/* ===== TABLES ===== */
.table th {
    background-color: var(--color-accent) !important;
    color: var(--color-primary) !important;
    border-bottom: 2px solid var(--color-primary) !important;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(220, 38, 38, 0.05) !important;
}

/* ===== ATTENDANCE SPECIFIC ===== */
.attendance-present {
    background-color: var(--color-success) !important;
    color: white !important;
}

.attendance-absent {
    background-color: var(--color-danger) !important;
    color: white !important;
}

.attendance-late {
    background-color: var(--color-warning) !important;
    color: white !important;
}

/* Mark all buttons */
.mark-all-present {
    background-color: var(--color-success) !important;
    border-color: var(--color-success) !important;
}

.mark-all-absent {
    background-color: var(--color-danger) !important;
    border-color: var(--color-danger) !important;
}

/* ===== BADGES ===== */
.badge-primary {
    background-color: var(--color-primary) !important;
}

.badge-success {
    background-color: var(--color-success) !important;
}

.badge-warning {
    background-color: var(--color-warning) !important;
}

.badge-danger {
    background-color: var(--color-danger) !important;
}

/* ===== PROGRESS BARS ===== */
.progress-bar {
    background-color: var(--color-primary) !important;
}

/* ===== PAGINATION ===== */
.pagination .page-link {
    color: var(--color-primary) !important;
}

.pagination .page-item.active .page-link {
    background-color: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
}

/* ===== DROPDOWNS ===== */
.dropdown-item:hover,
.dropdown-item:focus {
    background-color: var(--color-accent) !important;
    color: var(--color-primary) !important;
}

/* ===== TABS ===== */
.nav-tabs .nav-link.active {
    color: var(--color-primary) !important;
    border-bottom-color: var(--color-primary) !important;
}

.nav-tabs .nav-link:hover {
    border-color: var(--color-primary) !important;
}

/* ===== MOBILE RESPONSIVE ===== */
@media (max-width: 768px) {
    .mobile-sidebar {
        background-color: var(--color-primary) !important;
    }
    
    .mobile-nav-link {
        color: rgba(255, 255, 255, 0.9) !important;
    }
    
    .mobile-nav-link.active {
        background-color: var(--color-secondary) !important;
        color: white !important;
    }
}
";
    
    // Save to CSS file
    file_put_contents('css/club-custom.css', $css_content);
}

// Handle main settings form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $club_name = trim($_POST['club_name'] ?? 'VIVO United');
    $club_description = trim($_POST['club_description'] ?? '');
    $club_address = trim($_POST['club_address'] ?? '');
    $club_phone = trim($_POST['club_phone'] ?? '');
    $club_email = trim($_POST['club_email'] ?? '');
    $club_website = trim($_POST['club_website'] ?? '');
    
    // Handle logo upload
    $logo_path = '';
    $uploadErr = $_FILES['club_logo']['error'] ?? UPLOAD_ERR_NO_FILE;
    
    if (!empty($_FILES['club_logo']['name']) && $uploadErr === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        
        // Create uploads directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                $message = 'Failed to create uploads directory.';
                $messageType = 'error';
            }
        }
        
        if (empty($message)) {
            $file_extension = strtolower(pathinfo($_FILES['club_logo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $file_size = $_FILES['club_logo']['size'];
                if ($file_size <= 5 * 1024 * 1024) { // 5MB limit
                    $filename = 'club_logo_' . date('Ymd_His') . '.' . $file_extension;
                    $target_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['club_logo']['tmp_name'], $target_path)) {
                        $logo_path = $target_path;
                        $message = 'Logo uploaded successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to move uploaded file.';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'File size must be under 5MB.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Only JPG, PNG, GIF, and WEBP files are allowed.';
                $messageType = 'error';
            }
        }
    } elseif ($uploadErr !== UPLOAD_ERR_NO_FILE) {
        $message = 'Upload error: ' . $uploadErr;
        $messageType = 'error';
    }
    
    // Save to database if no errors
    if (empty($message) || $messageType === 'success') {
        try {
            // Prepare settings array
            $settings = [
                'club_name' => $club_name,
                'club_description' => $club_description,
                'club_address' => $club_address,
                'club_phone' => $club_phone,
                'club_email' => $club_email,
                'club_website' => $club_website
            ];
            
            // Only update logo if uploaded
            if ($logo_path) {
                $settings['club_logo'] = $logo_path;
            }
            
            // Save each setting
            foreach ($settings as $key => $value) {
                $stmt = $db->prepare("INSERT OR REPLACE INTO club_settings (setting_key, setting_value, updated_at) VALUES (?, ?, datetime('now'))");
                $stmt->execute([$key, $value]);
            }
            
            if (empty($message)) {
                $message = 'Club settings saved successfully!';
                $messageType = 'success';
            }
            
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Load current settings
$settings = [
    'club_name' => 'VIVO United',
    'club_description' => 'Elite Football Club',
    'club_address' => '',
    'club_phone' => '',
    'club_email' => '',
    'club_website' => '',
    'club_logo' => ''
];

// Load current colors using the new color system
$current_colors = VIVOColorSystem::getColors($db);

try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM club_settings WHERE setting_key NOT LIKE 'color_%'");
    while ($row = $stmt->fetch()) {
        if (isset($settings[$row['setting_key']])) {
            $settings[$row['setting_key']] = $row['setting_value'] ?? '';
        }
    }
} catch (Exception $e) {
    // Ignore if table doesn't exist yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Settings - VIVO United</title>
    <?php 
    require_once 'includes/css_helper.php';
    vivo_include_head_css($db);
    ?>
    <style>
        /* Settings-specific modern styles using global color system */
        .settings-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0;
        }
        
        .settings-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            transition: all 0.2s ease;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }
        
        .settings-card:hover {
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
        }
        
        .settings-card h2 {
            color: var(--text-primary);
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0 0 1.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .settings-card h2 i {
            color: var(--primary);
            font-size: 1.25rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.875rem;
            background: var(--input-bg);
            color: var(--text-primary);
            transition: all 0.2s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.5;
        }
        
        /* Color input special styling */
        .form-group input[type="color"] {
            height: 50px;
            padding: 5px;
            cursor: pointer;
        }
        
        /* Logo upload section */
        .logo-upload-section {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 1.5rem;
            background: var(--background-light);
            transition: all 0.2s ease;
        }
        
        .logo-upload-section:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }
        
        .current-logo {
            max-width: 200px;
            max-height: 200px;
            border-radius: 12px;
            margin-bottom: 1rem;
            background: white;
            padding: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }
        
        .upload-btn {
            background: var(--primary) !important;
            color: white !important;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .upload-btn:hover {
            background: var(--secondary) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .btn-primary {
            background: var(--primary) !important;
            color: white !important;
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 180px;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary:hover {
            background: var(--secondary) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px var(--primary-light);
        }
        
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #059669;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .alert i {
            font-size: 1.25rem;
        }
        
        /* Color picker grid */
        .color-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        .color-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--background-light);
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }
        
        .color-preview {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 2px solid var(--border-color);
            flex-shrink: 0;
        }
        
        .color-info {
            flex: 1;
        }
        
        .color-info label {
            margin: 0 0 0.25rem 0;
            font-size: 0.875rem;
        }
        
        .color-code {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-family: 'Monaco', 'Menlo', monospace;
        }
        
        /* Form actions */
        .form-actions {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        .debug-info {
            background: var(--background-light);
            padding: 1rem;
            border-radius: 8px;
            font-size: 0.75rem;
            margin-top: 1rem;
            border: 1px solid var(--border-color);
            color: var(--text-muted);
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .settings-container {
                max-width: 100%;
                padding: 0 1rem;
            }
            
            .settings-card {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .color-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="content-main">
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title">
                    <i class="fas fa-cog"></i>
                    Club Settings
                </h1>
                <p class="page-subtitle">Manage your club information and logo</p>
            </div>
        </div>
        
        <div class="settings-container">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="clubForm">
                <div class="settings-card">
                    <h2><i class="fas fa-image"></i> Club Logo</h2>
                    
                    <div class="logo-upload-section">
                        <?php if (!empty($settings['club_logo']) && file_exists($settings['club_logo'])): ?>
                            <img src="<?php echo htmlspecialchars($settings['club_logo']); ?>" alt="Current Logo" class="current-logo">
                            <br>
                            <small>Current logo: <?php echo htmlspecialchars($settings['club_logo']); ?></small>
                        <?php else: ?>
                            <i class="fas fa-image fa-3x" style="color: #9ca3af; margin-bottom: 15px;"></i>
                            <p>No logo uploaded</p>
                        <?php endif; ?>
                        
                        <div style="margin-top: 15px;">
                            <input type="file" id="club_logo" name="club_logo" accept="image/*" style="display: none;">
                            <button type="button" class="upload-btn" onclick="document.getElementById('club_logo').click();">
                                <i class="fas fa-upload"></i> Choose Logo
                            </button>
                        </div>
                        
                        <small style="color: #6b7280; margin-top: 10px; display: block;">
                            JPG, PNG, GIF, WEBP • Max 5MB
                        </small>
                    </div>
                </div>
                
                <div class="settings-card">
                    <h2><i class="fas fa-info-circle"></i> Club Information</h2>
                    
                    <div class="form-group">
                        <label for="club_name">Club Name *</label>
                        <input type="text" id="club_name" name="club_name" 
                               value="<?php echo htmlspecialchars($settings['club_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="club_description">Description</label>
                        <textarea id="club_description" name="club_description"><?php echo htmlspecialchars($settings['club_description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="club_address">Address</label>
                        <textarea id="club_address" name="club_address"><?php echo htmlspecialchars($settings['club_address']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="club_phone">Phone</label>
                        <input type="tel" id="club_phone" name="club_phone" 
                               value="<?php echo htmlspecialchars($settings['club_phone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="club_email">Email</label>
                        <input type="email" id="club_email" name="club_email" 
                               value="<?php echo htmlspecialchars($settings['club_email']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="club_website">Website</label>
                        <input type="url" id="club_website" name="club_website" 
                               value="<?php echo htmlspecialchars($settings['club_website']); ?>">
                    </div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <input type="hidden" name="action" value="save_settings">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- Color Customization Section -->
            <div class="settings-card">
                <h3><i class="fas fa-palette"></i> Club Colors</h3>
                <p>Customize your club's colors and branding</p>
                
                <form method="POST" action="" id="colorForm">
                    <input type="hidden" name="action" value="save_colors">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                        <!-- Primary Colors -->
                        <div style="background: #f9fafb; padding: 15px; border-radius: 8px;">
                            <h4 style="margin-bottom: 15px;"><i class="fas fa-star"></i> Primary Colors</h4>
                            
                            <div class="color-item" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <label style="flex: 1; margin-bottom: 0;">Primary</label>
                                <input type="color" name="color_primary" value="<?php echo $current_colors['color_primary']; ?>" style="width: 50px; height: 30px; border-radius: 4px;">
                            </div>
                            
                            <div class="color-item" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <label style="flex: 1; margin-bottom: 0;">Secondary</label>
                                <input type="color" name="color_secondary" value="<?php echo $current_colors['color_secondary']; ?>" style="width: 50px; height: 30px; border-radius: 4px;">
                            </div>
                            
                            <div class="color-item" style="display: flex; align-items: center; gap: 10px;">
                                <label style="flex: 1; margin-bottom: 0;">Accent</label>
                                <input type="color" name="color_accent" value="<?php echo $current_colors['color_accent']; ?>" style="width: 50px; height: 30px; border-radius: 4px;">
                            </div>
                        </div>
                        
                        <!-- Status Colors -->
                        <div style="background: #f9fafb; padding: 15px; border-radius: 8px;">
                            <h4 style="margin-bottom: 15px;"><i class="fas fa-traffic-light"></i> Status Colors</h4>
                            
                            <div class="color-item" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <label style="flex: 1; margin-bottom: 0;">Success</label>
                                <input type="color" name="color_success" value="<?php echo $current_colors['color_success']; ?>" style="width: 50px; height: 30px; border-radius: 4px;">
                            </div>
                            
                            <div class="color-item" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <label style="flex: 1; margin-bottom: 0;">Warning</label>
                                <input type="color" name="color_warning" value="<?php echo $current_colors['color_warning']; ?>" style="width: 50px; height: 30px; border-radius: 4px;">
                            </div>
                            
                            <div class="color-item" style="display: flex; align-items: center; gap: 10px;">
                                <label style="flex: 1; margin-bottom: 0;">Danger</label>
                                <input type="color" name="color_danger" value="<?php echo $current_colors['color_danger']; ?>" style="width: 50px; height: 30px; border-radius: 4px;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Color Presets -->
                    <div style="margin: 20px 0;">
                        <h4>Quick Presets:</h4>
                        <div style="display: flex; gap: 10px; margin-top: 10px;">
                            <button type="button" onclick="applyPreset('red')" class="preset-btn" style="padding: 8px 12px; border: 1px solid #ccc; background: white; border-radius: 4px; cursor: pointer;">
                                <span style="display: inline-block; width: 12px; height: 12px; background: var(--primary); border-radius: 50%; margin-right: 5px;"></span> Primary
                            </button>
                            <button type="button" onclick="applyPreset('blue')" class="preset-btn" style="padding: 8px 12px; border: 1px solid #ccc; background: white; border-radius: 4px; cursor: pointer;">
                                <span style="display: inline-block; width: 12px; height: 12px; background: #2563eb; border-radius: 50%; margin-right: 5px;"></span> Blue
                            </button>
                            <button type="button" onclick="applyPreset('green')" class="preset-btn" style="padding: 8px 12px; border: 1px solid #ccc; background: white; border-radius: 4px; cursor: pointer;">
                                <span style="display: inline-block; width: 12px; height: 12px; background: var(--success); border-radius: 50%; margin-right: 5px;"></span> Success
                            </button>
                            <button type="button" onclick="applyPreset('purple')" class="preset-btn" style="padding: 8px 12px; border: 1px solid #ccc; background: white; border-radius: 4px; cursor: pointer;">
                                <span style="display: inline-block; width: 12px; height: 12px; background: #7c3aed; border-radius: 50%; margin-right: 5px;"></span> Purple
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary" style="margin-right: 10px;">
                        <i class="fas fa-palette"></i> Save Colors
                    </button>
                    <button type="button" onclick="resetColors()" class="btn-secondary">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </form>
            </div>
            
            <!-- Debug Information -->
            <div class="settings-card">
                <h3><i class="fas fa-bug"></i> Debug Information</h3>
                <div class="debug-info">
                    <strong>Current Logo Path:</strong> <?php echo htmlspecialchars($settings['club_logo'] ?: 'None'); ?><br>
                    <strong>File Exists:</strong> <?php echo !empty($settings['club_logo']) && file_exists($settings['club_logo']) ? 'Yes' : 'No'; ?><br>
                    <strong>Uploads Directory:</strong> <?php echo is_dir('uploads/') ? 'Exists' : 'Missing'; ?><br>
                    <strong>Uploads Writable:</strong> <?php echo is_writable('uploads/') ? 'Yes' : 'No'; ?><br>
                    <strong>Upload Max Size:</strong> <?php echo ini_get('upload_max_filesize'); ?><br>
                    <strong>Post Max Size:</strong> <?php echo ini_get('post_max_size'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Color preset configurations
        const presets = {
            red: {
                color_primary: 'var(--primary)',
                color_secondary: 'var(--secondary)',
                color_accent: '#fef2f2',
                color_success: 'var(--success)',
                color_warning: 'var(--warning)',
                color_danger: 'var(--primary)'
            },
            blue: {
                color_primary: '#2563eb',
                color_secondary: '#1d4ed8',
                color_accent: '#eff6ff',
                color_success: 'var(--success)',
                color_warning: 'var(--warning)',
                color_danger: 'var(--primary)'
            },
            green: {
                color_primary: 'var(--success)',
                color_secondary: '#047857',
                color_accent: '#ecfdf5',
                color_success: 'var(--success)',
                color_warning: 'var(--warning)',
                color_danger: 'var(--primary)'
            },
            purple: {
                color_primary: '#7c3aed',
                color_secondary: '#6d28d9',
                color_accent: '#f5f3ff',
                color_success: 'var(--success)',
                color_warning: 'var(--warning)',
                color_danger: 'var(--primary)'
            }
        };

        function applyPreset(presetName) {
            const preset = presets[presetName];
            if (preset) {
                for (const [key, value] of Object.entries(preset)) {
                    const input = document.querySelector(`input[name="${key}"]`);
                    if (input) {
                        input.value = value;
                    }
                }
            }
        }

        function resetColors() {
            applyPreset('red');
        }

        // Live preview function - updates colors immediately without saving
        function updateLivePreview() {
            const root = document.documentElement;
            const inputs = document.querySelectorAll('input[type="color"]');
            
            inputs.forEach(input => {
                const varName = '--color-' + input.name.replace('color_', '');
                root.style.setProperty(varName, input.value);
            });
            
            // Force update sidebar elements
            const sidebar = document.querySelector('.sidebar');
            const sidebarHeader = document.querySelector('.sidebar-header');
            const logoContainer = document.querySelector('.logo-container');
            const logoTitle = document.querySelector('.logo-title');
            const logoSubtitle = document.querySelector('.logo-subtitle');
            
            const primaryColor = document.querySelector('input[name="color_primary"]').value;
            const secondaryColor = document.querySelector('input[name="color_secondary"]').value;
            
            if (sidebar) sidebar.style.backgroundColor = primaryColor;
            if (sidebarHeader) sidebarHeader.style.backgroundColor = primaryColor;
            if (logoContainer) logoContainer.style.backgroundColor = primaryColor;
            if (logoTitle) logoTitle.style.color = 'white';
            if (logoSubtitle) logoSubtitle.style.color = 'rgba(255, 255, 255, 0.8)';
            
            // Update navigation links
            document.querySelectorAll('.nav-link.active').forEach(link => {
                link.style.backgroundColor = secondaryColor;
            });
        }

        // Add event listeners for live preview
        document.addEventListener('DOMContentLoaded', function() {
            const colorInputs = document.querySelectorAll('input[type="color"]');
            colorInputs.forEach(input => {
                input.addEventListener('input', updateLivePreview);
            });
            
            // Initial preview update
            updateLivePreview();
        });

        // Auto-refresh CSS after color save
        <?php if (isset($autoRefresh) && $autoRefresh): ?>
        setTimeout(function() {
            // Force refresh of dynamic color styles without page reload
            var dynamicCSS = document.querySelector('#vivo-dynamic-colors');
            if (dynamicCSS) {
                // Refresh the dynamic CSS by reloading the color system
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newCSS = doc.querySelector('#vivo-dynamic-colors');
                        if (newCSS) {
                            dynamicCSS.innerHTML = newCSS.innerHTML;
                        }
                    })
                    .catch(err => console.log('Color refresh failed:', err));
            }
            
            // Force refresh of custom CSS without page reload
            var customCSS = document.querySelector('link[href*="club-custom.css"]');
            if (customCSS) {
                customCSS.href = 'css/club-custom.css?v=' + new Date().getTime();
            }
            
            // Also refresh main CSS to ensure import works
            var mainCSS = document.querySelector('link[href*="vivo-style.css"]');
            if (mainCSS) {
                mainCSS.href = 'css/vivo-style.css?v=' + new Date().getTime();
            }
        }, 1000);
        <?php endif; ?>

        // Show selected file name
        document.getElementById('club_logo').addEventListener('change', function() {
            const fileName = this.files[0]?.name;
            if (fileName) {
                const button = document.querySelector('.upload-btn');
                button.innerHTML = '<i class="fas fa-check"></i> ' + fileName;
            }
        });
        
        // Note: Auto-refresh removed to prevent reload loops during color editing
    </script>
    
    <?php
    // Include color refresh JavaScript (but disable auto-reload)
    // require_once 'includes/css_helper.php';
    // vivo_include_color_refresh_js();
    ?>
</body>
</html>
