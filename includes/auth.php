<?php
// Enhanced authentication system for VIVO United

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    // Support both legacy and new session keys
    return (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) || (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true);
}

function requireLogin() {
    if (!isLoggedIn()) {
        // Prevent redirect loops
        $currentPage = basename($_SERVER['PHP_SELF']);
        if ($currentPage !== 'login.php') {
            header('Location: login.php');
            exit();
        }
    }
}

function isAdmin() {
    return isLoggedIn() && (
        ($_SESSION['user_role'] ?? '') === 'admin' ||
        ($_SESSION['username'] ?? '') === 'admin' ||
        ($_SESSION['role'] ?? '') === 'admin'
    );
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: players.php?error=' . urlencode('Access denied. Admin privileges required.'));
        exit();
    }
}

function logout() {
    // Clear all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }

    // Destroy the session
    session_destroy();

    // Set a flag to prevent auto-login
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['just_logged_out'] = true;
}

function login($username, $password) {
    // If a DB is available with a users table, use it
    try {
        if (class_exists('DatabaseFactory')) {
            $db = DatabaseFactory::getConnection();

            // detect users table
            $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetchAll();
            if (count($tables) > 0) {
                $stmt = $db->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) AND status = 'active' LIMIT 1");
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && isset($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                    // Successful login
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['logged_in'] = true; // legacy key used elsewhere
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['username'] = $user['username'] ?? $user['email'];
                    $_SESSION['user_role'] = $user['role'] ?? 'player';
                    $_SESSION['login_time'] = time();
                    unset($_SESSION['just_logged_out']);
                    return true;
                }
            }
        }
    } catch (Exception $e) {
        error_log('Auth login error: ' . $e->getMessage());
    }

    // Fallback to legacy hard-coded users (kept for backward compatibility)
    $valid_users = [
        'vivoadmin' => 'password123',
        'admin' => 'admin123'
    ];
    if (isset($valid_users[$username]) && $valid_users[$username] === $password) {
        $_SESSION['user_logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['login_time'] = time();
        unset($_SESSION['just_logged_out']);
        return true;
    }

    return false;
}

/**
 * Returns true when the current user (session) owns the provided player id.
 * Admins always return true.
 */
function currentUserOwnsPlayer($player_id) {
    if (!isLoggedIn()) return false;
    if (isAdmin()) return true;

    $uid = $_SESSION['user_id'] ?? null;
    if (!$uid) return false;

    try {
        // Prefer the central DatabaseFactory connection if available
        if (class_exists('DatabaseFactory')) {
            $db = DatabaseFactory::getConnection();
        } elseif (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
            // Some pages set a local $db (like player_profile demo). Use it when provided.
            $db = $GLOBALS['db'];
        } else {
            // Fallback: attempt to open the default app database file
            $dbFile = __DIR__ . '/../database.db';
            if (!is_file($dbFile)) return false;
            $db = new PDO('sqlite:' . $dbFile);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        // Ensure players table supports user_id
        $cols = $db->query("PRAGMA table_info(players)")->fetchAll(PDO::FETCH_ASSOC);
        $names = array_column($cols, 'name');
        if (!in_array('user_id', $names)) return false;

        $stmt = $db->prepare("SELECT user_id FROM players WHERE id = ? LIMIT 1");
        $stmt->execute([$player_id]);
        $player = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$player) return false;

        return ((int)($player['user_id'] ?? 0) === (int)$uid);
    } catch (Exception $e) {
        // If something unexpected happens, deny access safely
        error_log('currentUserOwnsPlayer error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Generate and store a verification token for a user (valid for $hours validity).
 * Returns the token string on success or null on failure.
 */
function createEmailVerificationToken(int $userId, int $hours = 48): ?string {
    try {
        if (class_exists('DatabaseFactory')) {
            $db = DatabaseFactory::getConnection();
        } elseif (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
            $db = $GLOBALS['db'];
        } else {
            $db = new PDO('sqlite:' . __DIR__ . '/../database.db');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        $token = bin2hex(random_bytes(16));
        $expires = (new DateTime())->add(new DateInterval('PT' . ($hours*3600) . 'S'))->format('Y-m-d H:i:s');

        $stmt = $db->prepare('UPDATE users SET verification_token = ?, verification_expires = ?, email_verified = 0 WHERE id = ?');
        $stmt->execute([$token, $expires, $userId]);
        return $token;
    } catch (Exception $e) {
        error_log('createEmailVerificationToken error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Verify an email token. If successful, mark email_verified and clear token and expiry.
 * On success, returns the user row; on failure, returns null.
 */
function verifyEmailToken(string $token): ?array {
    try {
        if (class_exists('DatabaseFactory')) {
            $db = DatabaseFactory::getConnection();
        } elseif (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
            $db = $GLOBALS['db'];
        } else {
            $db = new PDO('sqlite:' . __DIR__ . '/../database.db');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        $now = (new DateTime())->format('Y-m-d H:i:s');
        $stmt = $db->prepare('SELECT * FROM users WHERE verification_token = ? AND (verification_expires IS NULL OR verification_expires >= ?) LIMIT 1');
        $stmt->execute([$token, $now]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return null;

        $update = $db->prepare('UPDATE users SET email_verified = 1, verification_token = NULL, verification_expires = NULL WHERE id = ?');
        $update->execute([$user['id']]);

        return $user;
    } catch (Exception $e) {
        error_log('verifyEmailToken error: ' . $e->getMessage());
        return null;
    }
}

function checkSessionTimeout() {
    $timeout = 3600; // 1 hour
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout) {
        logout();
        header('Location: login.php?message=' . urlencode('Session expired. Please login again.'));
        exit();
    }
    $_SESSION['login_time'] = time(); // Update last activity
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? 1,
        'username' => $_SESSION['username'] ?? 'admin',
        'role' => $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'admin'
    ];
}

// Development auto-login helper (only active if no user and not just logged out)
// NOTE: Remove or guard with an environment flag for production.
if (!isLoggedIn() && !isset($_SESSION['just_logged_out']) && getenv('VIVO_DEV_MODE') === 'true') {
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['user_role'] = 'admin';
    $_SESSION['login_time'] = time();
}

// Intentionally no closing PHP tag to avoid accidental output
