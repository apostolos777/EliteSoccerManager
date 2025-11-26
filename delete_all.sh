#!/bin/bash

# Delete all files from server
FTP_USER="ftpadmin@vivounited.org"
FTP_PASS="HR6DsVS#eaPp"
FTP_HOST="ftp.bizdynamix.co.za"
FTP_PATH="/public_html/vivounited/vivoapp"

echo "Deleting all files from server..."

# List of files to delete (from server listing)
files=(
    "config.php"
    "add_player.php"
    "logout.php"
    "event_details.php"
    "path_test.php"
    "assign_teams.php"
    "edit_team.php"
    "add_event.php"
    "add_team.php"
    "debug-test.php"
    "style.css"
    "edit_player.php"
    "delete_event.php"
    "test_teams.php"
    "vivo-style.css"
    "dashboard.php"
    "index.php"
    "team_details.php"
    "event_calendar.php"
    "assign_jerseys.php"
    "club_settings.php"
    "database.php"
    "header.php"
    "database.db"
    "add_player_new.php"
    "wp-config.php"
    "attendance.php"
    "mobile-navigation.js"
    "test_schema.php"
    "delete_team.php"
    "player_profile.php"
    "events.php"
    "test-simple.php"
    "teams.php"
    "edit_event.php"
    "phpinfo.php"
    ".htaccess"
    "settings.php"
    "functions.php"
    "models.php"
    "login.php"
    "players.php"
    "app.js"
    "delete_player.php"
)

# Delete each file
for file in "${files[@]}"; do
    echo "Deleting $file..."
    curl --user "$FTP_USER:$FTP_PASS" -Q "DELE $file" "ftp://$FTP_HOST$FTP_PATH/" 2>/dev/null || echo "Failed to delete $file"
done

echo "Server cleanup complete!"
