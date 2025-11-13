<?php
// test_qr_codes.php - Test QR code generation and URLs
require_once 'db_auto_include.php';

echo "<h2>🔍 QR Code Testing</h2>";

try {
    // Get all pets with their QR tokens
    $stmt = $pdo->prepare("SELECT pet_id, name, qr_token, owner_id FROM pets ORDER BY pet_id");
    $stmt->execute();
    $pets = $stmt->fetchAll();
    
    echo "<p>Testing " . count($pets) . " pets...</p>";
    
    if (count($pets) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 10px;'>Pet ID</th>";
        echo "<th style='padding: 10px;'>Name</th>";
        echo "<th style='padding: 10px;'>QR Token</th>";
        echo "<th style='padding: 10px;'>QR URL</th>";
        echo "<th style='padding: 10px;'>Test Link</th>";
        echo "</tr>";
        
        foreach ($pets as $pet) {
            // Build QR URL
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443 ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            $base = $scriptDir === '/' || $scriptDir === '\\' ? '' : $scriptDir;
            $qr_url = $scheme . '://' . $host . $base . '/includes/pet_info.php?token=' . urlencode($pet['qr_token']);
            
            echo "<tr>";
            echo "<td style='padding: 10px;'>" . $pet['pet_id'] . "</td>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars($pet['name']) . "</td>";
            echo "<td style='padding: 10px; font-family: monospace;'>" . ($pet['qr_token'] ? substr($pet['qr_token'], 0, 12) . "..." : "❌ NULL") . "</td>";
            echo "<td style='padding: 10px; font-size: 12px; word-break: break-all;'>" . htmlspecialchars($qr_url) . "</td>";
            echo "<td style='padding: 10px;'>";
            if ($pet['qr_token']) {
                echo "<a href='" . htmlspecialchars($qr_url) . "' target='_blank' style='background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 5px;'>Test</a>";
            } else {
                echo "❌ No Token";
            }
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Test database connection to pet_info.php
        echo "<h3>🔗 URL Structure Test</h3>";
        $sample_token = $pets[0]['qr_token'] ?? 'test123';
        $test_url = $scheme . '://' . $host . $base . '/includes/pet_info.php?token=' . $sample_token;
        echo "<p><strong>Sample URL:</strong> <code>" . htmlspecialchars($test_url) . "</code></p>";
        echo "<p><strong>Expected File:</strong> <code>" . __DIR__ . "/includes/pet_info.php</code></p>";
        echo "<p><strong>File Exists:</strong> " . (file_exists(__DIR__ . "/includes/pet_info.php") ? "✅ Yes" : "❌ No") . "</p>";
        
        // Check QR token uniqueness
        echo "<h3>🔍 QR Token Analysis</h3>";
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT qr_token) as unique_tokens, COUNT(*) as total_pets FROM pets WHERE qr_token IS NOT NULL");
        $stmt->execute();
        $stats = $stmt->fetch();
        
        echo "<p><strong>Total Pets:</strong> " . count($pets) . "</p>";
        echo "<p><strong>Pets with Tokens:</strong> " . $stats['total_pets'] . "</p>";
        echo "<p><strong>Unique Tokens:</strong> " . $stats['unique_tokens'] . "</p>";
        echo "<p><strong>Duplicates:</strong> " . ($stats['total_pets'] - $stats['unique_tokens']) . "</p>";
        
        if ($stats['total_pets'] != $stats['unique_tokens']) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
            echo "⚠️ <strong>Warning:</strong> Duplicate QR tokens detected! This will cause scanning issues.";
            echo "</div>";
        }
        
    } else {
        echo "<p>❌ No pets found in database.</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; }
th, td { border: 1px solid #ddd; text-align: left; }
code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
</style>
