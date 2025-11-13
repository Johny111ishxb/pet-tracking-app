<?php
session_start();
if (!isset($_SESSION['owner_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../db/db_connect.php';

$name = $type = $breed = $color = $age_years = $age_months = '';
$errors = [];
$success = '';

// QR display variables
$show_qr = false;
$qr_display_url = null;
$qr_download_path = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $breed = trim($_POST['breed'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $age_years = trim($_POST['age_years'] ?? '0');
    $age_months = trim($_POST['age_months'] ?? '0');

    // Basic validation
    if (empty($name)) {
        $errors[] = "Pet name is required";
    }

    if (empty($type)) {
        $errors[] = "Pet type is required";
    }

    // Handle file upload (optional)
    $photo_filename = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $tmpFile = $_FILES['profile_picture']['tmp_name'];
        $origName = basename($_FILES['profile_picture']['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];

        if (!in_array($ext, $allowed)) {
            $errors[] = 'Invalid image type. Allowed: jpg, png, gif.';
        } elseif ($_FILES['profile_picture']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'File too large (max 5MB).';
        } else {
            // Save to uploads/ with a unique name
            $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $newName;
            if (move_uploaded_file($tmpFile, $dest)) {
                $photo_filename = $newName;
            } else {
                $errors[] = 'Failed to save uploaded file.';
            }
        }
    }

    if (empty($errors)) {
        // Prepare age storage: prefer years if provided, otherwise months
        $iy = (int)$age_years;
        $im = (int)$age_months;
        if ($iy > 0) {
            $age = $iy;
            $age_unit = 'years';
        } elseif ($im > 0) {
            $age = $im;
            $age_unit = 'months';
        } else {
            $age = null;
            $age_unit = 'years';
        }

        try {
            $owner_id = $_SESSION['owner_id'];
            $stmt = $pdo->prepare("INSERT INTO pets (owner_id, name, type, breed, color, age, age_unit, photo, status, date_added) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'safe', NOW())");
            $stmt->execute([
                $owner_id,
                $name,
                $type,
                $breed ? $breed : null,
                $color ? $color : null,
                $age,
                $age_unit,
                $photo_filename
            ]);

            // Get inserted pet id
            $pet_id = $pdo->lastInsertId();

            // Generate a unique QR token and attempt to save a PNG under qr/
            $maxAttempts = 5;
            $qr_token = null;
            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                $candidate = bin2hex(random_bytes(10));
                $check = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE qr_code = ?");
                $check->execute([$candidate]);
                if ($check->fetchColumn() == 0) {
                    $qr_token = $candidate;
                    break;
                }
            }

            if ($qr_token) {
                // Build the URL that will be embedded in the QR code — compute base dynamically
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443 ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                $base = $scriptDir === '/' || $scriptDir === '\\' ? '' : $scriptDir;
                $qr_url = $scheme . '://' . $host . $base . '/includes/pet_info.php?token=' . $qr_token;
                $google_qr_url = "https://chart.googleapis.com/chart?chs=600x600&cht=qr&chl=" . urlencode($qr_url) . "&choe=UTF-8";


                // Ensure QR PNG exists using centralized helper
                require_once './qr_helper.php';
                $qrInfo = ensure_qr_png($qr_token, $qr_url, 8);
                $saved = $qrInfo['saved'];

                // Update the pet record with the qr_code token regardless of whether PNG was saved
                $update = $pdo->prepare("UPDATE pets SET qr_code = ? WHERE pet_id = ?");
                $update->execute([$qr_token, $pet_id]);

                // Prepare JSON-friendly info
                $qr_local_path = 'qr/' . $qr_token . '.png';
                $qr_image_url = null;
                if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . $qr_local_path)) {
                    // Build fully-qualified URL based on current request
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443 ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'];
                    // Compute script directory base (strip trailing / if any)
                    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                    $base = $scriptDir === '/' || $scriptDir === '\\' ? '' : $scriptDir;
                    $qr_image_url = $scheme . '://' . $host . $base . '/' . $qr_local_path;
                }

                $response = [
                    'pet_id' => (int)$pet_id,
                    'qr_token' => $qr_token,
                    'qr_url' => $qr_url,
                    'qr_image_url' => $qr_image_url,
                    'qr_saved' => $saved
                ];
            }

            // If client expects JSON (AJAX/API), return JSON response
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
            if (stripos($accept, 'application/json') !== false || strcasecmp($requestedWith, 'XMLHttpRequest') === 0) {
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
            }

            // For normal browser flow, show the QR on this page so the owner can view/download immediately
            $success = 'Pet added and QR code generated.';
            $show_qr = true;
            // Prefer absolute URL for display; fall back to web path
            $qr_display_url = $qrInfo['absolute_url'] ?? $qrInfo['web_path'];
            $qr_download_path = $qrInfo['web_path'];
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Pet - Pet Tracking</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 700px;
            width: 100%;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        /* --- START OF BACK BUTTON CODE --- */
        .back-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #5a6268;
            transform: translateX(-3px);
        }
        /* --- END OF BACK BUTTON CODE --- */


        h1 {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input, select {
            width: 100%;
            padding: 15px;
            border: 2px solid #e8eeff;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8faff;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .age-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            width: 100%;
            justify-content: center;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 25px;
                margin: 10px;
            }
            
            .form-row, .age-group {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="../owner_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
            <h1>Add New Pet</h1>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($show_qr) && !empty($qr_display_url)): ?>
            <div style="text-align:center; margin: 20px 0;">
                <h3>QR Code</h3>
                <div style="margin-bottom:10px;">
                    <img src="<?= htmlspecialchars($qr_display_url) ?>" alt="QR Code" style="max-width:250px; border:1px solid #eee; padding:10px; background:white;">
                </div>
                <div>
                    <a href="<?= htmlspecialchars($qr_download_path) ?>" download class="btn btn-success" style="margin-right:10px;">Download QR</a>
                    <a href="./generate_qr.php?id=<?= urlencode($pet_id ?? '') ?>" class="btn btn-primary">Open QR Page</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="profile-picture">Profile Picture</label>
                <div class="file-upload">
                    <input type="file" id="profile-picture" name="profile_picture" accept="image/*">
                    <div class="file-upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="file-upload-text">Click to upload photo</div>
                    <div class="file-upload-hint">JPG, PNG, GIF (Max 5MB)</div>
                </div>
                <div class="preview-container">
                    <img id="preview-image" class="preview-image" alt="Preview">
                </div>
            </div>

            <div class="form-group">
                <label>Pet Name *</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($name) ?>" placeholder="Enter pet name">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Type *</label>
                    <select name="type" required>
                        <option value="">Select Type</option>
                        <option value="Dog" <?= $type == 'Dog' ? 'selected' : '' ?>>🐕 Dog</option>
                        <option value="Cat" <?= $type == 'Cat' ? 'selected' : '' ?>>🐱 Cat</option>
                        <option value="Bird" <?= $type == 'Bird' ? 'selected' : '' ?>>🐦 Bird</option>
                        <option value="Rabbit" <?= $type == 'Rabbit' ? 'selected' : '' ?>>🐰 Rabbit</option>
                        <option value="Other" <?= $type == 'Other' ? 'selected' : '' ?>>🐾 Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Breed</label>
                    <input type="text" name="breed" value="<?= htmlspecialchars($breed) ?>" placeholder="Enter breed">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Color</label>
                    <input type="text" name="color" value="<?= htmlspecialchars($color) ?>" placeholder="Enter color">
                </div>

                <div class="form-group">
                    <label>Age</label>
                    <div class="age-group">
                        <div>
                            <label style="text-transform: none; font-size: 12px;">Years</label>
                            <input type="number" name="age_years" min="0" max="50" value="<?= htmlspecialchars($age_years) ?>" placeholder="0">
                        </div>
                        <div>
                            <label style="text-transform: none; font-size: 12px;">Months</label>
                            <input type="number" name="age_months" min="0" max="11" value="<?= htmlspecialchars($age_months) ?>" placeholder="0">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paw"></i>
                Add Pet
            </button>
        </form>
    </div>

    <script>
        // Image preview functionality
        document.getElementById('profile-picture').addEventListener('change', function(e) {
            const preview = document.getElementById('preview-image');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.querySelector('input[name="name"]').value.trim();
            const type = document.querySelector('select[name="type"]').value;
            
            if (!name) {
                e.preventDefault();
                alert('Please enter a pet name');
                return;
            }
            
            if (!type) {
                e.preventDefault();
                alert('Please select a pet type');
                return;
            }
        });
    </script>
</body>
</html>