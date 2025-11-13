<?php
// pet_info.php - Public page for QR code scanning
require_once __DIR__ . '/../db_auto_include.php';
session_start();

if (isset($_SESSION['toast'])) {
    echo "
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const msg = " . json_encode($_SESSION['toast']) . ";
        showToast(msg);
    });
    </script>
    ";
    unset($_SESSION['toast']); 
}

// Get token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Invalid QR code. No token provided.");
}

$name = '';
$contact = '';
$photo_filename = null;
try {
    // Get pet information
    $stmt = $pdo->prepare("
        SELECT p.*, o.name as owner_name, o.phone as owner_phone, o.email as owner_email 
        FROM pets p 
        LEFT JOIN owners o ON p.owner_id = o.owner_id 
        WHERE p.qr_token = ?
    ");
    $stmt->execute([$token]);
    $pet = $stmt->fetch();

    if (!$pet) {
        die("Pet not found. This QR code may be invalid or the pet has been removed.");
    }else if($pet['status'] == "active"){
        header("Location: qr_inactive.php");
        exit();
    }

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        // Record the scan (if location is available)
    
    if (isset($_FILES['attached-photo']) && $_FILES['attached-photo']['error'] === UPLOAD_ERR_OK) {
        $tmpFile = $_FILES['attached-photo']['tmp_name'];
        $origName = basename($_FILES['attached-photo']['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed)) {
            $errors[] = 'Invalid image type. Allowed: jpg, png, gif.';
        } elseif ($_FILES['attached-photo']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'File too large (max 5MB).';
        } else {
            
            $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'attached-photo';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = $uploadDir . DIRECTORY_SEPARATOR . $newName;
            if (move_uploaded_file($tmpFile, $dest)) {
                $photo_filename = $newName;
            } else {
                $errors[] = 'Failed to save uploaded file. Please check directory permissions.';
            }
        }
    }

    if(empty($photo_filename)){
        echo "
        <script>
        alert('Please attach a photo as proof that you\\'ve found the pet.');
        window.history.back();
        </script>
        ";
        exit();
    }else{
        $name = $_POST['name'];
        $contact = $_POST['contact'];
        
        // Check for location data in both POST and GET
        $lat = $_POST['lat'] ?? $_GET['lat'] ?? null;
        $lng = $_POST['lng'] ?? $_GET['lng'] ?? null;
        
        if ($lat && $lng) {
            $lat = floatval($lat);
            $lng = floatval($lng);
            
            $location_info = "Lat: $lat, Lng: $lng, Contact: $contact";
            $stmt = $pdo->prepare("
                INSERT INTO scans (pet_id, location, scanner_ip) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$pet['pet_id'], $location_info, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        } else { 
            // Record scan without location
            $stmt = $pdo->prepare("
                INSERT INTO scans (pet_id, location, scanner_ip) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$pet['pet_id'], "Contact: $contact", $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        }



        $stmt = $pdo->prepare("INSERT INTO found_reports
        (pet_id, finder_name, finder_contact, message, attached_photo) VALUES (?, ?, ?, ?, ?)");

        $stmt->execute([$pet['pet_id'], $name, $contact ,$_POST['message-input'], $photo_filename]);
        
        $_SESSION['toast'] = "Report submitted successfully!";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }    
    
    }
    

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pet['name']) ?> - Pawsitive Patrol</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #4a6fa5 0%, #2c3e50 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .pet-info {
            padding: 30px;
        }

        .pet-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            background: #eef4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #4a6fa5;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .pet-photo img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .pet-name {
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .pet-details {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
            font-size: 16px;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .info-section {
            margin-bottom: 25px;
        }

        .info-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .info-item {
            margin-bottom: 12px;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .info-value {
            color: #2c3e50;
            font-size: 16px;
        }

        .owner-contact {
            background: #e8f5e9;
            border: 2px solid #c8e6c9;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }

        .owner-contact h3 {
            color: #2d5016;
            margin-bottom: 15px;
        }

        .contact-info {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .contact-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }

        .contact-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .contact-btn.call {
            background: #28a745;
            color: white;
        }

        .contact-btn.message {
            background: #dc3545;
            color: white;
        }

        .contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .emergency-alert {
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }



        .form{
            display: none;
            position: absolute;
            top: 5%;
            left: 50%;
            translate: -50%;
            width: 400px; 
            border-radius: 10px;
            background: #fff;
            box-shadow: 0px 0px 10px 10px rgba(151, 10, 29, 0.15);
            padding: 15px;
            border: 2px solid #dc3545;
            
        }
        
        .form h1{
            text-align: center;
        }

        .form-group{
            display: flex;
            flex-direction: column;
        }

        input{
            width: 100%;
            padding: 15px;
            border: 2px solid #e8eeff;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8faff;
        }

        input:focus{
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }


        label{
            font-size: 20px;
            font-weight: 700;
            margin-top: 30px;
            display: inline-block;
        }

        #message-input{
            width: 100%;
            height: 200px;
            padding: 15px;
            border: 2px solid #e8eeff;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8faff;
            
        }

        #message-input:focus{
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        #close-btn{
            width:80px;
            height: 40px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            color: #fff;
            background-color: #dc3545;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        #submit-report-btn{
            width:120px;
            height:40px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            color: #fff;
            background-color: #28a745;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        #close-btn:hover, #submit-report-btn:hover{
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .button-container{
            display: flex;
            justify-content: center;   
            gap: 10px;
            margin-top: 40px;          
        }

         .file-upload {
            position: relative;
            border: 2px dashed #e8eeff;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            background: #f8faff;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .file-upload input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-icon {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 15px;
        }

        .file-upload-text {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .file-upload-hint {
            color: #6c757d;
            font-size: 14px;
        }

        .preview-container {
            text-align: center;
            margin-top: 15px;
        }

        .preview-image {
            max-width: 150px;
            max-height: 150px;
            border-radius: 12px;
            border: 3px solid #e8eeff;
            display: none;
        }

        .toast {
        position: fixed;
        top: 30px;
        right: 30px;
        background: #4CAF50;
        color: white;
        padding: 15px 25px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        opacity: 0;
        transition: opacity 0.5s, transform 0.5s;
        transform: translateY(20px);
        z-index: 1000;
        }
        .toast.show {
        opacity: 1;
        transform: translateY(0);
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .scan-message {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .scan-message {
            text-align: center;
            padding: 20px;
            background: #e8f5e9;
            color: #2d5016;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: 600;
            border-top: 1px solid #e0e0e0;
        }

        .location-status {
            text-align: center;
            padding: 15px;
            background: #e3f2fd;
            color: #1565c0;
            border-radius: 8px;
            margin-top: 10px;
            font-weight: 600;
            border: 1px solid #bbdefb;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .pet-info {
                padding: 20px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .contact-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="toast" id="toast"></div>
        <div class="form" id="form">

            <h1>Report This Scan</h1>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                <label>Founder's Name</label>
                <input type="text" name="name" id="contact" required value="<?= htmlspecialchars($name) ?>" placeholder="Enter your name">

                <label>Founder's Contact</label>
                <input type="tel" name="contact" id="contact" required value="<?= htmlspecialchars($contact) ?>" placeholder="Enter your contact number">
                </div>
                <label for="message-input">Enter Your Message</label>
                <textarea name="message-input" id="message-input"  placeholder="Type your message..." required></textarea>
                <label for="attached-photo">Attach Photo Here</label>
                <div class="file-upload">
                    <input type="file" id="attached-photo" name="attached-photo" accept="image/*">
                    <div class="file-upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="file-upload-text">Click to upload photo</div>
                    <div class="file-upload-hint">JPG, PNG, GIF (Max 5MB)</div>
                </div>
                <div class="preview-container">
                    <img id="preview-image" class="preview-image" alt="Preview">
                </div>
                <div class="button-container">
                    <button type="submit" id="submit-report-btn">Submit</button>
                    <button type="button" id="close-btn">Close</button>
                </div>
            </form>

        </div>

        <!-- HEADER -->
        <div class="header">
            <h1>🐾 Pawsitive Patrol</h1>
            <p>Pet Information & Owner Contact</p>
        </div>

        <!-- PET INFORMATION -->
        <div class="pet-info">
            <?php if ($pet['status'] === 'lost'): ?>
                <div class="emergency-alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>EMERGENCY: This pet is LOST!</strong><br>
                    Please contact the owner immediately if you find this pet.
                </div>
            <?php endif; ?>

            <!-- Pet Photo -->
            <div class="pet-photo">
                <?php if (!empty($pet['photo']) && file_exists('./uploads/' . $pet['photo'])): ?>
                    <img src="./uploads/<?= htmlspecialchars($pet['photo']) ?>" alt="<?= htmlspecialchars($pet['name']) ?>">
                <?php else: ?>
                    <?= $pet['type'] == 'Dog' ? '🐕' : ($pet['type'] == 'Cat' ? '🐈' : '🐾') ?>
                <?php endif; ?>
            </div>

            <h1 class="pet-name"><?= htmlspecialchars($pet['name']) ?></h1>
            <div class="pet-details">
                <?= htmlspecialchars($pet['type']) ?> • 
                <?= htmlspecialchars($pet['breed'] ?? 'Mixed') ?> • 
                <?= htmlspecialchars($pet['color']) ?>
            </div>

            <!-- Basic Information -->
            <div class="info-section">
                <h3><i class="fas fa-info-circle"></i> Pet Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Type</div>
                        <div class="info-value"><?= htmlspecialchars($pet['type']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Breed</div>
                        <div class="info-value"><?= htmlspecialchars($pet['breed'] ?? 'Mixed') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Color</div>
                        <div class="info-value"><?= htmlspecialchars($pet['color']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Gender</div>
                        <div class="info-value"><?= htmlspecialchars($pet['gender'] ?? 'Not specified') ?></div>
                    </div>
                    <?php if ($pet['age']): ?>
                    <div class="info-item">
                        <div class="info-label">Age</div>
                        <div class="info-value">
                            <?= $pet['age'] ?> 
                            <?= isset($pet['age_unit']) && $pet['age_unit'] === 'months' ? 'months' : 'years' ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Description -->
            <?php if (!empty($pet['description'])): ?>
            <div class="info-section">
                <h3><i class="fas fa-file-alt"></i> Description</h3>
                <div class="info-card">
                    <?= nl2br(htmlspecialchars($pet['description'])) ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Emergency Notes -->
            <?php if (!empty($pet['emergency_notes'])): ?>
            <div class="info-section">
                <h3><i class="fas fa-exclamation-circle"></i> Important Notes</h3>
                <div class="info-card" style="background: #fff3cd; border-color: #ffeaa7;">
                    <?= nl2br(htmlspecialchars($pet['emergency_notes'])) ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Owner Contact -->
            <div class="info-section">
                <h3><i class="fas fa-user"></i> Owner Information</h3>
                <div class="owner-contact">
                    <h3>Contact the Owner</h3>
                    <div class="contact-info">
                        <strong><?= htmlspecialchars($pet['owner_name']) ?></strong>
                    </div>
                    <?php if (!empty($pet['owner_phone'])): ?>
                        <div class="contact-info">
                            📞 <?= htmlspecialchars($pet['owner_phone']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($pet['owner_email'])): ?>
                        <div class="contact-info">
                            ✉️ <?= htmlspecialchars($pet['owner_email']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="contact-actions">
                        <?php if (!empty($pet['owner_phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($pet['owner_phone']) ?>" class="contact-btn call">
                                <i class="fas fa-phone"></i> Call
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($pet['owner_phone'])): ?>
                            <a href="#" class="contact-btn message" id="report-form">
                                <i class="fas fa-comment"></i> Report Scan
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="scan-message">
                <i class="fas fa-qrcode"></i> This scan has been recorded to help locate the pet if needed.
            </div>
            
            <div id="location-status" class="location-status" style="display: none;">
                <i class="fas fa-map-marker-alt"></i> <span id="location-text">Getting location...</span>
            </div>
        </div>
    </div>

    <script>

        const form = document.getElementById("form");
        const closeBtn = document.getElementById("close-btn");
        const openBtn = document.getElementById("report-form");


        openBtn.addEventListener("click", function(event){
            event.preventDefault();
            form.style.display = "block";
            form.style.zIndex = 999;
        });
        
        closeBtn.onclick = function(){
            form.style.display = "none";
        };


        document.getElementById("attached-photo").addEventListener('change', function(e){
            const preview = document.getElementById('preview-image');
            const file = e.target.files[0];


            if(file){
                const reader = new FileReader();
                reader.onload = function(e){
                    preview.src = e.target.result;
                    preview.style.display = "block";
                }
                reader.readAsDataURL(file);
            }else{
                preview.style.display = "none";
            }
        });

        function showToast(message) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
        }


        
        // Try to get location for scan recording
        let userLocation = null;
        const locationStatus = document.getElementById('location-status');
        const locationText = document.getElementById('location-text');
        
        if (navigator.geolocation) {
            locationStatus.style.display = 'block';
            locationText.textContent = 'Getting location...';
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    console.log('Location captured:', userLocation);
                    
                    // Update status
                    locationText.innerHTML = '<i class="fas fa-check-circle" style="color: green;"></i> Location captured successfully';
                    
                    // Add location to URL for reference
                    const url = new URL(window.location.href);
                    url.searchParams.set('lat', position.coords.latitude);
                    url.searchParams.set('lng', position.coords.longitude);
                    window.history.replaceState({}, '', url);
                    
                    // Add hidden fields to form if it exists
                    const form = document.querySelector('form');
                    if (form && !document.getElementById('lat-input')) {
                        const latInput = document.createElement('input');
                        latInput.type = 'hidden';
                        latInput.name = 'lat';
                        latInput.id = 'lat-input';
                        latInput.value = position.coords.latitude;
                        form.appendChild(latInput);
                        
                        const lngInput = document.createElement('input');
                        lngInput.type = 'hidden';
                        lngInput.name = 'lng';
                        lngInput.id = 'lng-input';
                        lngInput.value = position.coords.longitude;
                        form.appendChild(lngInput);
                    }
                    
                    // Hide status after 3 seconds
                    setTimeout(() => {
                        locationStatus.style.display = 'none';
                    }, 3000);
                },
                function(error) {
                    console.log('Location access denied or unavailable:', error.message);
                    locationText.innerHTML = '<i class="fas fa-exclamation-triangle" style="color: orange;"></i> Location not available - will record without location';
                    
                    // Hide status after 5 seconds
                    setTimeout(() => {
                        locationStatus.style.display = 'none';
                    }, 5000);
                }
            );
        } else {
            locationStatus.style.display = 'block';
            locationText.innerHTML = '<i class="fas fa-times-circle" style="color: red;"></i> Geolocation not supported';
            setTimeout(() => {
                locationStatus.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>