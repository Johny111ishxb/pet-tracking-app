<?php
// db_auto_include.php - Automatically include the correct database configuration
// Use this instead of directly including db_connect.php

// Use Render database config if deployed, otherwise use local config
// Check multiple ways Render might set environment variables
if (getenv('DATABASE_URL') || getenv('DB_HOST') || $_ENV['DATABASE_URL'] ?? false || $_ENV['DB_HOST'] ?? false || isset($_SERVER['DATABASE_URL']) || isset($_SERVER['DB_HOST'])) {
    require_once __DIR__ . '/db/db_connect_render.php';
} else {
    require_once __DIR__ . '/db/db_connect.php';
}
?>
