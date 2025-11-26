<?php
require_once 'includes/color_system.php';

echo "<!DOCTYPE html><html><head>";
echo "<title>CSS Debug</title>";

// Output the dynamic CSS
VIVOColorSystem::outputCSS();

echo "</head><body>";
echo "<h1>CSS Variables Test</h1>";
echo "<div style='background: var(--primary); color: white; padding: 20px; margin: 10px;'>Primary Color Test</div>";
echo "<div style='background: var(--secondary); color: white; padding: 20px; margin: 10px;'>Secondary Color Test</div>";

echo "<h2>Generated CSS:</h2>";
echo "<pre style='background: #f5f5f5; padding: 20px; overflow: auto;'>";
echo htmlspecialchars(VIVOColorSystem::generateDynamicCSS());
echo "</pre>";

echo "</body></html>";
?>
