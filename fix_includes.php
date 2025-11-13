<?php
// fix_includes.php - Batch fix all includes files to use correct database connection

$files_to_fix = [
    'includes/add_pet.php',
    'includes/edit_pet.php', 
    'includes/generate_qr.php',
    'includes/mark_found.php',
    'includes/mark_lost.php',
    'includes/pet.php',
    'includes/pet_info.php',
    'includes/record_scan.php',
    'includes/remove_pet.php',
    'includes/scan_report.php',
    'includes/settings.php',
    'includes/view_pets.php'
];

$old_patterns = [
    "require_once '../db/db_connect.php';",
    "require_once(__DIR__ . '/../db/db_connect.php');",
    "require_once '../db/db_connect.php';"
];

$new_include = "require_once __DIR__ . '/../db_auto_include.php';";

echo "<h2>🔧 Fixing Database Includes</h2>";

foreach ($files_to_fix as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $original_content = $content;
        
        // Replace all old patterns
        foreach ($old_patterns as $pattern) {
            $content = str_replace($pattern, $new_include, $content);
        }
        
        if ($content !== $original_content) {
            file_put_contents($file, $content);
            echo "<p>✅ Fixed: $file</p>";
        } else {
            echo "<p>⚠️ No changes needed: $file</p>";
        }
    } else {
        echo "<p>❌ File not found: $file</p>";
    }
}

echo "<h3>🎉 Batch fix complete!</h3>";
echo "<p>All includes files should now use the correct database connection.</p>";
?>
