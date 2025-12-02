<?php
/**
 * Migration: Create users table (if missing) and link players to users via user_id
 * - Adds users table (email, username, password_hash, role, player_id)
 * - Adds players.user_id column when missing
 * - Creates a sample admin and a sample player user for demo use (only when run interactively)
 */

require_once __DIR__ . '/../database_factory.php';

$db = DatabaseFactory::getConnection();

echo "🔐 Starting authentication migration...\n";

// Ensure users table exists (createTables in DatabaseFactory will already do this on connection), but be explicit
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    username TEXT UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'player',
    player_id INTEGER DEFAULT NULL,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE SET NULL
)");

// Add players.user_id column if missing
$stmt = $db->query("PRAGMA table_info(players)");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
$colNames = array_column($cols, 'name');

if (!in_array('user_id', $colNames)) {
    echo "➕ Adding players.user_id column...\n";
    try {
        $db->exec("ALTER TABLE players ADD COLUMN user_id INTEGER DEFAULT NULL");
        echo "  ✅ players.user_id added\n";
    } catch (Exception $e) {
        echo "  ❌ Failed to add players.user_id: " . $e->getMessage() . "\n";
    }
} else {
    echo "⏭ players.user_id already exists\n";
}

// Seed a basic admin account if none exists (for local/dev only)
$existing = $db->query("SELECT COUNT(*) as c FROM users")->fetch();
if ($existing && (int)$existing['c'] === 0) {
    echo "🔧 No users found — creating sample admin and sample player user for demo (email: admin@local / player@sample.org)\n";
    $passwordAdmin = password_hash('admin123', PASSWORD_DEFAULT);
    $passwordPlayer = password_hash('player123', PASSWORD_DEFAULT);

    // Create sample admin user (no linked player)
    $ins = $db->prepare("INSERT INTO users (email, username, password_hash, role) VALUES (?,?,?,?)");
    $ins->execute(['admin@local', 'admin', $passwordAdmin, 'admin']);

    // If players table has at least one player, attach a player user
    $playerRow = $db->query("SELECT id, email FROM players ORDER BY id LIMIT 1")->fetch();
    if ($playerRow) {
        $playerEmail = $playerRow['email'] ?: 'player@sample.org';
        $ins->execute([$playerEmail, 'player1', $passwordPlayer, 'player']);
        $newUserId = $db->lastInsertId();
        // Link to the player
        $db->exec("UPDATE players SET user_id = $newUserId WHERE id = " . (int)$playerRow['id']);
        echo "  ✅ Created player user ($playerEmail) and linked to player id={$playerRow['id']}\n";
    }

    echo "  ✅ Admin user created: admin@local (password: admin123)\n";
}

echo "🔐 Authentication migration completed.\n";
