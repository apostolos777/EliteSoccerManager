<?php
/**
 * VIVO United - Bulk Attendance Import
 * 
 * Features:
 * - Import attendance from CSV files
 * - Import from backup databases
 * - Preview before importing
 * - Validation and error handling
 * - Duplicate detection and handling
 */

require_once 'includes/color_system.php';
require_once 'includes/auth.php';

// Check authentication - admin only
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if (!isAdmin()) {
    header('Location: dashboard.php?error=' . urlencode('Admin access required'));
    exit();
}

$currentPage = 'attendance';

// Database connection
require_once 'database_config.php';
try {
    $db = DatabaseConfigSQLite::getConnection();
} catch (Exception $e) { 
    die("Database connection failed: " . $e->getMessage()); 
}

$message = '';
$messageType = '';
$importPreview = [];
$importStats = [];

// Handle file upload and preview
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['attendance_file'])) {
    try {
        $file = $_FILES['attendance_file'];
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error: " . $file['error']);
        }
        
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($fileType === 'csv') {
            // Parse CSV
            $importPreview = parseAttendanceCSV($file['tmp_name'], $db);
            $importStats = generateImportStats($importPreview);
            $message = "CSV file parsed successfully. Review the preview below and confirm to import.";
            $messageType = "info";
        } elseif ($fileType === 'json') {
            // Parse JSON
            $importPreview = parseAttendanceJSON($file['tmp_name'], $db);
            $importStats = generateImportStats($importPreview);
            $message = "JSON file parsed successfully. Review the preview below and confirm to import.";
            $messageType = "info";
        } else {
            throw new Exception("Unsupported file type. Please upload CSV or JSON files.");
        }
        
        // Store preview data in session for confirmation
        $_SESSION['attendance_import_preview'] = $importPreview;
        $_SESSION['attendance_import_stats'] = $importStats;
        
    } catch (Exception $e) {
        $message = "Error processing file: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Handle import confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_import'])) {
    try {
        if (!isset($_SESSION['attendance_import_preview']) || empty($_SESSION['attendance_import_preview'])) {
            throw new Exception("No preview data found. Please upload a file again.");
        }
        
        $importData = $_SESSION['attendance_import_preview'];
        $duplicateHandling = $_POST['duplicate_handling'] ?? 'skip';
        
        $db->beginTransaction();
        $importedCount = 0;
        $skippedCount = 0;
        $updatedCount = 0;
        $errors = [];
        
        foreach ($importData as $record) {
            try {
                if (!isset($record['player_id'], $record['event_id'], $record['status'])) {
                    $errors[] = "Missing required fields in record: " . json_encode($record);
                    continue;
                }
                
                $playerId = (int)$record['player_id'];
                $eventId = (int)$record['event_id'];
                $status = strtolower($record['status']);
                $notes = $record['notes'] ?? '';
                
                // Validate status
                $validStatuses = ['present', 'absent', 'late', 'excused', 'not_recorded'];
                if (!in_array($status, $validStatuses)) {
                    $errors[] = "Invalid status '$status' for player $playerId at event $eventId";
                    continue;
                }
                
                // Check if record exists
                $checkStmt = $db->prepare("SELECT id FROM attendance WHERE player_id = ? AND event_id = ?");
                $checkStmt->execute([$playerId, $eventId]);
                $existingRecord = $checkStmt->fetch();
                
                if ($existingRecord) {
                    if ($duplicateHandling === 'skip') {
                        $skippedCount++;
                        continue;
                    } elseif ($duplicateHandling === 'update') {
                        $updateStmt = $db->prepare("UPDATE attendance SET status = ?, notes = ?, recorded_at = CURRENT_TIMESTAMP WHERE player_id = ? AND event_id = ?");
                        $updateStmt->execute([$status, $notes, $playerId, $eventId]);
                        $updatedCount++;
                        continue;
                    }
                }
                
                // Insert new record
                $insertStmt = $db->prepare("INSERT INTO attendance (player_id, event_id, status, notes, recorded_at, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                $insertStmt->execute([$playerId, $eventId, $status, $notes]);
                $importedCount++;
                
            } catch (Exception $e) {
                $errors[] = "Error processing record: " . $e->getMessage();
            }
        }
        
        $db->commit();
        
        // Clear session
        unset($_SESSION['attendance_import_preview']);
        unset($_SESSION['attendance_import_stats']);
        
        $message = "Import completed! Imported: $importedCount, Updated: $updatedCount, Skipped: $skippedCount";
        if (!empty($errors)) {
            $message .= " | Errors: " . count($errors);
        }
        $messageType = "success";
        $importPreview = [];
        
    } catch (Exception $e) {
        $db->rollback();
        $message = "Error importing attendance: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Handle database restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_from_backup'])) {
    try {
        $backupDb = $_POST['backup_database'] ?? '';
        
        if (empty($backupDb) || !file_exists($backupDb)) {
            throw new Exception("Invalid backup database path");
        }
        
        // Open backup database
        $backupConnection = new PDO('sqlite:' . $backupDb);
        $backupConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get attendance records from backup
        $stmt = $backupConnection->query("SELECT player_id, event_id, status, notes FROM attendance");
        $backupRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $duplicateHandling = $_POST['duplicate_handling'] ?? 'skip';
        $db->beginTransaction();
        $importedCount = 0;
        $skippedCount = 0;
        $updatedCount = 0;
        $errors = [];
        
        foreach ($backupRecords as $record) {
            try {
                $playerId = (int)$record['player_id'];
                $eventId = (int)$record['event_id'];
                $status = strtolower($record['status']);
                $notes = $record['notes'] ?? '';
                
                // Check if record exists
                $checkStmt = $db->prepare("SELECT id FROM attendance WHERE player_id = ? AND event_id = ?");
                $checkStmt->execute([$playerId, $eventId]);
                $existingRecord = $checkStmt->fetch();
                
                if ($existingRecord) {
                    if ($duplicateHandling === 'skip') {
                        $skippedCount++;
                        continue;
                    } elseif ($duplicateHandling === 'update') {
                        $updateStmt = $db->prepare("UPDATE attendance SET status = ?, notes = ? WHERE player_id = ? AND event_id = ?");
                        $updateStmt->execute([$status, $notes, $playerId, $eventId]);
                        $updatedCount++;
                        continue;
                    }
                }
                
                // Insert new record
                $insertStmt = $db->prepare("INSERT INTO attendance (player_id, event_id, status, notes, recorded_at, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                $insertStmt->execute([$playerId, $eventId, $status, $notes]);
                $importedCount++;
                
            } catch (Exception $e) {
                $errors[] = "Error: " . $e->getMessage();
            }
        }
        
        $db->commit();
        $backupConnection = null;
        
        $message = "Backup import completed! Imported: $importedCount, Updated: $updatedCount, Skipped: $skippedCount";
        if (!empty($errors)) {
            $message .= " | Errors: " . count($errors);
        }
        $messageType = "success";
        
    } catch (Exception $e) {
        $db->rollback();
        $message = "Error importing from backup: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Helper function to parse CSV
function parseAttendanceCSV($filePath, $db) {
    $records = [];
    $handle = fopen($filePath, 'r');
    
    if (!$handle) {
        throw new Exception("Cannot open CSV file");
    }
    
    // Read header
    $header = fgetcsv($handle);
    if ($header === false) {
        throw new Exception("CSV file appears to be empty");
    }

    // Normalize headers: trim, lowercase, remove BOM
    $normalizedHeader = [];
    foreach ($header as $h) {
        $h = trim($h);
        // Remove BOM from first header if present
        $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
        $normalizedHeader[] = strtolower($h);
    }
    $headerMap = array_flip($normalizedHeader);
    
    // Read data rows
    $rowNum = 1; // header
    while (($row = fgetcsv($handle)) !== false) {
        $rowNum++;
        // Skip completely empty rows
        if (count(array_filter($row, fn($c) => trim((string)$c) !== '')) === 0) continue;

        $record = [];

        // Flexible column mapping - accept a few common header variants
        $getField = function($fieldNames) use ($headerMap, $row) {
            foreach ($fieldNames as $fn) {
                $fn = strtolower($fn);
                if (isset($headerMap[$fn])) {
                    return $row[$headerMap[$fn]] ?? '';
                }
            }
            return '';
        };

        $record['player_id'] = trim((string)$getField(['player_id', 'player id', 'playerid', 'id']));
        // attempt to resolve by name fields if player_id not provided
        $playerNameRaw = trim((string)$getField(['player_name', 'player', 'name', 'full_name', 'playerfullname']));
        $record['event_id'] = trim((string)$getField(['event_id', 'event id', 'eventid', 'event']));
        $record['status'] = strtolower(trim((string)$getField(['status', 'attendance_status', 'attendance'])));
        $record['notes'] = trim((string)$getField(['notes', 'note', 'comments']));

        // Basic validation and normalization
        $errors = [];

        if ($record['player_id'] === '') $errors[] = 'Missing player_id';
        if ($record['event_id'] === '') $errors[] = 'Missing event_id';

        // Validate numeric ids
        if ($record['player_id'] !== '' && !ctype_digit((string)$record['player_id'])) $errors[] = 'player_id must be numeric';
        if ($record['event_id'] !== '' && !ctype_digit((string)$record['event_id'])) $errors[] = 'event_id must be numeric';

        // Validate status (preview stage) and normalize common variants
        $statusMap = ['present' => 'present', 'p' => 'present', 'absent' => 'absent', 'a' => 'absent', 'late' => 'late', 'l' => 'late', 'excused' => 'excused', 'e' => 'excused', 'not_recorded' => 'not_recorded', 'nr' => 'not_recorded'];
        if ($record['status'] === '') {
            $record['status'] = 'not_recorded';
        } else {
            $s = $record['status'];
            if (isset($statusMap[$s])) $record['status'] = $statusMap[$s];
            else $errors[] = "Unrecognized status '$s'";
        }

        // If player_id missing or non-numeric, try to resolve by name
        if (($record['player_id'] === '' || !ctype_digit((string)$record['player_id'])) && $playerNameRaw !== '') {
            // attempt to split full name into first/last
            $nameParts = preg_split('/\s+/', $playerNameRaw);
            if (count($nameParts) >= 2) {
                $first = array_shift($nameParts);
                $last = implode(' ', $nameParts);
                $stmt = $db->prepare("SELECT id FROM players WHERE LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?) LIMIT 1");
                $stmt->execute([$first, $last]);
                $found = $stmt->fetchColumn();
                if ($found) {
                    $record['player_id'] = (string)$found;
                }
            } else {
                // single-name match attempt on name or surname fields
                $stmt = $db->prepare("SELECT id FROM players WHERE LOWER(name) = LOWER(?) OR LOWER(surname) = LOWER(?) LIMIT 1");
                $stmt->execute([$playerNameRaw, $playerNameRaw]);
                $found = $stmt->fetchColumn();
                if ($found) $record['player_id'] = (string)$found;
            }
        }

        // Check existence of player and event only if ids look numeric
        if (empty($errors)) {
            $playerStmt = $db->prepare("SELECT id FROM players WHERE id = ?");
            $playerStmt->execute([(int)$record['player_id']]);
            if (!$playerStmt->fetch()) {
                $errors[] = "Player not found (id={$record['player_id']})";
            }

            $eventStmt = $db->prepare("SELECT id FROM events WHERE id = ?");
            $eventStmt->execute([(int)$record['event_id']]);
            if (!$eventStmt->fetch()) {
                $errors[] = "Event not found (id={$record['event_id']})";
            }
        }

        if (!empty($errors)) {
            $record['error'] = implode('; ', $errors) . " (row $rowNum)";
        }

        $records[] = $record;
    }
    
    fclose($handle);
    return $records;
}

// Helper function to parse JSON
function parseAttendanceJSON($filePath, $db) {
    $json = file_get_contents($filePath);
    $data = json_decode($json, true);
    
    if (!is_array($data)) {
        throw new Exception("Invalid JSON format. Expected array of attendance records.");
    }
    
    $records = [];
    
    $rowNum = 0;
    foreach ($data as $record) {
        $rowNum++;
        if (!is_array($record)) {
            continue;
        }
        
        // Normalize keys to lowercase
        $normalized = [];
        foreach ($record as $key => $value) {
            $normalized[strtolower($key)] = $value;
        }
        
        // Basic validation and normalization
        $errors = [];
        $playerId = (int)($normalized['player_id'] ?? 0);
        // Try to resolve by name where id missing
        $playerNameRaw = trim((string)($normalized['player_name'] ?? $normalized['name'] ?? $normalized['full_name'] ?? ''));
        if ($playerId <= 0 && $playerNameRaw !== '') {
            $parts = preg_split('/\s+/', $playerNameRaw);
            if (count($parts) >= 2) {
                $first = array_shift($parts);
                $last = implode(' ', $parts);
                $stmt = $db->prepare("SELECT id FROM players WHERE LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?) LIMIT 1");
                $stmt->execute([$first, $last]);
                if ($pid = $stmt->fetchColumn()) $playerId = (int)$pid;
            } else {
                $stmt = $db->prepare("SELECT id FROM players WHERE LOWER(name) = LOWER(?) OR LOWER(surname) = LOWER(?) LIMIT 1");
                $stmt->execute([$playerNameRaw, $playerNameRaw]);
                if ($pid = $stmt->fetchColumn()) $playerId = (int)$pid;
            }
        }
        $eventId = (int)($normalized['event_id'] ?? 0);
        $statusRaw = strtolower(trim((string)($normalized['status'] ?? '')));

        if ($playerId <= 0) $errors[] = 'Missing or invalid player_id';
        if ($eventId <= 0) $errors[] = 'Missing or invalid event_id';

        // Validate status and normalize
        $statusMap = ['present' => 'present', 'p' => 'present', 'absent' => 'absent', 'a' => 'absent', 'late' => 'late', 'l' => 'late', 'excused' => 'excused', 'e' => 'excused', 'not_recorded' => 'not_recorded', 'nr' => 'not_recorded'];
        if ($statusRaw === '') {
            $normalized['status'] = 'not_recorded';
        } elseif (isset($statusMap[$statusRaw])) {
            $normalized['status'] = $statusMap[$statusRaw];
        } else {
            $errors[] = "Unrecognized status '$statusRaw'";
        }

        if (empty($errors)) {
            $playerStmt = $db->prepare("SELECT id FROM players WHERE id = ?");
            $playerStmt->execute([$playerId]);
            if (!$playerStmt->fetch()) {
                $errors[] = "Player not found (id=$playerId)";
            }

            $eventStmt = $db->prepare("SELECT id FROM events WHERE id = ?");
            $eventStmt->execute([$eventId]);
            if (!$eventStmt->fetch()) {
                $errors[] = "Event not found (id=$eventId)";
            }
        }

        if (!empty($errors)) {
            $normalized['error'] = implode('; ', $errors) . " (item $rowNum)";
        }
        
        $records[] = $normalized;
    }
    
    return $records;
}

// Helper function to generate import stats
function generateImportStats($records) {
    $stats = [
        'total' => count($records),
        'valid' => 0,
        'invalid' => 0,
        'by_status' => [],
        'by_event' => []
    ];
    
    foreach ($records as $record) {
        if (isset($record['error'])) {
            $stats['invalid']++;
        } else {
            $stats['valid']++;
            $status = $record['status'] ?? 'unknown';
            $event = $record['event_id'] ?? 'unknown';
            
            $stats['by_status'][$status] = ($stats['by_status'][$status] ?? 0) + 1;
            $stats['by_event'][$event] = ($stats['by_event'][$event] ?? 0) + 1;
        }
    }
    
    return $stats;
}

// Get available backup databases
$backupDatabases = [];
$backupPatterns = [
    'database.db.backup.*',
    'vivo_football.db.backup',
    'backups/database*.db',
    'backups/vivo*.db'
];

foreach ($backupPatterns as $pattern) {
    $files = glob($pattern);
    foreach ($files as $file) {
        if (file_exists($file) && is_file($file)) {
            try {
                $backupDb = new PDO('sqlite:' . $file);
                $backupDb->query("SELECT 1 FROM attendance LIMIT 1");
                $backupDatabases[$file] = basename($file);
            } catch (Exception $e) {
                // Not a valid attendance database
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Attendance Import - VIVO United</title>
    <?php
        require_once 'includes/css_helper.php';
        vivo_include_head_css($db);
    ?>
    <style>
        .import-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .import-section {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0,0,0,0.04);
            box-shadow: 0 10px 30px rgba(2,8,23,0.06);
        }
        
        .import-section h3 {
            margin-top: 0;
            color: #0f1724;
            font-weight: 700;
            border-bottom: 2px solid #0b6cff;
            padding-bottom: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .file-upload-area {
            border: 2px dashed #0b6cff;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            background: #f0f7ff;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .file-upload-area:hover {
            background: #e6f0ff;
            border-color: #0051cc;
        }
        
        .file-upload-area i {
            font-size: 2rem;
            color: #0b6cff;
            margin-bottom: 0.5rem;
        }
        
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .preview-table thead {
            background: #f3f4f6;
        }
        
        .preview-table th {
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .preview-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .preview-table tr:hover {
            background: #f9fafb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-present {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-absent {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-late {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-excused {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .error-badge {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .stat-box {
            background: #f9fafb;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            border: 1px solid #e5e7eb;
        }
        
        .stat-box .number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0b6cff;
        }
        
        .stat-box .label {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="content-main">
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title">
                    <i class="fas fa-file-import"></i>
                    Bulk Attendance Import
                </h1>
                <p class="page-subtitle">Import attendance records from CSV, JSON, or backup databases</p>
            </div>
            <div class="page-actions">
                <a href="attendance.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Attendance
                </a>
                <a href="#" onclick="downloadTemplate()" class="btn btn-outline" style="margin-left: 0.5rem;">
                    <i class="fas fa-download"></i>
                    Download Template
                </a>
            </div>
        </div>

        <div class="content-wrapper import-container">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                    <i class="fas fa-info-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- File Upload Section -->
            <div class="import-section">
                <h3><i class="fas fa-upload" style="margin-right: 0.5rem;"></i>Upload CSV or JSON File</h3>
                
                <form method="POST" enctype="multipart/form-data" id="fileUploadForm">
                    <div class="file-upload-area" onclick="document.getElementById('attendanceFile').click()">
                        <div>
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p style="margin: 0.5rem 0 0 0; font-weight: 600;">Click to upload or drag and drop</p>
                            <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.9rem;">CSV or JSON files</p>
                        </div>
                    </div>
                    <input type="file" id="attendanceFile" name="attendance_file" accept=".csv,.json" style="display: none;">
                    <button type="submit" name="upload" class="btn btn-primary" style="margin-top: 1rem; display: none;" id="submitBtn">
                        <i class="fas fa-check"></i>
                        Preview File
                    </button>
                </form>
                
                <script>
                    document.getElementById('attendanceFile').addEventListener('change', function(e) {
                        if (e.target.files.length > 0) {
                            document.getElementById('fileUploadForm').submit();
                        }
                    });
                    
                    document.querySelector('.file-upload-area').addEventListener('dragover', function(e) {
                        e.preventDefault();
                        this.style.background = '#e6f0ff';
                    });
                    
                    document.querySelector('.file-upload-area').addEventListener('dragleave', function(e) {
                        e.preventDefault();
                        this.style.background = '#f0f7ff';
                    });
                    
                    document.querySelector('.file-upload-area').addEventListener('drop', function(e) {
                        e.preventDefault();
                        document.getElementById('attendanceFile').files = e.dataTransfer.files;
                        document.getElementById('fileUploadForm').submit();
                    });
                </script>
            </div>

            <!-- Import Preview Section -->
            <?php if (!empty($importPreview)): ?>
                <div class="import-section">
                    <h3><i class="fas fa-eye" style="margin-right: 0.5rem;"></i>Preview Records</h3>
                    
                    <?php if (!empty($importStats)): ?>
                        <div class="stats-grid">
                            <div class="stat-box">
                                <div class="number"><?php echo $importStats['total']; ?></div>
                                <div class="label">Total Records</div>
                            </div>
                            <div class="stat-box">
                                <div class="number"><?php echo $importStats['valid']; ?></div>
                                <div class="label">Valid Records</div>
                            </div>
                            <div class="stat-box">
                                <div class="number"><?php echo $importStats['invalid']; ?></div>
                                <div class="label">Invalid Records</div>
                            </div>
                            <div class="stat-box">
                                <div class="number"><?php echo count($importStats['by_event']); ?></div>
                                <div class="label">Events</div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>Player ID</th>
                                <th>Event ID</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($importPreview, 0, 20) as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['player_id'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($record['event_id'] ?? ''); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars(strtolower($record['status'] ?? '')); ?>">
                                            <?php echo htmlspecialchars(ucfirst($record['status'] ?? '')); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['notes'] ?? ''); ?></td>
                                    <td>
                                        <?php if (isset($record['error'])): ?>
                                            <span class="status-badge error-badge">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <?php echo htmlspecialchars($record['error']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge" style="background: #dcfce7; color: #166534;">
                                                <i class="fas fa-check"></i>
                                                Ready
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if (count($importPreview) > 20): ?>
                        <p style="margin-top: 1rem; color: #6b7280;">
                            Showing 20 of <?php echo count($importPreview); ?> records
                        </p>
                    <?php endif; ?>
                    
                    <form method="POST" style="margin-top: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label class="form-label">Handle Duplicates (existing records):</label>
                            <select name="duplicate_handling" class="form-control">
                                <option value="skip">Skip duplicates</option>
                                <option value="update">Update duplicates</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="confirm_import" class="btn btn-primary">
                            <i class="fas fa-check-circle"></i>
                            Confirm Import
                        </button>
                        <a href="bulk_attendance_import.php" class="btn btn-secondary" style="margin-left: 0.5rem;">
                            Cancel
                        </a>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Backup Import Section -->
            <?php if (!empty($backupDatabases)): ?>
                <div class="import-section">
                    <h3><i class="fas fa-database" style="margin-right: 0.5rem;"></i>Import from Backup Database</h3>
                    
                    <form method="POST">
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label class="form-label">Select Backup Database:</label>
                            <select name="backup_database" class="form-control" required>
                                <option value="">-- Choose a backup --</option>
                                <?php foreach ($backupDatabases as $path => $name): ?>
                                    <option value="<?php echo htmlspecialchars($path); ?>">
                                        <?php echo htmlspecialchars($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label class="form-label">Handle Duplicates:</label>
                            <select name="duplicate_handling" class="form-control">
                                <option value="skip">Skip duplicates</option>
                                <option value="update">Update duplicates</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="import_from_backup" class="btn btn-primary">
                            <i class="fas fa-database"></i>
                            Import from Backup
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Template Information -->
            <div class="import-section">
                <h3><i class="fas fa-file-csv" style="margin-right: 0.5rem;"></i>File Format Requirements</h3>
                
                <h4 style="margin-top: 0; color: #0f1724; font-weight: 600;">CSV Format</h4>
                <p style="color: #6b7280; margin-bottom: 0.75rem;">Your CSV file must have these columns:</p>
                <pre style="background: #f3f4f6; padding: 1rem; border-radius: 6px; overflow-x: auto; font-size: 0.9rem;">player_id,event_id,status,notes
183,31,present,Player arrived on time
199,37,absent,Not available
255,37,late,Arrived 15 minutes late
217,37,excused,Excused due to injury</pre>
                
                <h4 style="margin-top: 1rem; color: #0f1724; font-weight: 600;">JSON Format</h4>
                <p style="color: #6b7280; margin-bottom: 0.75rem;">Your JSON file must be an array of objects:</p>
                <pre style="background: #f3f4f6; padding: 1rem; border-radius: 6px; overflow-x: auto; font-size: 0.9rem;">[
  {
    "player_id": 183,
    "event_id": 31,
    "status": "present",
    "notes": "Player arrived on time"
  },
  {
    "player_id": 199,
    "event_id": 37,
    "status": "absent",
    "notes": "Not available"
  }
]</pre>
                
                <h4 style="margin-top: 1rem; color: #0f1724; font-weight: 600;">Status Values</h4>
                <p style="color: #6b7280; margin-bottom: 0.75rem;">Use one of these status values:</p>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div>
                        <span class="status-badge status-present">present</span>
                        <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.9rem;">Player was present</p>
                    </div>
                    <div>
                        <span class="status-badge status-absent">absent</span>
                        <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.9rem;">Player was absent</p>
                    </div>
                    <div>
                        <span class="status-badge status-late">late</span>
                        <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.9rem;">Player was late</p>
                    </div>
                    <div>
                        <span class="status-badge status-excused">excused</span>
                        <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.9rem;">Player was excused</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function downloadTemplate() {
            // CSV template
            const csvContent = `player_id,event_id,status,notes
183,31,present,Player arrived on time
199,37,absent,Not available
255,37,late,Arrived 15 minutes late
217,37,excused,Excused due to injury
278,37,present,Present`;

            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'attendance_template.csv';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }
    </script>
</body>
</html>
