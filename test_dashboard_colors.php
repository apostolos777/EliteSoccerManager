<?php
require_once 'includes/auth.php';
require_once 'includes/color_system.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Database connection
$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Clear color cache
VIVOColorSystem::clearCache();

// Get colors
$colors = VIVOColorSystem::getColors($db);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Color System Test</title>
    <style><?php echo VIVOColorSystem::generateDynamicCSS($db); ?></style>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .color-box { 
            width: 200px; 
            height: 100px; 
            margin: 10px; 
            display: inline-block; 
            text-align: center; 
            line-height: 100px; 
            color: white; 
            font-weight: bold; 
        }
        .primary { background: var(--primary); }
        .secondary { background: var(--secondary); }
        .accent { background: var(--accent); color: black; }
        .success { background: var(--success); }
        .warning { background: var(--warning); }
        .danger { background: var(--danger); }
    </style>
</head>
<body>
    <h1>VIVO United Color System Test</h1>
    
    <h2>Database Colors:</h2>
    <?php foreach ($colors as $key => $value): ?>
        <p><strong><?php echo $key; ?>:</strong> <?php echo $value; ?></p>
    <?php endforeach; ?>
    
    <h2>CSS Variables in Action:</h2>
    <div class="color-box primary">Primary</div>
    <div class="color-box secondary">Secondary</div>
    <div class="color-box accent">Accent</div>
    <div class="color-box success">Success</div>
    <div class="color-box warning">Warning</div>
    <div class="color-box danger">Danger</div>
    
    <h2>Generated CSS:</h2>
    <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; font-size: 12px; overflow-x: auto;">
<?php echo htmlspecialchars(VIVOColorSystem::generateDynamicCSS($db)); ?>
    </pre>
</body>
</html>
