# VIVO United - FTP Deployment Guide

## Quick Setup with VS Code SFTP Extension

### 1. Install SFTP Extension
```
1. Open VS Code Extensions (Cmd+Shift+X)
2. Search for "SFTP" by Natizyskunk
3. Install the extension
```

### 2. Configure Your Server Details
Edit `.vscode/sftp.json` with your server information:

```json
{
    "name": "VIVO United Server",
    "host": "your-server.com",           // Replace with your server
    "protocol": "ftp",                   // or "sftp" if using SFTP
    "port": 21,                          // 21 for FTP, 22 for SFTP
    "username": "your-username",         // Your FTP username
    "password": "your-password",         // Your FTP password
    "remotePath": "/public_html/",       // Your web directory
    "uploadOnSave": false
}
```

### 3. Deploy Commands (Right-click in VS Code)

**Initial Full Upload:**
- Right-click on project folder → "SFTP: Upload Folder"

**Individual File Updates:**
- Right-click any file → "SFTP: Upload File"

**Download from Server:**
- Right-click → "SFTP: Download File/Folder"

**Sync Both Ways:**
- Command Palette (Cmd+Shift+P) → "SFTP: Sync Local -> Remote"

### 4. Alternative: Command Line FTP

If you prefer terminal FTP:

```bash
# Create deployment package
cd /Users/edwinbrooks/Desktop/vivo-app
tar -czf vivo-app-deploy.tar.gz --exclude='.git' --exclude='*.sql' --exclude='*.zip' --exclude='VIVONEW' --exclude='archive-workspace' .

# Upload via FTP
ftp your-server.com
# Login with credentials, then:
cd public_html
put vivo-app-deploy.tar.gz
quit

# SSH to server and extract:
ssh your-username@your-server.com
cd public_html
tar -xzf vivo-app-deploy.tar.gz
rm vivo-app-deploy.tar.gz
```

### 5. Server Requirements Check

Ensure your web server has:
- **PHP 7.4+** (for PHP features used)
- **SQLite3 extension** enabled
- **File write permissions** for uploads/ directory
- **mod_rewrite** enabled (if using clean URLs)

### 6. Post-Upload Steps

1. **Set permissions:**
   ```bash
   chmod 755 uploads/
   chmod 644 *.php
   ```

2. **Test database connection:**
   - Visit: `yourdomain.com/test_database_connection.php`

3. **Upload logo again:**
   - Visit: `yourdomain.com/club_settings.php`
   - Re-upload your club logo

### 7. Security Notes

- **Never commit passwords** to git
- Consider using SFTP instead of FTP for encryption
- Use environment variables for sensitive config

## Files Excluded from Upload

The SFTP config automatically excludes:
- Development files (.git, .vscode)
- Backup files (*.sql, *.zip)
- Archive folders (VIVONEW, archive-workspace)
- System files (.DS_Store, Thumbs.db)
