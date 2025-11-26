<?php
// Check production players table schema
require_once 'wp-config.php';

try {
    $db = get_db_connection();
    
    echo "<h3>Production Players Table Schema:</h3>";
    $result = $db->query("PRAGMA table_info(players)");
    $columns = $result->fetchAll();
    
    echo "<table border='1'>";
    echo "<tr><th>Column</th><th>Type</th><th>Not Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['name'] . "</td>";
        echo "<td>" . $col['type'] . "</td>";
        echo "<td>" . ($col['notnull'] ? 'YES' : 'NO') . "</td>";
        echo "<td>" . ($col['dflt_value'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Sample Players (using available columns):</h3>";
    // Try to find what name columns exist
    $nameColumns = [];
    foreach ($columns as $col) {
        if (strpos(strtolower($col['name']), 'name') !== false || 
            strpos(strtolower($col['name']), 'first') !== false ||
            strpos(strtolower($col['name']), 'last') !== false ||
            $col['name'] === 'surname') {
            $nameColumns[] = $col['name'];
        }
    }
    
    if (!empty($nameColumns)) {
        $columnList = implode(', ', array_merge(['id'], $nameColumns));
        $players = $db->query("SELECT $columnList FROM players LIMIT 5")->fetchAll();
        
        if (!empty($players)) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th>";
            foreach ($nameColumns as $col) {
                echo "<th>$col</th>";
            }
            echo "<th>Edit Link</th></tr>";
            
            foreach ($players as $p) {
                echo "<tr>";
                echo "<td>" . $p['id'] . "</td>";
                foreach ($nameColumns as $col) {
                    echo "<td>" . ($p[$col] ?? 'NULL') . "</td>";
                }
                echo "<td><a href='player_profile.php?id=" . $p['id'] . "&action=edit'>Edit</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
