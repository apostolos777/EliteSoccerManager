#!/bin/bash

# Create fresh deployment package
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DEPLOY_DIR="vivo-deploy-$TIMESTAMP"

echo "Creating deployment package: $DEPLOY_DIR"

# Create deployment directory
mkdir -p "$DEPLOY_DIR"

# Copy core application files
echo "Copying core application files..."
cp wp-config.php "$DEPLOY_DIR/"
cp config.php "$DEPLOY_DIR/"
cp functions.php "$DEPLOY_DIR/"
cp database.php "$DEPLOY_DIR/"
cp .htaccess "$DEPLOY_DIR/"

# Copy main application pages
echo "Copying main pages..."
cp index.php "$DEPLOY_DIR/"
cp login.php "$DEPLOY_DIR/"
cp dashboard.php "$DEPLOY_DIR/"
cp logout.php "$DEPLOY_DIR/"

# Copy player management
echo "Copying player management files..."
cp players.php "$DEPLOY_DIR/"
cp add_player.php "$DEPLOY_DIR/"
cp edit_player.php "$DEPLOY_DIR/"
cp delete_player.php "$DEPLOY_DIR/"
cp player_profile.php "$DEPLOY_DIR/"

# Copy team management
echo "Copying team management files..."
cp teams.php "$DEPLOY_DIR/"
cp add_team.php "$DEPLOY_DIR/"
cp edit_team.php "$DEPLOY_DIR/"
cp delete_team.php "$DEPLOY_DIR/"
cp team_details.php "$DEPLOY_DIR/"

# Copy event management
echo "Copying event management files..."
cp events.php "$DEPLOY_DIR/"
cp add_event.php "$DEPLOY_DIR/"
cp edit_event.php "$DEPLOY_DIR/"
cp delete_event.php "$DEPLOY_DIR/"
cp event_details.php "$DEPLOY_DIR/"
cp event_calendar.php "$DEPLOY_DIR/"

# Copy other features
echo "Copying additional features..."
cp attendance.php "$DEPLOY_DIR/"
cp assign_teams.php "$DEPLOY_DIR/"
cp assign_jerseys.php "$DEPLOY_DIR/"
cp settings.php "$DEPLOY_DIR/"
cp club_settings.php "$DEPLOY_DIR/"

# Copy utility files
echo "Copying utility files..."
cp header.php "$DEPLOY_DIR/"
cp models.php "$DEPLOY_DIR/"

# Copy JavaScript and CSS
echo "Copying frontend assets..."
cp app.js "$DEPLOY_DIR/"
cp mobile-navigation.js "$DEPLOY_DIR/"
cp style.css "$DEPLOY_DIR/"
cp vivo-style.css "$DEPLOY_DIR/"

# Copy includes directory
echo "Copying includes directory..."
cp -r includes "$DEPLOY_DIR/"

# Copy database
echo "Copying database..."
cp database.db "$DEPLOY_DIR/"

# Create archive
echo "Creating archive..."
tar -czf "$DEPLOY_DIR.tar.gz" "$DEPLOY_DIR"

echo ""
echo "✅ Deployment package created successfully!"
echo "📦 Package: $DEPLOY_DIR.tar.gz"
echo "📁 Directory: $DEPLOY_DIR/"
echo ""
echo "Files included:"
echo "- All core PHP files"
echo "- Complete includes directory"
echo "- JavaScript and CSS assets"
echo "- Database file"
echo "- .htaccess configuration"
echo ""
echo "Ready for deployment!"

# List contents
echo ""
echo "Package contents:"
ls -la "$DEPLOY_DIR/"
