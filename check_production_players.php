<?php
// Check players on production
require_once 'wp-config.php';

try {
    $db = get_db_connection();
    
    echo "<h3>Players Available on Production:</h3>";
    $players = $db->query("SELECT id, name, surname FROM players ORDER BY id LIMIT 10")->fetchAll();
    
    if (empty($players)) {
        echo "❌ No players found in production database.";
    } else {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Surname</th><th>Edit Link</th></tr>";
        foreach ($players as $p) {
            echo "<tr>";
            echo "<td>" . $p['id'] . "</td>";
            echo "<td>" . ($p['name'] ?? 'NULL') . "</td>";
            echo "<td>" . ($p['surname'] ?? 'NULL') . "</td>";
            echo "<td><a href='edit_player.php?id=" . $p['id'] . "'>Edit</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
