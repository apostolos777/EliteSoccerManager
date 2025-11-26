#!/bin/bash

# VIVO United - Live Server Update Script
# Uploads only the modified files to the live server

FTP_HOST="ftp.bizdynamix.co.za"
FTP_USER="ftpadmin@vivounited.org"
FTP_PASS="HR6DsVS#eaPp"
FTP_REMOTE_DIR="/vivoapp"

echo "🚀 VIVO United Live Server Update"
echo "=================================="
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

echo "🌐 Starting FTP upload..."

# Create FTP script
cat > ftp_upload.tmp << EOF
open $FTP_HOST
user $FTP_USER $FTP_PASS
binary
cd $FTP_REMOTE_DIR
EOF

# Add put commands for each file
for file in "${FILES_TO_UPLOAD[@]}"; do
    if [ -f "$file" ]; then
        echo "put $file" >> ftp_upload.tmp
        echo "🔄 Uploading $file..."
    fi
done

echo "quit" >> ftp_upload.tmp

# Execute FTP commands
ftp -n < ftp_upload.tmp

# Clean up
rm ftp_upload.tmp

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Upload completed successfully!"
    echo ""
    echo "🔗 Your application should now be updated at:"
    echo "   https://vivounited.org/"
    echo ""
    echo "📋 Updated features:"
    echo "   • Enhanced player profiles with career statistics"
    echo "   • Fixed player card printing with proper DOCTYPE"
    echo "   • Resolved database column errors"
    echo "   • Fixed team details and attendance pages"
else
    echo "❌ Upload failed!"
    exit 1
fi
