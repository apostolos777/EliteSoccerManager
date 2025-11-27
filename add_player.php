<?php
/**
 * VIVO United - Add Player (WordPress Style)
 */

// Database connection (WordPress-style, consistent with other files)
require_once 'includes/color_system.php';
require_once 'database_factory.php';
require_once 'includes/auth.php';
require_once 'functions.php';

requireLogin();
$currentPage = 'players';

$db = DatabaseFactory::getConnection();

$message = '';
$messageType = '';

// Get teams for dropdown
$teams = $db->query("SELECT * FROM teams ORDER BY name")->fetchAll();

// Age groups for player selection (including club-specific groups)
    $ageGroups = require_once __DIR__ . '/includes/age_groups.php';

// Check database schema to adapt to different column names
$schemaColumns = [];
try {
    $result = $db->query("PRAGMA table_info(players)");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $schemaColumns[] = $row['name'];
    }
} catch (Exception $e) {
    die("Error checking database schema: " . $e->getMessage());
}

// Determine which naming convention is used
$hasFirstLastName = in_array('first_name', $schemaColumns) && in_array('last_name', $schemaColumns);
$hasNameSurname = in_array('name', $schemaColumns) && in_array('surname', $schemaColumns);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Build insert data based on available schema
        $insertData = [];
        
        // Handle name fields based on schema
        if ($hasFirstLastName && !$hasNameSurname) {
            // Production schema: use first_name/last_name
            $insertData['first_name'] = $_POST['name'] ?? '';
            $insertData['last_name'] = $_POST['surname'] ?? '';
        } else {
            // Local schema: use name/surname
            $insertData['name'] = $_POST['name'] ?? '';
            $insertData['surname'] = $_POST['surname'] ?? '';
        }
        
        // Handle multiple team selection
        $selectedTeams = $_POST['team_ids'] ?? [];
        $teamIdsString = !empty($selectedTeams) ? implode(',', $selectedTeams) : '';
        $primaryTeamId = !empty($selectedTeams) ? $selectedTeams[0] : null;
        
        // Common fields that might exist in both schemas
        $commonFields = [
            'date_of_birth' => $_POST['date_of_birth'] ?? '',
            'age' => $_POST['age'] ?? '',
            'nationality' => $_POST['nationality'] ?? '',
            'contact_number' => $_POST['contact_number'] ?? '',
            'height' => $_POST['height'] ?? '',
            'weight' => $_POST['weight'] ?? '',
            'preferred_foot' => $_POST['preferred_foot'] ?? '',
            'primary_position' => $_POST['primary_position'] ?? '',
            'secondary_position' => $_POST['secondary_position'] ?? '',
            'third_position' => $_POST['third_position'] ?? '',
            'favorite_player' => $_POST['favorite_player'] ?? '',
            'nickname' => $_POST['nickname'] ?? '',
            'position' => $_POST['primary_position'] ?? '', // fallback for simple position field
            'jersey_number' => $_POST['jersey_number'] ?? '',
            'team_id' => $primaryTeamId, // Primary team (first selected)
            'team_ids' => $teamIdsString // Comma-separated list of all teams
        ];
        
        // Extended fields (for local schema)
        $extendedFields = [
        'identity_number' => $_POST['identity_number'] ?? '',
            'playing_style' => $_POST['playing_style'] ?? '',
            'why_started_playing' => $_POST['why_started_playing'] ?? '',
            'why_like_football' => $_POST['why_like_football'] ?? '',
            'personal_talents' => $_POST['personal_talents'] ?? '',
            'off_field_interests' => $_POST['off_field_interests'] ?? '',
            'facebook_url' => $_POST['facebook_url'] ?? '',
            'instagram_url' => $_POST['instagram_url'] ?? '',
            'twitter_url' => $_POST['twitter_url'] ?? '',
            'tiktok_url' => $_POST['tiktok_url'] ?? '',
            'youtube_url' => $_POST['youtube_url'] ?? '',
            'linkedin_url' => $_POST['linkedin_url'] ?? ''
        ];
        
        // Add common fields to insert data
        foreach ($commonFields as $field => $value) {
            if (!in_array($field, $schemaColumns)) continue;
            // Cast to string to avoid trim(null) deprecation warnings
            $trimmedValue = trim((string)$value);
            if ($field === 'team_id') {
                if ($trimmedValue !== '') {
                    $insertData[$field] = (int)$trimmedValue;
                }
            } elseif (in_array($field, ['age', 'height', 'weight', 'jersey_number'])) {
                if ($trimmedValue !== '') {
                    $insertData[$field] = (int)$trimmedValue;
                }
            } else {
                if ($trimmedValue !== '') {
                    $insertData[$field] = $trimmedValue;
                }
            }
        }
        
        // Add extended fields if they exist in schema (for local database)
        foreach ($extendedFields as $field => $value) {
            if (in_array($field, $schemaColumns)) {
                $trimmedValue = trim((string)$value);
                $insertData[$field] = !empty($trimmedValue) ? $trimmedValue : null;
            }
        }
        
        // Add default status if column exists
        if (in_array('status', $schemaColumns)) {
            $insertData['status'] = 'active';
        }
        
        // Add created timestamp if column exists
        if (in_array('created_at', $schemaColumns)) {
            $insertData['created_at'] = date('Y-m-d H:i:s');
        }
        
        // Validation - ensure required name fields are present
        $nameValid = false;
        if ($hasFirstLastName && !$hasNameSurname) {
            $nameValid = !empty($insertData['first_name']) && !empty($insertData['last_name']);
        } else {
            $nameValid = !empty($insertData['name']) && !empty($insertData['surname']);
        }
        
        if (!$nameValid) {
            throw new Exception('First name and last name are required.');
        }
        
    // No age limit validation (allow any age)
        // Calculate age from date of birth if provided
        if (!empty($insertData['date_of_birth']) && in_array('age', $schemaColumns)) {
            try {
                $dob = new DateTime($insertData['date_of_birth']);
                $now = new DateTime();
                $insertData['age'] = $now->diff($dob)->y;
            } catch (Exception $e) {
                // ignore invalid dob
            }
        }

        // If no team selected, auto-assign based on age -> age_group mapping
        if (empty($insertData['team_id']) && !empty($insertData['date_of_birth'])) {
            $age = isset($insertData['age']) ? (int)$insertData['age'] : null;
            if ($age !== null) {
                if ($age < 5) $suggest = 'Too Young';
                elseif ($age == 5) $suggest = 'U6';
                elseif ($age == 6) $suggest = 'U7';
                elseif ($age == 7) $suggest = 'U8';
                elseif ($age == 8) $suggest = 'U9';
                elseif ($age == 9) $suggest = 'U10';
                elseif ($age == 10) $suggest = 'U11';
                elseif ($age == 11) $suggest = 'U12';
                elseif ($age == 12) $suggest = 'U13';
                elseif ($age == 13) $suggest = 'U14';
                elseif ($age == 14) $suggest = 'U15';
                elseif ($age == 15) $suggest = 'U16';
                elseif ($age == 16) $suggest = 'U17';
                elseif ($age == 17) $suggest = 'U18';
                elseif ($age == 18) $suggest = 'U19';
                elseif ($age == 19) $suggest = 'U20';
                elseif ($age == 20) $suggest = 'U21';
                else $suggest = 'Senior';

                try {
                    // Fetch candidate teams and normalize their age_group values for matching
                    $allTeams = $db->query("SELECT id, age_group FROM teams")->fetchAll(PDO::FETCH_ASSOC);
                    $found = false;
                    foreach ($allTeams as $t) {
                        $ag = trim($t['age_group'] ?? '');
                        $norm = '';
                        if (preg_match('/(under|u)\s*(\d{1,2})/i', $ag, $m)) {
                            $norm = 'U' . intval($m[2]);
                        } elseif (preg_match('/^(\d{1,2})$/', $ag, $m)) {
                            $norm = 'U' . intval($m[1]);
                        } elseif (preg_match('/over\s*(\d{1,2})/i', $ag, $m)) {
                            $norm = 'Over ' . intval($m[1]);
                        } elseif (stripos($ag, 'senior') !== false) {
                            $norm = 'Senior';
                        } elseif (stripos($ag, 'veteran') !== false || stripos($ag, 'over') !== false) {
                            $norm = 'Over 35';
                        } else {
                            $norm = $ag; // fallback to raw
                        }

                        if ($norm === $suggest) {
                            $insertData['team_id'] = $t['id'];
                            $found = true;
                            break;
                        }
                    }
                } catch (Exception $e) {
                    // ignore
                }
            }
        }

        // Handle profile image upload (optional)
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/players/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $tmpName = $_FILES['profile_image']['tmp_name'];
            $uniqueName = 'player_' . time() . '_' . rand(100,999) . '_profile.' . $ext;
            $dest = $upload_dir . $uniqueName;
            if (move_uploaded_file($tmpName, $dest)) {
                if (in_array('profile_image', $schemaColumns) || in_array('profile_image', $schemaColumns)) {
                    $insertData['profile_image'] = $dest;
                }
            }
        }

        // Build and execute insert query
        $columns = array_keys($insertData);
        $placeholders = ':' . implode(', :', $columns);
        $sql = "INSERT INTO players (" . implode(', ', $columns) . ") VALUES (" . $placeholders . ")";
        
        $stmt = $db->prepare($sql);
        
        // Bind values
        foreach ($insertData as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        $newPlayerId = $db->lastInsertId();

        // If the user selected multiple teams we'll persist them to a join table.
        // Ensure the join table exists (create if missing) so older installs without migrations still get the feature.
        try {
            if (!empty($selectedTeams) && is_array($selectedTeams)) {
                // Create the join table if it doesn't exist yet (safe operation)
                $db->exec("CREATE TABLE IF NOT EXISTS player_teams (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    player_id INTEGER NOT NULL,
                    team_id INTEGER NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(player_id, team_id)
                )");
                $hasPlayerTeams = true;
            } else {
                $hasPlayerTeams = (bool)$db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='player_teams'")->fetch(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            // On any failure, gracefully fallback to keeping team_ids stored on the players table
            $hasPlayerTeams = false;
        }

        if ($hasPlayerTeams && !empty($selectedTeams) && is_array($selectedTeams)) {
            try {
                $insertStmt = $db->prepare('INSERT INTO player_teams (player_id, team_id) VALUES (?, ?)');
                foreach ($selectedTeams as $t) {
                    $insertStmt->execute([(int)$newPlayerId, (int)$t]);
                }
            } catch (Exception $e) {
                // ignore
            }
        }

        // Document uploads on create
        $documentUploadDir = 'uploads/players/documents/';
        if (!is_dir($documentUploadDir)) mkdir($documentUploadDir, 0755, true);
        $hasPlayerDocumentsTable = false;
        try {
            $r = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='player_documents'")->fetch(PDO::FETCH_ASSOC);
            if ($r) $hasPlayerDocumentsTable = true;
        } catch (Exception $e) { }

        $docHandler = function($input,$type) use(&$db,$newPlayerId,$documentUploadDir,$hasPlayerDocumentsTable){
            if (!isset($_FILES[$input]) || $_FILES[$input]['error'] !== UPLOAD_ERR_OK) return;
            $ext = pathinfo($_FILES[$input]['name'], PATHINFO_EXTENSION);
            $dest = $documentUploadDir . 'player_' . $newPlayerId . '_' . $type . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES[$input]['tmp_name'], $dest)) {
                if ($hasPlayerDocumentsTable) {
                    try {
                        $stmt = $db->prepare("INSERT INTO player_documents (player_id, document_type, filename, file_path, mime_type, file_size, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))");
                        $stmt->execute([$newPlayerId, $type, basename($dest), $dest, mime_content_type($dest), filesize($dest)]);
                    } catch (Exception $e) { }
                }
            }
        };

        $docHandler('id_document', 'id');
        $docHandler('passport_document', 'passport');
        $docHandler('birth_certificate_document', 'birth_certificate');
        
        $message = 'Player added successfully!';
        $messageType = 'success';

        // Set a flash message (with payload) and redirect to players list after successful creation
            if (function_exists('set_flash')) {
                // attempt to construct a display name based on schema
                $displayName = '';
                if (!empty($insertData['first_name']) || !empty($insertData['last_name'])) {
                    $displayName = trim(($insertData['first_name'] ?? '') . ' ' . ($insertData['last_name'] ?? ''));
                } elseif (!empty($insertData['name']) || !empty($insertData['surname'])) {
                    $displayName = trim(($insertData['name'] ?? '') . ' ' . ($insertData['surname'] ?? ''));
                }
                if (!$displayName) $displayName = 'New Player';
                // store simple string flash and keep created name in a dedicated session key
                set_flash('Player added successfully', 'success');
                $_SESSION['flash_created_name'] = $displayName;
            }
        header('Location: players.php');
        exit;
        
    } catch (Exception $e) {
        $message = 'Error adding player: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Use the same soccer position codes / labels as edit_player.php for consistent data and UI
$positionOptions = [
    'GK' => 'Goalkeeper',
    'RB' => 'Right Back',
    'LB' => 'Left Back',
    'CB' => 'Centre Back',
    'RWB' => 'Right Wing Back',
    'LWB' => 'Left Wing Back',
    'CDM' => 'Central Defensive Midfielder',
    'CM' => 'Central Midfielder',
    'CAM' => 'Central Attacking Midfielder',
    'RM' => 'Right Midfielder',
    'LM' => 'Left Midfielder',
    'RW' => 'Right Winger',
    'LW' => 'Left Winger',
    'CF' => 'Centre Forward',
    'SS' => 'Second Striker',
    'ST' => 'Striker',
];

// Age groups
$ageGroups = [
    'U6', 'U7', 'U8', 'U9', 'U10', 'U11', 'U12', 'U13', 'U14', 'U15', 'U16', 'U17', 'U18', 'U19', 'U20', 'U21', 'Senior'
];

// Even age groups (for display)
$evenAgeGroups = [
    'U6', 'U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'U20'
];

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Player - VIVO United</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <?php 
    // Include dynamic CSS system
    require_once 'includes/css_helper.php';
    vivo_include_head_css($db);
    ?>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-wrapper">
            <div class="container-fluid">
                <!-- Header -->
                <div class="row mb-4">
                    <div class="col">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h3 mb-0">Add New Player</h1>
                                <p class="text-muted">Add a new player to your club</p>
                                <div id="playerNameDisplay" class="mt-2" style="display: none;">
                                    <span class="badge bg-primary fs-6" id="playerNameBadge"></span>
                                </div>
                            </div>
                            <div>
                                <a href="players.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Players
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Flash Messages -->
                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Add Player Form -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user-plus"></i> Player Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div class="row">
                                        <!-- Basic Information -->
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">First Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="surname" class="form-label">Last Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="surname" name="surname" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="nickname" class="form-label">Nickname</label>
                                            <input type="text" class="form-control" id="nickname" name="nickname">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="age" class="form-label">Age</label>
                                            <input type="number" class="form-control" id="age" name="age" min="0" readonly>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="nationality" class="form-label">Nationality</label>
                                            <input type="text" class="form-control" id="nationality" name="nationality">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="contact_number" class="form-label">Contact Number</label>
                                            <input type="tel" class="form-control" id="contact_number" name="contact_number">
                                        </div>
                                        <?php if (in_array('identity_number', $schemaColumns)): ?>
                                        <div class="col-md-6 mb-3">
                                            <label for="identity_number" class="form-label">Identity Number</label>
                                            <input type="text" class="form-control" id="identity_number" name="identity_number">
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Team & Position -->
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="team_ids" class="form-label">Teams (Select Multiple)</label>
                                            <select class="form-select" id="team_ids" name="team_ids[]" multiple size="6" style="min-height: 150px;">
                                                <?php foreach ($teams as $team): ?>
                                                <option value="<?php echo $team['id']; ?>">
                                                    <?php echo htmlspecialchars($team['name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple teams</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="jersey_number" class="form-label">Jersey Number</label>
                                            <input type="number" class="form-control" id="jersey_number" name="jersey_number" min="1" max="99">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="primary_position" class="form-label">Primary Position</label>
                                            <select class="form-select" id="primary_position" name="primary_position">
                                                    <option value="">Select Position</option>
                                                    <?php foreach ($positionOptions as $key => $label): ?>
                                                    <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($label . ' (' . $key . ')'); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="secondary_position" class="form-label">Secondary Position</label>
                                            <select class="form-select" id="secondary_position" name="secondary_position">
                                                <option value="">Select Position</option>
                                                <?php foreach ($positionOptions as $key => $label): ?>
                                                <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($label . ' (' . $key . ')'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="third_position" class="form-label">Third Position</label>
                                            <select class="form-select" id="third_position" name="third_position">
                                                <option value="">Select Position</option>
                                                <?php foreach ($positionOptions as $key => $label): ?>
                                                <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($label . ' (' . $key . ')'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted">Optional — another role they can play on the field</small>
                                        </div>
                                        <?php if (in_array('age_group', $schemaColumns)): ?>
                                        <!-- Age group is assigned automatically based on DOB / team mapping -->
                                        <?php endif; ?>
                                    </div>

                                    <!-- Physical Information -->
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="height" class="form-label">Height (cm)</label>
                                            <input type="number" class="form-control" id="height" name="height" min="100" max="250">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="weight" class="form-label">Weight (kg)</label>
                                            <input type="number" class="form-control" id="weight" name="weight" min="20" max="150">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="preferred_foot" class="form-label">Preferred Foot</label>
                                            <select class="form-select" id="preferred_foot" name="preferred_foot">
                                                <option value="">Select</option>
                                                <option value="Left">Left</option>
                                                <option value="Right">Right</option>
                                                <option value="Both">Both</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Additional Information (for extended schema) -->
                                    <?php if (in_array('favorite_player', $schemaColumns)): ?>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="favorite_player" class="form-label">Favorite Player</label>
                                            <input type="text" class="form-control" id="favorite_player" name="favorite_player">
                                        </div>
                                        <?php if (in_array('playing_style', $schemaColumns)): ?>
                                        <div class="col-md-6 mb-3">
                                            <label for="playing_style" class="form-label">Playing Style</label>
                                            <input type="text" class="form-control" id="playing_style" name="playing_style">
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (in_array('why_started_playing', $schemaColumns)): ?>
                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <label for="why_started_playing" class="form-label">Why did you start playing football?</label>
                                            <textarea class="form-control" id="why_started_playing" name="why_started_playing" rows="3"></textarea>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label for="why_like_football" class="form-label">Why do you like football?</label>
                                            <textarea class="form-control" id="why_like_football" name="why_like_football" rows="3"></textarea>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label for="personal_talents" class="form-label">Personal Talents</label>
                                            <textarea class="form-control" id="personal_talents" name="personal_talents" rows="2"></textarea>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label for="off_field_interests" class="form-label">Off-field Interests</label>
                                            <textarea class="form-control" id="off_field_interests" name="off_field_interests" rows="2"></textarea>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- File Uploads -->
                                    <div class="row mt-3">
                                        <div class="col-md-6 mb-3">
                                            <label for="profile_image" class="form-label">Profile Image (optional)</label>
                                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                                            <small class="text-muted">JPG, PNG (Max 5MB) — this will be used as player's photo.</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="id_document" class="form-label">ID / License</label>
                                            <input type="file" class="form-control" id="id_document" name="id_document" accept="application/pdf,image/*">
                                            <small class="text-muted">PDF or image (Max 10MB)</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="passport_document" class="form-label">Passport</label>
                                            <input type="file" class="form-control" id="passport_document" name="passport_document" accept="application/pdf,image/*">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="birth_certificate_document" class="form-label">Birth Certificate</label>
                                            <input type="file" class="form-control" id="birth_certificate_document" name="birth_certificate_document" accept="application/pdf,image/*">
                                        </div>
                                    </div>

                                    <!-- Social Media (for extended schema) -->
                                    <?php if (in_array('facebook_url', $schemaColumns)): ?>
                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <h6>Social Media Links</h6>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="facebook_url" class="form-label">Facebook URL</label>
                                            <input type="url" class="form-control" id="facebook_url" name="facebook_url">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="instagram_url" class="form-label">Instagram URL</label>
                                            <input type="url" class="form-control" id="instagram_url" name="instagram_url">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="twitter_url" class="form-label">Twitter URL</label>
                                            <input type="url" class="form-control" id="twitter_url" name="twitter_url">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="tiktok_url" class="form-label">TikTok URL</label>
                                            <input type="url" class="form-control" id="tiktok_url" name="tiktok_url">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="youtube_url" class="form-label">YouTube URL</label>
                                            <input type="url" class="form-control" id="youtube_url" name="youtube_url">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="linkedin_url" class="form-label">LinkedIn URL</label>
                                            <input type="url" class="form-control" id="linkedin_url" name="linkedin_url">
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Submit Buttons -->
                                    <div class="row">
                                        <div class="col-12">
                                            <hr>
                                            <div class="d-flex justify-content-between">
                                                <a href="players.php" class="btn btn-secondary">
                                                    <i class="fas fa-arrow-left"></i> Cancel
                                                </a>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> Add Player
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar with Tips -->
                    <div class="col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-lightbulb"></i> Tips
                                </h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        First and last names are required
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        Jersey numbers should be unique within a team
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        You can edit player information later
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        Upload player photos after creation
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Auto-calculate age from date of birth -->
    <script>
    document.getElementById('date_of_birth').addEventListener('change', function() {
        const dob = new Date(this.value);
        const today = new Date();
        const age = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));

        if (!isNaN(age) && age >= 0 && age < 150) {
            document.getElementById('age').value = age;

            // Suggest age group
            const agSelect = document.getElementById('age_group');
            if (agSelect) {
                let suggested = '';
                if (age <= 6) suggested = 'U6';
                else if (age <= 7) suggested = 'U7';
                else if (age <= 8) suggested = 'U8';
                else if (age <= 9) suggested = 'U9';
                else if (age <= 10) suggested = 'U10';
                else if (age <= 11) suggested = 'U11';
                else if (age <= 12) suggested = 'U12';
                else if (age <= 13) suggested = 'U13';
                else if (age <= 14) suggested = 'U14';
                else if (age <= 15) suggested = 'U15';
                else if (age <= 16) suggested = 'U16';
                else if (age <= 17) suggested = 'U17';
                else if (age <= 18) suggested = 'U18';
                else if (age <= 19) suggested = 'U19';
                else if (age <= 20) suggested = 'U20';
                else if (age <= 21) suggested = 'U21';
                else if (age <= 23) suggested = 'U23';
                else if (age >= 35) suggested = 'Over 35';
                else suggested = 'Senior';

                // Try to find and select the option
                for (let i = 0; i < agSelect.options.length; i++) {
                    if (agSelect.options[i].value === suggested) {
                        agSelect.selectedIndex = i;
                        break;
                    }
                }
            }
        }
    });

    // Update player name display as user types
    const firstNameInput = document.getElementById('name');
    const lastNameInput = document.getElementById('surname');
    const playerNameDisplay = document.getElementById('playerNameDisplay');
    const playerNameBadge = document.getElementById('playerNameBadge');

    function updatePlayerNameDisplay() {
        const firstName = firstNameInput.value.trim();
        const lastName = lastNameInput.value.trim();
        
        if (firstName || lastName) {
            const fullName = `${firstName} ${lastName}`.trim();
            playerNameBadge.textContent = fullName;
            playerNameDisplay.style.display = 'block';
        } else {
            playerNameDisplay.style.display = 'none';
        }
    }

    firstNameInput.addEventListener('input', updatePlayerNameDisplay);
    lastNameInput.addEventListener('input', updatePlayerNameDisplay);
    </script>
</body>
</html>
