<?php
include 'config.php';
session_start();

// CHECK IF ADMIN IS LOGGED IN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$success = "";
$error = "";

// FETCH ADMIN DATA
$result = mysqli_query($conn, "SELECT * FROM users WHERE id = '$admin_id'");
$admin = mysqli_fetch_assoc($result);

// UPDATE PROFILE INFO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    mysqli_query($conn, "UPDATE users SET full_name='$full_name', email='$email' WHERE id='$admin_id'");
    $_SESSION['full_name'] = $full_name;
    $admin['full_name'] = $full_name;
    $admin['email'] = $email;
    $success = "Profile updated successfully!";
}

// UPLOAD PHOTO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $fileext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $filesize = $_FILES['photo']['size'];
    
    if ($filesize > 2097152) {
        $error = "File too large! Maximum 2MB allowed.";
    } elseif (in_array($fileext, $allowed)) {
        $new_filename = time() . "_admin_" . $admin_id . "." . $fileext;
        $upload_path = "uploads/profile_pics/" . $new_filename;
        
        // Create directory if not exists
        if (!file_exists("uploads/profile_pics/")) {
            mkdir("uploads/profile_pics/", 0777, true);
        }
        
        // Delete old photo if not default
        if ($admin['photo'] != 'default.png' && file_exists("uploads/profile_pics/" . $admin['photo'])) {
            unlink("uploads/profile_pics/" . $admin['photo']);
        }
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
            mysqli_query($conn, "UPDATE users SET photo='$new_filename' WHERE id='$admin_id'");
            $admin['photo'] = $new_filename;
            $success = "Photo uploaded successfully!";
        } else {
            $error = "Failed to upload photo.";
        }
    } else {
        $error = "File type not allowed. Allowed: JPG, JPEG, PNG, GIF";
    }
}

$first_name = explode(' ', $admin['full_name'])[0];
$last_name = isset(explode(' ', $admin['full_name'])[1]) ? explode(' ', $admin['full_name'])[1] : '';
$photo = $admin['photo'] ?? 'default.png';
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-top: 20px;
            max-width: 550px;
            margin-left: auto;
            margin-right: auto;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #0056b3;
            margin-bottom: 15px;
        }
        
        .photo-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px dashed #0056b3;
            margin: 15px auto;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-upload {
            background: #17a2b8;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-upload:hover {
            background: #138496;
        }
        
        .btn-save {
            background: #28a745;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .btn-save:hover {
            background: #218838;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .form-group input:disabled {
            background: #f5f5f5;
        }
        
        .success-msg, .error-msg {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success-msg {
            background: #d4edda;
            color: #155724;
        }
        
        .error-msg {
            background: #f8d7da;
            color: #721c24;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin: 25px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #0056b3;
            color: #0056b3;
            text-align: left;
        }
        
        .password-link {
            display: inline-block;
            margin-top: 15px;
            color: #0056b3;
            text-decoration: none;
        }
        
        .password-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

  <?php include 'sidebar_admin.php'; ?>

    <div class="content-wrapper">
        <header class="top-header">
            <div class="welcome-top">
                <p>Mwanza Campus | <strong>Today:</strong> <?php echo date("d M, Y"); ?></p>
                <i class="fas fa-bell"></i>
            </div>
        </header>

        <main>
            <div class="panel-title">My Profile</div>
            
            <div class="info-banner">
                <span><strong>Admin ID:</strong> <?php echo $_SESSION['username']; ?></span>
                <span><strong>System Status:</strong> Online</span>
                <span><strong>Role:</strong> Administrator</span>
            </div>

            <div class="profile-container">
                <?php if($success): ?>
                    <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Profile Photo Section -->
                <div class="section-title"><i class="fas fa-camera"></i> Profile Photo</div>
                
                <div>
                    <img src="uploads/profile_pics/<?php echo $photo; ?>" class="profile-photo" id="currentPhoto" alt="Profile Photo">
                </div>
                
                <form method="POST" enctype="multipart/form-data" id="photoForm">
                    <div style="margin: 15px 0;">
                        <input type="file" name="photo" accept="image/*" id="photoInput" style="display: none;" onchange="previewAndUpload(this)">
                        <button type="button" class="btn-upload" onclick="document.getElementById('photoInput').click()">
                            <i class="fas fa-camera"></i> Change Profile Photo
                        </button>
                        <small style="display: block; margin-top: 8px; color: #666;">Allowed: JPG, JPEG, PNG, GIF (Max 2MB)</small>
                    </div>
                    <div class="photo-preview" id="preview" style="display: none;"></div>
                </form>

                <!-- Profile Information Section -->
                <div class="section-title"><i class="fas fa-user-edit"></i> Account Information</div>
                
                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user-tag"></i> Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($admin['username']); ?>" disabled>
                        <small style="color: #666;">Username cannot be changed</small>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-shield-alt"></i> Role</label>
                        <input type="text" value="Administrator" disabled>
                    </div>
                    <button type="submit" name="update_profile" class="btn-save">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                    <a href="change_password.php" class="password-link">
                        <i class="fas fa-key"></i> Change Password
                    </a>
                </form>
            </div>
        </main>
    </div>

    <script>
        function toggleSub(id) {
            const menu = document.getElementById(id);
            if (menu) {
                menu.classList.toggle('active');
            }
        }
        
        function previewAndUpload(input) {
            const preview = document.getElementById('preview');
            const currentPhoto = document.getElementById('currentPhoto');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">';
                    preview.style.display = 'block';
                    currentPhoto.style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
                
                // Auto submit the form
                document.getElementById('photoForm').submit();
            }
        }
    </script>

</body>
</html>