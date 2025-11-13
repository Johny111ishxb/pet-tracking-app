<?php
session_start();
if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../db/db_connect.php';

// Get pet ID from URL
$pet_id = $_GET['id'] ?? null;
if (!$pet_id) {
    header("Location: view_pets.php");
    exit();
}

// Fetch pet (only if owned by current user)
$stmt = $pdo->prepare("SELECT * FROM pets WHERE pet_id = ? AND owner_id = ?");
$stmt->execute([$pet_id, $_SESSION['owner_id']]);
$pet = $stmt->fetch();

if (!$pet) {
    header("Location: view_pets.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $breed = trim($_POST['breed'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $gender = $_POST['gender'] ?? '';
    
    // Handle age with unit
    $age = !empty($_POST['age']) ? (float)$_POST['age'] : null;
    $age_unit = $_POST['age_unit'] ?? 'years';
    
    $description = trim($_POST['description'] ?? '');
    $emergency_notes = trim($_POST['emergency_notes'] ?? '');

    if (empty($name) || empty($type)) {
        $error = "Pet name and type are required.";
    } else {
        // Handle photo upload
        $photo_filename = $pet['photo']; // Keep existing photo by default
        
        if (!empty($_FILES['photo']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $file_name = $_FILES['photo']['name'];
            $file_tmp = $_FILES['photo']['tmp_name'];
            $file_size = $_FILES['photo']['size'];
            $file_error = $_FILES['photo']['error'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed) && $file_error == 0 && $file_size < 5000000) {
                $photo_filename = 'pet_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
                // Ensure uploads folder exists
                if (!is_dir('../uploads')) {
                    mkdir('../uploads', 0777, true);
                }
                move_uploaded_file($file_tmp, '../uploads/' . $photo_filename);
                
                // Delete old photo if it exists and is not the default
                if (!empty($pet['photo']) && $pet['photo'] !== $photo_filename && file_exists('../uploads/' . $pet['photo'])) {
                    unlink('../uploads/' . $pet['photo']);
                }
            } else {
                $error = "Invalid photo. Please upload JPG, PNG, or GIF under 5MB.";
            }
        }

        if (!$error) {
            try {
                // Detect optional columns and build UPDATE dynamically to avoid missing-column errors
                $checkAgeUnit = $pdo->prepare("SHOW COLUMNS FROM pets LIKE 'age_unit'");
                $checkAgeUnit->execute();
                $hasAgeUnit = (bool)$checkAgeUnit->fetch();

                $checkGender = $pdo->prepare("SHOW COLUMNS FROM pets LIKE 'gender'");
                $checkGender->execute();
                $hasGender = (bool)$checkGender->fetch();

                $checkDescription = $pdo->prepare("SHOW COLUMNS FROM pets LIKE 'description'");
                $checkDescription->execute();
                $hasDescription = (bool)$checkDescription->fetch();

                $checkEmergency = $pdo->prepare("SHOW COLUMNS FROM pets LIKE 'emergency_notes'");
                $checkEmergency->execute();
                $hasEmergency = (bool)$checkEmergency->fetch();

                // Build SET clauses and parameters dynamically
                $sets = [];
                $params = [];

                $sets[] = 'name = ?'; $params[] = $name;
                $sets[] = 'type = ?'; $params[] = $type;
                $sets[] = 'breed = ?'; $params[] = $breed ? $breed : null;
                $sets[] = 'color = ?'; $params[] = $color ? $color : null;
                if ($hasGender) { $sets[] = 'gender = ?'; $params[] = $gender ? $gender : null; }
                $sets[] = 'age = ?'; $params[] = $age;
                if ($hasAgeUnit) { $sets[] = 'age_unit = ?'; $params[] = $age_unit; }
                if ($hasDescription) { $sets[] = 'description = ?'; $params[] = $description ? $description : null; }
                if ($hasEmergency) { $sets[] = 'emergency_notes = ?'; $params[] = $emergency_notes ? $emergency_notes : null; }
                $sets[] = 'photo = ?'; $params[] = $photo_filename;

                $sql = 'UPDATE pets SET ' . implode(', ', $sets) . ' WHERE pet_id = ? AND owner_id = ?';
                $params[] = $pet_id;
                $params[] = $_SESSION['owner_id'];

                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute($params);

                if ($result) {
                    $success = "Pet updated successfully!";
                    // Refresh data
                    $stmt = $pdo->prepare("SELECT * FROM pets WHERE pet_id = ?");
                    $stmt->execute([$pet_id]);
                    $pet = $stmt->fetch();
                } else {
                    $error = "Failed to update pet.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Safe field access function
function getField($array, $key, $default = '') {
    return isset($array[$key]) && !empty($array[$key]) ? $array[$key] : $default;
}

// Format age display
function formatAge($age, $unit) {
    if (!$age) return '';
    return $age . ' ' . ($age == 1 ? rtrim($unit, 's') : $unit);
}

// Get age unit with fallback
function getAgeUnit($pet) {
    return isset($pet['age_unit']) ? $pet['age_unit'] : 'years';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?= htmlspecialchars(getField($pet, 'name')) ?> – Pawsitive Patrol</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS styles remain exactly the same */
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
        }

        .edit-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header-section {
            background: linear-gradient(135deg, #4a6fa5 0%, #2c3e50 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .back-btn {
            position: absolute;
            left: 30px;
            top: 30px;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: opacity 0.3s;
        }

        .back-btn:hover {
            opacity: 0.8;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .page-subtitle {
            opacity: 0.9;
            font-size: 16px;
        }

        .form-content {
            padding: 40px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
            text-align: center;
            border: 2px solid transparent;
        }

        .alert-success {
            background: #e8f5e8;
            color: #2d5016;
            border-color: #c8e6c9;
        }

        .alert-error {
            background: #ffe6e6;
            color: #d32f2f;
            border-color: #ffcdd2;
        }

        .pet-profile-section {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-bottom: 30px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 15px;
            border: 2px dashed #e0e0e0;
        }

        .photo-upload {
            text-align: center;
            flex-shrink: 0;
        }

        .photo-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 10px;
            background: #eef4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #4a6fa5;
        }

        .upload-btn {
            background: #4a6fa5;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .upload-btn:hover {
            background: #3a5a85;
        }

        .pet-current-info h3 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .pet-current-info p {
            color: #666;
            font-size: 16px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4a6fa5;
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.1);
        }

        .form-group textarea {
            height: 120px;
            resize: vertical;
            font-family: inherit;
        }

        .age-input-group {
            display: flex;
            gap: 10px;
        }

        .age-input-group input {
            flex: 2;
        }

        .age-input-group select {
            flex: 1;
            min-width: 100px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #4a6fa5;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #f0f0f0;
        }

        .btn {
            flex: 1;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4a6fa5 0%, #2c3e50 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 111, 165, 0.3);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #2c3e50;
            border: 2px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            border-color: #4a6fa5;
        }

        .file-input {
            display: none;
        }

        .field-error {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .form-content {
                padding: 25px;
            }

            .header-section {
                padding: 25px 20px;
            }

            .back-btn {
                position: static;
                margin-bottom: 15px;
                justify-content: center;
            }

            .pet-profile-section {
                flex-direction: column;
                text-align: center;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .btn-group {
                flex-direction: column;
            }

            .age-input-group {
                flex-direction: column;
            }
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

        .alert {
            animation: slideIn 0.3s ease-out;
        }
    </style>
</head>
<body>

<div class="edit-container">
    <!-- HEADER SECTION -->
    <div class="header-section">
        <a href="view_pets.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Pets
        </a>
        <h1 class="page-title">✏️ Edit Pet Profile</h1>
        <p class="page-subtitle">Update your pet's information and photo</p>
    </div>

    <!-- FORM CONTENT -->
    <div class="form-content">
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <!-- PET PROFILE SECTION -->
            <div class="pet-profile-section">
                <div class="photo-upload">
                    <div class="photo-preview" id="photoPreview">
                        <?php if (!empty($pet['photo']) && file_exists('../uploads/' . $pet['photo'])): ?>
                            <img src="../uploads/<?= htmlspecialchars($pet['photo']) ?>" alt="<?= htmlspecialchars($pet['name']) ?>" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <?= $pet['type'] == 'Dog' ? '🐕' : ($pet['type'] == 'Cat' ? '🐈' : '🐾') ?>
                        <?php endif; ?>
                    </div>
                    <input type="file" id="photoInput" name="photo" accept="image/*" class="file-input">
                    <button type="button" class="upload-btn" onclick="document.getElementById('photoInput').click()">
                        <i class="fas fa-camera"></i> Change Photo
                    </button>
                </div>
                <div class="pet-current-info">
                    <h3><?= htmlspecialchars(getField($pet, 'name')) ?></h3>
                    <p><?= htmlspecialchars(getField($pet, 'type')) ?> • <?= htmlspecialchars(getField($pet, 'breed', 'Mixed')) ?></p>
                    <p style="color: #4a6fa5; margin-top: 5px;">
                        <?= formatAge(getField($pet, 'age'), getAgeUnit($pet)) ?>
                    </p>
                </div>
            </div>

            <!-- BASIC INFORMATION SECTION -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-info-circle"></i> Basic Information
                </h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Pet Name *</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars(getField($pet, 'name')) ?>" required placeholder="Enter pet name">
                    </div>

                    <div class="form-group">
                        <label for="type">Pet Type *</label>
                        <select id="type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="Dog" <?= getField($pet, 'type') === 'Dog' ? 'selected' : '' ?>>🐕 Dog</option>
                            <option value="Cat" <?= getField($pet, 'type') === 'Cat' ? 'selected' : '' ?>>🐈 Cat</option>
                            <option value="Rabbit" <?= getField($pet, 'type') === 'Rabbit' ? 'selected' : '' ?>>🐇 Rabbit</option>
                            <option value="Bird" <?= getField($pet, 'type') === 'Bird' ? 'selected' : '' ?>>🐦 Bird</option>
                            <option value="Other" <?= getField($pet, 'type') === 'Other' ? 'selected' : '' ?>>🐾 Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="breed">Breed</label>
                        <input type="text" id="breed" name="breed" value="<?= htmlspecialchars(getField($pet, 'breed')) ?>" placeholder="e.g., Golden Retriever">
                    </div>

                    <div class="form-group">
                        <label for="color">Color/Markings</label>
                        <input type="text" id="color" name="color" value="<?= htmlspecialchars(getField($pet, 'color')) ?>" placeholder="e.g., Brown with white paws">
                    </div>

                    <div class="form-group">
                        <label for="age">Age</label>
                        <div class="age-input-group">
                            <input type="number" id="age" name="age" value="<?= getField($pet, 'age') ?>" min="0" max="50" step="0.1" placeholder="Age">
                            <select name="age_unit">
                                <option value="months" <?= getAgeUnit($pet) === 'months' ? 'selected' : '' ?>>Months</option>
                                <option value="years" <?= getAgeUnit($pet) === 'years' ? 'selected' : '' ?>>Years</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male" <?= getField($pet, 'gender') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= getField($pet, 'gender') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= getField($pet, 'gender') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ADDITIONAL INFORMATION SECTION -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-file-alt"></i> Additional Information
                </h3>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Tell us about your pet's personality, habits, favorite toys..."><?= htmlspecialchars(getField($pet, 'description')) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="emergency_notes">Emergency & Medical Notes</label>
                    <textarea id="emergency_notes" name="emergency_notes" placeholder="Important medical information, allergies, special needs, vet contact..."><?= htmlspecialchars(getField($pet, 'emergency_notes')) ?></textarea>
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Pet Profile
                </button>
                <a href="view_pets.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const photoInput = document.getElementById('photoInput');
    const photoPreview = document.getElementById('photoPreview');
    
    // Photo preview functionality
    photoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                photoPreview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Form validation
    const form = document.querySelector('form');
    const nameInput = document.getElementById('name');
    const typeSelect = document.getElementById('type');
    
    form.addEventListener('submit', function(e) {
        let valid = true;
        
        // Validate name
        if (!nameInput.value.trim()) {
            showError(nameInput, 'Pet name is required');
            valid = false;
        } else {
            clearError(nameInput);
        }
        
        // Validate type
        if (!typeSelect.value) {
            showError(typeSelect, 'Please select a pet type');
            valid = false;
        } else {
            clearError(typeSelect);
        }
        
        if (!valid) {
            e.preventDefault();
            // Scroll to first error
            const firstError = document.querySelector('.field-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
    
    function showError(element, message) {
        clearError(element);
        element.style.borderColor = '#dc3545';
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        element.parentNode.appendChild(errorDiv);
    }
    
    function clearError(element) {
        element.style.borderColor = '';
        const existingError = element.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }
    
    // Real-time validation
    nameInput.addEventListener('input', function() {
        if (this.value.trim()) {
            clearError(this);
        }
    });
    
    typeSelect.addEventListener('change', function() {
        if (this.value) {
            clearError(this);
        }
    });
});
</script>

</body>
</html>