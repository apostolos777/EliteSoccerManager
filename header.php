<?php
// Themed Header include — reads club settings for branding
require_once __DIR__ . '/database_config.php';
$clubSettings = [];
try {
		$db = DatabaseConfigSQLite::getConnection();
		$stmt = $db->query("SELECT setting_key, setting_value FROM club_settings");
		if ($stmt) while ($r = $stmt->fetch()) if (!empty($r['setting_key'])) $clubSettings[$r['setting_key']] = $r['setting_value'];
} catch (Throwable $e) { /* ignore - no DB yet */ }

$clubName = htmlspecialchars($clubSettings['club_name'] ?? 'VIVO United');
$tag = htmlspecialchars($clubSettings['club_tagline'] ?? 'Elite Football Manager');
?>
<header class="fc-hero" role="banner">
	<div class="container">
		<div style="display:flex;align-items:center;gap:18px;">
			<div style="flex:1">
				<h1 style="margin:0;font-size:2.4rem;line-height:1"><?php echo $clubName; ?></h1>
				<p style="margin:4px 0 0;color:var(--fc-accent);font-weight:600;"><?php echo $tag; ?></p>
			</div>
			<div style="text-align:right;">
				<a href="events.php" class="btn-secondary">Events</a>
				<a href="players.php" class="btn-primary" style="margin-left:8px;">Players</a>
			</div>
		</div>
	</div>
</header>
