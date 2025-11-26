<?php
require_once "wp-config.php";
$db = get_db_connection();
$db->exec("CREATE TABLE IF NOT EXISTS club_settings (id INTEGER PRIMARY KEY AUTOINCREMENT, setting_key TEXT UNIQUE NOT NULL, setting_value TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
$stmt = $db->prepare("INSERT OR REPLACE INTO club_settings (setting_key, setting_value) VALUES (?, ?)");
$colors = ["color_primary" => "#dc2626", "color_secondary" => "#991b1b", "color_accent" => "#fef2f2", "color_success" => "#059669", "color_warning" => "#d97706", "color_danger" => "#dc2626"];
foreach ($colors as $key => $value) { $stmt->execute([$key, $value]); }
echo "Setup complete! Colors configured.";
?>
