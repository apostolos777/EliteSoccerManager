#!/bin/bash

# VIVO United - Upload Player Creation Fixes
# Uploads the files we modified to fix the player creation issues

FTP_HOST="ftp.bizdynamix.co.za"
FTP_USER="ftpadmin@vivounited.org"
FTP_PASS="HR6DsVS#eaPp"
FTP_REMOTE_DIR="/vivoapp"

echo "🚀 VIVO United - Player Creation Fixes Upload"
echo "=============================================="
echo "Host: $FTP_HOST"
echo "Remote directory: $FTP_REMOTE_DIR"
echo ""

# Files we modified to fix player creation issues
FILES_TO_UPLOAD=(
    "add_player.php"
    "includes/auth.php"
    "includes/sidebar.php"
    "css/vivo-sidebar-override.css"
    "import_players_from_spreadsheet.php"
    "reset_players_demo.php"
    "setup_xampp_simple.php"
    "restore_sample_data.php"
    "login.php"
    "deployment/add_player.php"
    "deployment/import_players_from_spreadsheet.php"
)

echo "📁 Files to upload:"
TOTAL_SIZE=0
for file in "${FILES_TO_UPLOAD[@]}"; do
    if [ -f "$file" ]; then
        SIZE=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null || echo "0")
        TOTAL_SIZE=$((TOTAL_SIZE + SIZE))
        echo "  ✅ $file ($(ls -lh "$file" | awk '{print $5}'))"
    else
        echo "  ❌ $file (not found)"
    fi
done

# Convert total size to human readable
if [ $TOTAL_SIZE -gt 1048576 ]; then
    READABLE_SIZE="$(echo "scale=1; $TOTAL_SIZE/1048576" | bc)MB"
elif [ $TOTAL_SIZE -gt 1024 ]; then
    READABLE_SIZE="$(echo "scale=1; $TOTAL_SIZE/1024" | bc)KB"
else
    READABLE_SIZE="${TOTAL_SIZE}B"
fi

echo ""
echo "📊 Total upload size: $READABLE_SIZE"
echo ""

read -p "🔄 Proceed with upload? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Upload cancelled."
    exit 1
fi

echo "🌐 Starting SFTP upload with curl..."

UPLOAD_SUCCESS=0
UPLOAD_COUNT=0

# Upload each file using curl with SFTP
for file in "${FILES_TO_UPLOAD[@]}"; do
    if [ -f "$file" ]; then
        echo "📤 Uploading $file..."
        
        # Use curl with FTP to upload the file (password with special chars needs to be handled carefully)
        if curl -T "$file" --user "$FTP_USER:$FTP_PASS" "ftp://$FTP_HOST$FTP_REMOTE_DIR/$file" 2>/dev/null; then
            echo "  ✅ Successfully uploaded $file"
            UPLOAD_SUCCESS=$((UPLOAD_SUCCESS + 1))
        else
            echo "  ❌ Failed to upload $file"
        fi
        
        UPLOAD_COUNT=$((UPLOAD_COUNT + 1))
    fi
done

echo ""
echo "📊 Upload Summary:"
echo "  Total files: $UPLOAD_COUNT"
echo "  Successful: $UPLOAD_SUCCESS"
echo "  Failed: $((UPLOAD_COUNT - UPLOAD_SUCCESS))"

if [ $UPLOAD_SUCCESS -eq $UPLOAD_COUNT ]; then
    echo "🎉 All files uploaded successfully!"
    echo ""
    echo "Next steps:"
    echo "1. Test player creation on your live site"
    echo "2. Test the login/logout functionality"
    echo "3. Verify the sidebar shows the login/logout button correctly"
else
    echo "⚠️  Some files failed to upload. Please check the errors above."
fi

echo ""
echo "Live site URL: https://www.vivounited.org/vivoapp/"
