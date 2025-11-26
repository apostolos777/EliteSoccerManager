#!/bin/bash

# VIVO United - Google Calendar Integration Upload
# Uploads the Google Calendar integration files to live server

FTP_HOST="ftp.bizdynamix.co.za"
FTP_USER="ftpadmin@vivounited.org"
FTP_PASS="HR6DsVS#eaPp"
FTP_REMOTE_DIR="/vivoapp"

echo "🚀 VIVO United Google Calendar Integration Upload"
echo "================================================"
echo "Host: $FTP_HOST"
echo "Remote directory: $FTP_REMOTE_DIR"
echo ""

# Files to upload for Google Calendar integration
FILES_TO_UPLOAD=(
    "add_event.php"
    "google_calendar_helper.php"
    "test_google_calendar.php"
    "GOOGLE_CALENDAR_INTEGRATION_COMPLETE.md"
)

echo "Files to upload:"
for file in "${FILES_TO_UPLOAD[@]}"; do
    if [ -f "$file" ]; then
        echo "  ✅ $file"
    else
        echo "  ❌ $file (not found)"
    fi
done
echo ""

# Upload each file
for file in "${FILES_TO_UPLOAD[@]}"; do
    if [ -f "$file" ]; then
        echo "📤 Uploading $file..."
        
        # Use curl for FTP upload
        curl -T "$file" \
             --ftp-create-dirs \
             --user "$FTP_USER:$FTP_PASS" \
             "ftp://$FTP_HOST$FTP_REMOTE_DIR/$file"
        
        if [ $? -eq 0 ]; then
            echo "  ✅ Successfully uploaded $file"
        else
            echo "  ❌ Failed to upload $file"
        fi
        echo ""
    else
        echo "⚠️  Skipping $file (not found)"
        echo ""
    fi
done

echo "🎉 Google Calendar integration upload complete!"
echo ""
echo "Next steps:"
echo "1. Test the integration at https://www.vivounited.org/vivoapp/test_google_calendar.php"
echo "2. Try creating an event at https://www.vivounited.org/vivoapp/add_event.php"
echo "3. Make sure Google API Console has the production redirect URI configured:"
echo "   https://www.vivounited.org/vivoapp/add_event.php?gcal_auth=1"
