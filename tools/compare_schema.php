<?php
/**
 * Compare two schema files and print a diff.
 * Usage: php tools/compare_schema.php path/to/local_schema.sql path/to/live_schema.sql
 *
 * This helper is non-destructive. It relies on a local copy of the live schema file
 * (the live schema can be exported manually via mysqldump or similar and stored in a file).
 */

if ($argc < 3) {
    fwrite(STDERR, "Usage: php tools/compare_schema.php local_schema.sql live_schema.sql\n");
    exit(1);
}

$local = $argv[1];
$live = $argv[2];

if (!file_exists($local)) { fwrite(STDERR, "Local schema file not found: $local\n"); exit(2); }
if (!file_exists($live)) { fwrite(STDERR, "Live schema file not found: $live\n"); exit(2); }

$diffCmd = null;
// Prefer system diff if available for clearer output
if (function_exists('exec')) {
    $which = trim(shell_exec('which diff 2>/dev/null'));
    if ($which) $diffCmd = 'diff -u ' . escapeshellarg($local) . ' ' . escapeshellarg($live);
}

if ($diffCmd) {
    echo "Using system diff:\n\n";
    passthru($diffCmd);
    exit(0);
}

// Fallback: simple file compare
$a = file($local, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$b = file($live, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$max = max(count($a), count($b));
$changes = 0;
for ($i = 0; $i < $max; $i++) {
    $la = $a[$i] ?? '';
    $lb = $b[$i] ?? '';
    if ($la !== $lb) {
        echo sprintf("- local: %s\n+ live:  %s\n\n", $la, $lb);
        $changes++;
    }
}

if ($changes === 0) echo "No differences detected (line-by-line)\n";
else echo "Found $changes differing lines\n";

exit(0);
