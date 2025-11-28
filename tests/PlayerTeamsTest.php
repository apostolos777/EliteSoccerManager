<?php
use PHPUnit\Framework\TestCase;

class PlayerTeamsTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Minimal schema used by the tests
        $this->db->exec("CREATE TABLE players (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT);");
        $this->db->exec("CREATE TABLE teams (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT);");
        $this->db->exec("CREATE TABLE player_teams (id INTEGER PRIMARY KEY AUTOINCREMENT, player_id INTEGER NOT NULL, team_id INTEGER NOT NULL, UNIQUE(player_id, team_id));");
    }

    public function testAddingMultipleTeamsCreatesPlayerTeamsEntries()
    {
        // create teams and a player
        $this->db->exec("INSERT INTO teams (name) VALUES ('Team A'), ('Team B'), ('Team C');");
        $this->db->exec("INSERT INTO players (name) VALUES ('Alice');");

        $playerId = $this->db->lastInsertId();

        // fetch team ids
        $stmt = $this->db->query("SELECT id FROM teams ORDER BY id");
        $teamIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Simulate the app's add flow: insert into join table
        $insert = $this->db->prepare("INSERT INTO player_teams (player_id, team_id) VALUES (?, ?)");
        foreach ($teamIds as $t) {
            $insert->execute([(int)$playerId, (int)$t]);
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM player_teams WHERE player_id = ?");
        $stmt->execute([$playerId]);
        $this->assertEquals(count($teamIds), (int)$stmt->fetchColumn());
    }

    public function testEditingPlayerReplacesOldMappings()
    {
        // create teams and a player
        $this->db->exec("INSERT INTO teams (name) VALUES ('A'), ('B'), ('C'), ('D');");
        $this->db->exec("INSERT INTO players (name) VALUES ('Bob');");
        $playerId = $this->db->lastInsertId();

        // initial mapping: A,B
        $insert = $this->db->prepare("INSERT INTO player_teams (player_id, team_id) VALUES (?, ?)");
        $insert->execute([$playerId, 1]);
        $insert->execute([$playerId, 2]);

        // Verify initial state
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM player_teams WHERE player_id = ?");
        $stmt->execute([$playerId]);
        $this->assertEquals(2, (int)$stmt->fetchColumn());

        // Simulate edit: clear and set C,D
        $this->db->prepare('DELETE FROM player_teams WHERE player_id = ?')->execute([$playerId]);
        $insert->execute([$playerId, 3]);
        $insert->execute([$playerId, 4]);

        $stmt->execute([$playerId]);
        $this->assertEquals(2, (int)$stmt->fetchColumn());

        // Ensure the remaining rows are the expected team_ids (3 and 4)
        $stmt = $this->db->prepare("SELECT team_id FROM player_teams WHERE player_id = ? ORDER BY team_id");
        $stmt->execute([$playerId]);
        $teams = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $this->assertEquals(["3","4"], $teams);
    }

    public function testUniqueConstraintPreventsDuplicateMappings()
    {
        $this->db->exec("INSERT INTO teams (name) VALUES ('X');");
        $this->db->exec("INSERT INTO players (name) VALUES ('Chang');");
        $playerId = $this->db->lastInsertId();

        $insert = $this->db->prepare("INSERT INTO player_teams (player_id, team_id) VALUES (?, ?)");
        $insert->execute([$playerId, 1]);

        $this->expectException(PDOException::class);
        // duplicate insert should violate UNIQUE(player_id,team_id)
        $insert->execute([$playerId, 1]);
    }
}
