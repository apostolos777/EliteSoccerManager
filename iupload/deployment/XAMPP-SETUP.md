# VIVO Football Manager - XAMPP Setup Guide

## Quick Setup Instructions

### 1. Database Setup
1. Start XAMPP (Apache + MySQL)
2. Open phpMyAdmin: http://localhost/phpmyadmin
3. Create new database named: `vivo_football`
4. Import the setup file: `/xampp-setup.sql` (found in this directory)

### 2. Test the Application
1. Open browser and go to: http://localhost/vivo-app-new/
2. Login with default credentials (or skip if no auth required)
3. Test all pages:
   - Dashboard: http://localhost/vivo-app-new/dashboard.php
   - Players: http://localhost/vivo-app-new/players.php
   - Teams: http://localhost/vivo-app-new/teams.php
   - Events: http://localhost/vivo-app-new/events.php
   - Settings: http://localhost/vivo-app-new/settings.php

### 3. File Structure
```
vivo-app-new/
├── config/database.php (XAMPP configured)
├── xampp-setup.sql (Database setup)
├── dashboard.php
├── players.php
├── teams.php
├── events.php
├── settings.php
├── css/ (styling files)
└── uploads/ (logo uploads)
```

### 4. Features Included
- ✅ Player Management with Photos
- ✅ Team Management with Logos
- ✅ Event Scheduling
- ✅ Attendance Tracking
- ✅ Club Settings & Logo Upload
- ✅ Bootstrap 5 Responsive Design
- ✅ Modern Dashboard

### 5. Database Configuration
- Host: localhost
- Database: vivo_football
- Username: root
- Password: (empty)
- Port: 3306

### 6. Troubleshooting
- If database connection fails, check XAMPP MySQL is running
- If pages show errors, check file permissions
- If uploads don't work, check uploads/ directory permissions

Ready for local testing! 🚀
