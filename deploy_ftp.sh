#!/bin/bash

# VIVO United - FTP Deployment Script
# This script uploads files directly via FTP

FTP_HOST="ftp.bizdynamix.co.za"
FTP_USER="ftpadmin@vivounited.org"
FTP_PASS="HR6DsVS#eaPp"
FTP_REMOTE_DIR="/vivoapp"
DEPLOY_DIR="./deployment"

# If UPLOAD_CHANGED is set, use deploy_changed.sh to upload only changed files
if [ "${UPLOAD_CHANGED:-0}" = "1" ]; then
    echo "⚡ Uploading only changed files via deploy_changed.sh"
    export FTP_HOST FTP_USER FTP_PASS FTP_PATH="$FTP_REMOTE_DIR"
    ./deploy_changed.sh --dry-run || true
    ./deploy_changed.sh || true
    exit 0
fi

echo "🚀 VIVO United Deployment Script"
echo "================================"

# Check if deployment folder exists
if [ ! -d "$DEPLOY_DIR" ]; then
    echo "❌ Deployment folder $DEPLOY_DIR not found!"
    echo "Run the rsync command first to create the deployment folder."
    exit 1
fi

echo "📁 Using deployment folder: $DEPLOY_DIR"
echo "📊 Package size: $(du -sh $DEPLOY_DIR | cut -f1)"

# Create compressed archive
echo "📦 Creating deployment archive..."
cd "$DEPLOY_DIR"
tar -czf "../vivo-deploy-$(date +%Y%m%d_%H%M%S).tar.gz" .
cd ..

ARCHIVE_NAME="vivo-deploy-$(date +%Y%m%d_%H%M%S).tar.gz"
echo "✅ Created: $ARCHIVE_NAME ($(du -sh $ARCHIVE_NAME | cut -f1))"

# FTP Upload
echo "🌐 Starting FTP upload..."
echo "Host: $FTP_HOST"
echo "Remote directory: $FTP_REMOTE_DIR"

ftp -n $FTP_HOST << EOF
user $FTP_USER $FTP_PASS
binary
cd $FTP_REMOTE_DIR
put $ARCHIVE_NAME
quit
EOF

if [ $? -eq 0 ]; then
    echo "✅ FTP upload completed successfully!"
    echo ""
    echo "Next steps on your server:"
    echo "1. SSH to your server"
    echo "2. cd $FTP_REMOTE_DIR"
    echo "3. tar -xzf $ARCHIVE_NAME"
    echo "4. rm $ARCHIVE_NAME"
    echo "5. chmod 755 uploads/"
    echo "6. Visit your domain to test"
else
    echo "❌ FTP upload failed!"
    exit 1
fi

echo ""
echo "🎉 Deployment package ready!"
echo "Local archive: $ARCHIVE_NAME"
echo "Deployment folder: $DEPLOY_DIR"
