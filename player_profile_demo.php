<?php
// Demo page to preview the KickCV-like sample profile without authentication
require_once 'includes/css_helper.php';
// connect directly to the sample DB
if (!is_file(__DIR__ . '/database_kickcv.db')) {
    die('Sample database missing. Run scripts/create_kickcv_sample_db.php to generate it.');
}
$db = new PDO('sqlite:database_kickcv.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// set GET overrides so included script uses proper DB and id
$_GET['use_db'] = 'kickcv';
$_GET['id'] = '1';
// Auto-login demo user if present so demo remains usable without manual login
session_start();
try {
    $r = $db->query("SELECT * FROM users WHERE username = 'sam_sample' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($r) {
        // set session as this demo player
        $_SESSION['user_logged_in'] = true;
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $r['id'];
        $_SESSION['username'] = $r['username'];
        $_SESSION['user_email'] = $r['email'];
        $_SESSION['user_role'] = $r['role'] ?? 'player';
        // ensure session reflects the linked player id
        if (!empty($r['player_id'])) {
            $_SESSION['user_player_id'] = $r['player_id'];
        }
    }
} catch (Exception $e) { /* ignore */ }

require 'player_profile.php';
?>
