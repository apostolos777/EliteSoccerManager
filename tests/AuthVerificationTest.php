<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/auth.php';

class AuthVerificationTest extends TestCase
{
    public function testCreateAndVerifyToken()
    {
        // use in-memory DB and set as global for helper functions
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT UNIQUE NOT NULL, username TEXT UNIQUE, password_hash TEXT NOT NULL, role TEXT DEFAULT 'player', player_id INTEGER DEFAULT NULL, status TEXT DEFAULT 'active', email_verified INTEGER DEFAULT 0, verification_token TEXT DEFAULT NULL, verification_expires DATETIME DEFAULT NULL);");

        // seed user
        $db->exec("INSERT INTO users (email, username, password_hash) VALUES ('t@ex.com', 't', 'h')");
        $userId = (int)$db->lastInsertId();

        $GLOBALS['db'] = $db; // allow helper to use global PDO

        $token = createEmailVerificationToken($userId, 1); // one hour
        $this->assertNotEmpty($token);

        // Now verify using verifyEmailToken
        $found = verifyEmailToken($token);
        $this->assertNotNull($found);
        $this->assertEquals($userId, (int)$found['id']);

        // Ensure email_verified is set
        $row = $db->query('SELECT email_verified FROM users WHERE id = ' . $userId)->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(1, (int)$row['email_verified']);
    }
}
