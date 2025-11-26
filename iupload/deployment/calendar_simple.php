<?php
/**
 * VIVO United - Simple Calendar View
 */

// Include dynamic color system
require_once 'includes/color_system.php';

// Check authentication
if (!function_exists('isLoggedIn')) {
    require_once 'includes/auth.php';
}

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Set current page for sidebar navigation
$currentPage = 'calendar';

// Initialize database connection
require_once 'database_factory.php';
$db = DatabaseFactory::getConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - VIVO United Manager</title>
    
    <?php 
    require_once 'includes/css_helper.php';
    vivo_include_head_css($db);
    ?>
    
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css" rel="stylesheet">
    
    <style>
        /* Modern Calendar Container */
        .calendar-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        .calendar-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        /* Calendar Header */
        .calendar-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-medium));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .calendar-title {
            margin: 0;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .calendar-subtitle {
            margin: 0;
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 400;
        }
        
        /* Navigation Controls */
        .calendar-nav {
            background: #f8f9fb;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .calendar-btn {
            padding: 0.75rem 1.25rem;
            border: 1px solid #e2e8f0;
            background: white;
            color: #64748b;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .calendar-btn:hover {
            background: #f1f5f9;
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .calendar-btn.primary {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .calendar-btn.primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            color: white;
        }
        
        .calendar-btn.secondary {
            background: var(--secondary);
            border-color: var(--secondary);
            color: white;
        }
        
        .calendar-btn.secondary:hover {
            background: var(--secondary-dark, #4c7c98);
            border-color: var(--secondary-dark, #4c7c98);
        }
        
        /* Calendar Content */
        .calendar-content {
            padding: 0;
            min-height: 600px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .calendar-wrapper {
                padding: 0.5rem;
            }
            
            .calendar-header {
                padding: 1.5rem 1rem;
            }
            
            .calendar-title {
                font-size: 1.5rem;
            }
            
            .calendar-nav {
                padding: 1rem;
                flex-direction: column;
                text-align: center;
            }
            
            .calendar-btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 480px) {
            .calendar-container {
                border-radius: 12px;
                margin: 0.5rem;
            }
            
            .calendar-header {
                padding: 1rem;
            }
            
            .calendar-title {
                font-size: 1.25rem;
            }
            
            .calendar-subtitle {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="content-main">
        <!-- Header Section -->
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title">
                    <i class="fas fa-calendar-alt"></i> Calendar
                </h1>
                <p class="page-description">Event calendar view</p>
            </div>
        </div>
        
        <div class="calendar-container">
            <div class="calendar-header">
                <div class="calendar-title">
                    <h2>Event Calendar</h2>
                </div>
                <div class="calendar-controls">
                    <a href="events.php" class="calendar-btn" style="background: #6c757d; color: white;">
                        <i class="fas fa-arrow-left"></i> Back to Events
                    </a>
                    <button type="button" class="calendar-btn add-event-btn" onclick="window.location.href='event_edit.php?action=add'">
                        <i class="fas fa-plus"></i> Add Event
                    </button>
                    <button type="button" class="calendar-btn" onclick="calendar.changeView('dayGridMonth')">Month</button>
                    <button type="button" class="calendar-btn" onclick="calendar.changeView('timeGridWeek')">Week</button>
                    <button type="button" class="calendar-btn" onclick="calendar.changeView('timeGridDay')">Day</button>
                    <button type="button" class="calendar-btn" onclick="calendar.today()">Today</button>
                </div>
            </div>
            
            <!-- Calendar -->
            <div id="calendar"></div>
        </div>
    </div>

    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            
            window.calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                editable: true,
                droppable: true,
                selectable: true,
                selectMirror: true,
                dayMaxEvents: true,
                weekends: true,
                events: function(fetchInfo, successCallback, failureCallback) {
                    fetch('api/events.php?action=list')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                var events = data.data.map(event => ({
                                    id: event.id,
                                    title: event.title,
                                    start: event.date + (event.time ? 'T' + event.time : ''),
                                    description: event.description,
                                    location: event.location,
                                    color: getEventColor(event.event_type)
                                }));
                                successCallback(events);
                            } else {
                                failureCallback(data.message || 'Failed to load events');
                            }
                        })
                        .catch(error => {
                            console.error('Error loading events:', error);
                            failureCallback('Failed to load events');
                        });
                },
                select: function(arg) {
                    var title = prompt('Event Title:');
                    if (title) {
                        window.location.href = 'event_edit.php?action=add&date=' + arg.start.toISOString().split('T')[0];
                    }
                    calendar.unselect();
                },
                eventClick: function(arg) {
                    if (confirm('Edit this event?')) {
                        window.location.href = 'event_edit.php?action=edit&id=' + arg.event.id;
                    }
                }
            });
            
            calendar.render();
        });
        
        function getEventColor(eventType) {
            switch(eventType) {
                case 'Training': return '#007bff';
                case 'Match': return '#dc3545';
                case 'Meeting': return '#28a745';
                case 'Social': return '#ffc107';
                default: return '#6c757d';
            }
        }
    </script>
</body>
</html>
