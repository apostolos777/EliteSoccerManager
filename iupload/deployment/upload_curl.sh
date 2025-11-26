#!/bin/bash

# VIVO United - Live Server Update Script (using curl)
# Uploads only the modified files to the live server

FTP_HOST="ftp.bizdynamix.co.za"
FTP_USER="ftpadmin@vivounited.org"
FTP_PASS="HR6DsVS#eaPp"
FTP_REMOTE_DIR="/vivoapp"

echo "🚀 VIVO United Live Server Update (CURL)"
echo "========================================"
echo "Host: $FTP_HOST"
echo "Remote directory: $FTP_REMOTE_DIR"
echo ""

# Files to upload (the ones we've modified)
FILES_TO_UPLOAD=(
    "player_profile.php"
    "player_card_print.php" 
    "event_details.php"
    "team_details.php"
    "attendance.php"
)

echo "📁 Files to upload:"
for file in "${FILES_TO_UPLOAD[@]}"; do
    if [ -f "$file" ]; then
        echo "  ✅ $file ($(ls -lh "$file" | awk '{print $5}'))"
    else
        echo "  ❌ $file (not found)"
    fi
done
echo ""

read -p "🔄 Proceed with upload? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Upload cancelled."
    exit 1
fi

echo "🌐 Starting FTP upload with curl..."

UPLOAD_SUCCESS=0
UPLOAD_COUNT=0

# Upload each file using curl
for file in "${FILES_TO_UPLOAD[@]}"; do
    if [ -f "$file" ]; then
        echo "🔄 Uploading $file..."
        curl -T "$file" "ftp://$FTP_HOST$FTP_REMOTE_DIR/" --user "$FTP_USER:$FTP_PASS" --ftp-create-dirs
        
        if [ $? -eq 0 ]; then
            echo "  ✅ $file uploaded successfully"
            ((UPLOAD_COUNT++))
        else
            echo "  ❌ Failed to upload $file"
            UPLOAD_SUCCESS=1
        fi
    fi
done

echo ""
if [ $UPLOAD_SUCCESS -eq 0 ]; then
    echo "✅ All files uploaded successfully! ($UPLOAD_COUNT files)"
    echo ""
    echo "🔗 Your application should now be updated at:"
    echo "   https://vivounited.org/"
    echo ""
    echo "📋 Updated features:"
    echo "   • Enhanced player profiles with career statistics"
    echo "   • Fixed player card printing with proper DOCTYPE"
    echo "   • Resolved database column errors"
    echo "   • Fixed team details and attendance pages"
    echo ""
    echo "🔍 Test the following URLs:"
    echo "   • Player Profile: https://vivounited.org/player_profile.php?id=13"
    echo "   • Player Card Print: https://vivounited.org/player_card_print.php?id=13"
    echo "   • Team Details: https://vivounited.org/team_details.php?id=5"
    echo "   • Event Details: https://vivounited.org/event_details.php?id=6"
else
    echo "❌ Some uploads failed!"
    exit 1
fi
