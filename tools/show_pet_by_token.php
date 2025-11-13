<?php
// tools/show_pet_by_token.php
// Simple local utility: returns JSON for a pet row matching ?token=...
// Warning: this file exposes DB data. Use only on local/dev and remove when done.

require_once __DIR__ . '/../db/db_connect.php';

header('Content-Type: application/json');

$token = $_GET['token'] ?? '';
if (!$token) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing token parameter.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT * FROM pets WHERE qr_code = ?');
    $stmt->execute([$token]);
    $pet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pet) {
        http_response_code(404);
        echo json_encode(['error' => 'Pet not found for token ' . $token]);
        exit;
    }

    // Obfuscate sensitive fields if desired (none here)
    echo json_encode(['pet' => $pet]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'detail' => $e->getMessage()]);
}
