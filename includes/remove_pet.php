<?php
session_start();
require_once(__DIR__ . '/../db/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['owner_id'])) {
    $pet_id = $_POST['pet_id'] ?? null;
    $owner_id = $_SESSION['owner_id'];
    
    if ($pet_id) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Verify the pet belongs to the current owner
            $verifyStmt = $pdo->prepare("SELECT * FROM pets WHERE pet_id = ? AND owner_id = ?");
            $verifyStmt->execute([$pet_id, $owner_id]);
            $pet = $verifyStmt->fetch();
            
            if (!$pet) {
                $_SESSION['error'] = "Pet not found or you don't have permission to remove it.";
                $pdo->rollBack();
                header("Location: view_pets.php");
                exit();
            }
            
            // Delete related scans
            $scanStmt = $pdo->prepare("DELETE FROM scans WHERE pet_id = ?");
            $scanStmt->execute([$pet_id]);
            
            // Delete related found reports
            $reportStmt = $pdo->prepare("DELETE FROM found_reports WHERE pet_id = ?");
            $reportStmt->execute([$pet_id]);
            
            // Delete QR code file if exists
            if (!empty($pet['qr_code']) && file_exists("../qr_codes/{$pet['qr_code']}.png")) {
                unlink("../qr_codes/{$pet['qr_code']}.png");
            }
            
            // Delete the pet
            $petStmt = $pdo->prepare("DELETE FROM pets WHERE pet_id = ? AND owner_id = ?");
            $petStmt->execute([$pet_id, $owner_id]);
            
            // Commit transaction
            $pdo->commit();
            
            $_SESSION['success'] = "Pet '{$pet['name']}' has been successfully removed from the system.";
            header("Location: view_pets.php");
            exit();
            
        } catch(PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error removing pet: " . $e->getMessage();
            header("Location: pet.php?id=" . $pet_id);
            exit();
        }
    }
}

// If no POST data or invalid request, redirect to view pets
header("Location: view_pets.php");
exit();
?>