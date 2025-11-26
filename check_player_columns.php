<?php
require_once 'wp-config.php';

try {
    $db = get_db_connection();
    
    // Get table schema
    $result = $db->query("PRAGMA table_info(players)");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Players Table Columns:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>" . $column['name'] . " (" . $column['type'] . ")</li>";
    }
    echo "</ul>";
    
    // Also get a sample record to see the structure
    $sampleResult = $db->query("SELECT * FROM players LIMIT 1");
    $sample = $sampleResult->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Sample Record Structure:</h3>";
    echo "<pre>";
    print_r(array_keys($sample ?: []));
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
