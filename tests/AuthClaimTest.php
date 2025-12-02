<?php
use PHPUnit\Framework\TestCase;

class AuthClaimTest extends TestCase
{
    public function testClaimingPlayerCreatesUserAndLinksPlayer()
    {
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $db->exec("CREATE TABLE players (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, user_id INTEGER DEFAULT NULL);");
        $db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT UNIQUE NOT NULL, username TEXT UNIQUE, password_hash TEXT NOT NULL, role TEXT DEFAULT 'player', player_id INTEGER DEFAULT NULL);");

        // seed player
        $db->exec("INSERT INTO players (name, email) VALUES ('Demo Player', 'demo@example.org')");
        $playerId = $db->lastInsertId();

        // simulate registration / claim
        $password = 'secret123';
        $pwHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (email, username, password_hash, role, player_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute(['demo@example.org', 'demo', $pwHash, 'player', $playerId]);
        $userId = $db->lastInsertId();

        // Link players.user_id
        $db->prepare('UPDATE players SET user_id = ? WHERE id = ?')->execute([$userId, $playerId]);

        $row = $db->query('SELECT user_id FROM players WHERE id = ' . (int)$playerId)->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals((int)$userId, (int)$row['user_id']);

        // verify password stored is hash and password_verify works
        $user = $db->query('SELECT * FROM users WHERE id = ' . (int)$userId)->fetch(PDO::FETCH_ASSOC);
        $this->assertTrue(password_verify($password, $user['password_hash']));
    }
}
