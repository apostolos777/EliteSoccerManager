<?php
/**
 * VIVO App - Automatic Deployment Script
 * This script extracts the uploaded deployment archive and replaces all files
 * Place this in /vivoapp/ directory and access via browser to execute
 * Then delete this file when done
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$archive_name = 'vivo-deploy-20251117_165356.tar.gz';
$extract_dir = './';
$deploy_dir = 'vivo-deploy-20251117';

echo "<h2>VIVO App - Auto Deployment</h2>";
echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";

// Check if archive exists
if (!file_exists($archive_name)) {
    echo "❌ ERROR: Archive not found: $archive_name\n";
    echo "Upload the archive first via FTP.\n";
    exit;
}

echo "✅ Archive found: $archive_name\n";
echo "📊 Size: " . filesize($archive_name) . " bytes\n\n";

// Extract archive
echo "📦 Extracting archive...\n";
$cmd = "tar -xzf $archive_name";
$result = shell_exec($cmd . " 2>&1");
if ($result) {
    echo $result;
}

if (!is_dir($deploy_dir)) {
    echo "❌ ERROR: Failed to extract. Directory not found: $deploy_dir\n";
    exit;
}

echo "✅ Extracted successfully\n\n";

// Get list of files to replace
echo "📋 Files in deployment package:\n";
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($deploy_dir)
);
$file_count = 0;
foreach ($files as $file) {
    if ($file->isFile()) {
        $file_count++;
    }
}
echo "   Total files: $file_count\n\n";

// Backup current database
echo "💾 Backing up current database...\n";
if (file_exists('database.db')) {
    $backup_name = 'database.db.backup.' . date('YmdHis');
    if (copy('database.db', $backup_name)) {
        echo "✅ Backup created: $backup_name\n";
    } else {
        echo "⚠️  Warning: Could not backup database\n";
    }
} else {
    echo "ℹ️  No existing database found\n";
}
echo "\n";

// Replace files
echo "🔄 Replacing files...\n";
$skip_dirs = ['.', '..', '.git', 'uploads', 'vivo-deploy-20251117'];

function copy_recursive($src, $dst) {
    global $skip_dirs;
    
    $dir = opendir($src);
    @mkdir($dst);
    
    while (false !== ($file = readdir($dir))) {
        if (in_array($file, $skip_dirs)) {
            continue;
        }
        
        if (is_dir($src . '/' . $file)) {
            copy_recursive($src . '/' . $file, $dst . '/' . $file);
        } else {
            copy($src . '/' . $file, $dst . '/' . $file);
            echo "  ✓ " . str_replace($src . '/', '', $src . '/' . $file) . "\n";
        }
    }
    
    closedir($dir);
}

copy_recursive($deploy_dir, '.');

echo "\n✅ Files replaced successfully\n\n";

// Verify database
echo "🔍 Verifying deployment...\n";
if (file_exists('database.db')) {
    try {
        $db = new PDO('sqlite:database.db');
        $count = $db->query('SELECT COUNT(*) FROM players')->fetchColumn();
        echo "✅ Database verified\n";
        echo "   Players in database: " . $count . "\n";
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Database not found!\n";
}

echo "\n";

// Cleanup
echo "🧹 Cleaning up...\n";
$cmd = "rm -rf $deploy_dir";
shell_exec($cmd);
echo "✅ Extracted folder removed\n\n";

// Remove archive
echo "🗑️  Removing archive...\n";
unlink($archive_name);
echo "✅ Archive deleted\n\n";

echo "========================================\n";
echo "✅ DEPLOYMENT COMPLETE!\n";
echo "========================================\n\n";

echo "📊 Next Steps:\n";
echo "1. Delete this script (deploy.php)\n";
echo "2. Visit https://www.vivounited.org/vivoapp/\n";
echo "3. Dashboard should now show 105 players\n";
echo "4. Test all functionality\n";
echo "\n";

echo "📝 Instructions to delete this file:\n";
echo "Via FTP: Delete deploy.php from /vivoapp/\n";
echo "</pre>";

?>
