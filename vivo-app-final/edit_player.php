<?php
// Modern database connection with club settings support
require_once 'includes/color_system.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentPage = 'players';

try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}
$message = '';
$messageType = '';

// Get player ID from URL
$player_id = $_GET['id'] ?? null;

if (!$player_id) {
    header('Location: players.php');
    exit;
}

// Get teams for dropdown
$teams = $db->query("SELECT * FROM teams ORDER BY name")->fetchAll();

// Age groups for player selection (centralized)
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

// Provide a friendly alias used by some older forms
$availableColumns = $schemaColumns;

// Determine which naming convention is used
$hasFirstLastName = in_array('first_name', $schemaColumns) && in_array('last_name', $schemaColumns);
$hasNameSurname = in_array('name', $schemaColumns) && in_array('surname', $schemaColumns);

// Fetch existing player data
try {
    $stmt = $db->prepare("SELECT p.*, t.name as team_name FROM players p 
                         LEFT JOIN teams t ON p.team_id = t.id 
                         WHERE p.id = ?");
    $stmt->execute([$player_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$player) {
        header('Location: players.php');
        exit;
    }
    
    // Normalize player data for consistent access regardless of schema
    if ($hasFirstLastName && !$hasNameSurname) {
        // Production schema: map first_name/last_name to name/surname for form compatibility
        $player['name'] = $player['first_name'] ?? '';
        $player['surname'] = $player['last_name'] ?? '';
    }
    
} catch (Exception $e) {
    die("Error fetching player data: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Build update data based on available schema
        $updateData = [];
        
        // Validate name fields FIRST before processing
        $firstName = trim($_POST['name'] ?? '');
        $lastName = trim($_POST['surname'] ?? '');
        
        if (empty($firstName) || empty($lastName)) {
            throw new Exception("First name and last name are required.");
        }
        
        // Handle name fields based on schema
        if ($hasFirstLastName && !$hasNameSurname) {
            // Production schema: use first_name/last_name
            $updateData['first_name'] = $firstName;
            $updateData['last_name'] = $lastName;
        } else {
            // Local schema: use name/surname
            $updateData['name'] = $firstName;
            $updateData['surname'] = $lastName;
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
            'age_group' => $_POST['age_group'] ?? '',
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
        
        // Add common fields to update data
        foreach ($commonFields as $field => $value) {
            if (!in_array($field, $schemaColumns)) continue;
            // Ensure we pass a string to trim() to avoid PHP 8+ deprecation when null is used
            $trimmedValue = trim((string)$value);
            if ($field === 'team_id') {
                if ($trimmedValue !== '') {
                    $updateData[$field] = (int)$trimmedValue;
                }
            } elseif (in_array($field, ['age', 'height', 'weight', 'jersey_number'])) {
                if ($trimmedValue !== '') {
                    $updateData[$field] = (int)$trimmedValue;
                }
            } else {
                if ($trimmedValue !== '') {
                    $updateData[$field] = $trimmedValue;
                }
            }
        }
        
        // Add extended fields if they exist in schema (for local database)
        // Note: age_group is view-only and not written from this form to avoid blocking saves
        foreach ($extendedFields as $field => $value) {
            if ($field === 'age_group') continue;
            if (in_array($field, $schemaColumns)) {
                $trimmedValue = trim((string)$value);
                $updateData[$field] = !empty($trimmedValue) ? $trimmedValue : null;
            }
        }

    // No age limit validation (allow any age)

        // Always set status to active if the column exists
        if (in_array('status', $schemaColumns)) {
            $updateData['status'] = 'active';
        }
        if (in_array('is_active', $schemaColumns)) {
            $updateData['is_active'] = 1;
        }
        
        // Calculate age from date of birth if provided
        if (!empty($_POST['date_of_birth']) && in_array('age', $availableColumns)) {
            $dob = new DateTime($_POST['date_of_birth']);
            $now = new DateTime();
            $updateData['age'] = $now->diff($dob)->y;
        } elseif (!empty($_POST['age']) && in_array('age', $availableColumns)) {
            $updateData['age'] = (int)$_POST['age'];
        }

        // If no team selected, attempt to auto-assign based on DOB-derived age
        if (empty($updateData['team_id']) && !empty($_POST['date_of_birth'])) {
            $age = isset($updateData['age']) ? (int)$updateData['age'] : null;
            if ($age !== null) {
                if ($age <= 6) $suggest = 'U6';
                elseif ($age <= 7) $suggest = 'U7';
                elseif ($age <= 8) $suggest = 'U8';
                elseif ($age <= 9) $suggest = 'U9';
                elseif ($age <= 10) $suggest = 'U10';
                elseif ($age <= 11) $suggest = 'U11';
                elseif ($age <= 12) $suggest = 'U12';
                elseif ($age <= 13) $suggest = 'U13';
                elseif ($age <= 14) $suggest = 'U14';
                elseif ($age <= 15) $suggest = 'U15';
                elseif ($age <= 16) $suggest = 'U16';
                elseif ($age <= 17) $suggest = 'U17';
                elseif ($age <= 18) $suggest = 'U18';
                elseif ($age <= 19) $suggest = 'U19';
                elseif ($age <= 20) $suggest = 'U20';
                elseif ($age <= 21) $suggest = 'U21';
                elseif ($age <= 23) $suggest = 'U23';
                elseif ($age >= 35) $suggest = 'Over 35';
                else $suggest = 'Senior';

                try {
                    $tstmt = $db->prepare("SELECT id FROM teams WHERE age_group = ? ORDER BY name LIMIT 1");
                    $tstmt->execute([$suggest]);
                    $teamRow = $tstmt->fetch(PDO::FETCH_ASSOC);
                    if ($teamRow) $updateData['team_id'] = $teamRow['id'];
                } catch (Exception $e) {
                    // ignore
                }
            }
        }
        
        // Handle file uploads
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/players/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $unique_id = 'player_' . $player_id;
            $extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $profile_image_path = $upload_dir . $unique_id . '_profile.' . $extension;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $profile_image_path)) {
                if (in_array('profile_image', $availableColumns)) {
                    $updateData['profile_image'] = $profile_image_path;
                }
            }
        }
        
        if (isset($_FILES['introduction_video']) && $_FILES['introduction_video']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/players/videos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $unique_id = 'player_' . $player_id;
            $extension = pathinfo($_FILES['introduction_video']['name'], PATHINFO_EXTENSION);
            $video_path = $upload_dir . $unique_id . '_intro.' . $extension;
            
            if (move_uploaded_file($_FILES['introduction_video']['tmp_name'], $video_path)) {
                if (in_array('introduction_video_url', $availableColumns)) {
                    $updateData['introduction_video_url'] = $video_path;
                }
            }
        }
        
        // Check for duplicate jersey number in the same team (only if both fields have values)
        if (!empty($updateData['jersey_number']) && !empty($updateData['team_id'])) {
            $checkStmt = $db->prepare("SELECT id FROM players WHERE jersey_number = ? AND team_id = ? AND id != ?");
            $checkStmt->execute([$updateData['jersey_number'], $updateData['team_id'], $player_id]);
            
            if ($checkStmt->fetch()) {
                throw new Exception("Jersey number {$updateData['jersey_number']} is already taken by another player in this team.");
            }
        }
        
        // Always execute update (even if just clearing fields)
        if (!empty($updateData)) {
            $setClause = [];
            $values = [];
            foreach ($updateData as $column => $value) {
                $setClause[] = "$column = ?";
                $values[] = $value;
            }
            $values[] = $player_id;
            
            $sql = "UPDATE players SET " . implode(', ', $setClause) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($values);
            
            $message = "Player profile updated successfully!";
            $messageType = "success";
            
            // Refresh player data
            $stmt = $db->prepare("SELECT p.*, t.name as team_name FROM players p 
                                 LEFT JOIN teams t ON p.team_id = t.id 
                                 WHERE p.id = ?");
            $stmt->execute([$player_id]);
            $player = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $message = "No changes to save.";
            $messageType = "info";
        }
        
    } catch (Exception $e) {
        $message = "Error updating player profile: " . $e->getMessage();
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Player Profile - VIVO United</title>
    <?php 
    require_once 'includes/css_helper.php';
    vivo_include_head_css($db);
    ?>
    <style>
        .form-section {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: var(--space-8);
            margin-bottom: var(--space-6);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--neutral-200);
        }
        .form-section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--neutral-900);
            margin-bottom: var(--space-6);
            padding-bottom: var(--space-3);
            border-bottom: 2px solid var(--primary);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-4);
        }
        .form-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: var(--space-4);
        }
        .form-group {
            margin-bottom: var(--space-4);
        }
        .form-group label {
            display: block;
            margin-bottom: var(--space-2);
            font-weight: 600;
            color: var(--neutral-700);
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: var(--space-3);
            border: 1px solid var(--neutral-300);
            border-radius: var(--radius-md);
            font-size: 1rem;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(220, 38, 127, 0.1);
        }
        /* Buttons now use global styles from vivo-style.css */
        .alert {
            padding: var(--space-4);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-6);
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f1aeb5;
        }
        @media (max-width: 768px) {
            .form-grid, .form-grid-3 {
                grid-template-columns: 1fr;
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
                    <i class="fas fa-user-edit"></i>
                    Edit Player Profile
                </h1>
                <p class="page-subtitle">Update player information - only fill in fields with information</p>
                <?php 
                $playerName = trim(($player['name'] ?? '') . ' ' . ($player['surname'] ?? ''));
                if ($playerName): 
                ?>
                <div class="mt-2">
                    <span class="badge" style="background: var(--primary); color: white; font-size: 1rem; padding: 0.5rem 1rem;">
                        <?= htmlspecialchars($playerName) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <div class="page-actions">
                <a href="players.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Players
                </a>
                <a href="player_profile.php?id=<?= $player_id ?>" class="btn-secondary">
                    <i class="fas fa-eye"></i> View Profile
                </a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <!-- Basic Information -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <i class="fas fa-user"></i> Basic Information
                </h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">First Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" required value="<?= htmlspecialchars($player['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="surname">Last Name <span class="text-danger">*</span></label>
                        <input type="text" id="surname" name="surname" required value="<?= htmlspecialchars($player['surname'] ?? '') ?>">
                    </div>
                        <!-- Age group is assigned automatically based on DOB / team mapping -->
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($player['date_of_birth'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="age">Age</label>
                        <input type="number" id="age" name="age" min="0" placeholder="Calculated from DOB" value="<?= htmlspecialchars($player['age'] ?? '') ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="nationality">Nationality</label>
                        <input type="text" id="nationality" name="nationality" placeholder="e.g., South African" value="<?= htmlspecialchars($player['nationality'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="tel" id="contact_number" name="contact_number" placeholder="+27 XX XXX XXXX" value="<?= htmlspecialchars($player['contact_number'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Football Information -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <i class="fas fa-futbol"></i> Football Information
                </h2>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label for="primary_position">Primary Position</label>
                        <select id="primary_position" name="primary_position">
                            <option value="">Select Position</option>
                            <option value="Goalkeeper" <?= ($player['primary_position'] ?? '') === 'Goalkeeper' ? 'selected' : '' ?>>Goalkeeper</option>
                            <option value="Defender" <?= ($player['primary_position'] ?? '') === 'Defender' ? 'selected' : '' ?>>Defender</option>
                            <option value="Midfielder" <?= ($player['primary_position'] ?? '') === 'Midfielder' ? 'selected' : '' ?>>Midfielder</option>
                            <option value="Forward" <?= ($player['primary_position'] ?? '') === 'Forward' ? 'selected' : '' ?>>Forward</option>
                        </select>
                    </div>
                    <?php if (in_array('age_group', $schemaColumns)): ?>
                    <div class="form-group">
                        <label for="age_group">Age Group</label>
                        <select id="age_group" name="age_group" class="form-control">
                            <option value="">Select Age Group</option>
                            <?php foreach ($ageGroups as $ag): ?>
                                <option value="<?= htmlspecialchars($ag) ?>" <?= (isset($player['age_group']) && $player['age_group'] === $ag) ? 'selected' : '' ?>><?= htmlspecialchars($ag) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="secondary_position">Secondary Position</label>
                        <select id="secondary_position" name="secondary_position">
                            <option value="">Select Position</option>
                            <option value="Goalkeeper" <?= ($player['secondary_position'] ?? '') === 'Goalkeeper' ? 'selected' : '' ?>>Goalkeeper</option>
                            <option value="Defender" <?= ($player['secondary_position'] ?? '') === 'Defender' ? 'selected' : '' ?>>Defender</option>
                            <option value="Midfielder" <?= ($player['secondary_position'] ?? '') === 'Midfielder' ? 'selected' : '' ?>>Midfielder</option>
                            <option value="Forward" <?= ($player['secondary_position'] ?? '') === 'Forward' ? 'selected' : '' ?>>Forward</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="preferred_foot">Preferred Foot</label>
                        <select id="preferred_foot" name="preferred_foot">
                            <option value="">Select Foot</option>
                            <option value="Left" <?= ($player['preferred_foot'] ?? '') === 'Left' ? 'selected' : '' ?>>Left</option>
                            <option value="Right" <?= ($player['preferred_foot'] ?? '') === 'Right' ? 'selected' : '' ?>>Right</option>
                            <option value="Both" <?= ($player['preferred_foot'] ?? '') === 'Both' ? 'selected' : '' ?>>Both</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="jersey_number">Jersey Number</label>
                        <input type="number" id="jersey_number" name="jersey_number" min="1" max="99" placeholder="e.g., 10" value="<?= htmlspecialchars($player['jersey_number'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="team_ids">Teams (Select Multiple)</label>
                        <?php 
                        $playerTeamIds = [];
                        if (!empty($player['team_ids'])) {
                            $playerTeamIds = explode(',', $player['team_ids']);
                        } elseif (!empty($player['team_id'])) {
                            $playerTeamIds = [$player['team_id']];
                        }
                        ?>
                        <select id="team_ids" name="team_ids[]" multiple style="min-height: 150px; width: 100%; padding: 0.5rem; border: 1px solid var(--neutral-300); border-radius: var(--radius-md);">
                            <?php foreach ($teams as $team): ?>
                                <option value="<?= $team['id'] ?>" <?= in_array($team['id'], $playerTeamIds) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($team['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="display: block; margin-top: 0.5rem; color: var(--neutral-600);">Hold Ctrl (Windows) or Cmd (Mac) to select multiple teams</small>
                    </div>
                    <div class="form-group">
                        <label for="favorite_player">Favorite Player</label>
                        <input type="text" id="favorite_player" name="favorite_player" placeholder="e.g., Lionel Messi" value="<?= htmlspecialchars($player['favorite_player'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Personal Details -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <i class="fas fa-info-circle"></i> Personal Details
                </h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="height">Height (cm)</label>
                        <input type="number" id="height" name="height" min="100" max="250" placeholder="e.g., 175" value="<?= htmlspecialchars($player['height'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="weight">Weight (kg)</label>
                        <input type="number" id="weight" name="weight" min="30" max="150" placeholder="e.g., 70" value="<?= htmlspecialchars($player['weight'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="nickname">Nickname</label>
                        <input type="text" id="nickname" name="nickname" placeholder="Player's nickname" value="<?= htmlspecialchars($player['nickname'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="identity_number">ID/Passport Number</label>
                        <input type="text" id="identity_number" name="identity_number" placeholder="National ID / Passport" value="<?= htmlspecialchars($player['identity_number'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Player Story -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <i class="fas fa-book"></i> Player Story
                </h2>
                <div class="form-group">
                    <label for="playing_style">Playing Style</label>
                    <textarea id="playing_style" name="playing_style" rows="3" placeholder="Describe your playing style..."><?= htmlspecialchars($player['playing_style'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="why_started_playing">Why did you start playing football?</label>
                    <textarea id="why_started_playing" name="why_started_playing" rows="3" placeholder="Tell us your football journey..."><?= htmlspecialchars($player['why_started_playing'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="why_like_football">What do you love about football?</label>
                    <textarea id="why_like_football" name="why_like_football" rows="3" placeholder="What makes football special to you..."><?= htmlspecialchars($player['why_like_football'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Social Media -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <i class="fas fa-share-alt"></i> Social Media
                </h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="facebook_url">Facebook URL</label>
                        <input type="url" id="facebook_url" name="facebook_url" placeholder="https://facebook.com/username" value="<?= htmlspecialchars($player['facebook_url'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="instagram_url">Instagram URL</label>
                        <input type="url" id="instagram_url" name="instagram_url" placeholder="https://instagram.com/username" value="<?= htmlspecialchars($player['instagram_url'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="twitter_url">Twitter URL</label>
                        <input type="url" id="twitter_url" name="twitter_url" placeholder="https://twitter.com/username" value="<?= htmlspecialchars($player['twitter_url'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="tiktok_url">TikTok URL</label>
                        <input type="url" id="tiktok_url" name="tiktok_url" placeholder="https://tiktok.com/@username" value="<?= htmlspecialchars($player['tiktok_url'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Media Upload -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <i class="fas fa-camera"></i> Media Upload
                </h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="profile_image">Profile Image</label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*">
                        <?php if (!empty($player['profile_image'])): ?>
                            <p class="text-sm text-gray-600">Current: <?= basename($player['profile_image']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="introduction_video">Introduction Video</label>
                        <input type="file" id="introduction_video" name="introduction_video" accept="video/*">
                        <?php if (!empty($player['introduction_video_url'])): ?>
                            <p class="text-sm text-gray-600">Current: <?= basename($player['introduction_video_url']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="players.php" class="btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        // Auto-calculate age from date of birth and suggest age group
        document.getElementById('date_of_birth').addEventListener('change', function() {
            const dob = new Date(this.value);
            const today = new Date();
            const age = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));
            if (!isNaN(age) && age >= 0 && age < 150) {
                document.getElementById('age').value = age;

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

                    for (let i = 0; i < agSelect.options.length; i++) {
                        if (agSelect.options[i].value === suggested) {
                            agSelect.selectedIndex = i;
                            break;
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
