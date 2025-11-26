#!/bin/bash

# Fresh install upload script for Vivo United app
# Uploads all essential files to live server

FTP_USER="ftpadmin@vivounited.org"
FTP_PASS="HR6DsVS#eaPp"
FTP_HOST="ftp.bizdynamix.co.za"
FTP_PATH="/public_html/vivounited/vivoapp"

echo "Starting fresh upload of Vivo United app..."

# Core configuration files
echo "Uploading configuration files..."
curl --user "$FTP_USER:$FTP_PASS" -T "wp-config.php" "ftp://$FTP_HOST$FTP_PATH/wp-config.php"
curl --user "$FTP_USER:$FTP_PASS" -T "config.php" "ftp://$FTP_HOST$FTP_PATH/config.php"
curl --user "$FTP_USER:$FTP_PASS" -T "functions.php" "ftp://$FTP_HOST$FTP_PATH/functions.php"
curl --user "$FTP_USER:$FTP_PASS" -T "database.php" "ftp://$FTP_HOST$FTP_PATH/database.php"

# Main application files
echo "Uploading main application files..."
curl --user "$FTP_USER:$FTP_PASS" -T "index.php" "ftp://$FTP_HOST$FTP_PATH/index.php"
curl --user "$FTP_USER:$FTP_PASS" -T "login.php" "ftp://$FTP_HOST$FTP_PATH/login.php"
curl --user "$FTP_USER:$FTP_PASS" -T "dashboard.php" "ftp://$FTP_HOST$FTP_PATH/dashboard.php"
curl --user "$FTP_USER:$FTP_PASS" -T "logout.php" "ftp://$FTP_HOST$FTP_PATH/logout.php"

# Player management
echo "Uploading player management files..."
curl --user "$FTP_USER:$FTP_PASS" -T "players.php" "ftp://$FTP_HOST$FTP_PATH/players.php"
curl --user "$FTP_USER:$FTP_PASS" -T "add_player.php" "ftp://$FTP_HOST$FTP_PATH/add_player.php"
curl --user "$FTP_USER:$FTP_PASS" -T "edit_player.php" "ftp://$FTP_HOST$FTP_PATH/edit_player.php"
curl --user "$FTP_USER:$FTP_PASS" -T "delete_player.php" "ftp://$FTP_HOST$FTP_PATH/delete_player.php"
curl --user "$FTP_USER:$FTP_PASS" -T "player_profile.php" "ftp://$FTP_HOST$FTP_PATH/player_profile.php"

# Team management
echo "Uploading team management files..."
curl --user "$FTP_USER:$FTP_PASS" -T "teams.php" "ftp://$FTP_HOST$FTP_PATH/teams.php"
curl --user "$FTP_USER:$FTP_PASS" -T "add_team.php" "ftp://$FTP_HOST$FTP_PATH/add_team.php"
curl --user "$FTP_USER:$FTP_PASS" -T "edit_team.php" "ftp://$FTP_HOST$FTP_PATH/edit_team.php"
curl --user "$FTP_USER:$FTP_PASS" -T "delete_team.php" "ftp://$FTP_HOST$FTP_PATH/delete_team.php"
curl --user "$FTP_USER:$FTP_PASS" -T "team_details.php" "ftp://$FTP_HOST$FTP_PATH/team_details.php"

# Event management
echo "Uploading event management files..."
curl --user "$FTP_USER:$FTP_PASS" -T "events.php" "ftp://$FTP_HOST$FTP_PATH/events.php"
curl --user "$FTP_USER:$FTP_PASS" -T "add_event.php" "ftp://$FTP_HOST$FTP_PATH/add_event.php"
curl --user "$FTP_USER:$FTP_PASS" -T "edit_event.php" "ftp://$FTP_HOST$FTP_PATH/edit_event.php"
curl --user "$FTP_USER:$FTP_PASS" -T "delete_event.php" "ftp://$FTP_HOST$FTP_PATH/delete_event.php"
curl --user "$FTP_USER:$FTP_PASS" -T "event_details.php" "ftp://$FTP_HOST$FTP_PATH/event_details.php"
curl --user "$FTP_USER:$FTP_PASS" -T "event_calendar.php" "ftp://$FTP_HOST$FTP_PATH/event_calendar.php"

# Attendance and assignments
echo "Uploading attendance and assignment files..."
curl --user "$FTP_USER:$FTP_PASS" -T "attendance.php" "ftp://$FTP_HOST$FTP_PATH/attendance.php"
curl --user "$FTP_USER:$FTP_PASS" -T "assign_teams.php" "ftp://$FTP_HOST$FTP_PATH/assign_teams.php"
curl --user "$FTP_USER:$FTP_PASS" -T "assign_jerseys.php" "ftp://$FTP_HOST$FTP_PATH/assign_jerseys.php"

# Settings and utilities
echo "Uploading settings and utility files..."
curl --user "$FTP_USER:$FTP_PASS" -T "settings.php" "ftp://$FTP_HOST$FTP_PATH/settings.php"
curl --user "$FTP_USER:$FTP_PASS" -T "club_settings.php" "ftp://$FTP_HOST$FTP_PATH/club_settings.php"
curl --user "$FTP_USER:$FTP_PASS" -T "header.php" "ftp://$FTP_HOST$FTP_PATH/header.php"
curl --user "$FTP_USER:$FTP_PASS" -T "models.php" "ftp://$FTP_HOST$FTP_PATH/models.php"

# JavaScript and CSS
echo "Uploading JavaScript and CSS files..."
curl --user "$FTP_USER:$FTP_PASS" -T "app.js" "ftp://$FTP_HOST$FTP_PATH/app.js"
curl --user "$FTP_USER:$FTP_PASS" -T "mobile-navigation.js" "ftp://$FTP_HOST$FTP_PATH/mobile-navigation.js"
curl --user "$FTP_USER:$FTP_PASS" -T "style.css" "ftp://$FTP_HOST$FTP_PATH/style.css"
curl --user "$FTP_USER:$FTP_PASS" -T "vivo-style.css" "ftp://$FTP_HOST$FTP_PATH/vivo-style.css"

# Database file
echo "Uploading database file..."
curl --user "$FTP_USER:$FTP_PASS" -T "database.db" "ftp://$FTP_HOST$FTP_PATH/database.db"

echo "Fresh install upload completed successfully!"
echo "Your Vivo United app is now live at: https://www.vivounited.org/vivoapp/"
