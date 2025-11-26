<?php
// SQLite-only Database configuration for VIVO United Football Manager
// Created: July 26, 2025

class DatabaseConfigSQLite {
    // SQLite Database Configuration
    const DB_CHARSET = 'utf8mb4';
    
    // Environment detection - SQLite only
    const IS_PRODUCTION = false; // Always false for SQLite version
    
    private static $connection = null;
    
    public static function getConnection() {
        if (self::$connection === null) {
            self::$connection = self::getSQLiteConnection();
        }
        return self::$connection;
    }
    
    private static function getSQLiteConnection() {
        try {
            $db_path = __DIR__ . '/database.db';
            self::$connection = new PDO("sqlite:" . $db_path, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            // Create tables if they don't exist
            self::createSQLiteTables();
            
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
        return self::$connection;
    }
    
    private static function createSQLiteTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS teams (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            age_group VARCHAR(20),
            coach_name VARCHAR(100),
            formation VARCHAR(20),
            home_ground VARCHAR(100),
            founded_year INTEGER,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS players (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vivo_id VARCHAR(50) UNIQUE,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20),
            date_of_birth DATE,
            age INTEGER,
            position VARCHAR(30),
            team_id INTEGER,
            jersey_number INTEGER,
            height DECIMAL(5,2),
            weight DECIMAL(5,2),
            preferred_foot VARCHAR(10),
            nationality VARCHAR(50),
            address TEXT,
            emergency_contact_name VARCHAR(100),
            emergency_contact_phone VARCHAR(20),
            medical_notes TEXT,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL
        );
        
        CREATE TABLE IF NOT EXISTS events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            event_type VARCHAR(20) NOT NULL,
            date DATE NOT NULL,
            time TIME,
            location VARCHAR(200),
            team_id INTEGER,
            opponent VARCHAR(100),
            is_home_game BOOLEAN DEFAULT 1,
            status VARCHAR(20) DEFAULT 'Scheduled',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
        );
        
        CREATE TABLE IF NOT EXISTS attendance (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id INTEGER NOT NULL,
            player_id INTEGER NOT NULL,
            status VARCHAR(20) DEFAULT 'Present',
            notes TEXT,
            recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
            UNIQUE(event_id, player_id)
        );
        
        CREATE TABLE IF NOT EXISTS player_stats (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            player_id INTEGER NOT NULL,
            season VARCHAR(20) NOT NULL,
            games_played INTEGER DEFAULT 0,
            goals INTEGER DEFAULT 0,
            assists INTEGER DEFAULT 0,
            yellow_cards INTEGER DEFAULT 0,
            red_cards INTEGER DEFAULT 0,
            minutes_played INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
            UNIQUE(player_id, season)
        );
        
        CREATE INDEX IF NOT EXISTS idx_players_team_id ON players(team_id);
        CREATE INDEX IF NOT EXISTS idx_players_position ON players(position);
        CREATE INDEX IF NOT EXISTS idx_players_age ON players(age);
        CREATE INDEX IF NOT EXISTS idx_events_date ON events(date);
        CREATE INDEX IF NOT EXISTS idx_events_team_id ON events(team_id);
        ";
        
        self::$connection->exec($sql);
    }
    
    public static function testConnection() {
        try {
            $db = self::getConnection();
            return $db !== null;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
