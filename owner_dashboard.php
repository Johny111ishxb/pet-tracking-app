<?php
// owner_dashboard.php (ROOT FOLDER)
session_start();
if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_auto_include.php';

// Handle pet status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['pet_id'])) {
    $pet_id = $_POST['pet_id'];
    $action = $_POST['action'];
    
    // Verify the pet belongs to the current owner
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE pet_id = ? AND owner_id = ?");
    $stmt->execute([$pet_id, $_SESSION['owner_id']]);
    $pet = $stmt->fetch();
    
    if ($pet) {
        if ($action === 'mark_lost') {
            $stmt = $pdo->prepare("UPDATE pets SET status = 'lost', updated_at = CURRENT_TIMESTAMP WHERE pet_id = ?");
            $stmt->execute([$pet_id]);
        } elseif ($action === 'mark_found') {
            $stmt = $pdo->prepare("UPDATE pets SET status = 'active', updated_at = CURRENT_TIMESTAMP WHERE pet_id = ?");
            $stmt->execute([$pet_id]);
        }
        
        // Redirect to avoid form resubmission
        header("Location: owner_dashboard.php");
        exit();
    }
}

// Get stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE owner_id = ?");
$stmt->execute([$_SESSION['owner_id']]);
$totalPets = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE owner_id = ? AND status = 'lost'");
$stmt->execute([$_SESSION['owner_id']]);
$lostPets = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM scans WHERE pet_id IN (SELECT pet_id FROM pets WHERE owner_id = ?) AND scanned_at >= CURRENT_TIMESTAMP - INTERVAL '7 days'");
$stmt->execute([$_SESSION['owner_id']]);
$recentScans = $stmt->fetchColumn();

// Get recent scans with details
$stmt = $pdo->prepare("SELECT s.scanned_at, p.name as pet_name, p.species, p.breed, p.status FROM scans s JOIN pets p ON s.pet_id = p.pet_id WHERE p.owner_id = ? ORDER BY s.scanned_at DESC LIMIT 5");
$stmt->execute([$_SESSION['owner_id']]);
$recentScanDetails = $stmt->fetchAll();

// Get pets with their status
$stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM scans WHERE pet_id = p.pet_id AND scanned_at >= CURRENT_TIMESTAMP - INTERVAL '7 days') as recent_scans,
           (SELECT MAX(scanned_at) FROM scans WHERE pet_id = p.pet_id) as last_seen
    FROM pets p 
    WHERE p.owner_id = ? 
    ORDER BY p.status DESC, p.name
");
$stmt->execute([$_SESSION['owner_id']]);
$pets = $stmt->fetchAll();

