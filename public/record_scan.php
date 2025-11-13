<?php
require_once '../db/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pet_id = $_POST['pet_id'] ?? $_GET['pet_id'] ?? null;
    $scanner_info = $_POST['scanner_info'] ?? 'Anonymous Scanner';
    $location_lat = $_POST['location_lat'] ?? null;
    $location_lng = $_POST['location_lng'] ?? null;
    
    if ($pet_id) {
        try {
            // Record the scan
            $stmt = $pdo->prepare("INSERT INTO scans (pet_id, scanner_info, location_lat, location_lng) VALUES (?, ?, ?, ?)");
            $stmt->execute([$pet_id, $scanner_info, $location_lat, $location_lng]);
            
            // Get pet info to check if lost
            $petStmt = $pdo->prepare("SELECT * FROM pets WHERE pet_id = ?");
            $petStmt->execute([$pet_id]);
            $pet = $petStmt->fetch();
            
            if ($pet && $pet['status'] === 'lost') {
                // Show found report form for lost pets
                header("Location: found_report.php?pet_id=" . $pet_id . "&scan_id=" . $pdo->lastInsertId());
                exit();
            } else {
                // Just show pet info for safe pets
                header("Location: info.php?id=" . $pet_id);
                exit();
            }
        } catch(PDOException $e) {
            error_log("Scan recording error: " . $e->getMessage());
        }
    }
}

// If no POST data, redirect to home
header("Location: ../index.php");
exit();
?>