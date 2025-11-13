<?php
// db/db_connect.php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'pawsitive_patrol';
$username = 'root';
$password = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Set error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test connection by checking if owners table exists
    $pdo->query("SELECT 1 FROM owners LIMIT 1");
    
} catch(PDOException $e) {
    $error_message = $e->getMessage();
    
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px;'>";
    echo "<h3>❌ Database Connection Failed</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($error_message) . "</p>";
    
    if (strpos($error_message, '2002') !== false) {
        echo "<p><strong>Solution:</strong> MySQL is not running. Please:</p>";
        echo "<ol>";
        echo "<li>Open XAMPP Control Panel</li>";
        echo "<li>Start MySQL service</li>";
        echo "<li>Refresh this page</li>";
        echo "</ol>";
    } elseif (strpos($error_message, '1049') !== false) {
        echo "<p><strong>Solution:</strong> Database doesn't exist. Please create database 'pawsitive_patrol' first.</p>";
    } elseif (strpos($error_message, '42S02') !== false) {
        // Table doesn't exist error - this is normal for fresh install
        // Continue without showing error
    } else {
        echo "<p><strong>Debug Info:</strong></p>";
        echo "<ul>";
        echo "<li>Host: " . htmlspecialchars($host) . "</li>";
        echo "<li>Database: " . htmlspecialchars($dbname) . "</li>";
        echo "<li>Username: " . htmlspecialchars($username) . "</li>";
        echo "<li>Password: " . (empty($password) ? '(empty)' : '***') . "</li>";
        echo "</ul>";
        die();
    }
}
?>