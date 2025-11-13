<?php
// db/db_connect_render.php - Database connection for Render deployment

// Enable error reporting only in development
if (getenv('DEBUG_MODE') === '1') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Get database configuration from environment variables
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'pawsitive_patrol';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

// Render provides DATABASE_URL for PostgreSQL
if (getenv('DATABASE_URL')) {
    // Parse DATABASE_URL if provided (format: postgres://user:pass@host:port/db)
    $db_url = parse_url(getenv('DATABASE_URL'));
    $host = $db_url['host'];
    $dbname = ltrim($db_url['path'], '/');
    $username = $db_url['user'];
    $password = $db_url['pass'];
    $port = $db_url['port'] ?? 5432;
}

try {
    // Create PDO connection with additional options for production
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    // Use PostgreSQL connection for Render, MySQL for local
    if (getenv('DATABASE_URL') || getenv('DB_HOST')) {
        // PostgreSQL connection for Render
        $port = $port ?? 5432;
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password, $options);
    } else {
        // MySQL connection for local development
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
    }
    
    // Test connection by checking if owners table exists
    $pdo->query("SELECT 1 FROM owners LIMIT 1");
    
} catch(PDOException $e) {
    $error_message = $e->getMessage();
    
    // In production, log errors instead of displaying them
    if (getenv('DEBUG_MODE') !== '1') {
        error_log("Database connection failed: " . $error_message);
        die("Database connection failed. Please check the logs.");
    }
    
    // Development error display
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px;'>";
    echo "<h3>❌ Database Connection Failed</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($error_message) . "</p>";
    
    if (strpos($error_message, '2002') !== false) {
        echo "<p><strong>Solution:</strong> Database server is not reachable.</p>";
    } elseif (strpos($error_message, '1049') !== false) {
        echo "<p><strong>Solution:</strong> Database doesn't exist. Check database name.</p>";
    } elseif (strpos($error_message, '1045') !== false) {
        echo "<p><strong>Solution:</strong> Access denied. Check username and password.</p>";
    } elseif (strpos($error_message, '42S02') !== false) {
        echo "<p><strong>Note:</strong> Tables don't exist yet. Run database migration.</p>";
    }
    
    echo "<p><strong>Environment Info:</strong></p>";
    echo "<ul>";
    echo "<li>Host: " . htmlspecialchars($host) . "</li>";
    echo "<li>Database: " . htmlspecialchars($dbname) . "</li>";
    echo "<li>Username: " . htmlspecialchars($username) . "</li>";
    echo "<li>Password: " . (empty($password) ? '(empty)' : '***') . "</li>";
    echo "</ul>";
    echo "</div>";
    
    if (getenv('DEBUG_MODE') === '1') {
        die();
    }
}
?>
