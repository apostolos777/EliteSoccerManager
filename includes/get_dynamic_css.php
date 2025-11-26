<?php
/**
 * VIVO United - Dynamic CSS Endpoint
 * Returns current dynamic CSS for real-time updates
 */

header('Content-Type: text/css');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

require_once __DIR__ . '/color_system.php';

echo VIVOColorSystem::generateDynamicCSS();
