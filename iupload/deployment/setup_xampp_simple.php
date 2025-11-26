<?php
/**
 * Simple Database Setup for XAMPP - VIVO United Football Manager
 * Creates the basic database structure without complex features
 */

// Load WordPress configuration
require_once 'wp-config.php';

echo "🚀 VIVO United - Simple XAMPP Database Setup\n";
echo "============================================\n\n";

try {
    // Connect to MySQL server
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ Connected to MySQL server\n";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET " . DB_CHARSET . " COLLATE " . DB_COLLATE);
    echo "✅ Database '" . DB_NAME . "' created/verified\n";
    
    // Connect to the specific database
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Connected to database '" . DB_NAME . "'\n\n";
    
    // Simple Teams table
    $pdo->exec("CREATE TABLE IF NOT EXISTS teams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        age_group VARCHAR(50),
        coach_name VARCHAR(255),
        contact_email VARCHAR(255),
        contact_phone VARCHAR(20),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);
    
    echo "✅ Teams table created\n";
    
    // Simple Players table (without is_active column that was causing errors)
    $pdo->exec("CREATE TABLE IF NOT EXISTS players (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vivo_id VARCHAR(20) UNIQUE,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        age INT,
        position VARCHAR(50),
        jersey_number INT,
        team_id INT,
        parent_guardian VARCHAR(255),
        emergency_contact VARCHAR(255),
        emergency_phone VARCHAR(20),
        medical_conditions TEXT,
        status ENUM('active', 'inactive', 'injured', 'suspended') DEFAULT 'active',
        phone VARCHAR(20),
        email VARCHAR(255),
        address TEXT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);
    
    echo "✅ Players table created\n";
    
    // Simple Events table
    $pdo->exec("CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        event_type ENUM('match', 'training', 'meeting', 'tournament', 'other') DEFAULT 'match',
        date DATE NOT NULL,
        time TIME,
        location VARCHAR(255),
        opponent_team VARCHAR(255),
        team_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);
    
    echo "✅ Events table created\n";
    
    // Simple Attendance table
    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        player_id INT NOT NULL,
        status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
        arrival_time TIME,
        departure_time TIME,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
        UNIQUE KEY unique_attendance (event_id, player_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);
    
    echo "✅ Attendance table created\n\n";
    
    // Insert sample data if tables are empty
    $team_count = $pdo->query("SELECT COUNT(*) FROM teams")->fetchColumn();
    if ($team_count == 0) {
        echo "🔄 Adding sample data...\n";
        
        // Sample teams
        $pdo->exec("INSERT INTO teams (name, age_group, coach_name, contact_email) VALUES
            ('VIVO Juniors U12', 'U12', 'John Smith', 'john@vivounited.org'),
            ('VIVO Youth U16', 'U16', 'Sarah Johnson', 'sarah@vivounited.org'),
            ('VIVO Seniors', 'Senior', 'Mike Wilson', 'mike@vivounited.org')");
        
        // Sample players
        $pdo->exec("INSERT INTO players (vivo_id, first_name, last_name, name, age, position, team_id, parent_guardian, emergency_phone) VALUES
            ('VIVO001', 'Alex', 'Brown', 'Alex Brown', 11, 'Forward', 1, 'David Brown', '555-0101'),
            ('VIVO002', 'Emma', 'Davis', 'Emma Davis', 12, 'Midfielder', 1, 'Lisa Davis', '555-0102'),
            ('VIVO003', 'Josh', 'Wilson', 'Josh Wilson', 15, 'Defender', 2, 'Tom Wilson', '555-0103'),
            ('VIVO004', 'Maya', 'Johnson', 'Maya Johnson', 16, 'Goalkeeper', 2, 'Anna Johnson', '555-0104'),
            ('VIVO005', 'Chris', 'Martinez', 'Chris Martinez', 22, 'Forward', 3, 'Self', '555-0105')");
        
        // Sample events
        $pdo->exec("INSERT INTO events (title, event_type, date, time, location, team_id) VALUES
            ('Training Session', 'training', CURDATE(), '18:00:00', 'Main Field', 1),
            ('Match vs City FC', 'match', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '10:00:00', 'City Stadium', 2),
            ('Team Meeting', 'meeting', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '19:00:00', 'Club House', 3)");
        
        echo "✅ Sample data added\n";
    }
    
    // Test queries
    echo "🔍 Testing database setup...\n";
    $stats = [
        'Teams' => $pdo->query("SELECT COUNT(*) FROM teams")->fetchColumn(),
        'Players' => $pdo->query("SELECT COUNT(*) FROM players")->fetchColumn(),
        'Events' => $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn(),
        'Attendance Records' => $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn()
    ];
    
    foreach ($stats as $label => $count) {
        echo "   - $label: $count\n";
    }
    
    echo "\n🎉 Database setup completed successfully!\n";
    echo "🌐 You can now access:\n";
    echo "   - http://localhost/vivo-app/\n";
    echo "   - http://localhost/vivo-app/players.php\n";
    echo "   - http://localhost/vivo-app/teams.php\n";
    echo "   - http://localhost/vivo-app/events.php\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "\n🔧 Make sure:\n";
    echo "1. XAMPP is running (MySQL service started)\n";
    echo "2. Check wp-config.php database settings\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Setup completed at " . date('Y-m-d H:i:s') . "\n";
?>
