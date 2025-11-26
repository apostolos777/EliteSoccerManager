<?php
require_once 'database_factory.php';
require_once 'includes/auth.php';

requireLogin();

$db = DatabaseFactory::getConnection();

// Get player ID from URL
$player_id = $_GET['id'] ?? null;
if (!$player_id) {
    header('Location: players.php');
    exit;
}

// Get player information
try {
    $stmt = $db->prepare("
        SELECT p.*, 
               t.name as team_name,
               t.age_group as team_age_group,
               t.coach_name as team_coach
        FROM players p
        LEFT JOIN teams t ON p.team_id = t.id
        WHERE p.id = ?
    ");
    
    $stmt->execute([$player_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if player is active
    if ($player && isset($player['status']) && $player['status'] !== 'active') {
        $player = null;
    }
    
} catch (Exception $e) {
    $player = null;
}

if (!$player) {
    header('Location: players.php');
    exit;
}

// Generate unique player ID if not exists
$unique_player_id = $player['unique_player_id'] ?? 'VU-' . str_pad($player['id'], 6, '0', STR_PAD_LEFT);

// Get player statistics
$career_totals = ['games' => 0, 'goals' => 0, 'assists' => 0];
try {
    $stmt = $db->prepare("
        SELECT 
            SUM(games_played) as total_games,
            SUM(goals) as total_goals,
            SUM(assists) as total_assists
        FROM player_stats 
        WHERE player_id = ?
    ");
    $stmt->execute([$player_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($stats) {
        $career_totals['games'] = (int)($stats['total_games'] ?? 0);
        $career_totals['goals'] = (int)($stats['total_goals'] ?? 0);
        $career_totals['assists'] = (int)($stats['total_assists'] ?? 0);
    }
} catch (Exception $e) {
    // Use defaults
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Card - <?= htmlspecialchars($player['name']) ?></title>
    <?php 
    require_once 'includes/css_helper.php';
    vivo_include_head_css();
    ?>
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .player-card, .player-card * {
                visibility: visible;
            }
            .player-card {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }

        .card-container {
            max-width: 400px;
            margin: 2rem auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .player-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .player-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(45deg);
        }

        .card-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .player-avatar {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0 auto 1rem auto;
            border: 4px solid rgba(255, 255, 255, 0.3);
        }

        .player-name {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .player-details {
            opacity: 0.9;
            margin-bottom: 1.5rem;
        }

        .player-details p {
            margin: 0.25rem 0;
            font-size: 0.9rem;
        }

        .stats-row {
            display: flex;
            justify-content: space-around;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 800;
            display: block;
        }

        .stat-label {
            font-size: 0.7rem;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .club-info {
            background: #f8fafc;
            padding: 1.5rem;
            text-align: center;
            color: var(--text-dark);
        }

        .club-logo {
            width: 60px;
            height: 60px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .print-actions {
            text-align: center;
            margin: 2rem 0;
        }

        .btn-print {
            background: var(--primary);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            margin: 0 0.5rem;
            text-decoration: none;
            display: inline-block;
        }

        .btn-print:hover {
            background: var(--primary-dark);
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="print-actions no-print">
        <button onclick="window.print()" class="btn-print">
            <i class="fas fa-print"></i> Print Card
        </button>
        <a href="player_profile.php?id=<?= $player_id ?>" class="btn-print">
            <i class="fas fa-arrow-left"></i> Back to Profile
        </a>
    </div>

    <div class="card-container">
        <div class="player-card">
            <div class="card-content">
                <div class="player-avatar">
                    <?php if (!empty($player['profile_image'])): ?>
                        <img src="<?= htmlspecialchars($player['profile_image']) ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    <?php else: ?>
                        <?= strtoupper(substr($player['name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                
                <div class="player-name"><?= htmlspecialchars($player['name']) ?></div>
                
                <div class="player-details">
                    <p><strong>ID:</strong> <?= htmlspecialchars($unique_player_id) ?></p>
                    <?php if (!empty($player['position'])): ?>
                        <p><strong>Position:</strong> <?= htmlspecialchars($player['position']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($player['jersey_number'])): ?>
                        <p><strong>Jersey:</strong> #<?= htmlspecialchars($player['jersey_number']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($player['age'])): ?>
                        <p><strong>Age:</strong> <?= (int)$player['age'] ?> years</p>
                    <?php endif; ?>
                </div>

                <div class="stats-row">
                    <div class="stat-item">
                        <span class="stat-number"><?= $career_totals['games'] ?></span>
                        <span class="stat-label">Games</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= $career_totals['goals'] ?></span>
                        <span class="stat-label">Goals</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= $career_totals['assists'] ?></span>
                        <span class="stat-label">Assists</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="club-info">
            <div class="club-logo">VU</div>
            <h3 style="margin: 0 0 0.5rem 0; color: var(--primary);">VIVO United</h3>
            <?php if (!empty($player['team_name'])): ?>
                <p style="margin: 0; font-weight: 600;"><?= htmlspecialchars($player['team_name']) ?></p>
            <?php endif; ?>
            <?php if (!empty($player['team_age_group'])): ?>
                <p style="margin: 0; color: #6b7280; font-size: 0.9rem;"><?= htmlspecialchars($player['team_age_group']) ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
