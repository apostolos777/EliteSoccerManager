<?php
/**
 * VIVO United - Restore Sample Data
 * Populate the database with sample teams, players, and events
 */

require_once 'wp-config.php';

echo "<h1>VIVO United - Data Restoration</h1>";
echo "<pre>";

try {
    $db = get_db_connection();
    echo "✅ Database connection successful\n\n";
    
    // Create sample teams
    echo "🛡️ Creating sample teams...\n";
    $teams = [
        ['name' => 'U10 Dragons', 'age_group' => 'U10', 'description' => 'Under 10 development team'],
        ['name' => 'U12 Eagles', 'age_group' => 'U12', 'description' => 'Under 12 competitive team'],
        ['name' => 'U14 Lions', 'age_group' => 'U14', 'description' => 'Under 14 premier team'],
        ['name' => 'U16 Titans', 'age_group' => 'U16', 'description' => 'Under 16 elite team'],
        ['name' => 'Senior Squad', 'age_group' => 'Senior', 'description' => 'First team senior players']
    ];
    
    $stmt = $db->prepare("INSERT INTO teams (name, age_group, description, created_at) VALUES (?, ?, ?, datetime('now'))");
    foreach ($teams as $team) {
        $stmt->execute([$team['name'], $team['age_group'], $team['description']]);
        echo "  ✓ Created team: {$team['name']}\n";
    }
    
    // Create sample players (adaptive to schema)
    echo "\n👥 Creating sample players...\n";
    $players = [
        ['full' => 'Liam Johnson', 'age' => 9, 'team_id' => 1, 'position' => 'Forward'],
        ['full' => 'Emma Smith', 'age' => 10, 'team_id' => 1, 'position' => 'Midfielder'],
        ['full' => 'Noah Williams', 'age' => 9, 'team_id' => 1, 'position' => 'Defender'],
        ['full' => 'Olivia Brown', 'age' => 12, 'team_id' => 2, 'position' => 'Goalkeeper'],
        ['full' => 'William Jones', 'age' => 11, 'team_id' => 2, 'position' => 'Forward'],
        ['full' => 'Sophia Garcia', 'age' => 12, 'team_id' => 2, 'position' => 'Midfielder'],
        ['full' => 'James Miller', 'age' => 14, 'team_id' => 3, 'position' => 'Forward'],
        ['full' => 'Charlotte Davis', 'age' => 13, 'team_id' => 3, 'position' => 'Defender'],
        ['full' => 'Benjamin Rodriguez', 'age' => 14, 'team_id' => 3, 'position' => 'Midfielder'],
        ['full' => 'Mia Martinez', 'age' => 16, 'team_id' => 4, 'position' => 'Forward'],
        ['full' => 'Lucas Hernandez', 'age' => 15, 'team_id' => 4, 'position' => 'Goalkeeper'],
        ['full' => 'Harper Lopez', 'age' => 16, 'team_id' => 4, 'position' => 'Defender'],
        ['full' => 'Alexander Gonzalez', 'age' => 22, 'team_id' => 5, 'position' => 'Forward'],
        ['full' => 'Isabella Wilson', 'age' => 20, 'team_id' => 5, 'position' => 'Midfielder'],
        ['full' => 'Ethan Anderson', 'age' => 24, 'team_id' => 5, 'position' => 'Defender']
    ];

    // Introspect player columns
    $cols = $db->query("PRAGMA table_info(players)")->fetchAll(PDO::FETCH_ASSOC);
    $present = [];
    foreach ($cols as $c) { $present[$c['name']] = true; }

    foreach ($players as $p) {
        $first = $p['full']; $last = '';
        $parts = preg_split('/\s+/', $p['full']);
        if (count($parts) > 1) { $first = array_shift($parts); $last = implode(' ', $parts); }
        $data = [];
        if (isset($present['first_name']) || isset($present['last_name'])) {
            if (isset($present['first_name'])) $data['first_name'] = $first;
            if (isset($present['last_name'])) $data['last_name'] = $last;
        } elseif (isset($present['name'])) {
            $data['name'] = $p['full'];
        } else {
            echo "  ⚠️ Skipped {$p['full']} - no name columns present.\n";
            continue;
        }
        
        // Also set name column if it exists and we have first_name/last_name
        if (isset($present['name']) && isset($data['first_name']) && isset($data['last_name'])) {
            $data['name'] = trim($data['first_name'] . ' ' . $data['last_name']);
        }
        if (isset($present['age'])) $data['age'] = $p['age'];
        if (isset($present['team_id'])) $data['team_id'] = $p['team_id'];
        if (isset($present['position'])) $data['position'] = $p['position'];
        if (isset($present['status'])) $data['status'] = 'active';
        if (isset($present['is_active']) && !isset($present['status'])) $data['is_active'] = 1;
        if (isset($present['created_at'])) $data['created_at'] = date('Y-m-d H:i:s');

        $colsInsert = array_keys($data);
        $placeholders = array_map(fn($c)=>':'.$c, $colsInsert);
        $sql = 'INSERT INTO players (' . implode(', ',$colsInsert) . ') VALUES (' . implode(', ',$placeholders) . ')';
        $stmt = $db->prepare($sql);
        $exec = [];
        foreach ($data as $k=>$v) { $exec[':'.$k] = $v; }
        $stmt->execute($exec);
        echo "  ✓ Created player: {$p['full']} ({$p['position']})\n";
    }
    
    // Create sample events
    echo "\n📅 Creating sample events...\n";
    $events = [
        ['title' => 'U10 Training Session', 'event_type' => 'training', 'date' => date('Y-m-d', strtotime('+1 day')), 'location' => 'Main Field'],
        ['title' => 'U12 vs City FC', 'event_type' => 'match', 'date' => date('Y-m-d', strtotime('+3 days')), 'location' => 'Stadium A'],
        ['title' => 'U14 Training Session', 'event_type' => 'training', 'date' => date('Y-m-d', strtotime('+5 days')), 'location' => 'Training Ground'],
        ['title' => 'U16 vs United Academy', 'event_type' => 'match', 'date' => date('Y-m-d', strtotime('+7 days')), 'location' => 'Away Ground'],
        ['title' => 'Senior Team Training', 'event_type' => 'training', 'date' => date('Y-m-d', strtotime('+2 days')), 'location' => 'Main Field'],
        ['title' => 'Senior vs Athletic Club', 'event_type' => 'match', 'date' => date('Y-m-d', strtotime('+10 days')), 'location' => 'Home Stadium']
    ];
    
    $stmt = $db->prepare("INSERT INTO events (title, description, event_type, date, location, status, created_at) VALUES (?, ?, ?, ?, ?, 'scheduled', datetime('now'))");
    foreach ($events as $event) {
        $description = $event['event_type'] === 'match' ? 'Competitive match against opponent' : 'Regular training session';
        $stmt->execute([$event['title'], $description, $event['event_type'], $event['date'], $event['location']]);
        echo "  ✓ Created event: {$event['title']} on {$event['date']}\n";
    }
    
    echo "\n🎉 Sample data restoration completed successfully!\n";
    echo "\nSummary:\n";
    echo "  - 5 teams created\n";
    echo "  - 15 players created\n";
    echo "  - 6 events created\n";
    echo "\nYou can now test the application with this sample data.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
