<?php
/**
 * VIVO United - Dynamic Color System Test Page
 * Test all color applications across different components
 */

require_once 'includes/css_helper.php';
$currentPage = 'settings';

// Get current colors for display
$colors = vivo_get_all_colors();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Color System Test - VIVO United</title>
    <?php vivo_include_head_css(); ?>
    <style>
        .color-test-section {
            margin: 2rem 0;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .color-palette {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .color-swatch {
            padding: 1rem;
            border-radius: 8px;
            color: white;
            text-align: center;
            font-weight: 600;
        }
        
        .component-showcase {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="content-main">
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title">
                    <i class="fas fa-palette"></i>
                    Dynamic Color System Test
                </h1>
                <p class="page-subtitle">Testing all color applications across components</p>
            </div>
            <div class="page-actions">
                <a href="club_settings.php" class="btn btn-primary">
                    <i class="fas fa-cog"></i> Manage Colors
                </a>
            </div>
        </div>

        <!-- Current Color Palette -->
        <div class="color-test-section">
            <h2 class="form-section-title">
                <i class="fas fa-palette"></i>
                Current Color Palette
            </h2>
            <div class="color-palette">
                <div class="color-swatch" style="background: <?= $colors['color_primary'] ?>">
                    Primary<br><?= $colors['color_primary'] ?>
                </div>
                <div class="color-swatch" style="background: <?= $colors['color_secondary'] ?>">
                    Secondary<br><?= $colors['color_secondary'] ?>
                </div>
                <div class="color-swatch" style="background: <?= $colors['color_success'] ?>">
                    Success<br><?= $colors['color_success'] ?>
                </div>
                <div class="color-swatch" style="background: <?= $colors['color_warning'] ?>">
                    Warning<br><?= $colors['color_warning'] ?>
                </div>
                <div class="color-swatch" style="background: <?= $colors['color_danger'] ?>">
                    Danger<br><?= $colors['color_danger'] ?>
                </div>
                <div class="color-swatch" style="background: <?= $colors['color_accent'] ?>; color: #333;">
                    Accent<br><?= $colors['color_accent'] ?>
                </div>
            </div>
        </div>

        <!-- Button Tests -->
        <div class="color-test-section">
            <h2 class="form-section-title">
                <i class="fas fa-hand-pointer"></i>
                Button Components
            </h2>
            <div class="component-showcase">
                <div>
                    <h3>Primary Buttons</h3>
                    <button class="btn btn-primary">Primary Button</button>
                    <button class="btn btn-primary btn-lg">Large Primary</button>
                    <button class="btn btn-secondary">Secondary Button</button>
                </div>
                <div>
                    <h3>Action Buttons</h3>
                    <button class="btn btn-success">Success Button</button>
                    <button class="btn btn-warning">Warning Button</button>
                    <button class="btn btn-danger">Danger Button</button>
                </div>
            </div>
        </div>

        <!-- Alert Tests -->
        <div class="color-test-section">
            <h2 class="form-section-title">
                <i class="fas fa-exclamation-triangle"></i>
                Alert Components
            </h2>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                This is a success alert using the dynamic success color!
            </div>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                This is a warning alert using the dynamic warning color!
            </div>
            <div class="alert alert-danger">
                <i class="fas fa-times-circle"></i>
                This is a danger alert using the dynamic danger color!
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                This is an info alert using the dynamic primary color!
            </div>
        </div>

        <!-- Form Tests -->
        <div class="color-test-section">
            <h2 class="form-section-title">
                <i class="fas fa-edit"></i>
                Form Components
            </h2>
            <div class="form-grid">
                <div class="form-group">
                    <label for="test-input">Input Field</label>
                    <input type="text" id="test-input" class="form-control" placeholder="Focus to see primary color border">
                </div>
                <div class="form-group">
                    <label for="test-select">Select Field</label>
                    <select id="test-select" class="form-control">
                        <option>Option 1</option>
                        <option>Option 2</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="test-checkbox">Checkbox</label>
                    <input type="checkbox" id="test-checkbox" checked> Check me to see primary color
                </div>
            </div>
        </div>

        <!-- Card Tests -->
        <div class="color-test-section">
            <h2 class="form-section-title">
                <i class="fas fa-id-card"></i>
                Card Components
            </h2>
            <div class="component-showcase">
                <div class="card player-card">
                    <div class="card-header">
                        <h3 class="card-title">Player Card</h3>
                    </div>
                    <div class="card-body">
                        <p>This card uses dynamic primary colors for the left border and header styling.</p>
                    </div>
                </div>
                <div class="card team-card">
                    <div class="card-header">
                        <h3 class="card-title">Team Card</h3>
                    </div>
                    <div class="card-body">
                        <p>Team cards also inherit the dynamic color scheme for consistent branding.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Link Tests -->
        <div class="color-test-section">
            <h2 class="form-section-title">
                <i class="fas fa-link"></i>
                Link & Text Components
            </h2>
            <p>This is a <a href="#" class="text-primary">primary colored link</a> that changes on hover.</p>
            <p>Regular <a href="#">links</a> also use the dynamic color system.</p>
            <h3 class="text-primary">This heading uses the primary color</h3>
        </div>

        <!-- Badge Tests -->
        <div class="color-test-section">
            <h2 class="form-section-title">
                <i class="fas fa-tags"></i>
                Badge Components
            </h2>
            <div class="component-showcase">
                <div>
                    <span class="badge badge-primary">Primary Badge</span>
                    <span class="badge badge-secondary">Secondary Badge</span>
                    <span class="badge badge-success">Success Badge</span>
                </div>
                <div>
                    <span class="badge badge-warning">Warning Badge</span>
                    <span class="badge badge-danger">Danger Badge</span>
                </div>
            </div>
        </div>

        <!-- Table Tests -->
        <div class="color-test-section">
            <h2 class="form-section-title">
                <i class="fas fa-table"></i>
                Table Components
            </h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Header 1</th>
                        <th>Header 2</th>
                        <th>Header 3</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Row 1, Cell 1</td>
                        <td>Row 1, Cell 2</td>
                        <td>Row 1, Cell 3</td>
                    </tr>
                    <tr>
                        <td>Row 2, Cell 1</td>
                        <td>Row 2, Cell 2</td>
                        <td>Row 2, Cell 3</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Instructions -->
        <div class="color-test-section">
            <h2 class="form-section-title">
                <i class="fas fa-info-circle"></i>
                Testing Instructions
            </h2>
            <ol>
                <li>Go to <a href="club_settings.php" class="text-primary">Club Settings</a></li>
                <li>Change any of the colors in the "Club Colors" section</li>
                <li>Save the changes</li>
                <li>Return to this page or refresh to see the changes applied instantly</li>
                <li>All components above should reflect the new colors immediately</li>
            </ol>
            <div class="alert alert-info">
                <i class="fas fa-lightbulb"></i>
                <strong>Note:</strong> The dynamic color system uses CSS variables to ensure all components 
                automatically inherit the colors set in Club Settings. No hard-coded colors remain!
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh test for real-time updates
        let refreshCount = 0;
        function autoRefreshTest() {
            refreshCount++;
            if (refreshCount <= 20) { // Refresh for 2 minutes max
                if (window.VIVOColors) {
                    window.VIVOColors.refresh();
                }
                setTimeout(autoRefreshTest, 6000); // Every 6 seconds
            }
        }
        
        // Start auto-refresh after page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(autoRefreshTest, 3000); // Start after 3 seconds
        });
    </script>
</body>
</html>
