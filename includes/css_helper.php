<?php
/**
 * VIVO United - CSS Helper Functions
 * Helper functions to include dynamic CSS in all pages
 */

require_once __DIR__ . '/color_system.php';

/**
 * Include dynamic CSS styles in the page head
 * Call this function in the <head> section of every page
 */
function vivo_include_dynamic_css($db_connection = null) {
    VIVOColorSystem::outputCSS($db_connection);
}

/**
 * Include all required CSS files for VIVO pages
 * This includes base styles + dynamic colors
 */
function vivo_include_head_css($db_connection = null) {
    // Add FC United inspired theme first so it defines the main design system
    echo '<link rel="stylesheet" href="css/fcunited-theme.css?v=' . time() . '">' . "\n";
    // Keep the app specific overrides/utility styles after the theme
    echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">' . "\n";
    echo '<link rel="stylesheet" href="css/vivo-style.css?v=' . time() . '">' . "\n";
    echo '<link rel="stylesheet" href="css/react-components.css?v=' . time() . '">' . "\n";
    echo '<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">' . "\n";
    vivo_include_dynamic_css($db_connection);
}

/**
 * Include React and React DOM from CDN (no Node.js required)
 */
function vivo_include_react_scripts() {
    echo '<script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>' . "\n";
    echo '<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>' . "\n";
    echo '<script src="js/react-components.js?v=' . time() . '"></script>' . "\n";
}

/**
 * Get a specific color for inline styles
 */
function vivo_get_color($color_name) {
    return VIVOColorSystem::getColor($color_name);
}

/**
 * Get all colors as array
 */
function vivo_get_all_colors() {
    return VIVOColorSystem::getColors();
}

/**
 * Check if we have custom colors set
 */
function vivo_has_custom_colors() {
    $colors = VIVOColorSystem::getColors();
    $defaults = [
        'color_primary' => '#dc2626',
        'color_secondary' => '#991b1b', 
        'color_accent' => '#fef2f2',
        'color_success' => '#059669',
        'color_warning' => '#d97706',
        'color_danger' => '#dc2626'
    ];
    
    return $colors !== $defaults;
}

/**
 * Output JavaScript to handle color changes in real-time
 */
function vivo_include_color_refresh_js() {
    echo '<script>
    // VIVO Color System - Real-time refresh
    window.VIVOColors = {
        refresh: function() {
            // Force refresh dynamic CSS
            var dynamicCSS = document.getElementById("vivo-dynamic-colors");
            if (dynamicCSS) {
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "includes/get_dynamic_css.php", true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        dynamicCSS.innerHTML = xhr.responseText;
                    }
                };
                xhr.send();
            }
        },
        
        // Auto-refresh every 5 seconds when on settings page
        autoRefresh: function() {
            if (window.location.href.includes("club_settings.php")) {
                setInterval(this.refresh, 5000);
            }
        }
    };
    
    // Initialize
    document.addEventListener("DOMContentLoaded", function() {
        VIVOColors.autoRefresh();
    });
    </script>';
}
