<?php
// scan_report.php
session_start();
if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../db/db_connect.php';

// Get scan statistics
$stmt = $pdo->prepare("
    SELECT p.name, COUNT(s.scan_id) as scan_count, MAX(s.scanned_at) as last_scan
    FROM pets p 
    LEFT JOIN scans s ON p.pet_id = s.pet_id 
    WHERE p.owner_id = ? 
    GROUP BY p.pet_id 
    ORDER BY scan_count DESC
");
$stmt->execute([$_SESSION['owner_id']]);
$scanStats = $stmt->fetchAll();

// Get detailed scan history for the last 30 days
$stmt = $pdo->prepare("
    SELECT p.name as pet_name, s.scanned_at, s.location_lat, s.location_lng
    FROM scans s 
    JOIN pets p ON s.pet_id = p.pet_id 
    WHERE p.owner_id = ? AND s.scanned_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY s.scanned_at DESC
");
$stmt->execute([$_SESSION['owner_id']]);
$recentScans = $stmt->fetchAll();

// Get monthly scan statistics
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(s.scanned_at, '%Y-%m') as month,
        COUNT(*) as scan_count
    FROM scans s 
    JOIN pets p ON s.pet_id = p.pet_id 
    WHERE p.owner_id = ? 
    GROUP BY DATE_FORMAT(s.scanned_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6
");
$stmt->execute([$_SESSION['owner_id']]);
$monthlyStats = $stmt->fetchAll();

// Calculate total scans
$totalScans = 0;
foreach ($scanStats as $stat) {
    $totalScans += $stat['scan_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Reports - Pawsitive Patrol</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 20px;
            color: #2c3e50;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #4a6fa5;
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            background: #f0f7ff;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .back-link:hover {
            background: #e0efff;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
            color: #4a6fa5;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            font-weight: 600;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .pet-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .scan-count {
            text-align: center;
            font-weight: 700;
            color: #4a6fa5;
        }

        .last-scan {
            color: #666;
            font-size: 14px;
        }

        .no-scans {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-scans-icon {
            font-size: 60px;
            margin-bottom: 20px;
            color: #ddd;
        }

        .scan-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .scan-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .scan-item:last-child {
            border-bottom: none;
        }

        .scan-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #eef4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4a6fa5;
            font-size: 16px;
        }

        .scan-content {
            flex: 1;
        }

        .scan-pet {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .scan-time {
            font-size: 14px;
            color: #666;
        }

        .scan-location {
            font-size: 12px;
            color: #888;
            font-family: monospace;
        }

        .monthly-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .month-stat {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .month-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .month-count {
            font-size: 24px;
            font-weight: 700;
            color: #4a6fa5;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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
            .container {
                padding: 10px;
            }
            
            .header {
                padding: 20px;
            }
            
            .page-title {
                font-size: 24px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            th, td {
                padding: 10px;
                font-size: 14px;
            }
            
            .section {
                padding: 20px;
            }
        }

        .export-btn {
            background: #28a745;
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
            margin-left: auto;
        }

        .export-btn:hover {
            background: #218838;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1 class="page-title">
                <i class="fas fa-chart-bar"></i> Scan Reports
            </h1>
            <p class="page-subtitle">Track your pet's QR code scan activity and statistics</p>
            <a href="../owner_dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- SUMMARY STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-qrcode"></i>
                </div>
                <div class="stat-value"><?= $totalScans ?></div>
                <div class="stat-label">Total Scans</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-paw"></i>
                </div>
                <div class="stat-value"><?= count($scanStats) ?></div>
                <div class="stat-label">Registered Pets</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-value"><?= count($recentScans) ?></div>
                <div class="stat-label">Scans (Last 30 Days)</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value">
                    <?= count($scanStats) > 0 ? round($totalScans / count($scanStats)) : 0 ?>
                </div>
                <div class="stat-label">Average Scans per Pet</div>
            </div>
        </div>

        <!-- PET SCAN STATISTICS -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-list-ol"></i> Pet Scan Statistics
                </h2>
                <button class="export-btn" onclick="exportToCSV()">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>

            <?php if (count($scanStats) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Pet Name</th>
                            <th style="text-align: center;">Total Scans</th>
                            <th>Last Scan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scanStats as $stat): ?>
                            <tr>
                                <td class="pet-name"><?= htmlspecialchars($stat['name']) ?></td>
                                <td class="scan-count"><?= $stat['scan_count'] ?></td>
                                <td class="last-scan">
                                    <?= $stat['last_scan'] ? date('M j, Y g:i A', strtotime($stat['last_scan'])) : 'Never scanned' ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= $stat['scan_count'] > 0 ? 'status-active' : 'status-inactive' ?>">
                                        <?= $stat['scan_count'] > 0 ? 'Active' : 'No Scans' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-scans">
                    <div class="no-scans-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>No Scan Data Available</h3>
                    <p>Scan statistics will appear here when your pet's QR codes are scanned.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- RECENT SCAN ACTIVITY -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-history"></i> Recent Scan Activity (Last 30 Days)
            </h2>

            <?php if (count($recentScans) > 0): ?>
                <ul class="scan-list">
                    <?php foreach ($recentScans as $scan): ?>
                        <li class="scan-item">
                            <div class="scan-icon">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div class="scan-content">
                                <div class="scan-pet">
                                    <?= htmlspecialchars($scan['pet_name']) ?>'s QR code was scanned
                                </div>
                                <div class="scan-time">
                                    <?= date('M j, Y g:i A', strtotime($scan['scanned_at'])) ?>
                                </div>
                                <?php if ($scan['location_lat'] && $scan['location_lng']): ?>
                                    <div class="scan-location">
                                        📍 Lat: <?= number_format($scan['location_lat'], 4) ?>, Lng: <?= number_format($scan['location_lng'], 4) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="no-scans">
                    <div class="no-scans-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>No Recent Scans</h3>
                    <p>Recent scan activity will appear here when your pet's QR codes are scanned.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- MONTHLY STATISTICS -->
        <?php if (count($monthlyStats) > 0): ?>
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-calendar-alt"></i> Monthly Scan Trends
            </h2>
            <div class="monthly-stats">
                <?php foreach ($monthlyStats as $monthStat): ?>
                    <div class="month-stat">
                        <div class="month-name">
                            <?= date('M Y', strtotime($monthStat['month'] . '-01')) ?>
                        </div>
                        <div class="month-count">
                            <?= $monthStat['scan_count'] ?>
                        </div>
                        <div style="font-size: 12px; color: #666;">scans</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function exportToCSV() {
            // Simple CSV export functionality
            const data = [
                ['Pet Name', 'Total Scans', 'Last Scan', 'Status']
            ];
            
            <?php foreach ($scanStats as $stat): ?>
                data.push([
                    '<?= addslashes($stat['name']) ?>',
                    <?= $stat['scan_count'] ?>,
                    '<?= $stat['last_scan'] ? date('M j, Y g:i A', strtotime($stat['last_scan'])) : 'Never scanned' ?>',
                    '<?= $stat['scan_count'] > 0 ? 'Active' : 'No Scans' ?>'
                ]);
            <?php endforeach; ?>

            let csvContent = "data:text/csv;charset=utf-8,";
            data.forEach(row => {
                csvContent += row.join(",") + "\r\n";
            });

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "pet_scan_report_<?= date('Y-m-d') ?>.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Add some basic animations
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('fade-in');
            });
        });

        // Add fade-in animation
        const style = document.createElement('style');
        style.textContent = `
            .fade-in {
                animation: fadeInUp 0.6s ease-out forwards;
                opacity: 0;
            }
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>