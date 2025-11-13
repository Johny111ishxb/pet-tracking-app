<?php
// includes/settings.php
session_start();
if (!isset($_SESSION['owner_id'])) {
    header("Location: ../public/login.php");
    exit();
}

require_once(__DIR__ . '/../db/db_connect.php');
$owner_id = $_SESSION['owner_id'];

// Get current user info
$stmt = $pdo->prepare("SELECT * FROM owners WHERE owner_id = ?");
$stmt->execute([$owner_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        
        try {
            $stmt = $pdo->prepare("UPDATE owners SET name = ?, email = ?, phone = ? WHERE owner_id = ?");
            $stmt->execute([$name, $email, $phone, $owner_id]);
            $message = "Profile updated successfully!";
            $message_type = "success";
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM owners WHERE owner_id = ?");
            $stmt->execute([$owner_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            $message = "Error updating profile: " . $e->getMessage();
            $message_type = "error";
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE owners SET password = ? WHERE owner_id = ?");
                    $stmt->execute([$hashed_password, $owner_id]);
                    $message = "Password changed successfully!";
                    $message_type = "success";
                } else {
                    $message = "New password must be at least 6 characters long";
                    $message_type = "error";
                }
            } else {
                $message = "New passwords do not match";
                $message_type = "error";
            }
        } else {
            $message = "Current password is incorrect";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Pawsitive Patrol</title>
    <link rel="stylesheet" href="../css/style.css">
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
            color: #2c3e50;
        }

        .settings-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .page-title {
            font-size: 36px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .page-subtitle {
            color: #666;
            font-size: 18px;
            margin-bottom: 20px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
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

        .settings-grid {
            display: grid;
            gap: 25px;
        }

        .settings-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .card-title {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: #4a6fa5;
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #4a6fa5 0%, #2c3e50 100%);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 111, 165, 0.3);
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
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

        @media (max-width: 768px) {
            .settings-container {
                padding: 10px;
            }

            .header-section {
                padding: 20px;
            }

            .page-title {
                font-size: 28px;
            }

            .settings-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="settings-container">
    <!-- HEADER SECTION -->
    <div class="header-section">
        <h1 class="page-title">
            <i class="fas fa-cog"></i> Settings
        </h1>
        <p class="page-subtitle">Manage your account and preferences</p>
        
        <a href="../owner_dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- MESSAGES -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="settings-grid">
        <!-- PROFILE SETTINGS -->
        <div class="settings-card">
            <h2 class="card-title">
                <i class="fas fa-user"></i> Profile Settings
            </h2>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-input" 
                           value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" 
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-input" 
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>

        <!-- PASSWORD SETTINGS -->
        <div class="settings-card">
            <h2 class="card-title">
                <i class="fas fa-lock"></i> Change Password
            </h2>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-input" required 
                           minlength="6" placeholder="At least 6 characters">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-input" required>
                </div>
                
                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>

        <!-- ACCOUNT INFO -->
        <div class="settings-card">
            <h2 class="card-title">
                <i class="fas fa-info-circle"></i> Account Information
            </h2>
            
            <div class="form-group">
                <label class="form-label">Account Created</label>
                <input type="text" class="form-input" 
                       value="<?= date('F j, Y', strtotime($user['created_at'] ?? 'now')) ?>" 
                       readonly style="background: #f8f9fa;">
            </div>
            
            <div class="form-group">
                <label class="form-label">User ID</label>
                <input type="text" class="form-input" 
                       value="<?= htmlspecialchars($user['owner_id'] ?? '') ?>" 
                       readonly style="background: #f8f9fa;">
            </div>
        </div>
    </div>
</div>

</body>
</html>