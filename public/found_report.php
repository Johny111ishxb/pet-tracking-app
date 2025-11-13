<?php
require_once '../db/db_connect.php';

$pet_id = $_GET['pet_id'] ?? null;
$scan_id = $_GET['scan_id'] ?? null;

if (!$pet_id) {
    header("Location: ../index.php");
    exit();
}

// Get pet info
$stmt = $pdo->prepare("SELECT * FROM pets WHERE pet_id = ?");
$stmt->execute([$pet_id]);
$pet = $stmt->fetch();

if (!$pet) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $finder_name = $_POST['finder_name'] ?? '';
    $finder_contact = $_POST['finder_contact'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if ($finder_name && $finder_contact) {
        try {
            // Save found report
            $stmt = $pdo->prepare("INSERT INTO found_reports (pet_id, finder_name, finder_contact, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$pet_id, $finder_name, $finder_contact, $message]);
            
            // Show thank you page
            header("Location: thank_you.php?pet_id=" . $pet_id);
            exit();
        } catch(PDOException $e) {
            $error = "Error submitting report: " . $e->getMessage();
        }
    } else {
        $error = "Please provide your name and contact information";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Found Pet Report - Pawsitive Patrol</title>
    <style>
        /* Add similar styling as above */
    </style>
</head>
<body>
    <div class="container">
        <h1>🐕 Found <?= htmlspecialchars($pet['name']) ?>!</h1>
        <p>Thank you for scanning the QR code! Please help reunite this pet with their owner.</p>
        
        <form method="POST">
            <div class="form-group">
                <label>Your Name</label>
                <input type="text" name="finder_name" required>
            </div>
            <div class="form-group">
                <label>Contact Information</label>
                <input type="text" name="finder_contact" required placeholder="Phone number or email">
            </div>
            <div class="form-group">
                <label>Message for the Owner</label>
                <textarea name="message" placeholder="Where did you find the pet? Any additional information..."></textarea>
            </div>
            <button type="submit">Submit Found Report</button>
        </form>
    </div>
</body>
</html>