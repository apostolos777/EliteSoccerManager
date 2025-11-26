# VIVO United - Live Site Deployment Guide

## 🚀 Deploy New Version to vivounited.org/vivoapp/

### Step 1: Backup Current Live Site
Before deploying, create a backup of the current live site:
1. Login to your web hosting control panel (cPanel/FTP)
2. Navigate to the `/vivoapp/` directory
3. Create a backup zip of the current files
4. Download the backup to your local machine

### Step 2: Upload New Production Build
1. **File to Upload:** `vivo-football-manager-production-20250803_104958.zip`
2. **Location:** Upload to your web server's `/vivoapp/` directory
3. **Extract:** Unzip the file directly on the server or extract locally and upload files

### Step 3: File Upload Methods

#### Option A: cPanel File Manager
1. Login to your hosting cPanel
2. Go to File Manager
3. Navigate to `/public_html/vivoapp/` (or wherever vivoapp is located)
4. Upload the zip file
5. Extract it using cPanel's extract feature
6. Replace all existing files

#### Option B: FTP Client (FileZilla, etc.)
1. Connect to your server via FTP
2. Navigate to the vivoapp directory
3. Upload all files from the extracted zip
4. Overwrite existing files when prompted

#### Option C: SSH/Terminal (if available)
```bash
# Upload the zip file first, then:
cd /path/to/vivoapp/
unzip -o vivo-football-manager-production-20250803_104958.zip
```

### Step 4: Database Configuration
After uploading, you may need to update database settings:

1. **Check:** `database_config_production.php`
2. **Verify:** Database connection details match your live server
3. **Test:** Visit `https://www.vivounited.org/vivoapp/` to confirm it loads

### Step 5: File Permissions
Ensure proper file permissions on your server:
```bash
# Set directory permissions
find /path/to/vivoapp/ -type d -exec chmod 755 {} \;

# Set file permissions  
find /path/to/vivoapp/ -type f -exec chmod 644 {} \;

# Set uploads directory writable
chmod 777 /path/to/vivoapp/uploads/
```

### Step 6: Test Deployment
1. Visit: `https://www.vivounited.org/vivoapp/`
2. Test login functionality
3. Test team/player/event management
4. Verify all pages load correctly
5. Check responsive design on mobile

### 🆘 Troubleshooting

**If the site shows errors:**
1. Check file permissions
2. Verify database configuration
3. Check server error logs
4. Ensure all files uploaded correctly

**If database errors occur:**
1. Check `database_config_production.php` settings
2. Verify database credentials
3. Ensure database tables exist

**Cache Issues:**
- Clear browser cache
- Clear any server-side caching
- Force refresh with Ctrl+F5

### 📞 Need Help?
If you encounter issues during deployment, the most common problems are:
1. **File permissions** - Make sure uploads/ directory is writable
2. **Database settings** - Verify production database credentials
3. **File paths** - Ensure all files are in the correct directory structure

---

**Current Build:** `vivo-football-manager-production-20250803_104958.zip`
**Build Size:** 120KB
**Ready for:** Production deployment
