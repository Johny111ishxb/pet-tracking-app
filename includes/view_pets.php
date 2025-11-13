<?php
session_start();
if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../db_auto_include.php';
$owner_id = $_SESSION['owner_id'];

// Fetch all pets with last scan info
try {
    $stmt = $pdo->prepare("
        SELECT p.*,
               s.scanned_at,
               (SELECT COUNT(*) FROM scans WHERE pet_id = p.pet_id) as total_scans
        FROM pets p
        LEFT JOIN (
            SELECT pet_id, scanned_at,
                   ROW_NUMBER() OVER (PARTITION BY pet_id ORDER BY scanned_at DESC) as rn
            FROM scans
        ) s ON p.pet_id = s.pet_id AND s.rn = 1
        WHERE p.owner_id = ?
        ORDER BY p.status DESC, p.date_added DESC
    ");
    $stmt->execute([$owner_id]);
    $pets = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Error fetching pets: " . $e->getMessage());
}

// Safe field access function
function getField($array, $key, $default = '') {
    return isset($array[$key]) && !empty($array[$key]) ? $array[$key] : $default;
}

// Format age display
function formatAge($age, $unit) {
    if (!$age) return 'Not specified';
    return $age . ' ' . ($age == 1 ? rtrim($unit, 's') : $unit);
}

// Get age unit with fallback
function getAgeUnit($pet) {
    return isset($pet['age_unit']) ? $pet['age_unit'] : 'years';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Pets - Pawsitive Patrol</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #2c3e50;
        }

        .pets-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .page-title {
            font-size: 42px;
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .page-subtitle {
            color: #666;
            font-size: 18px;
            margin-bottom: 25px;
        }

        .header-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4a6fa5 0%, #2c3e50 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(74, 111, 165, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(74, 111, 165, 0.4);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #2c3e50;
            border: 2px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            border-color: #4a6fa5;
            transform: translateY(-2px);
        }

        /* Messages */
        .alert {
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-weight: 600;
            text-align: center;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 2px solid #b1dfbb;
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 2px solid #f1b0b7;
        }

        /* Pets Grid */
        .pets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .pet-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 3px solid transparent;
        }

        .pet-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .pet-card-lost {
            border-color: #dc3545;
            background: linear-gradient(135deg, #fff 0%, #fff5f5 100%);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { border-color: #dc3545; }
            50% { border-color: #ff6b6b; }
            100% { border-color: #dc3545; }
        }

        .pet-header {
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            border-bottom: 1px solid #f0f0f0;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }

        .pet-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #eef4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
            color: #4a6fa5;
            flex-shrink: 0;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .pet-avatar-lost {
            background: #ffe6e6;
            color: #dc3545;
        }

        .pet-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .pet-info {
            flex: 1;
        }

        .pet-name {
            font-size: 22px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .pet-details {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .pet-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
        }

        .status-safe {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            color: #2e7d32;
        }

        .status-lost {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            color: #c62828;
        }

        .pet-body {
            padding: 25px;
        }

        .pet-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .pet-stat {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            transition: background 0.3s;
        }

        .pet-stat:hover {
            background: #e9ecef;
        }

        .pet-stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .pet-stat-label {
            font-size: 12px;
            color: #666;
            font-weight: 600;
        }

        .qr-section {
            background: #f0f7ff;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            border: 2px dashed #4a6fa5;
        }

        .qr-image {
            max-width: 120px;
            margin: 0 auto 10px;
            display: block;
            border: 2px solid white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .qr-token {
            font-family: monospace;
            font-size: 12px;
            color: #4a6fa5;
            background: white;
            padding: 5px 10px;
            border-radius: 6px;
            display: inline-block;
        }

        .pet-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }

        .pet-btn {
            padding: 10px;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .pet-btn-primary {
            background: #4a6fa5;
            color: white;
        }

        .pet-btn-primary:hover {
            background: #3a5a85;
            transform: translateY(-2px);
        }

        .pet-btn-secondary {
            background: #f0f0f0;
            color: #555;
        }

        .pet-btn-secondary:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }

        .status-controls {
            display: flex;
            gap: 10px;
        }

        .status-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .status-btn-lost {
            background: #dc3545;
            color: white;
        }

        .status-btn-lost:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .status-btn-found {
            background: #28a745;
            color: white;
        }

        .status-btn-found:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
            color: #ddd;
        }

        .empty-text {
            font-size: 28px;
            color: #666;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .empty-subtext {
            font-size: 16px;
            color: #888;
            max-width: 400px;
            margin: 0 auto 30px;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .pets-container {
                padding: 10px;
            }

            .header-section {
                padding: 20px;
            }

            .page-title {
                font-size: 32px;
            }

            .pets-grid {
                grid-template-columns: 1fr;
            }

            .pet-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .pet-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .pet-actions {
                grid-template-columns: 1fr;
            }

            .status-controls {
                flex-direction: column;
            }

            .header-actions {
                flex-direction: column;
            }
        }

        /* Animations */
        .pet-card {
            opacity: 0;
            animation: fadeInUp 0.6s ease forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

<div class="pets-container">
    <!-- HEADER SECTION -->
    <div class="header-section">
        <h1 class="page-title">
            <i class="fas fa-paw"></i> My Pets
        </h1>
        <p class="page-subtitle">Manage all your registered pets and their QR codes</p>
        
        <div class="header-actions">
            <a href="add_pet.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Pet
            </a>
            <a href="../owner_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- MESSAGES -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= $_SESSION['success']; ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error']; ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- PETS GRID -->
    <?php if (empty($pets)): ?>
        <div class="empty-state">
            <div class="empty-icon">🐶</div>
            <div class="empty-text">No Pets Yet!</div>
            <div class="empty-subtext">
                You haven't added any pets to your account. Start by registering your first furry friend 
                and we'll generate a unique QR code for their safety.
            </div>
            <a href="add_pet.php" class="btn btn-primary" style="text-decoration: none;">
                <i class="fas fa-plus"></i> Add Your First Pet
            </a>
        </div>
    <?php else: ?>
        <div class="pets-grid">
            <?php foreach ($pets as $index => $pet): ?>
                <div class="pet-card <?= $pet['status'] === 'lost' ? 'pet-card-lost' : '' ?>" style="animation-delay: <?= $index * 0.1 ?>s">
                    <!-- PET HEADER -->
                    <div class="pet-header">
                        <div class="pet-avatar <?= $pet['status'] === 'lost' ? 'pet-avatar-lost' : '' ?>">
                            <?php if (!empty($pet['photo']) && file_exists('../uploads/' . $pet['photo'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($pet['photo']) ?>" alt="<?= htmlspecialchars($pet['name']) ?>">
                            <?php else: ?>
                                <?= $pet['type'] == 'Dog' ? '🐕' : ($pet['type'] == 'Cat' ? '🐈' : '🐾') ?>
                            <?php endif; ?>
                        </div>
                        <div class="pet-info">
                            <div class="pet-name"><?= htmlspecialchars($pet['name']) ?></div>
                            <div class="pet-details">
                                <?= htmlspecialchars($pet['type']) ?> • 
                                <?= htmlspecialchars(getField($pet, 'breed', 'Mixed')) ?> • 
                                <?= formatAge(getField($pet, 'age'), getAgeUnit($pet)) ?>
                            </div>
                            <div class="pet-status status-<?= $pet['status'] === 'lost' ? 'lost' : 'safe' ?>">
                                <?= $pet['status'] === 'lost' ? '🚨 LOST PET' : '✅ Safe at Home' ?>
                            </div>
                        </div>
                    </div>

                    <!-- PET BODY -->
                    <div class="pet-body">
                        <!-- STATISTICS -->
                        <div class="pet-stats">
                            <div class="pet-stat">
                                <div class="pet-stat-value"><?= $pet['total_scans'] ?? 0 ?></div>
                                <div class="pet-stat-label">Total Scans</div>
                            </div>
                            <div class="pet-stat">
                                <div class="pet-stat-value">
                                    <?= $pet['scanned_at'] ? date('M j', strtotime($pet['scanned_at'])) : 'Never' ?>
                                </div>
                                <div class="pet-stat-label">Last Scan</div>
                            </div>
                            <div class="pet-stat">
                                <div class="pet-stat-value">
                                    <?= $pet['status'] === 'lost' ? 'Active' : 'Inactive' ?>
                                </div>
                                <div class="pet-stat-label">QR Status</div>
                            </div>
                        </div>

                        <!-- QR CODE -->
                        <div class="qr-section">
                            <?php if (!empty($pet['qr_code']) && file_exists("../qr/{$pet['qr_code']}.png")): ?>
                                <img src="../qr/<?= htmlspecialchars($pet['qr_code']) ?>.png" alt="QR Code" class="qr-image">
                            <?php else: ?>
                                <div style="font-size: 32px; margin-bottom: 10px;">📱</div>
                            <?php endif; ?>
                            <div class="qr-token">ID: <?= htmlspecialchars(substr($pet['qr_code'] ?? 'NONE', 0, 8)) ?>...</div>
                        </div>

                        <!-- ACTION BUTTONS -->
                        <div class="pet-actions">
                            <a href="pet.php?id=<?= $pet['pet_id'] ?>" class="pet-btn pet-btn-primary">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <a href="edit_pet.php?id=<?= $pet['pet_id'] ?>" class="pet-btn pet-btn-secondary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </div>

                        <!-- STATUS CONTROLS -->
                        <div class="status-controls">
                            <?php if ($pet['status'] === 'lost'): ?>
                                <form method="POST" action="../includes/mark_found.php" style="flex: 1;">
                                    <input type="hidden" name="pet_id" value="<?= $pet['pet_id'] ?>">
                                    <button type="submit" class="status-btn status-btn-found">
                                        <i class="fas fa-home"></i> Mark Found
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="../includes/mark_lost.php" style="flex: 1;">
                                    <input type="hidden" name="pet_id" value="<?= $pet['pet_id'] ?>">
                                    <button type="submit" class="status-btn status-btn-lost" 
                                            onclick="return confirm('Mark <?= htmlspecialchars($pet['name']) ?> as lost? This will activate the QR code.')">
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

</body>
</html>