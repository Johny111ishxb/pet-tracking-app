<?php
// test_dashboard.php - Test dashboard queries individually
session_start();
require_once 'db_auto_include.php';

echo "<h2>🔍 Dashboard Query Test</h2>";

// Set a test owner_id if not logged in
if (!isset($_SESSION['owner_id'])) {
    $_SESSION['owner_id'] = 1; // Use first owner for testing
    echo "<p>⚠️ Using test owner_id = 1</p>";
}

try {
    echo "<h3>Test 1: Total Pets Count</h3>";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE owner_id = ?");
    $stmt->execute([$_SESSION['owner_id']]);
    $totalPets = $stmt->fetchColumn();
    echo "<p>✅ Total pets: $totalPets</p>";

    echo "<h3>Test 2: Lost Pets Count</h3>";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE owner_id = ? AND status = 'lost'");
    $stmt->execute([$_SESSION['owner_id']]);
    $lostPets = $stmt->fetchColumn();
    echo "<p>✅ Lost pets: $lostPets</p>";

    echo "<h3>Test 3: Recent Scans Count</h3>";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM scans WHERE pet_id IN (SELECT pet_id FROM pets WHERE owner_id = ?) AND scanned_at >= CURRENT_TIMESTAMP - INTERVAL '7 days'");
    $stmt->execute([$_SESSION['owner_id']]);
    $recentScans = $stmt->fetchColumn();
    echo "<p>✅ Recent scans: $recentScans</p>";

    echo "<h3>Test 4: Recent Scan Details</h3>";
    $stmt = $pdo->prepare("SELECT s.scanned_at, p.name as pet_name, p.species, p.breed, p.status FROM scans s JOIN pets p ON s.pet_id = p.pet_id WHERE p.owner_id = ? ORDER BY s.scanned_at DESC LIMIT 5");
    $stmt->execute([$_SESSION['owner_id']]);
    $recentScanDetails = $stmt->fetchAll();
    echo "<p>✅ Recent scan details: " . count($recentScanDetails) . " records</p>";

    echo "<h3>Test 5: Pets with Status</h3>";
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
    echo "<p>✅ Pets with status: " . count($pets) . " records</p>";

    echo "<h3>🎉 All Tests Passed!</h3>";
    echo "<p><a href='owner_dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Dashboard</a></p>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px;'>";
    echo "<h3>❌ Test Failed</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}
?>
