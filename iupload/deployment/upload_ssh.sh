#!/bin/bash

# Alternative upload using rsync over SSH
# This is more reliable than FTP

echo "🚀 VIVO United - SSH Upload Script"
echo "=================================="

DEPLOY_DIR="vivo-deploy-$(date +%Y%m%d)"
REMOTE_HOST="ftp.bizdynamix.co.za" 
REMOTE_USER="ftpadmin@vivounited.org"
REMOTE_PATH="/public_html/vivounited/vivoapp/"

if [ ! -d "$DEPLOY_DIR" ]; then
    echo "❌ Deployment folder $DEPLOY_DIR not found!"
    exit 1
fi

echo "📁 Uploading from: $DEPLOY_DIR"
echo "🌐 Target: $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH"
echo ""

# Try rsync over SSH first
echo "Attempting rsync upload..."
rsync -avz --progress "$DEPLOY_DIR/" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH"

if [ $? -eq 0 ]; then
    echo "✅ Upload completed successfully!"
else
    echo "❌ rsync failed. Server may not support SSH."
    echo ""
    echo "Manual upload options:"
    echo "1. Use FileZilla or Cyberduck FTP client"
    echo "2. Use web-based file manager in hosting control panel"
    echo "3. Upload via VS Code with SFTP extension"
    echo ""
    echo "FTP Details:"
    echo "Host: ftp.bizdynamix.co.za"
    echo "Username: ftpadmin@vivounited.org" 
    echo "Path: /public_html/vivounited/vivoapp/"
fi
