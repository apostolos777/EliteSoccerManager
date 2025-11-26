<?php
// Quick bulk event seeding script.
// Visit this in browser or run via CLI: php seed_events.php
// It will insert upcoming training sessions and matches if they don't already exist.

require_once __DIR__.'/database_factory.php';

try { $db = DatabaseFactory::getConnection(); } catch(Throwable $e){ die("DB connection failed: ".$e->getMessage()); }

// Ensure newer columns exist (idempotent guards)
$alterStatements = [
    "ALTER TABLE events ADD COLUMN age_groups TEXT NULL AFTER event_type",
    "ALTER TABLE events ADD COLUMN target_age_range VARCHAR(100) NULL AFTER age_groups",
    "ALTER TABLE events ADD COLUMN opponent_team VARCHAR(255) NULL AFTER status",
    "ALTER TABLE events ADD COLUMN home_away ENUM('home','away') NULL AFTER opponent_team"
];
foreach($alterStatements as $sql){
    try { $db->exec($sql); } catch(Throwable $e){ /* ignore if exists */ }
}

function makeTargetRange(array $groups): string {
    $nums = [];
    foreach($groups as $g){ if(preg_match('/U(\d+)/',$g,$m)) $nums[]=(int)$m[1]; }
    if(!$nums) return '';
    sort($nums); return count($nums)===1? 'U'.$nums[0] : 'U'.min($nums).'-U'.max($nums);
}

$now = new DateTime();

// Define template events (next 6 weeks) with variety
$events = [];
for($w=0;$w<6;$w++){
    $weekStart = (clone $now)->modify("+{$w} week");

    // 1. Youth combined training (Tue / Thu 18:00)
    foreach(['tuesday','thursday'] as $day){
        $d = (clone $weekStart)->modify($day.' this week')->setTime(18,0);
        if($d < $now) continue;
        $groups = ['U6','U7','U8','U9','U10','U11','U12','U13'];
        $events[] = [
            'title' => 'Combined Youth Training',
            'description' => 'Weekly youth development session focusing on fundamentals',
            'event_date' => $d->format('Y-m-d H:i:s'),
            'location' => 'Training Ground A',
            'event_type' => 'training',
            'age_groups' => $groups,
            'opponent_team' => null,
            'home_away' => null
        ];
    }

    // 2. Goalkeeper clinic (every other Wednesday 17:00) for U10-U13
    if($w % 2 === 0){
        $wed = (clone $weekStart)->modify('wednesday this week')->setTime(17,0);
        if($wed > $now){
            $events[] = [
                'title' => 'Goalkeeper Clinic',
                'description' => 'Specialized session for keepers (handling, distribution)',
                'event_date' => $wed->format('Y-m-d H:i:s'),
                'location' => 'Training Ground B',
                'event_type' => 'training',
                'age_groups' => ['U10','U11','U12','U13'],
                'opponent_team' => null,
                'home_away' => null
            ];
        }
    }

    // 3. Weekly senior tactical meeting (Monday 19:00)
    $mon = (clone $weekStart)->modify('monday this week')->setTime(19,0);
    if($mon > $now){
        $events[] = [
            'title' => 'Senior Tactical Meeting',
            'description' => 'Video review and tactical planning',
            'event_date' => $mon->format('Y-m-d H:i:s'),
            'location' => 'Clubhouse Briefing Room',
            'event_type' => 'meeting',
            'age_groups' => ['Senior'],
            'opponent_team' => null,
            'home_away' => null
        ];
    }

    // 4. Saturday league match 15:00 (alternating home/away)
    $sat = (clone $weekStart)->modify('saturday this week')->setTime(15,0);
    if($sat > $now){
        $events[] = [
            'title' => 'League Match Week '.($w+1),
            'description' => 'Scheduled league fixture week '.($w+1),
            'event_date' => $sat->format('Y-m-d H:i:s'),
            'location' => ($w % 2 === 0 ? 'VIVO Stadium' : 'Rivals Arena'),
            'event_type' => 'match',
            'age_groups' => ['Senior'],
            'opponent_team' => 'Rivals FC',
            'home_away' => ($w % 2 === 0 ? 'home':'away')
        ];
    }

    // 5. Recovery session Sunday 11:00 (light training) for Senior squad
    $sun = (clone $weekStart)->modify('sunday this week')->setTime(11,0);
    if($sun > $now){
        $events[] = [
            'title' => 'Senior Recovery Session',
            'description' => 'Light recovery and mobility following match day',
            'event_date' => $sun->format('Y-m-d H:i:s'),
            'location' => 'Training Ground Rehab Area',
            'event_type' => 'training',
            'age_groups' => ['Senior'],
            'opponent_team' => null,
            'home_away' => null
        ];
    }

    // 6. Mini tournament (Week 3) Saturday 09:00 (youth mixed) & Training camp (Week 5)
    if($w === 2){
        $tour = (clone $weekStart)->modify('saturday this week')->setTime(9,0);
        if($tour > $now){
            $events[] = [
                'title' => 'Youth Mini Tournament',
                'description' => 'Round-robin internal youth tournament',
                'event_date' => $tour->format('Y-m-d H:i:s'),
                'location' => 'Community Fields',
                'event_type' => 'tournament',
                'age_groups' => ['U10','U11','U12','U13'],
                'opponent_team' => null,
                'home_away' => null
            ];
        }
    }
    if($w === 4){
        $camp = (clone $weekStart)->modify('friday this week')->setTime(9,0);
        if($camp > $now){
            $events[] = [
                'title' => 'Pre-Season Training Camp Day 1',
                'description' => 'Intensive conditioning & team cohesion',
                'event_date' => $camp->format('Y-m-d H:i:s'),
                'location' => 'Mountain Sports Complex',
                'event_type' => 'camp',
                'age_groups' => ['Senior'],
                'opponent_team' => null,
                'home_away' => null
            ];
            $camp2 = (clone $camp)->modify('+1 day');
            $events[] = [
                'title' => 'Pre-Season Training Camp Day 2',
                'description' => 'Technical focus & tactical drills',
                'event_date' => $camp2->format('Y-m-d H:i:s'),
                'location' => 'Mountain Sports Complex',
                'event_type' => 'camp',
                'age_groups' => ['Senior'],
                'opponent_team' => null,
                'home_away' => null
            ];
        }
    }
}

$insert = $db->prepare("INSERT INTO events (title, description, event_date, location, event_type, age_groups, target_age_range, opponent_team, home_away) VALUES (?,?,?,?,?,?,?,?,?)");
$added=0; $skipped=0;
foreach($events as $e){
    // Skip if identical title+date exists
    $check = $db->prepare("SELECT id FROM events WHERE title=? AND event_date=? LIMIT 1");
    $check->execute([$e['title'],$e['event_date']]);
    if($check->fetch()){ $skipped++; continue; }
    $target = makeTargetRange($e['age_groups']);
    $insert->execute([
        $e['title'],
        $e['description'],
        $e['event_date'],
        $e['location'],
        $e['event_type'],
        implode(',',$e['age_groups']),
        $target,
        $e['opponent_team'],
        $e['home_away']
    ]);
    $added++;
}

header('Content-Type: text/plain');
echo "Seed complete. Added: $added Skipped (already existed): $skipped Total considered: ".count($events)."\n";
?>
