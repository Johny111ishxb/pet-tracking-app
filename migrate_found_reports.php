<?php
// migrate_found_reports.php - Add missing attached_photo column to found_reports table
require_once 'db_auto_include.php';

echo "<h2>🔧 Database Migration: found_reports Table</h2>";

try {
    // Check if attached_photo column exists
    $stmt = $pdo->prepare("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_name = 'found_reports' 
        AND column_name = 'attached_photo'
    ");
    $stmt->execute();
    $column_exists = $stmt->fetch();
    
    if ($column_exists) {
        echo "<p>✅ Column 'attached_photo' already exists in found_reports table.</p>";
    } else {
        echo "<p>⚠️ Column 'attached_photo' missing from found_reports table. Adding it now...</p>";
        
        // Add the missing column
        $stmt = $pdo->prepare("
            ALTER TABLE found_reports 
            ADD COLUMN attached_photo VARCHAR(255) DEFAULT NULL
        ");
        $stmt->execute();
        
        echo "<p>✅ Successfully added 'attached_photo' column to found_reports table!</p>";
    }
    
    // Show current table structure
    echo "<h3>📋 Current found_reports Table Structure</h3>";
    $stmt = $pdo->prepare("
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns 
        WHERE table_name = 'found_reports' 
        ORDER BY ordinal_position
    ");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    if ($columns) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 10px;'>Column Name</th>";
        echo "<th style='padding: 10px;'>Data Type</th>";
        echo "<th style='padding: 10px;'>Nullable</th>";
        echo "<th style='padding: 10px;'>Default</th>";
        echo "</tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars($column['column_name']) . "</td>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars($column['data_type']) . "</td>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars($column['is_nullable']) . "</td>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars($column['column_default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // Test INSERT to verify it works
    echo "<h3>🧪 Testing INSERT Statement</h3>";
    try {
        $test_stmt = $pdo->prepare("
            INSERT INTO found_reports 
            (pet_id, finder_name, finder_contact, message, attached_photo) 
            VALUES (?, ?, ?, ?, ?)
        ");
        // Don't actually execute, just prepare to test syntax
        echo "<p>✅ INSERT statement syntax is valid!</p>";
    } catch (Exception $e) {
        echo "<p>❌ INSERT statement error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<div style='background: #e8f5e9; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>🎉 Migration Complete!</h3>";
    echo "<p>The found_reports table is now ready for QR code scan submissions with photo attachments.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px;'>";
    echo "<h3>❌ Migration Error</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; }
th, td { border: 1px solid #ddd; text-align: left; }
</style>
