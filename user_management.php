<?php
require_once 'includes/auth.php';
require_once 'database_factory.php';
requireAdmin();

$db = DatabaseFactory::getConnection();

// Actions: resend, verify, deactivate
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid = intval($_POST['user_id'] ?? 0);
    if ($action === 'resend' && $uid) {
        $token = createEmailVerificationToken($uid, 48);
        // For local/dev, capture the link in session flash
        $link = sprintf('%s/verify_email.php?token=%s', (isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : ''), $token);
        $msg = "Verification link (dev): <a href=\"{$link}\">{$link}</a>";
        $_SESSION['admin_msg'] = $msg;
    } elseif ($action === 'verify' && $uid) {
        $stmt = $db->prepare('UPDATE users SET email_verified = 1, verification_token = NULL, verification_expires = NULL WHERE id = ?'); $stmt->execute([$uid]);
    } elseif ($action === 'deactivate' && $uid) {
        $stmt = $db->prepare('UPDATE users SET status = ? WHERE id = ?'); $stmt->execute(['inactive', $uid]);
    }
    header('Location: user_management.php');
    exit;
}

$users = $db->query('SELECT id, username, email, role, email_verified, status, player_id FROM users ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>User Management</title>
    <?php require_once 'includes/css_helper.php'; vivo_include_head_css(); ?>
</head>
<body>
    <div class="container" style="padding:24px;max-width:980px;margin:24px auto;">
        <h2>User Management (Admin)</h2>
        <?php if (!empty($_SESSION['admin_msg'])): ?>
            <div class="alert alert-info"><?php echo $_SESSION['admin_msg']; unset($_SESSION['admin_msg']); ?></div>
        <?php endif; ?>

        <table class="table" style="width:100%;border-collapse:collapse;">
            <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Verified</th><th>Player</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= (int)$u['id'] ?></td>
                    <td><?= htmlspecialchars($u['username'] ?? '') ?></td>
                    <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
                    <td><?= htmlspecialchars($u['role'] ?? '') ?></td>
                    <td><?= (int)($u['email_verified'] ?? 0) ? '<strong style="color:green">Yes</strong>' : '<em>No</em>' ?></td>
                    <td><?= htmlspecialchars($u['player_id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($u['status'] ?? '') ?></td>
                    <td>
                        <form method="POST" style="display:inline-block;margin-right:6px;">
                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                            <button name="action" value="resend" class="btn btn-sm">Resend</button>
                        </form>
                        <form method="POST" style="display:inline-block;margin-right:6px;">
                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                            <button name="action" value="verify" class="btn btn-sm">Mark Verified</button>
                        </form>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                            <button name="action" value="deactivate" class="btn btn-sm btn-danger">Deactivate</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</body>
</html>
