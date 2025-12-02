<?php
/**
 * Database Factory for VIVO United Football Manager
 * This factory provides a database connection without WordPress dependencies
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

class DatabaseFactory {
    private static $connection = null;
    
    public static function getConnection() {
        if (self::$connection === null) {
            self::$connection = self::createConnection();
        }
        return self::$connection;
    }
    
    private static function createConnection() {
        try {
            // Use SQLite exclusively - no MySQL dependency
            return self::createSQLiteConnection();
        } catch (Exception $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    private static function createSQLiteConnection() {
        try {
            $dbFile = __DIR__ . '/database.db';
            
            $pdo = new PDO("sqlite:$dbFile");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Enable foreign keys
            $pdo->exec('PRAGMA foreign_keys = ON');
            
            // Create tables if they don't exist
            self::createTables($pdo);
            // Run lightweight migrations to ensure required columns exist
            self::migrateSchema($pdo);
            
            return $pdo;
        } catch (Exception $e) {
            throw new Exception("SQLite connection failed: " . $e->getMessage());
        }
    }
    
    private static function createTables($pdo) {
        try {
            // Create teams table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS teams (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    coach_name TEXT,
                    assistant_coach TEXT,
                    description TEXT,
                    age_group TEXT,
                    contact_email TEXT,
                    contact_phone TEXT,
                    photo_url TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Create coaches table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS coaches (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    email TEXT,
                    phone TEXT,
                    role TEXT DEFAULT 'coach',
                    qualifications TEXT,
                    certifications TEXT,
                    bio TEXT,
                    photo_url TEXT,
                    status TEXT DEFAULT 'active',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Create team_coaches junction table (many-to-many relationship)
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS team_coaches (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    team_id INTEGER NOT NULL,
                    coach_id INTEGER NOT NULL,
                    role_in_team TEXT DEFAULT 'coach',
                    is_primary BOOLEAN DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
                    FOREIGN KEY (coach_id) REFERENCES coaches(id) ON DELETE CASCADE,
                    UNIQUE(team_id, coach_id)
                )
            ");
            
            // Create players table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS players (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    position TEXT,
                    team_id INTEGER,
                    jersey_number INTEGER,
                    age INTEGER,
                    photo_url TEXT,
                    status TEXT DEFAULT 'active',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (team_id) REFERENCES teams(id)
                )
            ");

                // Create users table for app authentication (lightweight)
                $pdo->exec("CREATE TABLE IF NOT EXISTS users (
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
            
            // Create events table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS events (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title TEXT NOT NULL,
                    description TEXT,
                    event_date DATETIME,
                    location TEXT,
                    event_type TEXT DEFAULT 'match',
                    status TEXT DEFAULT 'scheduled',
                    age_group TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Create attendance table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS attendance (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    player_id INTEGER NOT NULL,
                    event_id INTEGER NOT NULL,
                    status TEXT DEFAULT 'present',
                    notes TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (player_id) REFERENCES players(id),
                    FOREIGN KEY (event_id) REFERENCES events(id)
                )
            ");
            
        } catch (Exception $e) {
            throw new Exception("Table creation failed: " . $e->getMessage());
        }
    }

    /**
     * Run lightweight migrations to add missing columns to existing tables.
     * This keeps older database files compatible with newer code expectations.
     */
    private static function migrateSchema($pdo) {
        try {
            // Define expected additional columns per table: column => sql definition
            $migrations = [
                'team_coaches' => [
                    'role_in_team' => "TEXT DEFAULT 'coach'",
                    'is_primary' => "INTEGER DEFAULT 0"
                ],
                    'events' => [
                        // Add a unified event_date column if older DBs used separate date/time columns
                        'event_date' => "DATETIME"
                    ],
                // Add more migrations here if future schema changes are introduced
            ];

            foreach ($migrations as $table => $cols) {
                // Get existing columns
                $existing = [];
                $stmt = $pdo->query("PRAGMA table_info($table)");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) { $existing[] = $r['name']; }

                foreach ($cols as $col => $definition) {
                    if (!in_array($col, $existing)) {
                        // ALTER TABLE to add the missing column
                        $sql = "ALTER TABLE $table ADD COLUMN $col $definition";
                        $pdo->exec($sql);
                    }
                }
            }

            // Special-case backfill for events.event_date when date/time columns exist
            $r = $pdo->query("PRAGMA table_info(events)")->fetchAll(PDO::FETCH_ASSOC);
            $colNames = array_map(function($c){ return $c['name']; }, $r);
            if (in_array('event_date', $colNames) && in_array('date', $colNames)) {
                // Update event_date where null using date + time if available
                try {
                    $pdo->exec("UPDATE events SET event_date = (CASE WHEN time IS NOT NULL AND time != '' THEN date || ' ' || time ELSE date END) WHERE event_date IS NULL OR event_date = ''");
                } catch (Exception $e) {
                    error_log('Event backfill failed: ' . $e->getMessage());
                }
            }

        } catch (Exception $e) {
            // Non-fatal: log and continue (we don't want startup to completely fail on migration)
            error_log("Schema migration warning: " . $e->getMessage());
        }
    }
    
    public static function testConnection() {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
