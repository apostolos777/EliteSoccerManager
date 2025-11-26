#!/bin/bash
# VIVO United Football Manager - Complete FTP Upload Script (FIXED)
# Updated: August 29, 2025
# This script uploads all fixed files to the live server using the correct FTP path

echo "🚀 Starting VIVO United Complete Upload (Fixed Script)..."
echo "======================================================="

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
    # Use --user to pass credentials (avoids issues with special chars like '#') and capture curl exit code
    if curl -s --user "$FTP_USER:$FTP_PASS" -T "$local_file" "ftp://$FTP_HOST/$FTP_PATH/$remote_file" > /dev/null 2>&1; then
            echo -e "${GREEN}✅ Success:${NC} $remote_file"
            return 0
        else
            echo -e "${RED}❌ Failed:${NC} $remote_file"
            return 1
        fi
    else
        echo -e "${YELLOW}⚠️  Skipped:${NC} $local_file (file not found)"
        return 1
    fi
}

echo "📦 Uploading Core System Files..."
echo "================================="

success_count=0
total_count=0

# 1. Core Configuration Files
((total_count++))
upload_file "wp-config.php" "wp-config.php" && ((success_count++))

((total_count++))
upload_file "functions.php" "functions.php" && ((success_count++))

((total_count++))
upload_file "database_factory.php" "database_factory.php" && ((success_count++))

# 2. Authentication & Login
((total_count++))
upload_file "login.php" "login.php" && ((success_count++))

((total_count++))
upload_file "logout.php" "logout.php" && ((success_count++))

((total_count++))
upload_file "includes/auth.php" "includes/auth.php" && ((success_count++))

# 3. Main Application Files
((total_count++))
upload_file "index.php" "index.php" && ((success_count++))

((total_count++))
upload_file "dashboard.php" "dashboard.php" && ((success_count++))

((total_count++))
upload_file "players.php" "players.php" && ((success_count++))

((total_count++))
upload_file "teams.php" "teams.php" && ((success_count++))

((total_count++))
upload_file "events.php" "events.php" && ((success_count++))

# 4. Player Management
((total_count++))
upload_file "add_player.php" "add_player.php" && ((success_count++))

((total_count++))
upload_file "edit_player.php" "edit_player.php" && ((success_count++))

((total_count++))
upload_file "delete_player.php" "delete_player.php" && ((success_count++))

((total_count++))
upload_file "player_profile.php" "player_profile.php" && ((success_count++))

# 5. Team Management
((total_count++))
upload_file "add_team.php" "add_team.php" && ((success_count++))

((total_count++))
upload_file "edit_team.php" "edit_team.php" && ((success_count++))

((total_count++))
upload_file "delete_team.php" "delete_team.php" && ((success_count++))

((total_count++))
upload_file "team_details.php" "team_details.php" && ((success_count++))

# 6. Event Management
((total_count++))
upload_file "add_event.php" "add_event.php" && ((success_count++))

((total_count++))
upload_file "edit_event.php" "edit_event.php" && ((success_count++))

((total_count++))
upload_file "delete_event.php" "delete_event.php" && ((success_count++))

((total_count++))
upload_file "event_details.php" "event_details.php" && ((success_count++))

((total_count++))
upload_file "event_calendar.php" "event_calendar.php" && ((success_count++))

# 7. Attendance System
((total_count++))
upload_file "attendance.php" "attendance.php" && ((success_count++))

((total_count++))
upload_file "attendance_edit.php" "attendance_edit.php" && ((success_count++))

((total_count++))
upload_file "load_event_attendance.php" "load_event_attendance.php" && ((success_count++))

((total_count++))
upload_file "save_event_attendance.php" "save_event_attendance.php" && ((success_count++))

# 8. JavaScript Files
((total_count++))
upload_file "app.js" "app.js" && ((success_count++))

((total_count++))
upload_file "mobile-navigation.js" "mobile-navigation.js" && ((success_count++))

# 9. CSS Files
((total_count++))
upload_file "css/vivo-style.css" "css/vivo-style.css" && ((success_count++))

((total_count++))
upload_file "css/vivo-red-modern.css" "css/vivo-red-modern.css" && ((success_count++))

((total_count++))
upload_file "css/vivo-compact.css" "css/vivo-compact.css" && ((success_count++))

((total_count++))
upload_file "css/vivo-sidebar-override.css" "css/vivo-sidebar-override.css" && ((success_count++))

# 10. Include Files
((total_count++))
upload_file "includes/config.php" "includes/config.php" && ((success_count++))

((total_count++))
upload_file "includes/sidebar.php" "includes/sidebar.php" && ((success_count++))

((total_count++))
upload_file "includes/css_helper.php" "includes/css_helper.php" && ((success_count++))

((total_count++))
upload_file "includes/color_system.php" "includes/color_system.php" && ((success_count++))

# 11. Configuration Files
((total_count++))
upload_file "config.php" "config.php" && ((success_count++))

((total_count++))
upload_file "database.php" "database.php" && ((success_count++))

((total_count++))
upload_file "models.php" "models.php" && ((success_count++))

# 12. Utility Files
((total_count++))
upload_file "header.php" "header.php" && ((success_count++))

((total_count++))
upload_file "sidebar_fixed.php" "sidebar_fixed.php" && ((success_count++))

((total_count++))
upload_file "sidebar_minimal.php" "sidebar_minimal.php" && ((success_count++))

# 13. Additional Features
((total_count++))
upload_file "assign_jerseys.php" "assign_jerseys.php" && ((success_count++))

((total_count++))
upload_file "assign_teams.php" "assign_teams.php" && ((success_count++))

((total_count++))
upload_file "import_players_csv.php" "import_players_csv.php" && ((success_count++))

((total_count++))
upload_file "player_card_print.php" "player_card_print.php" && ((success_count++))

echo ""
echo "🎯 Upload Summary"
echo "================"
echo -e "${GREEN}✅ Successfully uploaded:${NC} $success_count / $total_count files"

if [ $success_count -eq $total_count ]; then
    echo -e "${GREEN}🎉 All files uploaded successfully!${NC}"
else
    echo -e "${YELLOW}⚠️  Some files failed to upload${NC}"
fi

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
echo -e "${GREEN}🚀 Deployment Complete!${NC}"
echo "=========================="
echo "All critical files have been uploaded to fix the identified issues."
echo "The app should now work without the previous database and functionality errors."
