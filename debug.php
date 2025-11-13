<?php
// debug.php - Check environment variables on Render
echo "<h2>🔍 Environment Debug Information</h2>";

echo "<h3>Environment Variables:</h3>";
echo "<ul>";
echo "<li><strong>DB_HOST:</strong> " . (getenv('DB_HOST') ?: 'Not set') . "</li>";
echo "<li><strong>DB_NAME:</strong> " . (getenv('DB_NAME') ?: 'Not set') . "</li>";
echo "<li><strong>DB_USER:</strong> " . (getenv('DB_USER') ?: 'Not set') . "</li>";
echo "<li><strong>DB_PASS:</strong> " . (getenv('DB_PASS') ? '***' : 'Not set') . "</li>";
echo "<li><strong>DATABASE_URL:</strong> " . (getenv('DATABASE_URL') ? '***' : 'Not set') . "</li>";
echo "<li><strong>PORT:</strong> " . (getenv('PORT') ?: 'Not set') . "</li>";
echo "</ul>";

echo "<h3>Detection Logic:</h3>";
if (getenv('DB_HOST') || getenv('DATABASE_URL')) {
    echo "<p>✅ <strong>Render environment detected</strong> - Should use PostgreSQL</p>";
    echo "<p>Will include: <code>db/db_connect_render.php</code></p>";
} else {
    echo "<p>❌ <strong>Local environment detected</strong> - Will use MySQL</p>";
    echo "<p>Will include: <code>db/db_connect.php</code></p>";
}

echo "<h3>All Environment Variables:</h3>";
echo "<pre>";
foreach ($_ENV as $key => $value) {
    if (strpos($key, 'DB_') === 0 || strpos($key, 'DATABASE') === 0) {
        echo "$key = " . (strlen($value) > 20 ? substr($value, 0, 20) . '...' : $value) . "\n";
    }
}
echo "</pre>";

echo "<h3>Test Database Connection:</h3>";
try {
    if (getenv('DB_HOST') || getenv('DATABASE_URL')) {
        require_once 'db/db_connect_render.php';
        echo "<p>✅ Successfully connected to database!</p>";
    } else {
        echo "<p>❌ Using local config - won't work on Render</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
