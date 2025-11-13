<?php
session_start();
if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../db_auto_include.php';

if (!isset($_GET['id'])) {
    header("Location: view_pets.php");
    exit();
}

$pet_id = $_GET['id'];
$owner_id = $_SESSION['owner_id'];

// Fetch pet details
try {
    //$stmt = $pdo->prepare("SELECT * FROM pets WHERE qr_code = '4452007d156f1175cdc5' AND owner_id = ?");
    $stmt =  $pdo->prepare("SELECT * FROM pets WHERE pet_id = ? AND owner_id = ?");
    $stmt->execute([$pet_id, $owner_id]);
    $pet = $stmt->fetch();

    if (!$pet) {
        header("Location: view_pets.php");
        exit();
    }
} catch(PDOException $e) {
    die("Error fetching pet: " . $e->getMessage());
}

// Fetch scan history
$scans = [];
try {
    $scanStmt = $pdo->prepare("SELECT * FROM scans WHERE pet_id = ? ORDER BY scanned_at DESC");
    $scanStmt->execute([$pet_id]);
    $scans = $scanStmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching scans: " . $e->getMessage());
}

// Fetch found reports
$reports = [];
try {
    $reportStmt = $pdo->prepare("SELECT * FROM found_reports WHERE pet_id = ? ORDER BY reported_at DESC");
    $reportStmt->execute([$pet_id]);
    $reports = $reportStmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching reports: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pet['name']) ?> - Pawsitive Patrol</title>
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header Section */
        .header {
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

        .pet-subtitle {
            color: #666;
            font-size: 20px;
            margin-bottom: 25px;
            font-weight: 500;
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

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }

        .btn-warning:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.4);
            background: linear-gradient(135deg, #e0a800 0%, #c69500 100%);
        }

        /* Content Layout */
        .content-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            margin-bottom: 30px;
        }

        /* Main Content */
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-title {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 3px solid #f0f0f0;
        }

        .card-title i {
            color: #4a6fa5;
        }

        /* Location History Section */
        .location-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .pet-badge {
            background: linear-gradient(135deg, #4a6fa5, #2c3e50);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .scan-count {
            font-size: 28px;
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .scan-label {
            color: #666;
            font-size: 14px;
            font-weight: 600;
        }

        /* Scan Items */
        .scan-item {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            border-left: 5px solid #4a6fa5;
            transition: all 0.3s ease;
        }

        .scan-item:hover {
            background: #eef4ff;
            transform: translateX(5px);
        }

        .scan-item.latest {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-left-color: #28a745;
        }

        .scan-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .scan-scanner {
            font-weight: 700;
            color: #2c3e50;
            font-size: 18px;
        }

        .scan-time {
            color: #666;
            font-size: 14px;
            font-weight: 600;
        }

        .scan-location {
            color: #555;
            margin-bottom: 15px;
            font-size: 16px;
            font-family: 'Courier New', monospace;
        }

        .scan-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .map-link {
            color: #4a6fa5;
            text-decoration: none;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: white;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .map-link:hover {
            background: #4a6fa5;
            color: white;
            transform: translateY(-2px);
        }

        /* Found Reports */
        .report-item {
            background: #fff3e0;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            border-left: 5px solid #ff9800;
            transition: all 0.3s ease;
        }

        .report-item:hover {
            background: #ffe0b2;
            transform: translateX(5px);
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .report-finder {
            font-weight: 700;
            color: #2c3e50;
            font-size: 18px;
        }

        .report-title {
            color: #666;
            font-size: 14px;
            font-weight: 600;
        }

        .report-time {
            color: #666;
            font-size: 14px;
            font-weight: 600;
        }

        .report-message {
            color: #555;
            margin-bottom: 15px;
            font-size: 16px;
            line-height: 1.5;
            font-style: italic;
        }

        .report-contact{
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .report-photo{
            margin-top: 10px;
           display: flex;
            gap: 10px;
            font-weight: 600; 
            flex-direction: column;
        }

        .contact-info, .photo-info{
            background: white;
            padding: 8px 16px;
            border-radius: 10px;
            font-family: monospace;
            color: #4a6fa5;
            font-weight: 700;
        }

        .attached-photo{
            max-width: 300px;
            max-height: 200px;
        }

        /* QR Code Section */
        .qr-section {
            text-align: center;
            padding: 30px;
        }

        .qr-image {
            max-width: 250px;
            margin: 0 auto 25px;
            border: 3px solid white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }

        .qr-image:hover {
            transform: scale(1.05);
        }

        .qr-instructions {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
            font-size: 16px;
        }

        .qr-instructions strong {
            color: #2c3e50;
            font-size: 18px;
        }

        .download-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .download-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }

        /* Remove Pet Section */
        .remove-section {
            background: linear-gradient(135deg, #fff5f5, #ffe6e6);
            border: 2px dashed #dc3545;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            margin-top: 20px;
        }

        .remove-title {
            color: #dc3545;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .remove-warning {
            color: #721c24;
            margin-bottom: 20px;
            line-height: 1.5;
            font-size: 14px;
        }

        .remove-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .remove-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
            background: linear-gradient(135deg, #c82333, #a71e2a);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-title {
            color: #dc3545;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .modal-text {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .modal-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .modal-btn-cancel {
            background: #6c757d;
            color: white;
        }

        .modal-btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .modal-btn-confirm {
            background: #dc3545;
            color: white;
        }

        .modal-btn-confirm:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 50px 30px;
            color: #666;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-text {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .empty-subtext {
            font-size: 14px;
            color: #888;
        }

        /* Status Badges */
        .status-badge {
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 20px;
        }

        .status-safe {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            color: #2e7d32;
        }

        .status-lost {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            color: #c62828;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .content-layout {
                grid-template-columns: 1fr;
            }
            
            .qr-section {
                order: -1;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header {
                padding: 20px;
            }

            .page-title {
                font-size: 32px;
            }

            .card {
                padding: 20px;
            }

            .header-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .scan-header, .report-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .scan-actions {
                flex-direction: column;
                align-items: flex-start;
            }

            .modal-actions {
                flex-direction: column;
            }

            .modal-btn {
                width: 100%;
            }
        }

        /* Custom Scrollbar */
        .card::-webkit-scrollbar {
            width: 6px;
        }

        .card::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .card::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        .card::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- HEADER SECTION -->
    <div class="header">
        <h1 class="page-title">
            <i class="fas fa-paw"></i> Pawsitive Patrol
        </h1>
        <div class="pet-subtitle">
            Pet Safety System • <?= htmlspecialchars($pet['name']) ?>
        </div>
        
        <div class="header-actions">
            <a href="../owner_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="edit_pet.php?id=<?= $pet_id ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Pet
            </a>
            <?php if ($pet['status'] === 'safe'): ?>
                <form method="POST" action="../includes/mark_lost.php" style="display: inline;">
                    <input type="hidden" name="pet_id" value="<?= $pet_id ?>">
                    <button type="submit" class="btn btn-danger" 
                            onclick="return confirm('Mark <?= htmlspecialchars($pet['name']) ?> as lost? This will activate emergency mode.')">
                        <i class="fas fa-exclamation-triangle"></i> Mark as Lost
                    </button>
                </form>
            <?php else: ?>
                <form method="POST" action="../includes/mark_found.php" style="display: inline;">
                    <input type="hidden" name="pet_id" value="<?= $pet_id ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-home"></i> Mark as Found
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="content-layout">
        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- Location History -->
            <div class="card">
                <div class="location-header">
                    <h2 class="card-title">
                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($pet['name']) ?>'s Location History
                    </h2>
                    <div class="pet-badge">
                        <?= htmlspecialchars($pet['breed'] ?? 'Unknown Breed') ?> · <?= htmlspecialchars($pet['type']) ?>
                    </div>
                </div>

                <div class="scan-count"><?= count($scans) ?></div>
                <div class="scan-label">Location Scans</div>

                <?php if (empty($scans)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📍</div>
                        <div class="empty-text">No Location Scans Yet</div>
                        <div class="empty-subtext">Scan history will appear here when someone scans your pet's QR code</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($scans as $index => $scan): ?>
                        <div class="scan-item <?= $index === 0 ? 'latest' : '' ?>">
                            <div class="scan-header">
                                <div class="scan-scanner">
                                    <?= $index === 0 ? '<strong>Latest</strong><br>' : '' ?>
                                    <?= htmlspecialchars($scan['scanner_info'] ?? 'Anonymous Scanner') ?>
                                </div>
                                <div class="scan-time"><?= date('m/d/Y, g:i:s A', strtotime($scan['scanned_at'])) ?></div>
                            </div>
                            <?php if ($scan['location_lat'] && $scan['location_lng']): ?>
                                <div class="scan-location">
                                    📍 Lat: <?= $scan['location_lat'] ?>, Lng: <?= $scan['location_lng'] ?>
                                </div>
                                <div class="scan-actions">
                                    <a href="https://maps.google.com/?q=<?= $scan['location_lat'] ?>,<?= $scan['location_lng'] ?>" 
                                       target="_blank" class="map-link">
                                        <i class="fas fa-external-link-alt"></i> View on Map
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="scan-location">📍 Location not recorded</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Found Reports -->
            <div class="card">
                <h2 class="card-title">
                    <i class="fas fa-flag"></i> Found Reports (<?= count($reports) ?>)
                </h2>
                
                <?php if (empty($reports)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📝</div>
                        <div class="empty-text">No Found Reports Yet</div>
                        <div class="empty-subtext">When someone finds your lost pet, their reports will appear here</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                        <div class="report-item">
                            <div class="report-header">
                                <div>
                                    <div class="report-finder"><?= htmlspecialchars($report['finder_name']) ?></div>
                                    <div class="report-title">Found Pet Report</div>
                                </div>
                                <div class="report-time"><?= date('m/d/Y, g:i:s A', strtotime($report['reported_at'])) ?></div>
                            </div>
                            <div class="report-message">
                                "<?= htmlspecialchars($report['message']) ?>"
                            </div>
                            <div class="report-contact">
                                <span>Contact:</span>
                                <span class="contact-info"><?= $report['finder_contact'] ? htmlspecialchars($report['finder_contact']) : "No Contacts Available." ?></span>
                            </div>
                            <div class="report-photo">
                                <span>Attached Photo:</span>
                                <span class="photo-info">
                                    <?php if (!empty($report['attached_photo']) && file_exists('./attached-photo/' . $report['attached_photo'])): ?>
                                    <img src="./attached-photo/<?= htmlspecialchars($report['attached_photo']) ?>" alt="No Photos Attached" class="attached-photo">
                                    <?php else:?>
                                        No Photos Attached
                                    <?php endif; ?>
                                </span>
                                  
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- SIDEBAR - QR CODE & REMOVE PET -->
        <div>
            <!-- QR Code Card -->
            <div class="card">
                <h2 class="card-title">
                    <i class="fas fa-qrcode"></i> QR Code for <?= htmlspecialchars($pet['name']) ?>
                </h2>
                
                <div class="status-badge status-<?= $pet['status'] ?>">
                    <?= $pet['status'] === 'lost' ? '🚨 LOST PET - EMERGENCY MODE' : '✅ Safe at Home' ?>
                </div>
                
                <div class="qr-section">
                    <?php if (!empty($pet['qr_code']) && file_exists("../qr/{$pet['qr_code']}.png")): ?>
                        <img src="../qr/<?= htmlspecialchars($pet['qr_code']) ?>.png" 
                             alt="QR Code for <?= htmlspecialchars($pet['name']) ?>" 
                             class="qr-image">
                    <?php else: ?>
                        <div style="font-size: 120px; margin-bottom: 20px; color: #ddd;">📱</div>
                        <div class="empty-state">
                            <div class="empty-text">QR Code Not Generated</div>
                            <a href="../includes/generate_qr.php?id=<?= $pet_id ?>" class="btn btn-primary" style="margin-top: 15px;">
                                <i class="fas fa-qrcode"></i> Generate QR Code
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="qr-instructions">
                        <strong>Print and attach to <?= htmlspecialchars($pet['name']) ?>'s collar</strong><br>
                        When someone scans this QR code, it will automatically log their location 
                        and show <?= htmlspecialchars($pet['name']) ?>'s information.
                    </div>
                    
                    <?php if (!empty($pet['qr_code']) && file_exists("../qr/{$pet['qr_code']}.png")): ?>
                        <a href="../qr/<?= htmlspecialchars($pet['qr_code']) ?>.png" 
                           download="<?= htmlspecialchars($pet['name']) ?>_qrcode.png" 
                           class="btn download-btn">
                            <i class="fas fa-download"></i> Download High-Res QR Code
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Remove Pet Section -->
            <div class="remove-section">
                <div class="remove-title">
                    <i class="fas fa-trash-alt"></i> Remove Pet
                </div>
                <div class="remove-warning">
                    <strong>Warning:</strong> This action cannot be undone. All pet data, scan history, and found reports will be permanently deleted.
                </div>
                <button type="button" class="remove-btn" onclick="openRemoveModal()">
                    <i class="fas fa-trash"></i> Remove Pet from System
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Remove Confirmation Modal -->
<div id="removeModal" class="modal">
    <div class="modal-content">
        <div class="modal-title">
            <i class="fas fa-exclamation-triangle"></i> Confirm Removal
        </div>
        <div class="modal-text">
            Are you sure you want to remove <strong><?= htmlspecialchars($pet['name']) ?></strong> from the system?<br><br>
            This will permanently delete:
            <ul style="text-align: left; margin: 15px 0; padding-left: 20px;">
                <li>Pet profile information</li>
                <li>All location scan history (<?= count($scans) ?> scans)</li>
                <li>All found reports (<?= count($reports) ?> reports)</li>
                <li>QR code and associated data</li>
            </ul>
            This action cannot be undone!
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeRemoveModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <form method="POST" action="./remove_pet.php" style="display: inline;">
                <input type="hidden" name="pet_id" value="<?= $pet_id ?>">
                <button type="submit" class="modal-btn modal-btn-confirm">
                    <i class="fas fa-trash"></i> Remove Pet
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Modal functions
function openRemoveModal() {
    document.getElementById('removeModal').style.display = 'block';
}

function closeRemoveModal() {
    document.getElementById('removeModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('removeModal');
    if (event.target === modal) {
        closeRemoveModal();
    }
}

// Add animations
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    const scanItems = document.querySelectorAll('.scan-item');
    const reportItems = document.querySelectorAll('.report-item');
    
    // Animate cards
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 200);
    });
    
    // Animate scan items
    scanItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-30px)';
        setTimeout(() => {
            item.style.transition = 'all 0.5s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, 600 + (index * 100));
    });
    
    // Animate report items
    reportItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(30px)';
        setTimeout(() => {
            item.style.transition = 'all 0.5s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, 800 + (index * 100));
    });
});
</script>

</body>
</html>