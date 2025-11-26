#!/bin/bash

# VIVO United - Upload Database to Live Server
# Uploads the updated SQLite database with sample data

FTP_HOST="ftp.bizdynamix.co.za"
FTP_USER="ftpadmin@vivounited.org"
FTP_PASS="HR6DsVS#eaPp"
FTP_REMOTE_DIR="/vivoapp"

echo "🚀 VIVO United Database Upload"
echo "=============================="
echo "Host: $FTP_HOST"
echo "Remote directory: $FTP_REMOTE_DIR"
echo ""

DB_FILE="vivo_football.db"

if [ -f "$DB_FILE" ]; then
    echo "📁 Database file to upload:"
    echo "  ✅ $DB_FILE ($(ls -lh "$DB_FILE" | awk '{print $5}'))"
    echo ""
    
    # Show what's in the database
    echo "📊 Database contents:"
    echo "  Players: $(sqlite3 "$DB_FILE" "SELECT COUNT(*) FROM players;")"
    echo "  Teams: $(sqlite3 "$DB_FILE" "SELECT COUNT(*) FROM teams;")"
    echo "  Events: $(sqlite3 "$DB_FILE" "SELECT COUNT(*) FROM events;")"
    echo "  Player Stats: $(sqlite3 "$DB_FILE" "SELECT COUNT(*) FROM player_stats;")"
    echo "  Attendance Records: $(sqlite3 "$DB_FILE" "SELECT COUNT(*) FROM attendance;")"
    echo ""
    
    echo "⚠️  WARNING: This will replace the database on the live server!"
    echo "   Make sure you want to overwrite any existing data."
    echo ""
    
    read -p "🔄 Proceed with database upload? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "❌ Database upload cancelled."
        exit 1
    fi
    
    echo "🌐 Uploading database..."
    curl -T "$DB_FILE" "ftp://$FTP_HOST$FTP_REMOTE_DIR/" --user "$FTP_USER:$FTP_PASS" --ftp-create-dirs
    
    if [ $? -eq 0 ]; then
        echo ""
        echo "✅ Database uploaded successfully!"
        echo ""
        echo "🔗 Your live server now has the updated database with:"
        echo "   • Sample players with enhanced profiles"
        echo "   • Teams with proper structure"
        echo "   • Events with correct date format"
        echo "   • Player statistics for career tracking"
        echo "   • Attendance records"
        echo ""
        echo "📋 The live application should now have all the same data as your local version."
        echo ""
        echo "🔍 Test these URLs to verify data sync:"
        echo "   • Players: https://vivounited.org/players.php"
        echo "   • Player Profile: https://vivounited.org/player_profile.php?id=13"
        echo "   • Teams: https://vivounited.org/teams.php"
        echo "   • Events: https://vivounited.org/events.php"
    else
        echo "❌ Database upload failed!"
        exit 1
    fi
else
    echo "❌ $DB_FILE not found!"
    exit 1
fi
