<?php
session_start();
if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../db/db_connect.php'; // ✅ Fixed path

// Check if pet ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../owner_dashboard.php");
    exit();
}

$pet_id = $_GET['id'];

try {
    // Verify the pet belongs to the current owner
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE pet_id = ? AND owner_id = ?");
    $stmt->execute([$pet_id, $_SESSION['owner_id']]);
    $pet = $stmt->fetch();

    if (!$pet) {
        header("Location: ../owner_dashboard.php");
        exit();
    }

    // Ensure pet has a qr_code token. If not, generate and store one.
    if (empty($pet['qr_code'])) {
        $maxAttempts = 5;
        $qr_token = null;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $candidate = bin2hex(random_bytes(10));
            $check = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE qr_code = ?");
            $check->execute([$candidate]);
            if ($check->fetchColumn() == 0) {
                $qr_token = $candidate;
                break;
            }
        }

        if ($qr_token) {
            $up = $pdo->prepare("UPDATE pets SET qr_code = ? WHERE pet_id = ?");
            $up->execute([$qr_token, $pet['pet_id']]);
            $pet['qr_code'] = $qr_token;
        }
    }

    // Create the URL that will be encoded in the QR code
    // Detect scheme (http/https) and compute application base dynamically so this works on any host/path
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    // Derive base path by removing the includes/ segment from the current script directory
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']); // e.g. /pettracking/includes
    $appBase = preg_replace('#/includes$#', '', $scriptDir);
    if ($appBase === null) { $appBase = ''; }
    $qr_url = $scheme . '://' . $host . $appBase . '/includes/pet_info.php?token=' . urlencode($pet['qr_code']);

    // Use centralized helper for QR generation
    require_once __DIR__ . '/qr_helper.php';
    $qrInfo = ensure_qr_png($pet['qr_code'], $qr_url, 8);
    $localQrFile = $qrInfo['local_file'];
    $generated = $qrInfo['saved'];

    // Build a web-accessible URL for the local PNG if it exists; otherwise use Google Charts URL
    if ($generated && file_exists($localQrFile)) {
        $qrWebPath = $qrInfo['web_path'];
        $google_qr_url = $qrInfo['absolute_url'] ?? ($scheme . '://' . $host . $qrWebPath);
    } else {
        $google_qr_url = "https://chart.googleapis.com/chart?chs=600x600&cht=qr&chl=" . urlencode($qr_url) . "&choe=UTF-8";
    }

    // If requested as raw image, ensure local PNG exists (generate or download) and serve it
    $raw = (isset($_GET['raw']) && $_GET['raw'] == '1') || (isset($_GET['img']) && $_GET['img'] == '1');
    if ((isset($_GET['raw']) && $_GET['raw'] == '1')) {
        // If local file exists now, serve it
        if ($generated && file_exists($localQrFile)) {
            header('Content-Type: image/png');
            header('Content-Length: ' . filesize($localQrFile));
            readfile($localQrFile);
            exit();
        }

        // Final fallback: attempt to fetch from Google Charts and serve
        $qr_data = false;
        if (ini_get('allow_url_fopen')) {
            $qr_data = @file_get_contents($google_qr_api);
        }
        if ($qr_data === false && function_exists('curl_version')) {
            $ch = curl_init($google_qr_api);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $qr_data = curl_exec($ch);
            curl_close($ch);
        }
        if ($qr_data !== false && $qr_data !== null) {
            if ($generated) {
                // try to save and serve
                @file_put_contents($localQrFile, $qr_data);
                if (file_exists($localQrFile)) {
                    header('Content-Type: image/png');
                    header('Content-Length: ' . filesize($localQrFile));
                    readfile($localQrFile);
                    exit();
                }
            }
            header('Content-Type: image/png');
            header('Content-Length: ' . strlen($qr_data));
            echo $qr_data;
            exit();
        }

        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
        echo 'Unable to generate QR code image.';
        exit();

    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Safe field access function
function getField($array, $key, $default = '') {
    return isset($array[$key]) && !empty($array[$key]) ? $array[$key] : $default;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code for <?= htmlspecialchars($pet['name']) ?> - Pawsitive Patrol</title>
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
        }

        .qr-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header-section {
            background: linear-gradient(135deg, #4a6fa5 0%, #2c3e50 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .back-btn {
            position: absolute;
            left: 30px;
            top: 30px;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: opacity 0.3s;
        }

        .back-btn:hover {
            opacity: 0.8;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .page-subtitle {
            opacity: 0.9;
            font-size: 16px;
        }

        .content-section {
            padding: 40px;
        }

        .pet-info {
            text-align: center;
            margin-bottom: 30px;
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            border: 2px dashed #e0e0e0;
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
            margin: 0 auto 15px;
            color: #4a6fa5;
        }

        .pet-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .pet-name {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .pet-details {
            color: #666;
            font-size: 16px;
        }

        .qr-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .qr-code {
            max-width: 300px;
            margin: 0 auto 20px;
            padding: 20px;
            background: white;
            border: 2px dashed #4a6fa5;
            border-radius: 12px;
            display: inline-block;
        }

        .qr-code img {
            max-width: 100%;
            height: auto;
            border: 1px solid #eee;
            border-radius: 8px;
        }

        .qr-info {
            text-align: center;
            margin-bottom: 25px;
        }

        .qr-url {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 14px;
            word-break: break-all;
            border: 1px solid #e0e0e0;
        }

        .instructions {
            background: #f0f7ff;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 4px solid #4a6fa5;
        }

        .instructions h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }

        .instructions ol {
            text-align: left;
            margin-left: 20px;
            color: #555;
        }

        .instructions li {
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            color: white;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4a6fa5 0%, #2c3e50 100%);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 111, 165, 0.3);
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-2px);
        }

        .note {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 25px;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
        }

        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-inactive {
            background: #fff3e0;
            color: #ef6c00;
        }

        @media (max-width: 768px) {
            .qr-container {
                margin: 10px;
            }
            
            .content-section {
                padding: 25px;
            }
            
            .header-section {
                padding: 25px 20px;
            }
            
            .back-btn {
                position: static;
                margin-bottom: 15px;
                justify-content: center;
                display: inline-flex;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .qr-code {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>

<div class="qr-container">
    <!-- HEADER SECTION -->
    <div class="header-section">
        <a href="../owner_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <h1 class="page-title"><i class="fas fa-qrcode"></i> Pet QR Code</h1>
        <p class="page-subtitle">Print this QR code and attach it to your pet's collar</p>
    </div>

    <!-- CONTENT SECTION -->
    <div class="content-section">
        <!-- PET INFORMATION -->
        <div class="pet-info">
            <div class="pet-avatar">
                <?php if (!empty($pet['photo']) && file_exists('./uploads/' . $pet['photo'])): ?>
                    <img src="./uploads/<?= htmlspecialchars($pet['photo']) ?>" alt="<?= htmlspecialchars($pet['name']) ?>">
                <?php else: ?>
                    <?= $pet['type'] == 'Dog' ? '🐕' : ($pet['type'] == 'Cat' ? '🐈' : '🐾') ?>
                <?php endif; ?>
            </div>
            <div class="pet-name"><?= htmlspecialchars($pet['name']) ?></div>
            <div class="pet-details">
                <strong>Type:</strong> <?= htmlspecialchars($pet['type']) ?> • 
                <strong>Breed:</strong> <?= htmlspecialchars(getField($pet, 'breed', 'Mixed')) ?> • 
                <strong>Color:</strong> <?= htmlspecialchars(getField($pet, 'color')) ?>
            </div>
            <div class="status-badge <?= $pet['status'] === 'lost' ? 'status-active' : 'status-inactive' ?>">
                QR Code: <?= $pet['status'] === 'lost' ? 'ACTIVE - Pet is lost' : 'INACTIVE - Pet is safe' ?>
            </div>
        </div>

        <!-- QR CODE -->
        <div class="qr-section">
            <div class="qr-code">
                <img src="?id=<?= urlencode($pet['pet_id']) ?>&amp;raw=1" alt="QR Code for <?= htmlspecialchars($pet['name']) ?>">
            </div>
            <p><strong>Scan this QR code to view pet information</strong></p>
            
            <div class="qr-url">
                <strong>QR Code URL:</strong><br>
                <?= htmlspecialchars($qr_url) ?>
            </div>
        </div>

        <!-- DOWNLOAD BUTTONS -->
        <div class="actions">
            <a href="?id=<?= urlencode($pet['pet_id']) ?>&amp;raw=1" download="qr-code-<?= htmlspecialchars($pet['name']) ?>.png" class="btn btn-success">
                <i class="fas fa-download"></i> Download QR Code
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print This Page
            </button>
            <a href="./add_pet.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Back to Add Pet
            </a>
        </div>

        <!-- INSTRUCTIONS -->
        <div class="instructions">
            <h3><i class="fas fa-print"></i> Printing Instructions</h3>
            <ol>
                <li><strong>Download</strong> the QR code using the button above</li>
                <li><strong>Print</strong> on waterproof paper or laminate for durability</li>
                <li><strong>Cut</strong> to size and attach securely to your pet's collar</li>
                <li><strong>Test</strong> with your phone to ensure it scans properly</li>
                <li><strong>Update</strong> your pet's information regularly in the dashboard</li>
            </ol>
        </div>

        <!-- IMPORTANT NOTES -->
        <div class="note">
            <i class="fas fa-lightbulb"></i> 
            <strong>Important:</strong> 
            • Make sure the QR code is clearly visible and not covered<br>
            • Protect it from weather conditions (laminate if possible)<br>
            • Test regularly to ensure it still scans properly<br>
            • Replace if damaged or faded
        </div>
    </div>
</div>

<script>
// Add print styles
const style = document.createElement('style');
style.textContent = `
    @media print {
        body * {
            visibility: hidden;
        }
        .qr-container, .qr-container * {
            visibility: visible;
        }
        .qr-container {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            box-shadow: none;
            margin: 0;
            padding: 20px;
        }
        .actions, .note, .back-btn {
            display: none;
        }
        .btn {
            display: none;
        }
        .header-section {
            background: #2c3e50 !important;
            -webkit-print-color-adjust: exact;
        }
    }
`;
document.head.appendChild(style);

// Add some animations
document.addEventListener('DOMContentLoaded', function() {
    const qrCode = document.querySelector('.qr-code');
    qrCode.style.animation = 'fadeIn 0.6s ease-out';
});
</script>

</body>
</html>