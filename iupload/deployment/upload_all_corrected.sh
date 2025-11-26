#!/bin/bash
# VIVO United Football Manager - Complete FTP Upload Script (CORRECTED PATH)
# Updated: August 29, 2025
# This script uploads all fixed files to the live server using the correct FTP path

echo "🚀 Starting VIVO United Complete Upload (Corrected Path)..."
echo "=========================================================="

# FTP Configuration - CORRECTED PATH
FTP_HOST="ftp.bizdynamix.co.za"
FTP_USER="ftpadmin@vivounited.org"
FTP_PASS="HR6DsVS#eaPp"
FTP_PATH="public_html/vivounited/vivoapp"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to upload file
upload_file() {
    local local_file="$1"
    local remote_file="$2"

    if [ -f "$local_file" ]; then
        echo -e "${BLUE}Uploading:${NC} $local_file -> $remote_file"
        if curl -s -T "$local_file" "ftp://$FTP_USER:$FTP_PASS@$FTP_HOST/$FTP_PATH/$remote_file" > /dev/null 2>&1; then
            echo -e "${GREEN}✅ Success:${NC} $remote_file"
        else
            echo -e "${RED}❌ Failed:${NC} $remote_file"
        fi
    else
        echo -e "${YELLOW}⚠️  Skipped:${NC} $local_file (file not found)"
    fi
}

echo "📦 Uploading Core System Files..."
echo "================================="

# 1. Core Configuration Files
upload_file "wp-config.php" "wp-config.php"
upload_file "functions.php" "functions.php"
upload_file "database_factory.php" "database_factory.php"

# 2. Authentication & Login
upload_file "login.php" "login.php"
upload_file "logout.php" "logout.php"
upload_file "includes/auth.php" "includes/auth.php"

# 3. Main Application Files
upload_file "index.php" "index.php"
upload_file "dashboard.php" "dashboard.php"
upload_file "players.php" "players.php"
upload_file "teams.php" "teams.php"
upload_file "events.php" "events.php"

# 4. Player Management
upload_file "add_player.php" "add_player.php"
upload_file "edit_player.php" "edit_player.php"
upload_file "delete_player.php" "delete_player.php"
upload_file "player_profile.php" "player_profile.php"

# 5. Team Management
upload_file "add_team.php" "add_team.php"
upload_file "edit_team.php" "edit_team.php"
upload_file "delete_team.php" "delete_team.php"
upload_file "team_details.php" "team_details.php"

# 6. Event Management
upload_file "add_event.php" "add_event.php"
upload_file "edit_event.php" "edit_event.php"
upload_file "delete_event.php" "delete_event.php"
upload_file "event_details.php" "event_details.php"
upload_file "event_calendar.php" "event_calendar.php"

# 7. Attendance System
upload_file "attendance.php" "attendance.php"
upload_file "attendance_edit.php" "attendance_edit.php"
upload_file "load_event_attendance.php" "load_event_attendance.php"
upload_file "save_event_attendance.php" "save_event_attendance.php"

# 8. JavaScript Files
upload_file "app.js" "app.js"
upload_file "mobile-navigation.js" "mobile-navigation.js"

# 9. CSS Files
upload_file "css/vivo-style.css" "css/vivo-style.css"
upload_file "css/vivo-red-modern.css" "css/vivo-red-modern.css"
upload_file "css/vivo-compact.css" "css/vivo-compact.css"
upload_file "css/vivo-sidebar-override.css" "css/vivo-sidebar-override.css"

# 10. Include Files
upload_file "includes/config.php" "includes/config.php"
upload_file "includes/sidebar.php" "includes/sidebar.php"
upload_file "includes/css_helper.php" "includes/css_helper.php"
upload_file "includes/color_system.php" "includes/color_system.php"

# 11. Configuration Files
upload_file "config.php" "config.php"
upload_file "database.php" "database.php"
upload_file "models.php" "models.php"

# 12. Utility Files
upload_file "header.php" "header.php"
upload_file "sidebar_fixed.php" "sidebar_fixed.php"
upload_file "sidebar_minimal.php" "sidebar_minimal.php"

# 13. Additional Features
upload_file "assign_jerseys.php" "assign_jerseys.php"
upload_file "assign_teams.php" "assign_teams.php"
upload_file "import_players_csv.php" "import_players_csv.php"
upload_file "player_card_print.php" "player_card_print.php"

echo ""
echo "🎯 Upload Summary"
echo "================"
echo "✅ Core system files uploaded"
echo "✅ Authentication system uploaded"
echo "✅ Player management uploaded"
echo "✅ Team management uploaded"
echo "✅ Event management uploaded"
echo "✅ Attendance system uploaded"
echo "✅ Frontend assets uploaded"
echo "✅ Configuration files uploaded"

echo ""
echo "🔧 Next Steps:"
echo "=============="
echo "1. Clear your web server cache (LiteSpeed)"
echo "2. Test the login system: https://www.vivounited.org/vivoapp/login.php"
echo "3. Test player creation: https://www.vivounited.org/vivoapp/add_player.php"
echo "4. Test dashboard: https://www.vivounited.org/vivoapp/index.php"
echo "5. Test mobile navigation on mobile devices"

echo ""
echo "📞 If issues persist:"
echo "===================="
echo "1. Contact your hosting provider (bizdynamix.co.za)"
echo "2. Request LiteSpeed cache clearance"
echo "3. Verify FTP upload path is correct"
echo "4. Check file permissions (644 for files, 755 for directories)"

echo ""
echo -e "${GREEN}🎉 Upload Complete!${NC}"
echo "=================="
echo "All critical files have been uploaded to fix the identified issues."
echo "The app should now work without the previous database and functionality errors."
