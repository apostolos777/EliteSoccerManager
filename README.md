# VIVO United Football Manager

A comprehensive football team management system built with PHP and SQLite.

## Features

- **Dashboard**: Interactive overview with team statistics
- **Teams Management**: Create and manage football teams with detailed information
- **Players Management**: Track player profiles, positions, and team assignments  
- **Events Management**: Schedule matches, training sessions, and team events
- **Attendance Tracking**: Monitor player attendance for events and training
- **Statistics**: Comprehensive reporting and analytics

## System Requirements

- PHP 7.4 or higher
- SQLite 3 support
- Web server (Apache/Nginx)
- Modern web browser

## Installation

1. Upload all files to your web hosting directory
2. Ensure the web server has write permissions for the SQLite database file (`vivo_football.db`)
3. Access the application through your web browser
4. Login with default credentials or set up authentication as needed

## File Structure

```
/
├── index.php              # Main dashboard
├── teams.php              # Teams management
├── players.php            # Players management  
├── events.php             # Events management
├── attendance.php         # Attendance tracking
├── login.php              # Authentication
├── database_config.php    # Database configuration
├── vivo_football.db       # SQLite database
├── css/                   # Stylesheets
├── js/                    # JavaScript files
├── includes/              # PHP includes and utilities
│   ├── auth.php
│   ├── config.php
│   ├── functions.php
│   ├── models.php
│   └── sidebar.php
└── README.md              # This file
```

## Configuration

### Database Configuration
The system uses SQLite by default. Database configuration is in `database_config.php`.

### Styling
- Main styles: `css/vivo-style.css`
- Legacy styles: `css/style.css`

## Features Overview

### Dashboard (index.php)
- Team, player, and event statistics
- Quick access to all sections
- Interactive stat cards

### Teams Management (teams.php)
- Add, edit, and view teams
- Search and filter functionality
- Team statistics and player counts

### Players Management (players.php)  
- Player profiles and information
- Team assignments and positions
- Search and filtering options

### Events Management (events.php)
- Match and training scheduling
- Event details and participants
- Calendar integration

### Attendance Tracking (attendance.php)
- Mark player attendance for events
- Attendance statistics and reporting
- Team-based attendance views

## Security Notes

- Ensure proper file permissions
- Configure authentication as needed
- Regular database backups recommended
- Keep the system updated

## Support

For technical support or feature requests, contact the development team.

## Version

Current Version: 1.0.0 (July 2025)
