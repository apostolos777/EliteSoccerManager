<?php
/**
 * VIVO United - Dynamic Color System
 * Central color management for the entire application
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

class VIVOColorSystem {
    private static $colors = null;
    private static $default_colors = [
        'color_primary' => '#dc2626',
        'color_secondary' => '#991b1b', 
        'color_accent' => '#fef2f2',
        'color_success' => '#059669',
        'color_warning' => '#d97706',
        'color_danger' => '#dc2626'
    ];
    
    /**
     * Clear the colors cache
     */
    public static function clearCache() {
        self::$colors = null;
    }
    
    /**
     * Get club colors from database with fallback to defaults
     */
    public static function getColors($db_connection = null) {
        if (self::$colors !== null) {
            return self::$colors;
        }
        
        try {
            // Use provided connection or try to get one
            $db = $db_connection;
            if (!$db) {
                if (function_exists('get_db_connection')) {
                    $db = get_db_connection();
                } else if (class_exists('DatabaseFactory')) {
                    $db = DatabaseFactory::getConnection();
                } else {
                    // Try main app database
                    $db = new PDO('sqlite:database.db');
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                }
            }
            
            // Get colors from database
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM club_settings WHERE setting_key LIKE 'color_%'");
            $stmt->execute();
            $db_colors = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Merge with defaults
            self::$colors = array_merge(self::$default_colors, $db_colors);
            
        } catch (Exception $e) {
            // Fallback to defaults on any error
            error_log("Color system error: " . $e->getMessage());
            self::$colors = self::$default_colors;
        }
        
        return self::$colors;
    }
    
    /**
     * Generate CSS variables for all colors
     */
    public static function generateCSSVariables($db_connection = null) {
        $colors = self::getColors($db_connection);
        $css = ":root {\n";
        
        foreach ($colors as $key => $value) {
            $css_var_name = str_replace('color_', '', $key);

            // Emit both short and legacy-prefixed variable names so both stylesheets work
            // e.g. --primary and --color-primary
            $css .= "    --{$css_var_name}: {$value};\n";
            $css .= "    --color-{$css_var_name}: {$value};\n";

            // Generate additional shades for each color for both naming styles
            $rgb = self::hexToRgb($value);
            if ($rgb) {
                $css .= "    --{$css_var_name}-rgb: {$rgb['r']}, {$rgb['g']}, {$rgb['b']};\n";
                $css .= "    --{$css_var_name}-light: rgba({$rgb['r']}, {$rgb['g']}, {$rgb['b']}, 0.1);\n";
                $css .= "    --{$css_var_name}-medium: rgba({$rgb['r']}, {$rgb['g']}, {$rgb['b']}, 0.5);\n";
                $css .= "    --{$css_var_name}-dark: rgba({$rgb['r']}, {$rgb['g']}, {$rgb['b']}, 0.8);\n";

                $css .= "    --color-{$css_var_name}-rgb: {$rgb['r']}, {$rgb['g']}, {$rgb['b']};\n";
                $css .= "    --color-{$css_var_name}-light: rgba({$rgb['r']}, {$rgb['g']}, {$rgb['b']}, 0.1);\n";
                $css .= "    --color-{$css_var_name}-medium: rgba({$rgb['r']}, {$rgb['g']}, {$rgb['b']}, 0.5);\n";
                $css .= "    --color-{$css_var_name}-dark: rgba({$rgb['r']}, {$rgb['g']}, {$rgb['b']}, 0.8);\n";
            }
        }
        
        $css .= "}\n";
        return $css;
    }
    
    /**
     * Generate comprehensive CSS for all components
     */
    public static function generateDynamicCSS($db_connection = null) {
        $css = self::generateCSSVariables($db_connection);
        
        $css .= "
/* ===== VIVO UNITED DYNAMIC COLOR SYSTEM ===== */

/* Sidebar and Navigation */
.sidebar {
    background: linear-gradient(180deg, var(--primary), var(--secondary)) !important;
}

.sidebar-header {
    background: linear-gradient(135deg, var(--primary), var(--secondary)) !important;
}

.sidebar .logo-container {
    background: var(--primary) !important;
    border-color: var(--secondary) !important;
}

.sidebar .logo-image {
    border-color: var(--secondary) !important;
}

.logo-title, .logo-subtitle, .sidebar .logo-text {
    color: white !important;
}

.nav-link {
    color: rgba(255, 255, 255, 0.9) !important;
}

.nav-link:hover, .nav-link.active {
    background: var(--secondary) !important;
    color: white !important;
}

.nav-link.active {
    border-left: 4px solid white !important;
}

.sidebar-toggle {
    background: var(--primary) !important;
}

.sidebar-toggle:hover {
    background: var(--secondary) !important;
}

/* Buttons */
.btn-primary, .bg-primary {
    background: var(--primary) !important;
    border-color: var(--primary) !important;
    color: white !important;
}

.btn-primary:hover, .btn-primary:focus, .btn-primary:active {
    background: var(--secondary) !important;
    border-color: var(--secondary) !important;
    color: white !important;
}

.btn-secondary {
    background: var(--secondary) !important;
    border-color: var(--secondary) !important;
    color: white !important;
}

.btn-secondary:hover, .btn-secondary:focus, .btn-secondary:active {
    background: var(--primary-dark) !important;
    border-color: var(--primary-dark) !important;
    color: white !important;
}

.btn-success {
    background: var(--success) !important;
    border-color: var(--success) !important;
}

.btn-success:hover, .btn-success:focus, .btn-success:active {
    background: var(--success-dark) !important;
    border-color: var(--success-dark) !important;
}

.btn-warning {
    background: var(--warning) !important;
    border-color: var(--warning) !important;
}

.btn-warning:hover, .btn-warning:focus, .btn-warning:active {
    background: var(--warning-dark) !important;
    border-color: var(--warning-dark) !important;
}

.btn-danger {
    background: var(--danger) !important;
    border-color: var(--danger) !important;
}

.btn-danger:hover, .btn-danger:focus, .btn-danger:active {
    background: var(--danger-dark) !important;
    border-color: var(--danger-dark) !important;
}

/* Links */
.text-primary, a.text-primary {
    color: var(--primary) !important;
}

a.text-primary:hover {
    color: var(--secondary) !important;
}

a:not(.btn):not(.nav-link) {
    color: var(--primary);
}

a:not(.btn):not(.nav-link):hover {
    color: var(--secondary);
}

/* Headers & Titles */
.page-title, .page-header h1 {
    color: var(--primary) !important;
}

.page-subtitle {
    color: var(--secondary) !important;
}

.card-header, .form-section-title {
    background: var(--accent) !important;
    color: var(--primary) !important;
    border-bottom: 2px solid var(--primary) !important;
}

/* Forms */
.form-control:focus {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 0.2rem var(--primary-light) !important;
}

.form-group label {
    color: var(--primary) !important;
    font-weight: 600;
}

input[type=\"checkbox\"]:checked {
    background: var(--primary) !important;
    border-color: var(--primary) !important;
}

select:focus, textarea:focus {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 0.2rem var(--primary-light) !important;
}

/* Alerts */
.alert-success {
    background: var(--success-light) !important;
    border: 1px solid var(--success) !important;
    color: var(--success-dark) !important;
}

.alert-warning {
    background: var(--warning-light) !important;
    border: 1px solid var(--warning) !important;
    color: var(--warning-dark) !important;
}

.alert-danger, .alert-error {
    background: var(--danger-light) !important;
    border: 1px solid var(--danger) !important;
    color: var(--danger-dark) !important;
}

.alert-info {
    background: var(--primary-light) !important;
    border: 1px solid var(--primary) !important;
    color: var(--primary-dark) !important;
}

/* Cards & Components */
.card {
    border-top: 4px solid var(--primary) !important;
}

.player-card, .team-card, .event-card {
    border-left: 4px solid var(--primary) !important;
}

.card-title, .player-card-header, .team-card-header {
    color: var(--primary) !important;
}

/* Stats & Metrics */
.stat-card {
    border-left: 4px solid var(--primary) !important;
}

.stat-value {
    color: var(--primary) !important;
}

/* Tables */
.table thead th {
    background: var(--accent) !important;
    color: var(--primary) !important;
    border-bottom: 2px solid var(--primary) !important;
}

.table-striped tbody tr:nth-of-type(odd) {
    background: var(--accent) !important;
}

/* Badges */
.badge-primary {
    background: var(--primary) !important;
}

.badge-secondary {
    background: var(--secondary) !important;
}

.badge-success {
    background: var(--success) !important;
}

.badge-warning {
    background: var(--warning) !important;
}

.badge-danger {
    background: var(--danger) !important;
}

/* Progress Bars */
.progress-bar {
    background: var(--primary) !important;
}

.progress-bar-success {
    background: var(--success) !important;
}

.progress-bar-warning {
    background: var(--warning) !important;
}

.progress-bar-danger {
    background: var(--danger) !important;
}

/* Custom Components */
.hero-section {
    background: linear-gradient(135deg, var(--primary), var(--secondary)) !important;
}

.sidebar-footer {
    background: var(--secondary) !important;
}

/* Action Buttons */
.action-btn, .btn-action {
    background: var(--primary) !important;
    color: white !important;
}

.action-btn:hover, .btn-action:hover {
    background: var(--secondary) !important;
}

/* Form Sections */
.form-section {
    border-top: 3px solid var(--primary) !important;
}

.form-section-title i {
    color: var(--primary) !important;
}

/* File Upload Areas */
.file-upload-area:hover {
    border-color: var(--primary) !important;
    background: var(--accent) !important;
}

.file-upload-area.has-file {
    border-color: var(--success) !important;
    background: var(--success-light) !important;
}

/* Social Media Icons - Keep original brand colors but add hover effects */
.social-input-group input:focus {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 0.2rem var(--primary-light) !important;
}

/* Mobile Menu */
.mobile-menu-toggle {
    background: var(--primary) !important;
    color: white !important;
}

.mobile-menu-toggle:hover {
    background: var(--secondary) !important;
}

/* Dashboard Stats */
.dashboard-stat {
    border-left: 4px solid var(--primary) !important;
}

.dashboard-stat-icon {
    color: var(--primary) !important;
}

/* Event Status Colors */
.event-scheduled { color: var(--primary) !important; }
.event-completed { color: var(--success) !important; }
.event-cancelled { color: var(--danger) !important; }
.event-postponed { color: var(--warning) !important; }

/* Player Status Colors */
.player-active { color: var(--success) !important; }
.player-inactive { color: var(--warning) !important; }
.player-injured { color: var(--danger) !important; }

/* Team Colors */
.team-header {
    background: var(--primary) !important;
    color: white !important;
}

/* Attendance Colors */
.attendance-present { color: var(--success) !important; }
.attendance-absent { color: var(--danger) !important; }
.attendance-late { color: var(--warning) !important; }

/* Responsive Design Helpers */
@media (max-width: 768px) {
    .mobile-primary-bg {
        background: var(--primary) !important;
    }
    
    .mobile-secondary-bg {
        background: var(--secondary) !important;
    }
}

/* Focus States for Accessibility */
*:focus {
    outline: 2px solid var(--primary) !important;
    outline-offset: 2px !important;
}

/* Loading States */
.loading {
    color: var(--primary) !important;
}

.spinner {
    border-color: var(--primary-light) var(--primary-light) var(--primary) var(--primary) !important;
}
";
        
        return $css;
    }
    
    /**
     * Convert hex color to RGB array
     */
    private static function hexToRgb($hex) {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) !== 6) {
            return null;
        }
        
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }
    
    /**
     * Output dynamic CSS as style tag
     */
    public static function outputCSS($db_connection = null) {
        echo "<style id=\"vivo-dynamic-colors\">\n";
        echo self::generateDynamicCSS($db_connection);
        echo "</style>\n";
    }
    
    /**
     * Save colors to database
     */
    public static function saveColors($colors) {
        try {
            if (function_exists('get_db_connection')) {
                $db = get_db_connection();
            } else if (class_exists('DatabaseFactory')) {
                $db = DatabaseFactory::getConnection();
            } else {
                throw new Exception('No database connection available');
            }
            
            // Create table if it doesn't exist
            $db->exec("CREATE TABLE IF NOT EXISTS club_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key TEXT UNIQUE,
                setting_value TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            
            // Save each color
            foreach ($colors as $key => $value) {
                if (array_key_exists($key, self::$default_colors) && preg_match('/^#[a-fA-F0-9]{6}$/', $value)) {
                    $stmt = $db->prepare("INSERT OR REPLACE INTO club_settings (setting_key, setting_value, updated_at) VALUES (?, ?, datetime('now'))");
                    $stmt->execute([$key, $value]);
                }
            }
            
            // Reset cached colors
            self::$colors = null;
            
            return true;
            
        } catch (Exception $e) {
            error_log("VIVOColorSystem::saveColors Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get a specific color value
     */
    public static function getColor($key) {
        $colors = self::getColors();
        return $colors[$key] ?? self::$default_colors[$key] ?? '#000000';
    }
}
