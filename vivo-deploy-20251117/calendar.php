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
// Load teams for modal dropdown
$teams = [];
try { $teams = $db->query('SELECT id, name FROM teams ORDER BY name')->fetchAll(); } catch (Throwable $e) { /* ignore */ }
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
            position: relative;
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
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            flex-wrap: nowrap;
            gap: 1rem;
            border-bottom: 1px solid #e2e8f0;
            position: relative;
            z-index: 30;
        }
        
        .nav-left, .nav-right {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            min-height: 40px;
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

        /* Active state for top nav buttons: respect primary vs secondary */
        .calendar-nav .calendar-btn.primary.active {
            background: var(--primary) !important;
            border-color: var(--primary) !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12) !important;
            transform: translateY(-1px);
        }

        .calendar-nav .calendar-btn.secondary.active {
            background: var(--secondary) !important;
            border-color: var(--secondary) !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12) !important;
            transform: translateY(-1px);
        }
        
        /* Calendar Content */
        .calendar-content {
            padding: 0;
            min-height: 600px;
        }

        /* Inner frame around the calendar with padding and rounded border */
        .calendar-frame {
            padding: 1rem; /* space around the calendar */
            border-radius: 5px; /* requested rounded border */
            background: #ffffff;
            margin: 1rem;
            border: 1px solid rgba(0,0,0,0.04);
        }
        
        /* FullCalendar Custom Styling */
        .fc {
            font-family: inherit;
        }
        
        .fc-toolbar {
            padding: 1.5rem 2rem;
            background: white;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .fc-toolbar-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .fc-button-primary {
            background: var(--primary) !important;
            border-color: var(--primary) !important;
            color: white !important;
            border-radius: 8px !important;
            padding: 0.5rem 1rem !important;
            font-weight: 500 !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
            transition: all 0.2s ease !important;
        }
        
        .fc-button-primary:hover {
            background: var(--primary-dark) !important;
            border-color: var(--primary-dark) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15) !important;
        }
        
        .fc-button-primary:not(:disabled):active,
        .fc-button-primary:not(:disabled).fc-button-active {
            background: var(--primary-dark) !important;
            border-color: var(--primary-dark) !important;
        }
        
        .fc-daygrid-day {
            border-color: #e2e8f0 !important;
        }
        
        .fc-daygrid-day-number {
            color: #475569;
            font-weight: 500;
            padding: 0.5rem;
        }
        
        .fc-day-today {
            background: rgba(var(--primary-rgb), 0.05) !important;
        }
        
        .fc-day-today .fc-daygrid-day-number {
            background: var(--primary);
            color: white;
            border-radius: 50%;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0.25rem;
        }
        
        .fc-event {
            border-radius: 6px !important;
            border: none !important;
            padding: 2px 6px !important;
            font-size: 0.75rem !important;
            font-weight: 500 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
            margin-bottom: 2px !important;
        }
        
        .fc-event:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15) !important;
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
            
            .nav-left, .nav-right {
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .calendar-btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }
            
            .fc-toolbar {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }
            
            .fc-toolbar-chunk {
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .fc-button {
                padding: 0.4rem 0.8rem !important;
                font-size: 0.8rem !important;
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
        
        /* Loading State */
        .calendar-loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 400px;
            color: #64748b;
        }

        /* View period text shown under header */
        .view-period {
            margin-top: 0.5rem;
            font-size: 1rem;
            opacity: 0.95;
            color: rgba(255,255,255,0.95);
            font-weight: 500;
        }

        /* Modal for adding events (styled like add_event.php form) */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 200;
            padding: 1rem;
        }

        .modal-card, .form-card {
            background: #fff;
            padding: 1.25rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            width: 560px;
            max-width: 100%;
        }

        .page-header {
            margin-bottom: 1rem;
            text-align: left;
        }

        .page-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            margin: 0 0 0.25rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .page-title i { color: var(--primary); }

        .page-sub { color: #6b7280; margin: 0 0 0.75rem 0; font-size: 0.95rem; }

        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
        .fg-full { grid-column: 1 / -1; }
        .fg { display:block; }
        .fg label { display:block; font-weight:600; color:#374151; margin-bottom:0.35rem; font-size:0.85rem; }
        .fg input, .fg select, .fg textarea { width:100%; padding:0.6rem; border:2px solid #e5e7eb; border-radius:8px; font-size:0.9rem; }
        .fg input:focus, .fg select:focus, .fg textarea:focus { outline:none; border-color:var(--primary); }
        textarea { min-height:100px; resize:vertical; font-family:inherit; }

        .actions { display:flex; gap:0.5rem; justify-content:flex-end; margin-top:0.75rem; }

        .btn { padding:0.55rem 1rem; border-radius:8px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:0.5rem; border:none; }
        .btn-primary { background:var(--primary); color:#fff; }
        .btn-primary:hover { background:var(--secondary); }
        .btn-secondary { background:#6b7280; color:#fff; }
        
        .loading-spinner {
            width: 2rem;
            height: 2rem;
            border: 2px solid #e2e8f0;
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 0.75rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="content-main">
        <div class="calendar-wrapper">
            <!-- Modern Calendar Container -->
            <div class="calendar-container">
                <!-- Calendar Header -->
                <div class="calendar-header">
                    <h1 class="calendar-title">
                        <i class="fas fa-calendar-alt"></i> Event Calendar
                    </h1>
                    <p class="calendar-subtitle">Manage and view your events</p>
                    <div id="view-period" class="view-period"></div>
                </div>
                
                <!-- Navigation Controls -->
                <div class="calendar-nav">
                    <div class="nav-left">
                        <a href="events.php" class="calendar-btn secondary">
                            <i class="fas fa-arrow-left"></i> Back to Events
                        </a>
                    </div>
                    <div class="nav-right" style="margin:0 auto;">
                        <button id="btn-prev" type="button" class="calendar-btn secondary" onclick="calendar.prev()">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button id="btn-month" type="button" class="calendar-btn primary" onclick="calendar.changeView('dayGridMonth')">
                            <i class="fas fa-calendar"></i> Month
                        </button>
                        <button id="btn-next" type="button" class="calendar-btn secondary" onclick="calendar.next()">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button id="btn-week" type="button" class="calendar-btn primary" onclick="calendar.changeView('timeGridWeek')">
                            <i class="fas fa-calendar-week"></i> Week
                        </button>
                        <button id="btn-day" type="button" class="calendar-btn primary" onclick="calendar.changeView('timeGridDay')">
                            <i class="fas fa-calendar-day"></i> Day
                        </button>
                        <button id="btn-today" type="button" class="calendar-btn primary" onclick="calendar.today()">
                            <i class="fas fa-home"></i> Today
                        </button>
                    </div>
                </div>
                
                <!-- Calendar Content -->
                <div class="calendar-content">
                    <div class="calendar-frame">
                        <div id="calendar-loading" class="calendar-loading">
                            <div class="loading-spinner"></div>
                            Loading calendar...
                        </div>
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Add Event -->
    <div id="modal-backdrop" class="modal-backdrop" role="dialog" aria-modal="true">
        <div class="modal-card">
            <div class="page-header">
                <h2 class="page-title"><i class="fas fa-calendar-plus"></i> Create Event</h2>
                <p class="page-sub">Quickly add an event for the selected date</p>
            </div>
            <form id="modal-event-form" method="POST" action="add_event.php">
                <input type="hidden" name="action" value="create">
                <input type="hidden" id="modal-date" name="date" value="">

                <div class="form-grid">
                    <div class="fg fg-full">
                        <label for="modal-title">Event Title *</label>
                        <input id="modal-title" name="title" required />
                    </div>

                    <div class="fg">
                        <label for="modal-type">Event Type *</label>
                        <select id="modal-type" name="event_type" required>
                            <option value="">Select Type</option>
                            <option value="training">Training</option>
                            <option value="match">Match</option>
                            <option value="meeting">Meeting</option>
                            <option value="tournament">Tournament</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="fg">
                        <label for="modal-time">Time</label>
                        <input id="modal-time" name="time" type="time" />
                    </div>

                    <div class="fg">
                        <label for="modal-team">Team</label>
                        <select id="modal-team" name="team_id">
                            <option value="">All Teams / General</option>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="fg">
                        <label for="modal-opponent">Opponent</label>
                        <input id="modal-opponent" name="opponent" placeholder="Opponent (for matches)" />
                    </div>

                    <div class="fg fg-full">
                        <label for="modal-location">Location</label>
                        <input id="modal-location" name="location" />
                    </div>

                    <div class="fg fg-full">
                        <label for="modal-description">Description</label>
                        <textarea id="modal-description" name="description"></textarea>
                    </div>
                </div>

                <div class="actions">
                    <button type="button" class="btn btn-secondary" id="modal-cancel"><i class="fas fa-times"></i> Cancel</button>
                    <button type="submit" class="btn btn-primary" id="modal-save"><i class="fas fa-save"></i> Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: View Event -->
    <div id="view-modal-backdrop" class="modal-backdrop" role="dialog" aria-modal="true" style="display:none;">
        <div class="modal-card">
            <div class="page-header">
                <h2 class="page-title"><i class="fas fa-calendar-day"></i> <span id="view-title">Event</span></h2>
                <p class="page-sub" id="view-date">Date</p>
            </div>

            <div class="form-grid">
                <div class="fg fg-full">
                    <label>Type</label>
                    <div id="view-type" style="font-weight:600;color:#374151"></div>
                </div>
                <div class="fg fg-full">
                    <label>Location</label>
                    <div id="view-location" style="color:#374151"></div>
                </div>
                <div class="fg fg-full">
                    <label>Description</label>
                    <div id="view-description" style="color:#4b5563"></div>
                </div>
            </div>

            <div class="actions" style="margin-top:1rem;">
                <button id="view-delete-button" class="btn btn-secondary"><i class="fas fa-trash"></i> Delete</button>
                <a id="view-edit-button" class="btn btn-primary" href="#"><i class="fas fa-edit"></i> Edit</a>
                <button type="button" id="view-close" class="btn btn-secondary" style="margin-left:0.5rem;">Close</button>
            </div>
        </div>
    </div>

    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var loadingEl = document.getElementById('calendar-loading');
            
            // Hide loading initially
            loadingEl.style.display = 'none';
            
            window.calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 'auto',
                // Disable FullCalendar's built-in toolbar - we use the top nav buttons instead
                headerToolbar: false,
                editable: true,
                droppable: true,
                selectable: true,
                selectMirror: true,
                dayMaxEvents: 3,
                moreLinkText: function(num) {
                    return '+' + num + ' more';
                },
                weekends: true,
                themeSystem: 'standard',
                loading: function(isLoading) {
                    if (isLoading) {
                        loadingEl.style.display = 'flex';
                        calendarEl.style.opacity = '0.5';
                    } else {
                        loadingEl.style.display = 'none';
                        calendarEl.style.opacity = '1';
                    }
                },
                events: function(fetchInfo, successCallback, failureCallback) {
                    // For now, use a simple PHP endpoint to get events
                    // This can be replaced with an API endpoint later
                    var url = 'get_events_json.php?start=' + fetchInfo.startStr + '&end=' + fetchInfo.endStr + '&_=' + Date.now();
                    fetch(url, { cache: 'no-store' })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                successCallback(data.events);
                            } else {
                                console.error('Failed to load events:', data.message);
                                successCallback([]); // Show empty calendar instead of failing
                            }
                        })
                        .catch(error => {
                            console.error('Error loading events:', error);
                            successCallback([]); // Show empty calendar instead of failing
                        });
                },
                select: function(arg) {
                    // Open modal for quick event creation
                    var isoDate = arg.start.toISOString().split('T')[0];
                    document.getElementById('modal-date').value = isoDate;
                    document.getElementById('modal-title').value = '';
                    document.getElementById('modal-type').value = '';
                    document.getElementById('modal-time').value = '';
                    document.getElementById('modal-location').value = '';
                    document.getElementById('modal-backdrop').style.display = 'flex';
                    // Focus title
                    setTimeout(function(){ document.getElementById('modal-title').focus(); }, 100);
                    calendar.unselect();
                },
                eventClick: function(arg) {
                    // Show a styled view modal with event details
                    var event = arg.event;
                    var vb = document.getElementById('view-modal-backdrop');
                    if (!vb) return;

                    document.getElementById('view-title').textContent = event.title || '';
                    var d = event.start;
                    var dateStr = d ? d.toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' }) : '';
                    var timeStr = event.start && event.start.toLocaleTimeString ? event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '';
                    document.getElementById('view-date').textContent = dateStr + (timeStr ? ' • ' + timeStr : '');
                    document.getElementById('view-type').textContent = event.extendedProps.event_type || '';
                    document.getElementById('view-location').textContent = event.extendedProps.location || '';
                    document.getElementById('view-description').textContent = event.extendedProps.description || '';

                    // set action URLs and store id on the buttons/backdrop for reliable access
                    var editBtn = document.getElementById('view-edit-button');
                    var deleteBtn = document.getElementById('view-delete-button');
                    if (editBtn) { editBtn.setAttribute('href', 'event_edit.php?action=edit&id=' + event.id); editBtn.dataset.eventId = event.id; }
                    if (deleteBtn) { deleteBtn.dataset.eventId = event.id; }
                    vb.dataset.eventId = event.id;

                    vb.style.display = 'flex';
                },
                eventDidMount: function(arg) {
                    // Add tooltip functionality
                    arg.el.title = arg.event.title + 
                                  (arg.event.extendedProps.description ? '\n' + arg.event.extendedProps.description : '') +
                                  (arg.event.extendedProps.location ? '\nLocation: ' + arg.event.extendedProps.location : '');
                },
                dayCellDidMount: function(arg) {
                    // Add hover effects to day cells
                    if (arg.date > new Date()) {
                        arg.el.style.cursor = 'pointer';
                    }
                }
            });
            
            // Render calendar
            calendar.render();
            
            // Update view buttons to reflect current view
            function updateViewButtons() {
                document.querySelectorAll('.calendar-nav .calendar-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                var currentView = calendar.view.type;
                var viewButtons = {
                    'dayGridMonth': document.getElementById('btn-month'),
                    'timeGridWeek': document.getElementById('btn-week'),
                    'timeGridDay': document.getElementById('btn-day')
                };

                if (viewButtons[currentView]) {
                    viewButtons[currentView].classList.add('active');
                }
                // Set human-readable view period (month / week range / day)
                var viewPeriodEl = document.getElementById('view-period');
                if (!viewPeriodEl) return;

                function fmt(d, opts) { return d.toLocaleDateString(undefined, opts); }

                var start = calendar.view.currentStart;
                var end = calendar.view.currentEnd; // exclusive for FullCalendar

                if (currentView === 'dayGridMonth') {
                    // Show full month and year (e.g., "September 2025")
                    var monthLabel = start.toLocaleString(undefined, { month: 'long', year: 'numeric' });
                    viewPeriodEl.textContent = monthLabel;
                } else if (currentView === 'timeGridWeek') {
                    // Week range: inclusive start to end-1 day
                    var s = new Date(start);
                    var e = new Date(end);
                    e.setDate(e.getDate() - 1);

                    if (s.getFullYear() === e.getFullYear()) {
                        if (s.getMonth() === e.getMonth()) {
                            // Same month: "Aug 3–9, 2025"
                            viewPeriodEl.textContent = fmt(s, { month: 'short', day: 'numeric' }) + '–' + fmt(e, { day: 'numeric', year: 'numeric' });
                        } else {
                            // Different months same year: "Aug 30 – Sep 5, 2025"
                            viewPeriodEl.textContent = fmt(s, { month: 'short', day: 'numeric' }) + ' – ' + fmt(e, { month: 'short', day: 'numeric', year: 'numeric' });
                        }
                    } else {
                        // Different years: include full dates
                        viewPeriodEl.textContent = fmt(s, { month: 'short', day: 'numeric', year: 'numeric' }) + ' – ' + fmt(e, { month: 'short', day: 'numeric', year: 'numeric' });
                    }
                } else if (currentView === 'timeGridDay') {
                    viewPeriodEl.textContent = fmt(start, { month: 'long', day: 'numeric', year: 'numeric' });
                } else {
                    // Fallback: show current view title from FullCalendar
                    var title = document.querySelector('.fc-toolbar-title');
                    viewPeriodEl.textContent = title ? title.textContent : '';
                }
            }
            
            // Initial update
            updateViewButtons();
            
            // Listen for view changes
            calendar.on('datesSet', updateViewButtons);
            
            // Modal wiring
            var modalBackdrop = document.getElementById('modal-backdrop');
            document.getElementById('modal-cancel').addEventListener('click', function(){ modalBackdrop.style.display = 'none'; });

            // Submit modal form via fetch to add_event.php and refresh calendar
            document.getElementById('modal-event-form').addEventListener('submit', function(e){
                e.preventDefault();
                var form = e.target;
                var data = new FormData(form);
                // include description if present
                var desc = document.getElementById('modal-description');
                if (desc && !data.has('description')) data.append('description', desc.value || '');

                fetch(form.action, { method: 'POST', body: data })
                .then(resp => {
                    // on success, close modal and refetch events
                    modalBackdrop.style.display = 'none';
                    calendar.refetchEvents();
                })
                .catch(err => {
                    console.error('Failed to create event', err);
                    // fallback: navigate to add_event.php with query params
                    var params = new URLSearchParams();
                    data.forEach(function(v,k){ params.append(k,v); });
                    window.location.href = form.action + '?' + params.toString();
                });
            });

            // View modal close wiring
            var viewBackdrop = document.getElementById('view-modal-backdrop');
            var viewCloseBtn = document.getElementById('view-close');
            if (viewCloseBtn) viewCloseBtn.addEventListener('click', function(){ viewBackdrop.style.display = 'none'; });

            // Toggle opponent field in modal when type is 'match'
            var modalType = document.getElementById('modal-type');
            var modalOpponent = document.getElementById('modal-opponent');
            function toggleOpponentField(){ if (!modalType || !modalOpponent) return; modalOpponent.parentElement.style.display = (modalType.value === 'match') ? 'block' : 'none'; }
            if (modalType) { modalType.addEventListener('change', toggleOpponentField); toggleOpponentField(); }

            // Wire view modal delete to AJAX call
            var viewDeleteBtn = document.getElementById('view-delete-button');
            var viewEditBtn = document.getElementById('view-edit-button');
            if (viewDeleteBtn) {
                viewDeleteBtn.addEventListener('click', function(){
                    var id = viewBackdrop.dataset.eventId || viewDeleteBtn.dataset.eventId;
                    if (!id) { alert('Event id missing'); return; }
                    if (!confirm('Delete this event?')) return;

                    var data = new FormData(); data.append('action','delete'); data.append('id', id);
                    fetch('event_edit.php', { method: 'POST', body: data, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(json => {
                        if (json && json.success) {
                            viewBackdrop.style.display = 'none';
                            calendar.refetchEvents();
                        } else {
                            alert('Delete failed');
                        }
                    })
                    .catch(err => { console.error('Delete error', err); alert('Delete error'); });
                });
            }

            // Close view modal on ESC
            document.addEventListener('keydown', function(e){ if (e.key === 'Escape') { if (modalBackdrop.style.display === 'flex') modalBackdrop.style.display = 'none'; if (viewBackdrop && viewBackdrop.style.display === 'flex') viewBackdrop.style.display = 'none'; } });
        });
        
        function getEventColor(eventType) {
            const colors = {
                'training': '#3b82f6',   // Blue
                'match': '#ef4444',      // Red  
                'meeting': '#10b981',    // Green
                'social': '#f59e0b',     // Yellow
                'tournament': '#8b5cf6', // Purple
                'default': '#6b7280'     // Gray
            };
            
            return colors[eventType?.toLowerCase()] || colors.default;
        }
        
        // Add active state styles (only generic fallback)
        const style = document.createElement('style');
        style.textContent = `
            .calendar-btn.active {
                box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
