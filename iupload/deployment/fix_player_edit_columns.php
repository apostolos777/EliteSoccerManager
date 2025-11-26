<?php
require_once 'database_factory.php';

echo "🔧 Fixing Player Edit - Adding Missing Columns\n";
echo "===============================================\n\n";

try {
    $db = DatabaseFactory::getConnection();
    
    // List of columns needed for the edit form
    $columnsToAdd = [
        'date_of_birth' => 'TEXT',
        'identity_number' => 'TEXT',
        'age_group' => 'TEXT',
        'nationality' => 'TEXT',
        'contact_number' => 'TEXT',
        'height' => 'INTEGER',
        'weight' => 'INTEGER',
        'preferred_foot' => 'TEXT',
        'primary_position' => 'TEXT',
        'secondary_position' => 'TEXT',
        'favorite_player' => 'TEXT',
        'nickname' => 'TEXT',
        'playing_style' => 'TEXT',
        'why_started_playing' => 'TEXT',
        'why_like_football' => 'TEXT',
        'personal_talents' => 'TEXT',
        'off_field_interests' => 'TEXT',
        'profile_image' => 'TEXT',
        'introduction_video_url' => 'TEXT',
        'facebook_url' => 'TEXT',
        'instagram_url' => 'TEXT',
        'twitter_url' => 'TEXT',
        'tiktok_url' => 'TEXT',
        'youtube_url' => 'TEXT',
        'linkedin_url' => 'TEXT'
    ];
    
    // Get current table structure
    $result = $db->query("PRAGMA table_info(players)");
    $existingColumns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['name'];
    }
    
    echo "📊 Current columns: " . implode(', ', $existingColumns) . "\n\n";
    
    // Add missing columns
    $added = 0;
    foreach ($columnsToAdd as $columnName => $columnType) {
        if (!in_array($columnName, $existingColumns)) {
            $sql = "ALTER TABLE players ADD COLUMN $columnName $columnType";
            $db->exec($sql);
            echo "✅ Added column: $columnName ($columnType)\n";
            $added++;
        } else {
            echo "ℹ️  Column already exists: $columnName\n";
        }
    }
    
    echo "\n📈 Summary: Added $added new columns\n";
    
    // Show final table structure
    echo "\n📋 Final table structure:\n";
    $result = $db->query("PRAGMA table_info(players)");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "   - {$row['name']} ({$row['type']})\n";
    }
    
    echo "\n✅ Database structure updated successfully!\n";
    echo "Next step: Test the edit form functionality.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
