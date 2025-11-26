#!/bin/bash

# VIVO United - Upload Dashboard to Live Server

FTP_HOST="ftp.bizdynamix.co.za"
FTP_USER="ftpadmin@vivounited.org"
FTP_PASS="HR6DsVS#eaPp"
FTP_REMOTE_DIR="/vivoapp"

echo "🚀 VIVO United Dashboard Upload"
echo "==============================="
echo "Host: $FTP_HOST"
echo "Remote directory: $FTP_REMOTE_DIR"
echo ""

FILE_TO_UPLOAD="dashboard.php"

if [ -f "$FILE_TO_UPLOAD" ]; then
    echo "📁 File to upload:"
    echo "  ✅ $FILE_TO_UPLOAD ($(ls -lh "$FILE_TO_UPLOAD" | awk '{print $5}'))"
    echo ""
    
    read -p "🔄 Proceed with dashboard upload? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "❌ Upload cancelled."
        exit 1
    fi
    
    echo "🌐 Uploading dashboard.php..."
    curl -T "$FILE_TO_UPLOAD" "ftp://$FTP_HOST$FTP_REMOTE_DIR/" --user "$FTP_USER:$FTP_PASS" --ftp-create-dirs
    
    if [ $? -eq 0 ]; then
        echo ""
        echo "✅ Dashboard uploaded successfully!"
        echo ""
        echo "🔗 Your dashboard is now updated at:"
        echo "   https://vivounited.org/dashboard.php"
        echo ""
        echo "📋 Dashboard should now match your local version with all latest updates."
    else
        echo "❌ Dashboard upload failed!"
        exit 1
    fi
else
    echo "❌ dashboard.php not found!"
    exit 1
fi
