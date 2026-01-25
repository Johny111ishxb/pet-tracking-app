<?php
// migrate.php - Database migration script for Render deployment
// Run this once after deployment to set up the database

require_once 'db/db_connect_render.php';

echo "<h2>🚀 Database Migration for Pet Tracking App</h2>";

try {
    // Use PostgreSQL schema for Render, MySQL for local
    if (getenv('DATABASE_URL') || getenv('DB_HOST')) {
        $sql_file = __DIR__ . '/db/pawsitive_patrol_postgres.sql';
    } else {
        $sql_file = __DIR__ . '/db/pawsitive_patrol.sql';
    }
    
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    $sql = file_get_contents($sql_file);
    
    // Split SQL into individual statements - handle multi-statement CREATE TABLE blocks
    $statements = array();
    $current_statement = '';
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Skip comments and empty lines
        if (empty($trimmed) || preg_match('/^(--|#)/', $trimmed)) {
            continue;
        }
        
        $current_statement .= $line . "\n";
        
        // If line ends with semicolon, it's a complete statement
        if (substr($trimmed, -1) === ';') {
            $statements[] = trim($current_statement);
            $current_statement = '';
        }
    }
    
    echo "<p>📁 Found " . count($statements) . " SQL statements to execute...</p>";
    
    $pdo->beginTransaction();
    
    foreach ($statements as $i => $statement) {
        if (!empty(trim($statement))) {
            try {
                $pdo->exec($statement);
                echo "<p>✅ Statement " . ($i + 1) . " executed successfully</p>";
                echo "<p><small>" . substr($statement, 0, 100) . "...</small></p>";
            } catch (PDOException $e) {
                // Ignore "table already exists" errors
                if (strpos($e->getMessage(), '42P01') === false && strpos($e->getMessage(), '42S01') === false) {
                    throw $e;
                }
                echo "<p>⚠️ Statement " . ($i + 1) . " skipped (table exists): " . substr($statement, 0, 50) . "...</p>";
            }
        }
    }
    
    $pdo->commit();
    
    echo "<h3>🎉 Database migration completed successfully!</h3>";
    echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Application</a></p>";
    
    // Create .migrated file to indicate migration is complete
    file_put_contents('.migrated', date('Y-m-d H:i:s'));
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollback();
    }
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px;'>";
    echo "<h3>❌ Migration Failed</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
