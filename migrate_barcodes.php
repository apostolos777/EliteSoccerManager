<?php
/**
 * Add Barcode Support to Players Table
 * Adds barcode column and generates barcodes for existing players
 */

require_once 'database_factory.php';
require_once 'includes/barcode_generator.php';

echo "<h2>🏷️ Adding Barcode Support to VIVO United</h2>\n";

try {
    $pdo = DatabaseFactory::getConnection();
    
    // Add barcode column to players table
    echo "<h3>Adding barcode column to players table...</h3>\n";
    $pdo->exec("ALTER TABLE players ADD COLUMN IF NOT EXISTS barcode VARCHAR(20) UNIQUE");
    echo "✅ Barcode column added successfully<br>\n";
    
    // Generate barcodes for existing players
    echo "<h3>Generating barcodes for existing players...</h3>\n";
    
    $stmt = $pdo->query("SELECT id, name, date_of_birth, team_id FROM players WHERE barcode IS NULL OR barcode = ''");
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $generated_count = 0;
    foreach ($players as $player) {
        $barcode = PlayerBarcodeGenerator::generateBarcode($player);
        
        // Ensure uniqueness
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM players WHERE barcode = ?");
        $check_stmt->execute([$barcode]);
        
        if ($check_stmt->fetchColumn() == 0) {
            $update_stmt = $pdo->prepare("UPDATE players SET barcode = ? WHERE id = ?");
            $update_stmt->execute([$barcode, $player['id']]);
            
            echo "✅ Generated barcode for {$player['name']}: <strong>{$barcode}</strong><br>\n";
            $generated_count++;
        } else {
            echo "⚠️ Barcode collision for {$player['name']}, trying alternative...<br>\n";
            
            // Add random suffix to handle collisions
            $alternative_barcode = $barcode . rand(10, 99);
            $update_stmt = $pdo->prepare("UPDATE players SET barcode = ? WHERE id = ?");
            $update_stmt->execute([$alternative_barcode, $player['id']]);
            
            echo "✅ Generated alternative barcode for {$player['name']}: <strong>{$alternative_barcode}</strong><br>\n";
            $generated_count++;
        }
    }
    
    echo "<h3>✅ Barcode Migration Completed Successfully!</h3>\n";
    echo "<p>📊 <strong>Generated barcodes for {$generated_count} players</strong></p>\n";
    
    // Display sample barcodes
    echo "<h3>📋 Sample Generated Barcodes:</h3>\n";
    $sample_stmt = $pdo->query("SELECT name, barcode, date_of_birth FROM players WHERE barcode IS NOT NULL LIMIT 5");
    $samples = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='display: grid; gap: 15px; margin: 20px 0;'>\n";
    foreach ($samples as $sample) {
        echo "<div style='border: 1px solid #ccc; padding: 15px; border-radius: 8px; background: white;'>\n";
        echo "<h4 style='margin: 0 0 10px 0; color: #dc2626;'>{$sample['name']}</h4>\n";
        echo "<p><strong>DOB:</strong> {$sample['date_of_birth']}</p>\n";
        echo "<p><strong>Barcode:</strong> <span style='font-family: monospace; background: #f3f4f6; padding: 4px 8px; border-radius: 4px;'>{$sample['barcode']}</span></p>\n";
        
        // Generate SVG barcode
        $svg = PlayerBarcodeGenerator::generateBarcodeSVG($sample['barcode']);
        echo "<div style='margin-top: 10px;'>{$svg}</div>\n";
        echo "</div>\n";
    }
    echo "</div>\n";
    
    echo "<h3>📈 Next Steps:</h3>\n";
    echo "<ul>\n";
    echo "<li>✅ Barcode column added to database</li>\n";
    echo "<li>✅ Unique barcodes generated for all players</li>\n";
    echo "<li>🔄 Enhanced player profiles now include barcode display</li>\n";
    echo "<li>🎯 Visit player profiles to see barcode integration</li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; border-radius: 4px; background: #fef2f2;'>\n";
    echo "<h3>❌ Migration Error</h3>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
    echo "</div>\n";
}
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    color: #374151;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    background: #f9fafb;
}

h2 {
    color: #dc2626;
    border-bottom: 2px solid #dc2626;
    padding-bottom: 10px;
}

h3 {
    color: #1f2937;
    margin-top: 30px;
}

pre {
    background: #f3f4f6;
    padding: 15px;
    border-radius: 8px;
    overflow-x: auto;
}

.success {
    color: #10b981;
    font-weight: bold;
}

.warning {
    color: #f59e0b;
    font-weight: bold;
}
</style>
