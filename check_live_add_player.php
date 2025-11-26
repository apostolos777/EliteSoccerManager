<?php
// Check what's in the live add_player.php
$content = file_get_contents('https://www.vivounited.org/vivoapp/add_player.php');

echo "<h1>Live add_player.php Analysis</h1>";
echo "<pre>";

// Look for INSERT statements
if (preg_match('/INSERT INTO players[^;]+;/i', $content, $matches)) {
    echo "Found INSERT statement:\n";
    echo $matches[0] . "\n\n";
} else {
    echo "No INSERT INTO players statement found\n\n";
}

// Look for name column references
if (preg_match_all('/\bname\b/i', $content, $matches)) {
    echo "Found " . count($matches[0]) . " references to 'name':\n";
    foreach ($matches[0] as $match) {
        echo "  - $match\n";
    }
    echo "\n";
} else {
    echo "No 'name' references found\n\n";
}

// Look for first_name/last_name references
if (preg_match_all('/\bfirst_name\b|\blast_name\b/i', $content, $matches)) {
    echo "Found " . count($matches[0]) . " references to first_name/last_name:\n";
    foreach ($matches[0] as $match) {
        echo "  - $match\n";
    }
    echo "\n";
} else {
    echo "No first_name/last_name references found\n\n";
}

echo "File size: " . strlen($content) . " bytes\n";
echo "</pre>";
?>
