<?php
/**
 * VIVO United - Update All Pages to Use Club Colors
 * This script updates all PHP pages to use the dynamic color system
 */

echo "<h1>VIVO United - Color System Update</h1>\n";
echo "<pre>\n";

// Files to update
$pages_to_update = [
    'index.php',
    'events.php', 
    'players.php',
    'teams.php',
    'coaches.php',
    'dashboard.php',
    'attendance.php',
    'event_calendar.php',
    'event_details.php',
    'team_details.php',
    'edit_event.php',
    'edit_player.php',
    'edit_team.php',
    'edit_coach.php',
    'add_player.php',
    'add_team.php',
    'add_coach.php',
    'club_settings.php'
];

$updated_count = 0;
$errors = [];

foreach ($pages_to_update as $page) {
    if (!file_exists($page)) {
        echo "⚠️  Skipping $page (file not found)\n";
        continue;
    }
    
    try {
        $content = file_get_contents($page);
        $original_content = $content;
        
        // Check if color system is already included
        if (strpos($content, "require_once 'includes/color_system.php'") === false && 
            strpos($content, 'includes/color_system.php') === false) {
            
            // Find PHP opening tag and add color system include
            if (preg_match('/(<\?php.*?)(require_once|include)/s', $content, $matches)) {
                $content = str_replace($matches[0], $matches[1] . "require_once 'includes/color_system.php';\n" . $matches[2], $content);
                echo "✅ Added color system include to $page\n";
            } else if (strpos($content, '<?php') !== false) {
                $content = str_replace('<?php', "<?php\nrequire_once 'includes/color_system.php';", $content);
                echo "✅ Added color system include to $page\n";
            }
        }
        
        // Update hardcoded colors in CSS
        $color_replacements = [
            // VIVO Red variations
            '#dc2626' => 'var(--primary)',
            '#b91c1c' => 'var(--secondary)', 
            '#991b1b' => 'var(--secondary)',
            '#ef4444' => 'var(--primary)',
            '#f87171' => 'var(--primary-light)',
            
            // Background colors
            'background: #dc2626' => 'background: var(--primary)',
            'background-color: #dc2626' => 'background-color: var(--primary)',
            'border-color: #dc2626' => 'border-color: var(--primary)',
            'color: #dc2626' => 'color: var(--primary)',
            
            // Hover states
            'background: #b91c1c' => 'background: var(--secondary)',
            'background-color: #b91c1c' => 'background-color: var(--secondary)',
            'border-color: #b91c1c' => 'border-color: var(--secondary)',
            
            // Success/Warning colors
            '#059669' => 'var(--success)',
            '#d97706' => 'var(--warning)',
        ];
        
        $replacements_made = 0;
        foreach ($color_replacements as $old_color => $new_color) {
            $new_content = str_replace($old_color, $new_color, $content);
            if ($new_content !== $content) {
                $content = $new_content;
                $replacements_made++;
            }
        }
        
        // Add dynamic CSS generation if <style> tag exists and doesn't have it
        if (strpos($content, '<style>') !== false && 
            strpos($content, 'VIVOColorSystem::generateDynamicCSS()') === false) {
            
            $content = str_replace('<style>', 
                "<style>\n        <?php echo VIVOColorSystem::generateDynamicCSS(); ?>\n        ", 
                $content);
            echo "✅ Added dynamic CSS generation to $page\n";
            $replacements_made++;
        }
        
        // Only write file if changes were made
        if ($content !== $original_content) {
            file_put_contents($page, $content);
            $updated_count++;
            echo "✅ Updated $page ($replacements_made replacements)\n";
        } else {
            echo "ℹ️  No changes needed for $page\n";
        }
        
    } catch (Exception $e) {
        $errors[] = "$page: " . $e->getMessage();
        echo "❌ Error updating $page: " . $e->getMessage() . "\n";
    }
}

echo "\n=== UPDATE SUMMARY ===\n";
echo "📊 Files processed: " . count($pages_to_update) . "\n";
echo "✅ Files updated: $updated_count\n";
echo "❌ Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\n=== ERRORS ===\n";
    foreach ($errors as $error) {
        echo "❌ $error\n";
    }
}

echo "\n🎉 Color system update completed!\n";
echo "📝 Next steps:\n";
echo "   1. Test pages to ensure colors are working\n";
echo "   2. Check club_settings.php to customize colors\n";
echo "   3. Clear browser cache if needed\n";

echo "</pre>\n";
?>
