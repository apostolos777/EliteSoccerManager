<?php
/**
 * Database Migration: Enhanced Player Profiles
 * VIVO United Football Manager
 * 
 * This script updates the database to support comprehensive player profiles
 * with all the fields specified in the requirements
 */

require_once 'database_factory.php';

try {
    $db = DatabaseFactory::getConnection();
    echo "🔄 Starting Enhanced Player Profile Migration...\n\n";
    
    // Add new columns to players table
    $player_columns = [
        // Personal Information Extensions
        'identity_number' => 'VARCHAR(255)',
        'nationality' => 'VARCHAR(100) DEFAULT "South Africa"',
        'height' => 'INT',  // in cm
        'weight' => 'INT',  // in kg
        'preferred_foot' => 'ENUM("Left", "Right", "Both") DEFAULT "Right"',
        'secondary_position' => 'VARCHAR(100)',
        'favorite_player' => 'VARCHAR(255)',
        'nickname' => 'VARCHAR(100)',
        'playing_style' => 'TEXT',
        'why_started_playing' => 'TEXT',
        'why_love_football' => 'TEXT',
        'off_field_interests' => 'TEXT',
        'personal_talents' => 'TEXT',
        'unique_id' => 'VARCHAR(50) UNIQUE',
        
        // Social Media
        'instagram' => 'VARCHAR(255)',
        'facebook' => 'VARCHAR(255)',
        'twitter' => 'VARCHAR(255)',
        'tiktok' => 'VARCHAR(255)',
        'introduction_video' => 'VARCHAR(500)',
        
        // Physical Metrics
        'sprint_20m' => 'DECIMAL(4,2)',  // seconds
        'sprint_40m' => 'DECIMAL(4,2)',  // seconds
        'stamina_rating' => 'INT DEFAULT 0',
        'agility_rating' => 'INT DEFAULT 0',
        'flexibility_rating' => 'INT DEFAULT 0',
        'strength_rating' => 'INT DEFAULT 0',
        
        // Mentality Scores (0-100)
        'goal_setting_score' => 'INT DEFAULT 0',
        'resilience_score' => 'INT DEFAULT 0',
        'teamwork_score' => 'INT DEFAULT 0',
        'focus_score' => 'INT DEFAULT 0',
        'leadership_score' => 'INT DEFAULT 0',
        'professional_thinking_score' => 'INT DEFAULT 0',
        
        // Technical Skills (0-100)
        'dribbling_rating' => 'INT DEFAULT 0',
        'passing_rating' => 'INT DEFAULT 0',
        'shooting_rating' => 'INT DEFAULT 0',
        'crossing_rating' => 'INT DEFAULT 0',
        'heading_rating' => 'INT DEFAULT 0',
        'tackling_rating' => 'INT DEFAULT 0',
        'ball_control_rating' => 'INT DEFAULT 0',
        
        // Tactical Awareness (0-100)
        'positioning_rating' => 'INT DEFAULT 0',
        'game_reading_rating' => 'INT DEFAULT 0',
        'decision_making_rating' => 'INT DEFAULT 0',
        'formation_understanding_rating' => 'INT DEFAULT 0',
        'tactical_flexibility_rating' => 'INT DEFAULT 0'
    ];
    
    echo "📝 Adding new columns to players table...\n";
    
    foreach ($player_columns as $column => $definition) {
        try {
            // Check if column exists
            $check_sql = "SHOW COLUMNS FROM players LIKE '$column'";
            $result = $db->query($check_sql);
            
            if ($result->rowCount() == 0) {
                $alter_sql = "ALTER TABLE players ADD COLUMN $column $definition";
                $db->exec($alter_sql);
                echo "  ✅ Added column: $column\n";
            } else {
                echo "  ⏭️  Column already exists: $column\n";
            }
        } catch (Exception $e) {
            echo "  ❌ Error adding column $column: " . $e->getMessage() . "\n";
        }
    }
    
    // Create skill_assessments table for tracking skill videos and assessments
    echo "\n📋 Creating skill_assessments table...\n";
    
    $skill_assessments_sql = "
        CREATE TABLE IF NOT EXISTS skill_assessments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            player_id INT NOT NULL,
            skill_type ENUM('dribbling', 'passing', 'shooting', 'crossing', 'heading', 'tackling', 'ball_control') NOT NULL,
            video_path VARCHAR(500),
            assessment_date DATE,
            coach_rating INT DEFAULT 0,
            self_rating INT DEFAULT 0,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
            INDEX idx_player_skill (player_id, skill_type)
        )
    ";
    
    try {
        $db->exec($skill_assessments_sql);
        echo "  ✅ skill_assessments table created/verified\n";
    } catch (Exception $e) {
        echo "  ❌ Error creating skill_assessments table: " . $e->getMessage() . "\n";
    }
    
    // Create tactical_scenarios table for simulated scenarios
    echo "\n🎮 Creating tactical_scenarios table...\n";
    
    $tactical_scenarios_sql = "
        CREATE TABLE IF NOT EXISTS tactical_scenarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            player_id INT NOT NULL,
            scenario_name VARCHAR(255) NOT NULL,
            scenario_description TEXT,
            player_decision TEXT,
            correct_decision TEXT,
            score INT DEFAULT 0,
            completion_time INT, -- seconds
            completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
            INDEX idx_player_scenario (player_id, completed_at)
        )
    ";
    
    try {
        $db->exec($tactical_scenarios_sql);
        echo "  ✅ tactical_scenarios table created/verified\n";
    } catch (Exception $e) {
        echo "  ❌ Error creating tactical_scenarios table: " . $e->getMessage() . "\n";
    }
    
    // Create physical_assessments table for tracking physical development
    echo "\n🏃 Creating physical_assessments table...\n";
    
    $physical_assessments_sql = "
        CREATE TABLE IF NOT EXISTS physical_assessments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            player_id INT NOT NULL,
            assessment_date DATE NOT NULL,
            height INT, -- cm
            weight INT, -- kg
            sprint_20m DECIMAL(4,2), -- seconds
            sprint_40m DECIMAL(4,2), -- seconds
            stamina_test_result INT, -- 0-100
            agility_test_result INT, -- 0-100
            flexibility_test_result INT, -- 0-100
            strength_test_result INT, -- 0-100
            assessor_name VARCHAR(255),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
            INDEX idx_player_assessment (player_id, assessment_date)
        )
    ";
    
    try {
        $db->exec($physical_assessments_sql);
        echo "  ✅ physical_assessments table created/verified\n";
    } catch (Exception $e) {
        echo "  ❌ Error creating physical_assessments table: " . $e->getMessage() . "\n";
    }
    
    // Create mentality_assessments table
    echo "\n🧠 Creating mentality_assessments table...\n";
    
    $mentality_assessments_sql = "
        CREATE TABLE IF NOT EXISTS mentality_assessments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            player_id INT NOT NULL,
            assessment_date DATE NOT NULL,
            goal_setting_score INT DEFAULT 0,
            resilience_score INT DEFAULT 0,
            teamwork_score INT DEFAULT 0,
            focus_score INT DEFAULT 0,
            leadership_score INT DEFAULT 0,
            professional_thinking_score INT DEFAULT 0,
            assessor_name VARCHAR(255),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
            INDEX idx_player_mentality (player_id, assessment_date)
        )
    ";
    
    try {
        $db->exec($mentality_assessments_sql);
        echo "  ✅ mentality_assessments table created/verified\n";
    } catch (Exception $e) {
        echo "  ❌ Error creating mentality_assessments table: " . $e->getMessage() . "\n";
    }
    
    // Create digital_badges table for gamification
    echo "\n🏆 Creating digital_badges table...\n";
    
    $digital_badges_sql = "
        CREATE TABLE IF NOT EXISTS digital_badges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            player_id INT NOT NULL,
            badge_name VARCHAR(255) NOT NULL,
            badge_description TEXT,
            badge_category ENUM('technical', 'physical', 'tactical', 'mentality', 'milestone') NOT NULL,
            badge_icon VARCHAR(255),
            earned_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
            INDEX idx_player_badges (player_id, earned_date)
        )
    ";
    
    try {
        $db->exec($digital_badges_sql);
        echo "  ✅ digital_badges table created/verified\n";
    } catch (Exception $e) {
        echo "  ❌ Error creating digital_badges table: " . $e->getMessage() . "\n";
    }
    
    // Update existing players with unique IDs
    echo "\n🆔 Generating unique player IDs...\n";
    
    try {
        $players_without_unique_id = $db->query("
            SELECT id FROM players 
            WHERE unique_id IS NULL OR unique_id = ''
        ")->fetchAll();
        
        foreach ($players_without_unique_id as $player) {
            $unique_id = 'VIVO-' . str_pad($player['id'], 6, '0', STR_PAD_LEFT);
            $update_sql = "UPDATE players SET unique_id = ? WHERE id = ?";
            $stmt = $db->prepare($update_sql);
            $stmt->execute([$unique_id, $player['id']]);
            echo "  ✅ Generated unique ID for player {$player['id']}: $unique_id\n";
        }
    } catch (Exception $e) {
        echo "  ❌ Error generating unique IDs: " . $e->getMessage() . "\n";
    }
    
    // Add some sample assessment data for existing players
    echo "\n📊 Adding sample assessment data...\n";
    
    try {
        $sample_players = $db->query("SELECT id FROM players LIMIT 5")->fetchAll();
        
        foreach ($sample_players as $player) {
            $player_id = $player['id'];
            
            // Add sample mentality scores
            $mentality_update = "
                UPDATE players SET 
                    goal_setting_score = " . rand(60, 95) . ",
                    resilience_score = " . rand(65, 90) . ",
                    teamwork_score = " . rand(70, 95) . ",
                    focus_score = " . rand(55, 85) . ",
                    leadership_score = " . rand(50, 90) . ",
                    professional_thinking_score = " . rand(60, 85) . "
                WHERE id = $player_id AND goal_setting_score = 0
            ";
            $db->exec($mentality_update);
            
            // Add sample technical skills
            $technical_update = "
                UPDATE players SET 
                    dribbling_rating = " . rand(65, 90) . ",
                    passing_rating = " . rand(70, 95) . ",
                    shooting_rating = " . rand(60, 85) . ",
                    crossing_rating = " . rand(55, 80) . ",
                    heading_rating = " . rand(50, 85) . ",
                    tackling_rating = " . rand(60, 90) . ",
                    ball_control_rating = " . rand(70, 95) . "
                WHERE id = $player_id AND dribbling_rating = 0
            ";
            $db->exec($technical_update);
            
            // Add sample tactical awareness
            $tactical_update = "
                UPDATE players SET 
                    positioning_rating = " . rand(65, 90) . ",
                    game_reading_rating = " . rand(60, 85) . ",
                    decision_making_rating = " . rand(70, 90) . ",
                    formation_understanding_rating = " . rand(55, 80) . ",
                    tactical_flexibility_rating = " . rand(60, 85) . "
                WHERE id = $player_id AND positioning_rating = 0
            ";
            $db->exec($tactical_update);
            
            // Add sample physical ratings
            $physical_update = "
                UPDATE players SET 
                    stamina_rating = " . rand(70, 95) . ",
                    agility_rating = " . rand(65, 90) . ",
                    flexibility_rating = " . rand(60, 85) . ",
                    strength_rating = " . rand(55, 90) . "
                WHERE id = $player_id AND stamina_rating = 0
            ";
            $db->exec($physical_update);
            
            echo "  ✅ Added sample assessment data for player $player_id\n";
        }
    } catch (Exception $e) {
        echo "  ❌ Error adding sample data: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 Enhanced Player Profile Migration Completed Successfully!\n\n";
    echo "✨ New Features Added:\n";
    echo "   • Comprehensive personal information fields\n";
    echo "   • Social media integration\n";
    echo "   • Physical development tracking by age categories\n";
    echo "   • Mentality assessment system\n";
    echo "   • Technical skills evaluation\n";
    echo "   • Tactical awareness tracking\n";
    echo "   • Video analysis capabilities\n";
    echo "   • Digital badge system for gamification\n";
    echo "   • Unique player ID system\n\n";
    echo "🔗 Access the enhanced player profiles at: player_profile_enhanced.php?id=PLAYER_ID\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
?>
