<?php
// Simple DB error logger for development
function log_db_error($exception, $context = '') {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
    $file = $logDir . '/db_errors.log';
    $msg = date('Y-m-d H:i:s') . " | " . ($context ? $context . ' | ' : '') . $exception->getMessage() . "\n";
    @file_put_contents($file, $msg, FILE_APPEND | LOCK_EX);
}

return true;
