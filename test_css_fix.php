<?php
// Test file to verify CSS fixes are working
require_once 'includes/color_system.php';
require_once __DIR__ . '/wp-config.php';
require_once 'includes/auth.php';

try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Fix Test</title>
    <?php vivo_include_head_css($db); ?>
</head>
<body style="padding: 20px;">
    <h1>CSS Fix Test Page</h1>
    <p>This page tests if CSS is loading correctly without showing raw CSS code.</p>
    
    <div style="background: var(--primary); color: white; padding: 10px; margin: 10px 0; border-radius: 5px;">
        Primary Color Test - Should show club's primary color
    </div>
    
    <div style="background: var(--secondary); color: white; padding: 10px; margin: 10px 0; border-radius: 5px;">
        Secondary Color Test - Should show club's secondary color
    </div>
    
    <div style="background: var(--accent); padding: 10px; margin: 10px 0; border-radius: 5px;">
        Accent Color Test - Should show club's accent color
    </div>
    
    <p><strong>CSS Fix Summary:</strong></p>
    <ul>
        <li>✅ add_player.php - Fixed CSS inclusion, replaced invalid get_dynamic_css() call</li>
        <li>✅ players.php - Removed nested VIVOColorSystem::outputCSS() call, updated color variables</li>
        <li>✅ player_profile.php - Removed invalid style block after &lt;/html&gt; tag</li>
        <li>✅ edit_player.php - Removed nested VIVOColorSystem::outputCSS() call, updated color variables</li>
    </ul>
    
    <p>If you can see this page with proper styling and no raw CSS code, all fixes are working correctly!</p>
</body>
</html>
