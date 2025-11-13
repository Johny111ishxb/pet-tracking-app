<?php
session_start();
require_once(__DIR__ . '/../db/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['owner_id'])) {
    $pet_id = $_POST['pet_id'] ?? null;
    $owner_id = $_SESSION['owner_id'];
    
    if ($pet_id) {
        try {
            $stmt = $pdo->prepare("UPDATE pets SET status = 'lost' WHERE pet_id = ? AND owner_id = ?");
            $stmt->execute([$pet_id, $owner_id]);
            $_SESSION['success'] = "Pet marked as lost!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error updating pet status: " . $e->getMessage();
        }
    }
}

header("Location: pet.php?id=" . $pet_id);
exit();
?>