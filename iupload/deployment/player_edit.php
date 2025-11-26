<?php
// VIVO United Football Manager - Player Edit/Add/Delete
// Updated: August 2, 2025

require_once 'database_factory.php';

// Check authentication
if (!function_exists('isLoggedIn')) {
    require_once 'includes/auth.php';
}

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Set current page for sidebar navigation
$currentPage = 'players';

// Initialize database connection
try {
    $db = DatabaseFactory::getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

$message = '';
$messageType = '';
$player = null;
$action = $_GET['action'] ?? 'add';
$playerId = $_GET['id'] ?? null;

// Handle GET delete action (when clicking delete link)
if ($action === 'delete' && $playerId && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Soft delete - mark as inactive
        $stmt = $db->prepare("UPDATE players SET is_active = 0 WHERE id = ?");
        $stmt->execute([$playerId]);
        
        $message = "Player deleted successfully!";
        $messageType = 'success';
        header("Location: players.php?message=" . urlencode($message) . "&type=success");
        exit;
    } catch (Exception $e) {
        $message = "Error deleting player: " . $e->getMessage();
        $messageType = 'error';
        header("Location: players.php?message=" . urlencode($message) . "&type=error");
        exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    $stmt = $db->prepare("
                        INSERT INTO players (
                            vivo_id, first_name, last_name, email, phone, 
                            date_of_birth, age, position, team_id, jersey_number, 
                            height, weight, preferred_foot, nationality, address,
                            emergency_contact, phone_emergency, medical_notes, is_active
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                    ");
                    
                    $age = !empty($_POST['date_of_birth']) ? 
                        date_diff(date_create($_POST['date_of_birth']), date_create('today'))->y : ($_POST['age'] ?: null);
                    
                    $stmt->execute([
                        trim($_POST['vivo_id']) ?: null,
                        trim($_POST['first_name']),
                        trim($_POST['last_name']),
                        trim($_POST['email']) ?: null,
                        trim($_POST['phone']) ?: null,
                        $_POST['date_of_birth'] ?: null,
                        $age,
                        $_POST['position'] ?: null,
                        $_POST['team_id'] ?: null,
                        $_POST['jersey_number'] ?: null,
                        $_POST['height'] ?: null,
                        $_POST['weight'] ?: null,
                        $_POST['preferred_foot'] ?: null,
                        trim($_POST['nationality']) ?: null,
                        trim($_POST['address']) ?: null,
                        trim($_POST['emergency_contact']) ?: null,
                        trim($_POST['phone_emergency']) ?: null,
                        trim($_POST['medical_notes']) ?: null
                    ]);
                    
                    $message = "Player added successfully!";
                    $messageType = 'success';
                    header("Location: players.php?message=" . urlencode($message) . "&type=success");
                    exit;
                } catch (Exception $e) {
                    $message = "Error adding player: " . $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'edit':
                try {
                    $stmt = $db->prepare("
                        UPDATE players SET 
                            vivo_id = ?, first_name = ?, last_name = ?, email = ?, phone = ?,
                            date_of_birth = ?, age = ?, position = ?, team_id = ?, jersey_number = ?,
                            height = ?, weight = ?, preferred_foot = ?, nationality = ?, address = ?,
                            emergency_contact_name = ?, emergency_contact_phone = ?, medical_notes = ?,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");
                    
                    $age = !empty($_POST['date_of_birth']) ? 
                        date_diff(date_create($_POST['date_of_birth']), date_create('today'))->y : null;
                    
                    $stmt->execute([
                        trim($_POST['vivo_id']),
                        trim($_POST['first_name']),
                        trim($_POST['last_name']),
                        trim($_POST['email']),
                        trim($_POST['phone']),
                        $_POST['date_of_birth'] ?: null,
                        $age,
                        $_POST['position'] ?: null,
                        $_POST['team_id'] ?: null,
                        $_POST['jersey_number'] ?: null,
                        $_POST['height'] ?: null,
                        $_POST['weight'] ?: null,
                        $_POST['preferred_foot'] ?: null,
                        trim($_POST['nationality']),
                        trim($_POST['address']),
                        trim($_POST['emergency_contact_name']),
                        trim($_POST['emergency_contact_phone']),
                        trim($_POST['medical_notes']),
                        $playerId
                    ]);
                    
                    $message = "Player updated successfully!";
                    $messageType = 'success';
                    header("Location: players.php?message=" . urlencode($message) . "&type=success");
                    exit;
                } catch (Exception $e) {
                    $message = "Error updating player: " . $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'delete':
                try {
                    // Soft delete - mark as inactive
                    $stmt = $db->prepare("UPDATE players SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$playerId]);
                    
                    $message = "Player deleted successfully!";
                    $messageType = 'success';
                    header("Location: players.php?message=" . urlencode($message) . "&type=success");
                    exit;
                } catch (Exception $e) {
                    $message = "Error deleting player: " . $e->getMessage();
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Load player data for editing or deletion
if (($action === 'edit' || $action === 'delete') && $playerId) {
    // For delete, allow inactive records; for edit, only active
    if ($action === 'edit') {
        $sql = "SELECT * FROM players WHERE id = ? AND is_active = 1";
    } else {
        $sql = "SELECT * FROM players WHERE id = ?";
    }
    $stmt = $db->prepare($sql);
    $stmt->execute([$playerId]);
    $player = $stmt->fetch();
    if (!$player) {
        header("Location: players.php?message=" . urlencode("Player not found") . "&type=error");
        exit;
    }
}

// Get teams for dropdown
$teams = $db->query("SELECT id, name FROM teams ORDER BY name")->fetchAll();

// Define positions
$positions = [
    'Goalkeeper', 'Centre-Back', 'Left-Back', 'Right-Back', 'Defensive Midfielder',
    'Central Midfielder', 'Attacking Midfielder', 'Left Winger', 'Right Winger',
    'Centre-Forward', 'Striker'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ucfirst($action) ?> Player - VIVO United Manager</title>
    <link rel="stylesheet" href="css/vivo-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .form-header h1 {
            color: #dc2626;
            margin: 0;
            font-size: 1.8em;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            margin-bottom: 4px;
            font-weight: 600;
            color: #374151;
            font-size: 0.85em;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-size: 0.85em;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #dc2626;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #f0f0f0;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #dc2626;
            color: white;
        }
        
        .btn-primary:hover {
            background: #b91c1c;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .alert {
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 12px;
            font-weight: 500;
            font-size: 0.85em;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .delete-confirmation {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 12px;
        }
        
        .delete-confirmation h3 {
            color: #dc2626;
            margin: 0 0 6px 0;
            font-size: 1.1em;
        }
        
        .delete-confirmation p {
            color: #4b5563;
            margin: 0 0 8px 0;
            font-size: 0.85em;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 8px;
            }
            
            .form-container {
                padding: 10px;
                margin: 10px;
            }
            
            .form-header h1 {
                font-size: 1.5em;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 8px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="compact-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <div class="form-header">
                <h1>
                    <i class="fas fa-user-<?= $action === 'add' ? 'plus' : ($action === 'edit' ? 'edit' : 'times') ?>"></i>
                    <?= ucfirst($action) ?> Player
                </h1>
            </div>
            
            <?php if ($action === 'delete'): ?>
                <div class="delete-confirmation">
                    <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
                    <p>Are you sure you want to delete <strong><?= htmlspecialchars($player['first_name'] . ' ' . $player['last_name']) ?></strong>?</p>
                    <p>This action will deactivate the player but preserve their data for historical records.</p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <div class="form-actions">
                        <a href="players.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Player
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="action" value="<?= $action ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="vivo_id">VIVO ID</label>
                            <input type="text" id="vivo_id" name="vivo_id" 
                                   value="<?= htmlspecialchars($player['vivo_id'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="jersey_number">Jersey Number</label>
                            <input type="number" id="jersey_number" name="jersey_number" 
                                   value="<?= htmlspecialchars($player['jersey_number'] ?? '') ?>" min="1" max="99">
                        </div>
                        
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required
                                   value="<?= htmlspecialchars($player['first_name'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required
                                   value="<?= htmlspecialchars($player['last_name'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email"
                                   value="<?= htmlspecialchars($player['email'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone"
                                   value="<?= htmlspecialchars($player['phone'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth"
                                   value="<?= htmlspecialchars($player['date_of_birth'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="nationality">Nationality</label>
                            <input type="text" id="nationality" name="nationality"
                                   value="<?= htmlspecialchars($player['nationality'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="position">Position</label>
                            <select id="position" name="position">
                                <option value="">Select Position</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?= $pos ?>" <?= ($player['position'] ?? '') === $pos ? 'selected' : '' ?>>
                                        <?= $pos ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="team_id">Team</label>
                            <select id="team_id" name="team_id">
                                <option value="">Select Team</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= $team['id'] ?>" <?= ($player['team_id'] ?? '') == $team['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($team['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="height">Height (cm)</label>
                            <input type="number" id="height" name="height" step="0.1"
                                   value="<?= htmlspecialchars($player['height'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="weight">Weight (kg)</label>
                            <input type="number" id="weight" name="weight" step="0.1"
                                   value="<?= htmlspecialchars($player['weight'] ?? '') ?>">
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
                            <label for="emergency_contact_name">Emergency Contact Name</label>
                            <input type="text" id="emergency_contact_name" name="emergency_contact_name"
                                   value="<?= htmlspecialchars($player['emergency_contact_name'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="emergency_contact_phone">Emergency Contact Phone</label>
                            <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone"
                                   value="<?= htmlspecialchars($player['emergency_contact_phone'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="address">Address</label>
                            <textarea id="address" name="address"><?= htmlspecialchars($player['address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="medical_notes">Medical Notes</label>
                            <textarea id="medical_notes" name="medical_notes"><?= htmlspecialchars($player['medical_notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="players.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Players
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?= $action === 'add' ? 'Add Player' : 'Update Player' ?>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="js/sidebar.js"></script>
</body>
</html>
