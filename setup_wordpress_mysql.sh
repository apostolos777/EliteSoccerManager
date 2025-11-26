#!/bin/bash

# VIVO United Football Manager - WordPress-style MySQL Setup Script
# This script sets up and tests the MySQL database connection

echo "🚀 VIVO United Football Manager - WordPress MySQL Setup"
echo "======================================================"
echo ""

# Check if wp-config.php exists
if [ ! -f "wp-config.php" ]; then
    echo "❌ wp-config.php not found!"
    echo "Please make sure wp-config.php exists with proper MySQL credentials."
    echo ""
    echo "Required constants in wp-config.php:"
    echo "- DB_HOST (usually 'localhost')"
    echo "- DB_NAME (database name, e.g., 'vivo_football')"
    echo "- DB_USER (MySQL username, e.g., 'root')"
    echo "- DB_PASSWORD (MySQL password)"
    echo ""
    exit 1
fi

echo "✅ wp-config.php found"
echo ""

# Run the MySQL setup
echo "🔄 Running MySQL database setup..."
php setup_mysql_wordpress.php

echo ""
echo "🔄 Testing database connection..."
php test_mysql_connection.php

echo ""
echo "🔧 If there were any errors above, please:"
echo "1. Make sure MySQL server is running"
echo "2. Check credentials in wp-config.php"
echo "3. Ensure MySQL user has proper permissions"
echo "4. Try accessing http://localhost/phpmyadmin if using XAMPP"
echo ""
echo "📱 After successful setup, you can access:"
echo "- Dashboard: index.php"
echo "- Players: players.php"
echo "- Teams: teams.php"
echo "- Events: events.php"
echo ""
echo "Setup script completed at $(date)"
