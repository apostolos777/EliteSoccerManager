<?php
/**
 * Add sample events to the database
 */

require_once 'database_factory.php';

$db = DatabaseFactory::getConnection();

// Sample events data
$sampleEvents = [
    [
        'title' => 'Weekly Training Session',
        'description' => 'Regular training session for all teams',
        'date' => date('Y-m-d', strtotime('+2 days')),
        'time' => '16:00:00',
        'event_date' => date('Y-m-d', strtotime('+2 days')),
        'event_type' => 'training',
        'location' => 'Main Training Ground',
        'age_groups' => 'U12,U14,U16',
        'status' => 'Scheduled'
    ],
    [
        'title' => 'Match vs Eagles FC',
        'description' => 'League match against Eagles FC',
        'date' => date('Y-m-d', strtotime('+5 days')),
        'time' => '14:00:00',
        'event_date' => date('Y-m-d', strtotime('+5 days')),
        'event_type' => 'match',
        'location' => 'City Stadium',
        'age_groups' => 'U16',
        'status' => 'Scheduled'
    ],
    [
        'title' => 'Team Meeting',
        'description' => 'Monthly team meeting and strategy discussion',
        'date' => date('Y-m-d', strtotime('+7 days')),
        'time' => '18:00:00',
        'event_date' => date('Y-m-d', strtotime('+7 days')),
        'event_type' => 'meeting',
        'location' => 'Club House',
        'age_groups' => 'All',
        'status' => 'Scheduled'
    ],
    [
        'title' => 'Fitness Assessment',
        'description' => 'Quarterly fitness assessment for all players',
        'date' => date('Y-m-d', strtotime('+10 days')),
        'time' => '09:00:00',
        'event_date' => date('Y-m-d', strtotime('+10 days')),
        'event_type' => 'training',
        'location' => 'Training Ground',
        'age_groups' => 'U12,U14,U16,U18',
        'status' => 'Scheduled'
    ],
    [
        'title' => 'Tournament Final',
        'description' => 'Championship final match',
        'date' => date('Y-m-d', strtotime('+14 days')),
        'time' => '15:00:00',
        'event_date' => date('Y-m-d', strtotime('+14 days')),
        'event_type' => 'match',
        'location' => 'National Stadium',
        'age_groups' => 'U18',
        'status' => 'Scheduled'
    ]
];

// Insert sample events
$insertSQL = "INSERT INTO events (title, description, date, time, event_date, event_type, location, age_groups, status, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))";

$stmt = $db->prepare($insertSQL);

foreach ($sampleEvents as $event) {
    $stmt->execute([
        $event['title'],
        $event['description'],
        $event['date'],
        $event['time'],
        $event['event_date'],
        $event['event_type'],
        $event['location'],
        $event['age_groups'],
        $event['status']
    ]);
}

echo "Sample events added successfully!";
?>
