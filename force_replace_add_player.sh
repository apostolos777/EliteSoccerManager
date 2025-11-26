#!/bin/bash

# Force replace add_player.php on live server
FTP_HOST="ftp.bizdynamix.co.za"
FTP_USER="ftpadmin@vivounited.org"
FTP_PASS="HR6DsVS#eaPp"
FTP_REMOTE_DIR="/vivoapp"

echo "🔄 Force replacing add_player.php on live server..."

# Create FTP script to delete and upload
cat > ftp_commands.txt << COMMANDS
open $FTP_HOST
user $FTP_USER $FTP_PASS
binary
cd $FTP_REMOTE_DIR
delete add_player.php
put add_player.php
quit
COMMANDS

# Execute FTP commands
ftp -n < ftp_commands.txt

# Clean up
rm ftp_commands.txt

echo "✅ Force replace completed"
echo "🌐 Test: https://www.vivounited.org/vivoapp/add_player.php"
