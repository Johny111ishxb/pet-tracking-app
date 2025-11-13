<?php
// db/db_auto.php - Automatically include the correct database configuration

// Use Render database config if deployed, otherwise use local config
if (getenv('DB_HOST') || getenv('DATABASE_URL')) {
    require_once __DIR__ . '/db_connect_render.php';
} else {
    require_once __DIR__ . '/db_connect.php';
}
?>
