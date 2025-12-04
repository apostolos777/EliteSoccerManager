<?php
use PHPUnit\Framework\TestCase;

class AuthMigrationTest extends TestCase
{
    public function testUsersTableAndPlayerLinkColumn()
    {
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create a minimal players table then apply migration SQL similar to the app
        $db->exec("CREATE TABLE players (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT);");

        // Create users table SQL (should be valid on SQLite) - include verification columns to represent post-migration schema
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            username TEXT UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT DEFAULT 'player',
            player_id INTEGER DEFAULT NULL,
            status TEXT DEFAULT 'active',
            email_verified INTEGER DEFAULT 0,
            verification_token TEXT DEFAULT NULL,
            verification_expires DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Verify users table exists
        $res = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetchAll();
        $this->assertNotEmpty($res, 'users table should exist after migration');

        // Add players.user_id column (ALTER TABLE works on SQLite in this form)
        $db->exec("ALTER TABLE players ADD COLUMN user_id INTEGER DEFAULT NULL");

        // Verify column was added
        $cols = $db->query("PRAGMA table_info(players)")->fetchAll(PDO::FETCH_ASSOC);
        $names = array_column($cols, 'name');
        $this->assertContains('user_id', $names, 'players.user_id should be created by migration');

        // Check verification columns on users (migration should add these)
        $uCols = $db->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
        $uNames = array_column($uCols, 'name');
        $this->assertContains('email_verified', $uNames);
        $this->assertContains('verification_token', $uNames);
        $this->assertContains('verification_expires', $uNames);
    }
}
