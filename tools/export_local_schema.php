<?php
/**
 * Export local SQLite schema to a SQL file for manual comparison with live DB.
 * Usage: php tools/export_local_schema.php [output-file]
 */

$out = $argv[1] ?? __DIR__ . '/../schema_local.sql';
$dbFile = __DIR__ . '/../database.db';
if (!file_exists($dbFile)) {
    fwrite(STDERR, "Local database not found at $dbFile\n");
    exit(1);
}

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query schema from sqlite_master
    $stmt = $pdo->query("SELECT type, name, tbl_name, sql FROM sqlite_master WHERE type IN ('table','index','trigger') AND name NOT LIKE 'sqlite_%' ORDER BY type, name");

    $sqlDump = "-- VIVO local SQLite schema export\n-- Generated: " . date('c') . "\n\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['sql'])) {
            $sqlDump .= $row['sql'] . ";\n\n";
        }
    }

    file_put_contents($out, $sqlDump);
    echo "Exported schema to: $out\n";
    exit(0);
} catch (Exception $e) {
    fwrite(STDERR, "Failed to export schema: " . $e->getMessage() . "\n");
    exit(2);
}
