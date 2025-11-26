<?php
/**
 * MySQL Database Setup for VIVO United Football Manager
 * WordPress-style MySQL setup and table creation
 */

// Load WordPress configuration
if (file_exists(__DIR__ . '/wp-config.php')) {
    require_once __DIR__ . '/wp-config.php';
} else {
    die("❌ wp-config.php not found. Please create it first.\n");
}

echo "🚀 VIVO United Football Manager - MySQL Setup\n";
echo "===============================================\n\n";

// Check if constants are defined
$required_constants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
foreach ($required_constants as $constant) {
    if (!defined($constant)) {
        die("❌ Required constant $constant is not defined in wp-config.php\n");
    }
}

echo "✅ WordPress constants loaded:\n";
echo "   - Host: " . DB_HOST . "\n";
echo "   - Database: " . DB_NAME . "\n";
echo "   - Username: " . DB_USER . "\n";
echo "   - Password: " . (DB_PASSWORD ? '[SET]' : '[EMPTY]') . "\n\n";

try {
    // First, connect to MySQL server (without database) to create database if needed
    $server_dsn = "mysql:host=" . DB_HOST . ";charset=" . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4');
    $server_pdo = new PDO($server_dsn, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ Connected to MySQL server\n";
    
    // Create database if it doesn't exist
    $server_pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET " . 
                      (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4') . 
                      " COLLATE " . (defined('DB_COLLATE') ? DB_COLLATE : 'utf8mb4_unicode_ci'));
    
    echo "✅ Database '" . DB_NAME . "' created/verified\n";
    
    // Now connect to the specific database
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4');
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4')
    ]);
    
    echo "✅ Connected to database '" . DB_NAME . "'\n\n";
    
    // Create tables
    echo "🔄 Creating database tables...\n";
    
    // Teams table
    $pdo->exec("CREATE TABLE IF NOT EXISTS teams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        age_group VARCHAR(50),
        coach_name VARCHAR(255),
        assistant_coach VARCHAR(255),
        manager_name VARCHAR(255),
        contact_email VARCHAR(255),
        contact_phone VARCHAR(20),
        home_ground VARCHAR(255),
        founded_year INT,
        team_color VARCHAR(50),
        description TEXT,
        logo_url VARCHAR(500),
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=" . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4') . " COLLATE=" . (defined('DB_COLLATE') ? DB_COLLATE : 'utf8mb4_unicode_ci'));
    
    echo "✅ Teams table created\n";
    
    // Players table
    $pdo->exec("CREATE TABLE IF NOT EXISTS players (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vivo_id VARCHAR(20) UNIQUE,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        date_of_birth DATE,
        age INT,
        height INT COMMENT 'Height in cm',
        weight DECIMAL(5,2) COMMENT 'Weight in kg',
        position VARCHAR(50),
        jersey_number INT,
        team_id INT,
        parent_guardian VARCHAR(255),
        emergency_contact VARCHAR(255),
        emergency_phone VARCHAR(20),
        medical_conditions TEXT,
        profile_image VARCHAR(500),
        status ENUM('active', 'inactive', 'injured', 'suspended') DEFAULT 'active',
        phone VARCHAR(20),
        email VARCHAR(255),
        address TEXT,
        school VARCHAR(255),
        grade_level VARCHAR(20),
        skills_rating JSON,
        notes TEXT,
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL,
        INDEX idx_team_id (team_id),
        INDEX idx_status (status),
        INDEX idx_position (position)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4') . " COLLATE=" . (defined('DB_COLLATE') ? DB_COLLATE : 'utf8mb4_unicode_ci'));
    
    echo "✅ Players table created\n";
    
    // Events table
    $pdo->exec("CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        event_type ENUM('match', 'training', 'meeting', 'tournament', 'other') DEFAULT 'match',
        date DATE NOT NULL,
        time TIME,
        location VARCHAR(255),
        opponent_team VARCHAR(255),
        home_away ENUM('home', 'away', 'neutral') DEFAULT 'home',
        weather_conditions VARCHAR(100),
        score_home INT,
        score_away INT,
        team_id INT,
        is_cancelled BOOLEAN DEFAULT 0,
        cancellation_reason TEXT,
        max_participants INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL,
        INDEX idx_date (date),
        INDEX idx_event_type (event_type),
        INDEX idx_team_id (team_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4') . " COLLATE=" . (defined('DB_COLLATE') ? DB_COLLATE : 'utf8mb4_unicode_ci'));
    
    echo "✅ Events table created\n";
    
    // Attendance table
    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        player_id INT NOT NULL,
        status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
        arrival_time TIME,
        departure_time TIME,
        performance_rating TINYINT CHECK (performance_rating BETWEEN 1 AND 10),
        notes TEXT,
        recorded_by VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
        UNIQUE KEY unique_attendance (event_id, player_id),
        INDEX idx_event_id (event_id),
        INDEX idx_player_id (player_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4') . " COLLATE=" . (defined('DB_COLLATE') ? DB_COLLATE : 'utf8mb4_unicode_ci'));
    
    echo "✅ Attendance table created\n";
    
    // Schema version table for migrations
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_version (
        id INT AUTO_INCREMENT PRIMARY KEY,
        version INT NOT NULL,
        description VARCHAR(255),
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=" . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4') . " COLLATE=" . (defined('DB_COLLATE') ? DB_COLLATE : 'utf8mb4_unicode_ci'));
    
    echo "✅ Schema version table created\n";
    
    // Insert initial schema version
    $pdo->exec("INSERT IGNORE INTO schema_version (version, description) VALUES (1, 'Initial database schema')");
    
    echo "\n🎉 Database setup completed successfully!\n";
    echo "📊 Database is ready for VIVO United Football Manager\n\n";
    
    // Test the setup
    echo "🔍 Testing database setup...\n";
    $tables = ['teams', 'players', 'events', 'attendance', 'schema_version'];
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "   - Table '$table': $count records\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Check MySQL server is running\n";
    echo "2. Verify credentials in wp-config.php\n";
    echo "3. Ensure MySQL user has CREATE DATABASE privileges\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Setup completed at " . date('Y-m-d H:i:s') . "\n";
?>
