<?php
/**
 * VIVO United - Event Calendar View
 * Full calendar view of all events with month navigation
 */

// Load WordPress-style config
require_once 'includes/color_system.php';
require_once 'database_factory.php';
require_once 'includes/auth.php';

// auth.php starts session when needed; avoid calling session_start() here to prevent notices
$currentPage = 'events';

$db = DatabaseFactory::getConnection();

// Get current month/year or from URL parameters
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Ensure valid month/year
$currentMonth = max(1, min(12, $currentMonth));
$currentYear = max(2020, min(2030, $currentYear));

// Get first and last day of the month
$firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$lastDay = mktime(23, 59, 59, $currentMonth, date('t', $firstDay), $currentYear);

// Get events for the current month
$startDate = date('Y-m-d H:i:s', $firstDay);
$endDate = date('Y-m-d H:i:s', $lastDay);

$eventsQuery = "SELECT *, 
                CASE 
                    WHEN time IS NOT NULL THEN date || ' ' || time
                    ELSE date || ' 00:00:00'
                END as event_datetime
                FROM events 
                WHERE date >= ? AND date <= ?
                ORDER BY date ASC, time ASC";
$stmt = $db->prepare($eventsQuery);
$stmt->execute([date('Y-m-d', $firstDay), date('Y-m-d', $lastDay)]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group events by day
$eventsByDay = [];
foreach ($events as $event) {
    $day = (int)date('j', strtotime($event['date']));
    if (!isset($eventsByDay[$day])) {
        $eventsByDay[$day] = [];
    }
    $eventsByDay[$day][] = $event;
}

// Calendar navigation
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Calendar generation
$daysInMonth = date('t', $firstDay);
$firstDayOfWeek = date('w', $firstDay); // 0 = Sunday, 1 = Monday, etc.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Calendar - VIVO United</title>
    <?php 
    require_once 'includes/css_helper.php';
    vivo_include_head_css($db);
    ?>
    <style>
        .calendar-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .calendar-header {
            background: var(--primary);
            color: white;
            padding: 1.5rem;
            text-align: center;
            position: relative;
        }
        
        .calendar-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .calendar-nav.prev { left: 1rem; }
        .calendar-nav.next { right: 1rem; }
        
        .calendar-nav a {
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            padding: 0.5rem;
            border-radius: 50%;
            transition: background-color 0.3s;
        }
        
        .calendar-nav a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }
        
        .calendar-day-header {
            background: var(--gray-100);
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            border-bottom: 1px solid var(--gray-200);
            color: var(--gray-700);
        }
        
        .calendar-day {
            min-height: 120px;
            border-bottom: 1px solid var(--gray-200);
            border-right: 1px solid var(--gray-200);
            padding: 0.5rem;
            position: relative;
            background: white;
        }
        
        .calendar-day:nth-child(7n) {
            border-right: none;
        }
        
        .calendar-day.other-month {
            background: var(--gray-50);
            color: var(--gray-400);
        }
        
        .calendar-day.today {
            background: var(--primary-light);
        }
        
        .day-number {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .event-item {
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.5rem;
            margin-bottom: 0.25rem;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .event-item:hover {
            background: var(--primary-dark);
        }
        
        .event-item.training {
            background: var(--success);
        }
        
        .event-item.match {
            background: var(--danger);
        }
        
        .event-item.meeting {
            background: var(--warning);
            color: var(--warning-dark);
        }
        
        .more-events {
            font-size: 0.75rem;
            color: var(--gray-600);
            font-style: italic;
        }
        
        .event-tooltip {
            position: absolute;
            background: var(--gray-900);
            color: white;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            z-index: 1000;
            display: none;
            max-width: 200px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="content-main">
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title">
                    <i class="fas fa-calendar-alt"></i>
                    Event Calendar
                </h1>
                <p class="page-subtitle"><?= $monthNames[$currentMonth] ?> <?= $currentYear ?></p>
            </div>
            <div class="page-actions">
                <a href="events.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> List View
                </a>
                <a href="event_edit.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Event
                </a>
            </div>
        </div>

        <div class="calendar-container">
            <div class="calendar-header">
                <div class="calendar-nav prev">
                    <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </div>
                <h2><?= $monthNames[$currentMonth] ?> <?= $currentYear ?></h2>
                <div class="calendar-nav next">
                    <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="calendar-grid">
                <!-- Day headers -->
                <div class="calendar-day-header">Sun</div>
                <div class="calendar-day-header">Mon</div>
                <div class="calendar-day-header">Tue</div>
                <div class="calendar-day-header">Wed</div>
                <div class="calendar-day-header">Thu</div>
                <div class="calendar-day-header">Fri</div>
                <div class="calendar-day-header">Sat</div>
                
                <?php
                // Add empty cells for days before the first day of the month
                for ($i = 0; $i < $firstDayOfWeek; $i++) {
                    $prevMonthDay = date('j', strtotime("-" . ($firstDayOfWeek - $i) . " days", $firstDay));
                    echo '<div class="calendar-day other-month">';
                    echo '<div class="day-number">' . $prevMonthDay . '</div>';
                    echo '</div>';
                }
                
                // Add days of the current month
                $today = date('j');
                $todayMonth = date('n');
                $todayYear = date('Y');
                
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $isToday = ($day == $today && $currentMonth == $todayMonth && $currentYear == $todayYear);
                    $dayClass = $isToday ? 'calendar-day today' : 'calendar-day';
                    
                    echo '<div class="' . $dayClass . '">';
                    echo '<div class="day-number">' . $day . '</div>';
                    
                    // Show events for this day
                    if (isset($eventsByDay[$day])) {
                        $dayEvents = $eventsByDay[$day];
                        $maxVisible = 3;
                        
                        for ($i = 0; $i < min($maxVisible, count($dayEvents)); $i++) {
                            $event = $dayEvents[$i];
                            $eventClass = 'event-item';
                            if ($event['event_type']) {
                                $eventClass .= ' ' . strtolower($event['event_type']);
                            }
                            
                            echo '<div class="' . $eventClass . '" title="' . htmlspecialchars($event['title']) . '">';
                            echo htmlspecialchars(substr($event['title'], 0, 20));
                            if (strlen($event['title']) > 20) echo '...';
                            echo '</div>';
                        }
                        
                        if (count($dayEvents) > $maxVisible) {
                            echo '<div class="more-events">+' . (count($dayEvents) - $maxVisible) . ' more</div>';
                        }
                    }
                    
                    echo '</div>';
                }
                
                // Fill remaining cells for next month
                $totalCells = $firstDayOfWeek + $daysInMonth;
                $remainingCells = 42 - $totalCells; // 6 weeks * 7 days = 42
                
                for ($i = 1; $i <= $remainingCells && $totalCells < 42; $i++) {
                    echo '<div class="calendar-day other-month">';
                    echo '<div class="day-number">' . $i . '</div>';
                    echo '</div>';
                    $totalCells++;
                }
                ?>
            </div>
        </div>
        
        <!-- Event Legend -->
        <div class="mt-4">
            <div class="card">
                <div class="card-header">
                    <h3>Event Types</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="event-item training d-inline-block">Training</div>
                        </div>
                        <div class="col-md-3">
                            <div class="event-item match d-inline-block">Match</div>
                        </div>
                        <div class="col-md-3">
                            <div class="event-item meeting d-inline-block">Meeting</div>
                        </div>
                        <div class="col-md-3">
                            <div class="event-item d-inline-block">Other</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add click handlers for events
        document.querySelectorAll('.event-item').forEach(function(element) {
            element.addEventListener('click', function() {
                // You could add a modal or redirect to event details here
                alert('Event: ' + this.title);
            });
        });
        
        // Add today button functionality
        function goToToday() {
            window.location.href = '?month=<?= date('n') ?>&year=<?= date('Y') ?>';
        }
    </script>
</body>
</html>
