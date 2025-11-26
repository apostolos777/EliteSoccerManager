<?php
// Migrate color_* settings from database.db to vivo_football.db
$src = __DIR__ . "/../database.db";
$dst = __DIR__ . "/../vivo_football.db";
try {
    $s = new PDO("sqlite:$src");
    $s->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $d = new PDO("sqlite:$dst");
    $d->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $rows = $s->query("SELECT setting_key, setting_value FROM club_settings WHERE setting_key LIKE 'color_%'")->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($rows) . " color rows in $src\n";

    $d->exec("CREATE TABLE IF NOT EXISTS club_settings (id INTEGER PRIMARY KEY AUTOINCREMENT, setting_key TEXT UNIQUE, setting_value TEXT, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $stmt = $d->prepare("INSERT OR REPLACE INTO club_settings (setting_key, setting_value, updated_at) VALUES (?, ?, datetime('now'))");

    foreach ($rows as $r) {
        $stmt->execute([$r['setting_key'], $r['setting_value']]);
        echo "Migrated {$r['setting_key']} = {$r['setting_value']}\n";
    }

    echo "Migration complete\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
