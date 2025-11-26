<?php
/**
 * VIVO United - WordPress Style Database Setup
 */

// Load WordPress-style config
require_once 'wp-config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIVO United - Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .box { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .btn { background: #0073aa; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; }
    </style>
</head>
<body>

<h1>🔧 VIVO United Database Setup</h1>

<div class="box">
    <h2>Step 1: Testing Database Connection</h2>
    <?php
    try {
        $db = get_db_connection();
        echo '<p class="success">✓ Connected to MySQL server successfully</p>';
        
        // Check if database exists
        $db_exists = wp_get_var("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [DB_NAME]);
        if ($db_exists) {
            echo '<p class="success">✓ Database "' . DB_NAME . '" exists</p>';
        } else {
            echo '<p class="error">⚠ Database "' . DB_NAME . '" does not exist</p>';
            echo '<p>Please create the database through your hosting control panel.</p>';
        }
        
    } catch (Exception $e) {
        echo '<p class="error">✗ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>Please check your database credentials in wp-config.php</strong></p>';
        exit;
    }
    ?>
</div>

<div class="box">
    <h2>Step 2: Creating Database Tables</h2>
    <?php
    if ($db_exists) {
        // Load schema file
        if (file_exists('database_schema.sql')) {
            $schema = file_get_contents('database_schema.sql');
            
            // Split by semicolon and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $schema)));
            
            $success_count = 0;
            $error_count = 0;
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    try {
                        wp_query($statement);
                        $success_count++;
                    } catch (Exception $e) {
                        // Ignore table exists errors
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            echo '<p class="error">Warning: ' . htmlspecialchars($e->getMessage()) . '</p>';
                            $error_count++;
                        }
                    }
                }
            }
            
            echo '<p class="success">✓ Executed ' . $success_count . ' SQL statements successfully</p>';
            if ($error_count > 0) {
                echo '<p class="error">⚠ ' . $error_count . ' statements had warnings</p>';
            }
            
        } else {
            echo '<p class="error">✗ Schema file "database_schema.sql" not found</p>';
        }
        
        // Check tables
        $tables = wp_get_results("SHOW TABLES");
        echo '<p class="success">✓ Found ' . count($tables) . ' tables in database</p>';
        
        $required_tables = ['players', 'teams', 'events', 'attendance', 'club_settings'];
        foreach ($required_tables as $table) {
            $exists = wp_get_var("SHOW TABLES LIKE '$table'");
            if ($exists) {
                echo '<p class="success">✓ Table "' . $table . '" exists</p>';
            } else {
                echo '<p class="error">✗ Table "' . $table . '" missing</p>';
            }
        }
    }
    ?>
</div>

<div class="box">
    <h2>Step 3: Setup Complete!</h2>
    <p class="success">Database setup completed successfully!</p>
    <p><a href="index.php" class="btn">Go to Dashboard</a></p>
    <p><a href="test.php" class="btn" style="background: #666;">Run System Test</a></p>
</div>

</body>
</html>
