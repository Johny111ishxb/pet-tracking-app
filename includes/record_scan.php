<?php
// public/record_scan.php - Records scan from public page

header('Content-Type: application/json');

// Allow CORS (optional, but safe for same-origin)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../db/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);

$token = $input['token'] ?? null;
$lat = $input['lat'] ?? null;
$lng = $input['lng'] ?? null;

if (!$token || !is_numeric($lat) || !is_numeric($lng)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit();
}

// Get pet_id from token (only if status = 'Active')
$stmt = $pdo->prepare("
    SELECT pet_id FROM pets 
    WHERE qr_code = ? AND status = 'Active'
");
$stmt->execute([$token]);
$pet = $stmt->fetch();

if ($pet) {
    $stmt = $pdo->prepare("
        INSERT INTO scans (pet_id, location_lat, location_lng) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$pet['pet_id'], $lat, $lng]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Pet not found or already found']);
}
?>