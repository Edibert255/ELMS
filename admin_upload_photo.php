<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'];
$success = "";
$error = "";

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$user = null;

if ($user_id > 0) {
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_photo'])) {
    $user_id = (int)$_POST['user_id'];
    
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $fileext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $filesize = $_FILES['photo']['size'];
        
        if ($filesize > 2097152) {
            $error = "File too large! Maximum 2MB allowed.";
        } elseif (in_array($fileext, $allowed)) {
            $new_filename = time() . "_" . $user_id . "." . $fileext;
            $upload_path = "uploads/profile_pics/" . $new_filename;
            
            if (!file_exists("uploads/profile_pics/")) mkdir("uploads/profile_pics/", 0777, true);
            
            $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo FROM users WHERE id='$user_id'"));
            if ($current['photo'] != 'default.png' && file_exists("uploads/profile_pics/" . $current['photo'])) {
                unlink("uploads/profile_pics/" . $current['photo']);
            }
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                if (mysqli_query($conn, "UPDATE users SET photo = '$new_filename' WHERE id = '$user_id'")) {
                    $success = "Photo uploaded successfully!";
                    $user['photo'] = $new_filename;
                } else {
                    $error = "Database error!";
                }
            } else {
                $error = "Failed to upload file.";
            }
        } else {
            $error = "File type not allowed. Allowed: JPG, JPEG, PNG, GIF";
        }
    } else {
        $error = "Please select a file to upload.";
    }
}

$photo = 'default.png';
$first_name = explode(' ', $admin_name)[0];
$last_name = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Photo - Admin</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .upload-container { background: white; border-radius: 12px; padding: 30px; margin-top: 20px; max-width: 550px; margin: 20px auto; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .user-avatar { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #0056b3; margin-bottom: 20px; }
        .photo-preview { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px dashed #0056b3; margin: 20px auto; background: #f8f9fa; display: flex; align-items: center; justify-content: center; }
        .btn-upload { background: #28a745; color: white; padding: 12px 30px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin-top: 15px; }
        .btn-back { background: #6c757d; color: white; padding: 10px 25px; border: none; border-radius: 6px; text-decoration: none; display: inline-block; margin-top: 15px; }
        .user-info { background: #e8f4fd; padding: 15px; border-radius: 10px; margin-bottom: 25px; }
        .role-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .role-student { background: #28a745; color: white; }
        .role-lecturer { background: #ffc107; color: #333; }
        .success-msg, .error-msg { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .success-msg { background: #d4edda; color: #155724; }
        .error-msg { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

  <?php include 'sidebar_admin.php'; ?>
  
    <div class="content-wrapper">
        <header class="top-header"><div class="welcome-top"><p>Mwanza Campus | Today: <?php echo date("d M, Y"); ?></p></div></header>
        <main>
            <div class="upload-container">
                <?php if($success): ?><div class="success-msg"><?php echo $success; ?></div><?php endif; ?>
                <?php if($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>

                <?php if($user): ?>
                    <div class="user-info">
                        <h3><?php echo $user['full_name']; ?></h3>
                        <p><i class="fas fa-envelope"></i> <?php echo $user['email']; ?></p>
                        <p><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></p>
                    </div>
                    <img src="uploads/profile_pics/<?php echo $user['photo'] ?? 'default.png'; ?>" class="user-avatar" id="currentPhoto">
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <input type="file" name="photo" accept="image/*" required onchange="previewImage(this)" style="margin: 15px 0;">
                        <div class="photo-preview" id="preview" style="display: none;"></div>
                        <button type="submit" name="upload_photo" class="btn-upload"><i class="fas fa-upload"></i> Upload Photo</button>
                    </form>
                    <a href="admin_manage_photos.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
                <?php else: ?>
                    <p>User not found.</p>
                    <a href="admin_manage_photos.php" class="btn-back">Go Back</a>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function toggleSub(id){const m=document.getElementById(id);if(m){m.classList.toggle('active');}}
        function previewImage(input){
            const preview=document.getElementById('preview');
            const current=document.getElementById('currentPhoto');
            if(input.files&&input.files[0]){
                const reader=new FileReader();
                reader.onload=function(e){
                    preview.innerHTML='<img src="'+e.target.result+'" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">';
                    preview.style.display='block';
                    if(current)current.style.display='none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>