# VIVO United Football Manager - WordPress-Style MySQL Setup

This version of VIVO United Football Manager is configured to work with MySQL using WordPress-style configuration, making it compatible with WordPress hosting environments and standard MySQL setups.

## 🚀 Quick Setup

### 1. Configure Database Connection

Edit `wp-config.php` with your MySQL credentials:

```php
// MySQL settings
define( 'DB_NAME', 'vivo_football' );      // Your database name
define( 'DB_USER', 'root' );               // MySQL username  
define( 'DB_PASSWORD', '' );               // MySQL password
define( 'DB_HOST', 'localhost' );          // MySQL host
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', 'utf8mb4_unicode_ci' );
```

### 2. Run Setup Script

```bash
./setup_wordpress_mysql.sh
```

Or manually run:

```bash
php setup_mysql_wordpress.php
php test_mysql_connection.php
```

### 3. Access Your Application

- **Dashboard**: `index.php` or `dashboard.php`
- **Players**: `players.php`
- **Teams**: `teams.php`
- **Events**: `events.php`

## 📊 Database Configuration Priority

The system uses the following priority order for database configuration:

1. **WordPress Constants** (highest priority)
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`
   - Defined in `wp-config.php`

2. **Environment Variables**
   - `DB_HOST`, `DB_NAME`, `DB_USERNAME`, `DB_PASSWORD`

3. **Config Files**
   - `config/database.php`
   - `.env` file

4. **Defaults**
   - XAMPP/local development defaults

## 🔧 WordPress Integration

### In WordPress Environment

If you're running this within a WordPress installation:

1. The system will automatically use your existing `wp-config.php`
2. It will inherit your WordPress database connection
3. Tables will be created in your WordPress database with `vivo_` prefix

### Standalone Installation

For standalone installations:

1. Create your own `wp-config.php` (already provided)
2. Set your MySQL credentials
3. Run the setup script
4. The system works independently of WordPress

## 🏗️ Database Schema

The system creates these tables:

- `teams` - Football teams information
- `players` - Player profiles and details
- `events` - Matches, training sessions, meetings
- `attendance` - Player attendance tracking
- `schema_version` - Database versioning for migrations

## 🛠️ Troubleshooting

### Common Issues

**Database Connection Failed**
```bash
# Check MySQL is running
sudo systemctl status mysql  # Linux
brew services list | grep mysql  # macOS

# Test connection manually
mysql -u root -p -h localhost
```

**Permission Denied**
```sql
-- Grant necessary permissions
GRANT ALL PRIVILEGES ON vivo_football.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

**Tables Don't Exist**
```bash
# Re-run setup
php setup_mysql_wordpress.php
```

### XAMPP Users

For XAMPP users, the default configuration should work:
- Host: `localhost`
- Username: `root`
- Password: `` (empty)
- Database: `vivo_football` (will be created automatically)

### Production Deployment

For production servers:

1. Update `wp-config.php` with production credentials
2. Ensure database user has proper permissions
3. Run setup script on production server
4. Set proper file permissions

## 🔐 Security

### Production Recommendations

1. **Change default credentials**
2. **Use strong passwords**
3. **Limit database user permissions**
4. **Enable SSL for database connections**
5. **Regular backups**

### Authentication

The system includes a simple authentication system:
- Development mode: Auto-login enabled
- Production: Can be integrated with WordPress authentication

## 📁 File Structure

```
vivo-app/
├── wp-config.php                 # WordPress-style configuration
├── database_factory.php          # Database connection factory
├── setup_mysql_wordpress.php     # Database setup script
├── test_mysql_connection.php     # Connection test script
├── setup_wordpress_mysql.sh      # Automated setup script
├── players.php                   # Player management
├── teams.php                     # Team management
├── events.php                    # Event management
├── dashboard.php                 # Main dashboard
└── includes/
    ├── auth.php                  # Authentication system
    └── sidebar.php               # Navigation sidebar
```

## 🚀 Development

### Local Development Setup

1. Install XAMPP or similar MySQL stack
2. Clone/download the application
3. Configure `wp-config.php`
4. Run setup script
5. Access via `http://localhost/vivo-app/`

### Adding New Features

The system uses:
- **PDO** for database operations
- **WordPress-style** configuration
- **Responsive design** with modern CSS
- **Modular PHP** architecture

## 📞 Support

If you encounter issues:

1. Check `wp-config.php` configuration
2. Verify MySQL server is running
3. Run the test script: `php test_mysql_connection.php`
4. Check file permissions
5. Review error logs

---

**VIVO United Football Manager** - WordPress-Compatible MySQL Version
*Built for reliability, scalability, and ease of deployment*
