<?php
// tools/qr_image.php
// Serve a saved QR PNG by token: /tools/qr_image.php?token=4452007d156f1175cdc5
// NOTE: Intended for local/dev use only.

$token = $_GET['token'] ?? '';
if (!$token) {
    http_response_code(400);
    echo 'Missing token';
    exit;
}

$path = __DIR__ . '/../qr/' . basename($token) . '.png';
if (!file_exists($path)) {
    http_response_code(404);
    echo 'QR image not found';
    exit;
}

header('Content-Type: image/png');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
