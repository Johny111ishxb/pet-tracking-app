<?php
// setup_db.php - Simple database setup without table checks
echo "<h2>🚀 Database Setup for Pet Tracking App</h2>";

try {
    // Get database configuration directly
    $host = getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? 'localhost';
    $dbname = getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? 'pawsitive_patrol';
    $username = getenv('DB_USER') ?: $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? 'root';
    $password = getenv('DB_PASS') ?: $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? '';
    $port = 5432;

    $database_url = getenv('DATABASE_URL') ?: $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? null;
    if ($database_url) {
        $db_url = parse_url($database_url);
        $host = $db_url['host'];
        $dbname = ltrim($db_url['path'], '/');
        $username = $db_url['user'];
        $password = $db_url['pass'];
        $port = $db_url['port'] ?? 5432;
    }

    echo "<p><strong>Connecting to:</strong> $host:$port/$dbname as $username</p>";

    // Create direct PDO connection
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password, $options);
    echo "<p>✅ <strong>Database connection successful!</strong></p>";

    // Read PostgreSQL schema
    $sql_file = __DIR__ . '/db/pawsitive_patrol_postgres.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    $sql = file_get_contents($sql_file);
    echo "<p>📁 <strong>SQL file loaded successfully</strong></p>";

    // Execute the entire SQL as one statement
    $pdo->exec($sql);
    echo "<p>✅ <strong>Database schema created successfully!</strong></p>";

    echo "<h3>🎉 Setup Complete!</h3>";
    echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Application</a></p>";
    
    // Create .migrated file
    file_put_contents('.migrated', date('Y-m-d H:i:s'));

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px;'>";
    echo "<h3>❌ Setup Failed</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Host:</strong> " . htmlspecialchars($host ?? 'unknown') . "</p>";
    echo "<p><strong>Database:</strong> " . htmlspecialchars($dbname ?? 'unknown') . "</p>";
    echo "<p><strong>Username:</strong> " . htmlspecialchars($username ?? 'unknown') . "</p>";
    echo "</div>";
}
?>
