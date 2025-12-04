<?php
require_once 'includes/auth.php';

$token = $_GET['token'] ?? null;
if (!$token) {
    die('Invalid verification request');
}

$user = verifyEmailToken($token);
if (!$user) {
    // token invalid or expired
    header('Location: login.php?message=' . urlencode('Verification token invalid or expired.'));
    exit;
}

// Auto-login the user after verification
$_SESSION['user_logged_in'] = true;
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'] ?? $user['email'];
$_SESSION['user_role'] = $user['role'] ?? 'player';

header('Location: player_profile.php?id=' . ($user['player_id'] ?? ''));
exit;
