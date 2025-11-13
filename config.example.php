<?php
// config.example.php - Copy this to config.php and update with your settings

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'pawsitive_patrol');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Application Configuration
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost');
define('DEBUG_MODE', $_ENV['DEBUG_MODE'] ?? true);

// Upload Configuration
define('UPLOAD_MAX_SIZE', $_ENV['UPLOAD_MAX_SIZE'] ?? 5242880); // 5MB
define('ALLOWED_EXTENSIONS', $_ENV['ALLOWED_EXTENSIONS'] ?? 'jpg,jpeg,png,gif');

// Paths
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('QR_PATH', __DIR__ . '/qr_codes/');

// Create upload directories if they don't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!file_exists(QR_PATH)) {
    mkdir(QR_PATH, 0755, true);
}
?>
