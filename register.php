<?php
// Simple player self-registration (claim profile)
require_once 'includes/auth.php';
require_once 'database_factory.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$db = DatabaseFactory::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $playerId = intval($_POST['player_id'] ?? 0);

    if (empty($email) || empty($password) || empty($confirm)) {
        $error = 'Please fill all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // verify player exists and (optionally) email matches
        $stmt = $db->prepare('SELECT id, email FROM players WHERE id = ? LIMIT 1');
        $stmt->execute([$playerId]);
        $player = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$player) {
            $error = 'Player not found. Please check your Player ID.';
        } else {
            if (!empty($player['email']) && strtolower(trim($player['email'])) !== strtolower(trim($email))) {
                $error = 'Provided email does not match the email on file for that player.';
            } else {
                // create user
                $pwHash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $db->prepare('INSERT INTO users (email, username, password_hash, role, player_id) VALUES (?, ?, ?, ?, ?)');
                $username = strstr($email, '@', true);
                try {
                    $ins->execute([$email, $username, $pwHash, 'player', $playerId]);
                    $newId = $db->lastInsertId();
                    // update players.user_id
                    $db->prepare('UPDATE players SET user_id = ? WHERE id = ?')->execute([$newId, $playerId]);

                    // Create verification token (expire in 48 hours)
                    if (function_exists('createEmailVerificationToken')) {
                        $token = createEmailVerificationToken((int)$newId, 48);
                    } else {
                        // fallback: store token manually
                        $token = bin2hex(random_bytes(16));
                        $expires = (new DateTime())->add(new DateInterval('PT172800S'))->format('Y-m-d H:i:s');
                        $db->prepare('UPDATE users SET verification_token=?, verification_expires=? WHERE id=?')->execute([$token, $expires, $newId]);
                    }

                    // Show verification link for dev/ref (don't auto-login until verified)
                    $verifyLink = sprintf('%s/verify_email.php?token=%s', rtrim((isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : ''), '/'), $token);
                    // For dev environments, display the link so testers can verify
                    $message = 'Account created. Please verify your email address using the link provided.';
                    $message .= "<div style=\"margin-top:10px;\"><a href=\"{$verifyLink}\">Verify email &rarr;</a></div>";
                } catch (Exception $e) {
                    $error = 'Failed to create account - email might already be taken.';
                }
            }
        }
    }
}

// simple registration page
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Claim your Player Profile</title>
    <?php require_once 'includes/css_helper.php'; vivo_include_head_css(); ?>
</head>
<body>
    <div class="container" style="max-width:640px;margin:48px auto">
        <h2>Claim your player profile</h2>
        <p>Enter your Player ID (shown in your club system) and the email that matches your player record.</p>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group"><label for="player_id">Player ID</label><input id="player_id" name="player_id" type="number" required class="form-control" value="<?= htmlspecialchars($_POST['player_id'] ?? '') ?>"></div>
            <div class="form-group"><label for="email">Email</label><input id="email" name="email" type="email" required class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"></div>
            <div class="form-group"><label for="password">Password</label><input id="password" name="password" type="password" required class="form-control"></div>
            <div class="form-group"><label for="confirm">Confirm password</label><input id="confirm" name="confirm" type="password" required class="form-control"></div>
            <button class="btn btn-primary" type="submit">Create account & claim profile</button>
            <a href="login.php" class="btn btn-link">Already have an account? Login</a>
        </form>
    </div>
</body>
</html>
