<?php
// Creates a sample SQLite database pre-populated with a KickCV-style player for local testing
$fn = __DIR__ . '/../database_kickcv.db';
if (file_exists($fn)) unlink($fn);
$db = new PDO('sqlite:' . $fn);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// create minimal schema
$db->exec("CREATE TABLE players (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    first_name TEXT,
    last_name TEXT,
    nickname TEXT,
    nationality TEXT,
    profile_image TEXT,
    date_of_birth TEXT,
    age INTEGER,
    height INTEGER,
    weight INTEGER,
    preferred_foot TEXT,
    primary_position TEXT,
    jersey_number INTEGER,
    playing_style TEXT,
    personal_talents TEXT,
    career_history_json TEXT,
    skills_json TEXT,
    achievements_json TEXT,
    season_goals INTEGER,
    season_assists INTEGER,
    season_matches INTEGER,
    pass_accuracy INTEGER,
    goals_per_90 REAL,
    shots_on_target INTEGER,
    email TEXT,
    location TEXT
);");

$db->exec("CREATE TABLE teams (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT);");
$db->exec("CREATE TABLE player_teams (id INTEGER PRIMARY KEY AUTOINCREMENT, player_id INTEGER NOT NULL, team_id INTEGER NOT NULL, UNIQUE(player_id, team_id));");

// Add users table and a sample user linked to the sample player
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    username TEXT UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'player',
    player_id INTEGER DEFAULT NULL,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// sample teams
$db->exec("INSERT INTO teams (name) VALUES ('FC Barcelona'), ('Chelsea FC'), ('Real Madrid');");

// sample player (Samuel Eto'o style)
$player = [
    'name' => 'Samuel Eto\'o',
    'nickname' => 'Sam',
    'nationality' => 'Cameroon',
    'profile_image' => 'https://kickcv.com/images/player.jpg',
    'date_of_birth' => '1981-03-10',
    'age' => 44,
    'height' => 180,
    'weight' => 75,
    'preferred_foot' => 'Right',
    'primary_position' => 'Striker',
    'jersey_number' => 9,
    'playing_style' => 'Professional striker with extensive experience in top European leagues. Clinical finishing, speed, and technical ability.',
    'personal_talents' => 'Finishing,Speed,Dribbling,Positioning',
    'career_history_json' => json_encode([
        ['title' => 'FC Barcelona', 'period' => '2004 - 2009 | La Liga', 'desc' => '25 goals in 35 matches (2015) and key player in multiple seasons.'],
        ['title' => 'Chelsea FC', 'period' => '2013 - 2015 | Premier League', 'desc' => 'Part of the squad that won the Premier League in 2014.']
    ]),
    'skills_json' => json_encode(['Finishing' => 95, 'Speed' => 90, 'Dribbling' => 85, 'Positioning' => 92]),
    'achievements_json' => json_encode([
        ['title' => 'La Liga Top Scorer', 'sub' => '2022-2023 Season'],
        ['title' => 'African Player of the Year', 'sub' => '2010, 2012, 2013'],
        ['title' => 'Champions League Winner', 'sub' => '2009, 2010']
    ]),
    'season_goals' => 25,
    'season_assists' => 12,
    'season_matches' => 35,
    'pass_accuracy' => 87,
    'goals_per_90' => 1.4,
    'shots_on_target' => 42,
    'email' => 'sam@sample.org',
    'location' => 'Barcelona, Spain'
];

$insert = $db->prepare("INSERT INTO players (name,nickname,nationality,profile_image,date_of_birth,age,height,weight,preferred_foot,primary_position,jersey_number,playing_style,personal_talents,career_history_json,skills_json,achievements_json,season_goals,season_assists,season_matches,pass_accuracy,goals_per_90,shots_on_target,email,location) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
$insert->execute([
    $player['name'],$player['nickname'],$player['nationality'],$player['profile_image'],$player['date_of_birth'],$player['age'],$player['height'],$player['weight'],$player['preferred_foot'],$player['primary_position'],$player['jersey_number'],$player['playing_style'],$player['personal_talents'],$player['career_history_json'],$player['skills_json'],$player['achievements_json'],$player['season_goals'],$player['season_assists'],$player['season_matches'],$player['pass_accuracy'],$player['goals_per_90'],$player['shots_on_target'],$player['email'],$player['location']
]);
$playerId = $db->lastInsertId();

// assign to clubs
$assign = $db->prepare("INSERT INTO player_teams (player_id, team_id) VALUES (?,?)");
$assign->execute([$playerId, 1]);
$assign->execute([$playerId, 2]);

// create a corresponding user for that player with known password (player123)
$pw = password_hash('player123', PASSWORD_DEFAULT);
$userInsert = $db->prepare("INSERT INTO users (email, username, password_hash, role, player_id) VALUES (?, ?, ?, ?, ?)");
$userInsert->execute([ $player['email'], 'sam_sample', $pw, 'player', $playerId ]);

echo "Created sample DB at: " . $fn . " with player id=" . $playerId . " and linked user 'sam_sample' (password: player123)\n";

echo "Created sample DB at: " . $fn . " with player id=" . $playerId . "\n";

?>