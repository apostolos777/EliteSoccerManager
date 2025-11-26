#!/bin/bash

# Upload edit_player.php to live server

FTP_HOST="ftp.bizdynamix.co.za"
FTP_USER="ftpadmin@vivounited.org"
FTP_PASS="HR6DsVS#eaPp"
FTP_REMOTE_DIR="/vivoapp"

echo "🚀 Uploading edit_player.php to live server"
echo "==========================================="

if [ -f "edit_player.php" ]; then
    echo "📤 Uploading edit_player.php..."
    
    # Upload using curl
    curl -T "edit_player.php" "ftp://$FTP_HOST$FTP_REMOTE_DIR/" --user "$FTP_USER:$FTP_PASS" --ftp-create-dirs
    
    if [ $? -eq 0 ]; then
        echo "✅ Successfully uploaded edit_player.php"
    else
        echo "❌ Failed to upload edit_player.php"
        exit 1
    fi
else
    echo "❌ edit_player.php not found!"
    exit 1
fi

echo "🎉 Upload complete!"
