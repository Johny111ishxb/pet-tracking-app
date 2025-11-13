<?php
// fix_existing_pets.php - Fix existing pets that have NULL qr_token
require_once 'db_auto_include.php';

echo "<h2>🔧 Fixing Existing Pets QR Tokens</h2>";

try {
    // Find pets with NULL or empty qr_token
    $stmt = $pdo->prepare("SELECT pet_id, name FROM pets WHERE qr_token IS NULL OR qr_token = ''");
    $stmt->execute();
    $pets_to_fix = $stmt->fetchAll();
    
    echo "<p>Found " . count($pets_to_fix) . " pets without QR tokens.</p>";
    
    if (count($pets_to_fix) > 0) {
        foreach ($pets_to_fix as $pet) {
            // Generate unique QR token
            $maxAttempts = 5;
            $qr_token = null;
            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                $candidate = bin2hex(random_bytes(10));
                $check = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE qr_token = ?");
                $check->execute([$candidate]);
                if ($check->fetchColumn() == 0) {
                    $qr_token = $candidate;
                    break;
                }
            }
            
            if ($qr_token) {
                // Update pet with new QR token
                $update = $pdo->prepare("UPDATE pets SET qr_token = ? WHERE pet_id = ?");
                $update->execute([$qr_token, $pet['pet_id']]);
                echo "<p>✅ Fixed pet: " . htmlspecialchars($pet['name']) . " (ID: {$pet['pet_id']}) - Token: " . substr($qr_token, 0, 8) . "...</p>";
            } else {
                echo "<p>❌ Failed to generate token for pet: " . htmlspecialchars($pet['name']) . "</p>";
            }
        }
        
        echo "<h3>🎉 All existing pets have been fixed!</h3>";
    } else {
        echo "<p>✅ All pets already have QR tokens.</p>";
    }
    
    // Show summary
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE qr_token IS NOT NULL AND qr_token != ''");
    $stmt->execute();
    $total_with_tokens = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pets");
    $stmt->execute();
    $total_pets = $stmt->fetchColumn();
    
    echo "<div style='background: #e8f5e9; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>📊 Summary</h3>";
    echo "<p><strong>Total Pets:</strong> $total_pets</p>";
    echo "<p><strong>Pets with QR Tokens:</strong> $total_with_tokens</p>";
    echo "<p><strong>Status:</strong> " . ($total_pets == $total_with_tokens ? "✅ All pets have QR tokens" : "⚠️ Some pets still need tokens") . "</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
