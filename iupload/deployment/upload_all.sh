#!/bin/bash

# VIVO United - Complete Site Upload to Live Server
# Uploads all PHP files and essential assets to ensure live server matches local

FTP_HOST="ftp.bizdynamix.co.za"
FTP_USER="ftpadmin@vivounited.org"
FTP_PASS="HR6DsVS#eaPp"
FTP_REMOTE_DIR="/vivoapp"

echo "🚀 VIVO United Complete Site Upload"
echo "==================================="
echo "Host: $FTP_HOST"
echo "Remote directory: $FTP_REMOTE_DIR"
echo ""

# Get all PHP files and important assets
PHP_FILES=($(find . -maxdepth 1 -name "*.php" -type f | sort))
CSS_FILES=($(find . -path "./css/*" -name "*.css" -type f 2>/dev/null | sort))
JS_FILES=($(find . -path "./js/*" -name "*.js" -type f 2>/dev/null | sort))
INCLUDE_FILES=($(find . -path "./includes/*" -name "*.php" -type f 2>/dev/null | sort))

ALL_FILES=("${PHP_FILES[@]}" "${CSS_FILES[@]}" "${JS_FILES[@]}" "${INCLUDE_FILES[@]}")

# Filter out backup and temporary files
FILTERED_FILES=()
for file in "${ALL_FILES[@]}"; do
    if [[ ! "$file" =~ (backup|temp|tmp|test|debug|_old|_bak|\~) ]]; then
        FILTERED_FILES+=("$file")
    fi
done

echo "📁 Files to upload (${#FILTERED_FILES[@]} total):"
TOTAL_SIZE=0
for file in "${FILTERED_FILES[@]}"; do
    if [ -f "$file" ]; then
        SIZE=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null || echo "0")
        TOTAL_SIZE=$((TOTAL_SIZE + SIZE))
        echo "  ✅ $file"
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

read -p "🔄 Proceed with complete upload? This will sync all files. (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Upload cancelled."
    exit 1
fi

echo "🌐 Starting complete site upload..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

UPLOAD_SUCCESS=0
UPLOAD_COUNT=0
FAILED_FILES=()

# Upload each file
for file in "${FILTERED_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "🔄 Uploading $file..."
        
        # Create directory structure if needed
        REMOTE_DIR="$FTP_REMOTE_DIR"
        if [[ "$file" == ./css/* ]]; then
            REMOTE_DIR="$FTP_REMOTE_DIR/css"
        elif [[ "$file" == ./js/* ]]; then
            REMOTE_DIR="$FTP_REMOTE_DIR/js"
        elif [[ "$file" == ./includes/* ]]; then
            REMOTE_DIR="$FTP_REMOTE_DIR/includes"
        fi
        
        # Upload file
        curl -T "$file" "ftp://$FTP_HOST$REMOTE_DIR/" --user "$FTP_USER:$FTP_PASS" --ftp-create-dirs -s
        
        if [ $? -eq 0 ]; then
            echo "  ✅ $file uploaded successfully"
            ((UPLOAD_COUNT++))
        else
            echo "  ❌ Failed to upload $file"
            FAILED_FILES+=("$file")
            UPLOAD_SUCCESS=1
        fi
    fi
done

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if [ $UPLOAD_SUCCESS -eq 0 ]; then
    echo "✅ Complete upload successful! ($UPLOAD_COUNT files)"
    echo ""
    echo "🔗 Your application is now fully synced at:"
    echo "   https://vivounited.org/"
    echo ""
    echo "📋 All features updated:"
    echo "   • Enhanced player profiles with career statistics"
    echo "   • Fixed player card printing with proper DOCTYPE"
    echo "   • Resolved all database column errors"
    echo "   • Updated dashboard and all core functionality"
    echo "   • Synced CSS, JS, and include files"
    echo ""
    echo "🔍 Main URLs to test:"
    echo "   • Dashboard: https://vivounited.org/dashboard.php"
    echo "   • Players: https://vivounited.org/players.php"
    echo "   • Teams: https://vivounited.org/teams.php"
    echo "   • Events: https://vivounited.org/events.php"
    echo "   • Player Profile: https://vivounited.org/player_profile.php?id=13"
    echo "   • Player Card: https://vivounited.org/player_card_print.php?id=13"
else
    echo "❌ Some uploads failed!"
    echo "Failed files:"
    for file in "${FAILED_FILES[@]}"; do
        echo "  ❌ $file"
    done
    echo ""
    echo "Successfully uploaded: $UPLOAD_COUNT files"
    exit 1
fi