// Calculate pet status for display
foreach ($pets as &$pet) {
    // If pet is marked as lost, show lost status
    if ($pet['status'] === 'lost') {
        $pet['display_status'] = 'lost';
        $pet['status_text'] = 'Lost - Needs Help!';
        continue;
    }
    
    // Otherwise calculate based on last scan
    if ($pet['last_seen']) {
        $lastSeen = new DateTime($pet['last_seen']);
        $now = new DateTime();
        $diff = $now->diff($lastSeen);
        
        if ($diff->days < 1) {
            $pet['display_status'] = 'safe';
            $pet['status_text'] = 'Recently seen';
        } elseif ($diff->days < 3) {
            $pet['display_status'] = 'warning';
            $pet['status_text'] = 'Seen ' . $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
        } else {
            $pet['display_status'] = 'danger';
            $pet['status_text'] = 'Not seen recently';
        }
    } else {
        $pet['display_status'] = 'unknown';
        $pet['status_text'] = 'Never scanned';
    }
}
unset($pet);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pawsitive Patrol – Owner Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ======================
           DASHBOARD LAYOUT
           ====================== */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            background: white;
            border-right: 1px solid #e0e0e0;
            box-shadow: 2px 0 8px rgba(0,0,0,0.05);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-header {
            padding: 20px 24px;
            border-bottom: 1px solid #eee;
            background: #f9f9f9;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-logo {
            width: 40px;
            height: 40px;
            background: #4a6fa5;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .sidebar-brand {
            font-weight: 700;
            font-size: 18px;
            color: #333;
        }

        .sidebar-user {
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #eee;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: #eef4ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #4a6fa5;
        }

        .user-info {
            line-height: 1.4;
        }

        .user-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .user-email {
            font-size: 14px;
            color: #777;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin: 0 16px 8px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            text-decoration: none;
            color: #444;
            border-radius: 8px;
            transition: background 0.2s, color 0.2s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #4a6fa5;
            color: white;
        }

        .sidebar-menu a.active {
            font-weight: 600;
        }

        .sidebar-menu .icon {
            font-size: 20px;
            width: 24px;
            text-align: center;
        }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
            overflow-y: auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
        }

        .add-pet-btn {
            background: #2c3e50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
            text-decoration: none;
        }

        .add-pet-btn:hover {
            background: #1a252f;
        }

        .welcome-text {
            font-size: 18px;
            color: #555;
            margin-bottom: 20px;
        }

        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-info {
            display: flex;
            flex-direction: column;
        }

        .stat-label {
            font-size: 14px;
            color: #777;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
        }

        .stat-subtext {
            font-size: 13px;
            color: #999;
            margin-top: 4px;
        }

        .stat-icon {
            font-size: 28px;
            color: #4a6fa5;
        }

        .stat-card-lost {
            border-left: 4px solid #dc3545;
        }

        .stat-card-lost .stat-value {
            color: #dc3545;
        }

        /* PET CARDS */
        .pets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .pet-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            border: 2px solid transparent;
        }

        .pet-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .pet-card-lost {
            border-color: #dc3545;
            background: linear-gradient(135deg, #fff 0%, #fff5f5 100%);
        }

        .pet-header {
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #f0f0f0;
        }

        .pet-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #eef4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #4a6fa5;
        }

        .pet-avatar-lost {
            background: #ffe6e6;
            color: #dc3545;
        }

        .pet-info {
            flex: 1;
        }

        .pet-name {
            font-weight: 600;
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .pet-details {
            font-size: 14px;
            color: #777;
        }

        .pet-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-safe {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-warning {
            background: #fff3e0;
            color: #ef6c00;
        }

        .status-danger {
            background: #ffebee;
            color: #c62828;
        }

        .status-unknown {
            background: #f5f5f5;
            color: #757575;
        }

        .status-lost {
            background: #dc3545;
            color: white;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .pet-body {
            padding: 16px;
        }

        .pet-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .pet-stat {
            text-align: center;
        }

        .pet-stat-value {
            font-weight: 700;
            font-size: 18px;
            color: #2c3e50;
        }

        .pet-stat-label {
            font-size: 12px;
            color: #777;
        }

        .pet-actions {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }

        .pet-btn {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            text-decoration: none;
        }

        .pet-btn-primary {
            background: #4a6fa5;
            color: white;
        }

        .pet-btn-primary:hover {
            background: #3a5a85;
        }

        .pet-btn-secondary {
            background: #f0f0f0;
            color: #555;
        }

        .pet-btn-secondary:hover {
            background: #e0e0e0;
        }

        .status-controls {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
        }

        .status-btn {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .status-btn-lost {
            background: #dc3545;
            color: white;
        }

        .status-btn-lost:hover {
            background: #c82333;
        }

        .status-btn-found {
            background: #28a745;
            color: white;
        }

        .status-btn-found:hover {
            background: #218838;
        }

        .qr-status {
            text-align: center;
            padding: 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .qr-active {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .qr-inactive {
            background: #fff3e0;
            color: #ef6c00;
            border: 1px solid #ffe0b2;
        }

        /* ACTIVITY FEED */
        .activity-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #eef4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4a6fa5;
            font-size: 14px;
        }

        .activity-content {
            flex: 1;
        }

        .activity-text {
            margin-bottom: 4px;
            color: #444;
        }

        .activity-time {
            font-size: 12px;
            color: #777;
        }

        /* EMPTY STATES */
        .empty-pets {
            text-align: center;
            padding: 40px 20px;
            color: #777;
        }

        .empty-icon {
            font-size: 60px;
            margin-bottom: 20px;
            color: #ddd;
        }

        .empty-text {
            font-size: 18px;
            margin-bottom: 10px;
            color: #555;
        }

        .empty-subtext {
            font-size: 14px;
            color: #777;
            max-width: 300px;
            margin: 0 auto 20px;
        }

        .add-first-pet-btn {
            background: #2c3e50;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
            text-decoration: none;
            margin: 0 auto;
        }

        .add-first-pet-btn:hover {
            background: #1a252f;
        }

        /* PETS SECTION HEADER */
        .pets-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-actions {
            display: flex;
            gap: 10px;
        }

        .section-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .section-btn-primary {
            background: #4a6fa5;
            color: white;
        }

        .section-btn-primary:hover {
            background: #3a5a85;
        }

        .section-btn-secondary {
            background: #f0f0f0;
            color: #555;
        }

        .section-btn-secondary:hover {
            background: #e0e0e0;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: static;
                height: auto;
                box-shadow: none;
                border-bottom: 1px solid #e0e0e0;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .page-header {
                flex-direction: column;
                gap: 16px;
            }

            .add-pet-btn {
                width: 100%;
                justify-content: center;
            }

            .pets-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .section-actions {
                width: 100%;
                justify-content: space-between;
            }

            .section-btn {
                flex: 1;
                justify-content: center;
            }

            .pet-actions {
                flex-direction: column;
            }

            .status-controls {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">🐾</div>
            <div class="sidebar-brand">Pawsitive Patrol</div>
        </div>

        <div class="sidebar-user">
            <div class="user-avatar">👤</div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></div>
                <div class="user-email"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></div>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li><a href="owner_dashboard.php" class="active"><span class="icon">🏠</span> Dashboard</a></li>
            <li><a href="includes/add_pet.php"><span class="icon">➕</span> Add Pet</a></li>
            <li><a href="includes/view_pets.php"><span class="icon">📋</span> My Pets</a></li>
            <li><a href="includes/scan_report.php"><span class="icon">📊</span> Scan Reports</a></li>
            <li><a href="includes/settings.php"><span class="icon">⚙️</span> Settings</a></li>
            <li>
                <form action="includes/logout.php" method="POST" style="margin:0;">
                    <button type="submit" style="background:none; border:none; padding:0; width:100%; text-align:left; color:#444; display:flex; align-items:center; gap:12px; padding:12px 16px; border-radius:8px; transition:background 0.2s; cursor:pointer;">
                        <span class="icon">🚪</span> Logout
                    </button>
                </form>
            </li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="welcome-text">Welcome back, <strong><?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></strong>!<br>Manage your pets and their QR code status here.</p>
            </div>
            <a href="includes/add_pet.php" class="add-pet-btn">➕ Add Pet</a>
        </div>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Total Pets</div>
                    <div class="stat-value"><?= $totalPets ?></div>
                    <div class="stat-subtext">Registered in your account</div>
                </div>
                <div class="stat-icon">🐾</div>
            </div>

            <div class="stat-card <?= $lostPets > 0 ? 'stat-card-lost' : '' ?>">
                <div class="stat-info">
                    <div class="stat-label">Lost Pets</div>
                    <div class="stat-value"><?= $lostPets ?></div>
                    <div class="stat-subtext">Currently marked as lost</div>
                </div>
                <div class="stat-icon">🚨</div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Recent Scans</div>
                    <div class="stat-value"><?= $recentScans ?></div>
                    <div class="stat-subtext">In the last 7 days</div>
                </div>
                <div class="stat-icon">📍</div>
            </div>
        </div>

        <!-- YOUR PETS -->
        <div class="pets-section">
            <div class="section-title">
                <span>Your Pets (<?= $totalPets ?>)</span>
                <?php if ($totalPets > 0): ?>
                <div class="section-actions">
                    <a href="includes/view_pets.php" class="section-btn section-btn-primary">
                        <i class="fas fa-list"></i> View All
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($totalPets === 0): ?>
                <div class="empty-pets">
                    <div class="empty-icon">🐶</div>
                    <div class="empty-text">No pets registered yet</div>
                    <div class="empty-subtext">Add your first pet to get started with QR identification</div>
                    <a href="includes/add_pet.php" class="add-first-pet-btn">➕ Add Your First Pet</a>
                </div>
            <?php else: ?>
                <div class="pets-grid">
                    <?php foreach ($pets as $pet): ?>
                        <div class="pet-card <?= $pet['status'] === 'lost' ? 'pet-card-lost' : '' ?>">
                            <div class="pet-header">
                                <div class="pet-avatar <?= $pet['status'] === 'lost' ? 'pet-avatar-lost' : '' ?>">
                                    <?= $pet['type'] == 'Dog' ? '🐕' : ($pet['type'] == 'Cat' ? '🐈' : '🐾') ?>
                                </div>
                                <div class="pet-info">
                                    <div class="pet-name"><?= htmlspecialchars($pet['name']) ?></div>
                                    <div class="pet-details"><?= htmlspecialchars($pet['breed'] ?? 'Unknown') ?> • <?= htmlspecialchars($pet['color'] ?? 'Unknown') ?></div>
                                </div>
                                <div class="pet-status status-<?= $pet['display_status'] ?>"><?= $pet['status_text'] ?></div>
                            </div>
                            <div class="pet-body">
                                <div class="pet-stats">
                                    <div class="pet-stat">
                                        <div class="pet-stat-value"><?= $pet['recent_scans'] ?></div>
                                        <div class="pet-stat-label">Scans (7d)</div>
                                    </div>
                                    <div class="pet-stat">
                                        <div class="pet-stat-value">
                                            <?= $pet['last_seen'] ? date('M j', strtotime($pet['last_seen'])) : 'Never' ?>
                                        </div>
                                        <div class="pet-stat-label">Last Seen</div>
                                    </div>
                                </div>

                                <!-- QR Code Status -->
                                <div class="qr-status <?= $pet['status'] === 'lost' ? 'qr-active' : 'qr-inactive' ?>">
                                    <i class="fas fa-<?= $pet['status'] === 'lost' ? 'check-circle' : 'pause-circle' ?>"></i>
                                    QR Code: <?= $pet['status'] === 'lost' ? 'ACTIVE - Pet is lost' : 'INACTIVE - Pet is safe' ?>
                                </div>

                                <div class="pet-actions">
                                    <a href="includes/pet.php?id=<?= $pet['pet_id'] ?>" class="pet-btn pet-btn-primary">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <a href="includes/generate_qr.php?id=<?= $pet['pet_id'] ?>" class="pet-btn pet-btn-secondary">
                                        <i class="fas fa-qrcode"></i> Get QR Code
                                    </a>
                                </div>

                                <!-- Status Control Buttons -->
                                <div class="status-controls">
                                    <?php if ($pet['status'] === 'lost'): ?>
                                        <form method="POST" style="flex: 1;">
                                            <input type="hidden" name="pet_id" value="<?= $pet['pet_id'] ?>">
                                            <input type="hidden" name="action" value="mark_found">
                                            <button type="submit" class="status-btn status-btn-found">
                                                <i class="fas fa-home"></i> Mark Found
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="flex: 1;">
                                            <input type="hidden" name="pet_id" value="<?= $pet['pet_id'] ?>">
                                            <input type="hidden" name="action" value="mark_lost">
                                            <button type="submit" class="status-btn status-btn-lost">
                                                <i class="fas fa-exclamation-triangle"></i> Mark Lost
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- RECENT ACTIVITY -->
        <?php if ($totalPets > 0): ?>
        <div class="activity-section">
            <h2 class="section-title">Recent Activity</h2>
            
            <?php if (count($recentScanDetails) > 0): ?>
                <ul class="activity-list">
                    <?php foreach ($recentScanDetails as $scan): ?>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-text">
                                    <strong><?= htmlspecialchars($scan['pet_name']) ?></strong>'s QR code was scanned
                                    <?php if ($scan['status'] === 'lost'): ?>
                                        <span style="color: #dc3545; font-weight: 600;">(Pet is currently lost!)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-time">
                                    <?= date('M j, Y g:i A', strtotime($scan['scanned_at'])) ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-pets" style="padding: 20px;">
                    <div class="empty-icon">📊</div>
                    <div class="empty-text">No recent activity</div>
                    <div class="empty-subtext">When your pet's QR code is scanned, it will appear here</div>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>

</div>

</body>
</html>