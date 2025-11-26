<?php
/**
 * Minimal index.php that loads components one by one to isolate the age error
 */

echo "Starting minimal index loading...<br>\n";
flush();

// Step 1: Basic PHP setup
if (!session_id()) {
    session_start();
}
$currentPage = 'dashboard';
echo "Step 1: Session started<br>\n";
flush();

// Step 2: Load wp-config
echo "Step 2: Loading wp-config.php...<br>\n";
flush();
try {
    require_once 'wp-config.php';
    echo "wp-config.php loaded successfully<br>\n";
    flush();
} catch (Exception $e) {
    echo "ERROR in wp-config.php: " . $e->getMessage() . "<br>\n";
    exit;
}

// Step 3: Load color system
echo "Step 3: Loading includes/color_system.php...<br>\n";
flush();
try {
    require_once 'includes/color_system.php';
    echo "color_system.php loaded successfully<br>\n";
    flush();
} catch (Exception $e) {
    echo "ERROR in color_system.php: " . $e->getMessage() . "<br>\n";
    exit;
}

// Step 4: Basic database queries
echo "Step 4: Testing database queries...<br>\n";
flush();
try {
    $db = get_db_connection();
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM players");
    $result = $stmt->fetch();
    $totalPlayers = $result ? $result['count'] : 0;
    echo "Total players: $totalPlayers<br>\n";
    flush();
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM players WHERE is_active = 1");
    $result = $stmt->fetch();
    $activePlayersCount = $result ? $result['count'] : 0;
    echo "Active players: $activePlayersCount<br>\n";
    flush();
    
} catch (Exception $e) {
    echo "ERROR in database queries: " . $e->getMessage() . "<br>\n";
    exit;
}

// Step 5: Start HTML output (this is where the error might be triggered)
echo "Step 5: Starting HTML output...<br>\n";
flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minimal Index Test</title>
    <?php
    echo "<!-- Loading CSS helper -->\n";
    flush();
    try {
        require_once 'includes/css_helper.php';
        vivo_include_head_css();
        echo "<!-- CSS helper loaded successfully -->\n";
        flush();
    } catch (Exception $e) {
        echo "<!-- ERROR in CSS helper: " . $e->getMessage() . " -->\n";
        exit;
    }
    ?>
</head>
<body>
    <?php
    echo "Step 6: Loading sidebar...<br>\n";
    flush();
    try {
        include 'includes/sidebar.php';
        echo "Sidebar loaded successfully!<br>\n";
        flush();
    } catch (Exception $e) {
        echo "ERROR in sidebar: " . $e->getMessage() . "<br>\n";
        exit;
    }
    ?>
    
    <div class="content-main">
        <h1>Minimal Index Test Complete</h1>
        <p>If you see this message, all components loaded successfully!</p>
        <p>Total Players: <?php echo $totalPlayers; ?></p>
        <p>Active Players: <?php echo $activePlayersCount; ?></p>
    </div>
</body>
</html>
