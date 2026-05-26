<?php
include 'config.php';
session_start();

// CHECK IF STUDENT IS LOGGED IN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// FETCH STUDENT DATA - IMPROVED
$sql = "SELECT s.*, u.full_name, u.email, u.username, u.photo 
        FROM users u 
        LEFT JOIN students s ON u.id = s.user_id 
        WHERE u.id = '$user_id' AND u.role = 'student'";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $student_data = mysqli_fetch_assoc($result);
    $full_name = $student_data['full_name'] ?? 'Student';
    $photo = $student_data['photo'] ?? 'default.png';
    $registration_no = $student_data['registration_no'] ?? 'Not Assigned';
    $first_name = explode(' ', $full_name)[0];
    $last_name = isset(explode(' ', $full_name)[1]) ? explode(' ', $full_name)[1] : '';
} else {
    // If no data found, create default
    $full_name = $_SESSION['full_name'] ?? 'Student';
    $photo = 'default.png';
    $registration_no = 'Not Assigned';
    $first_name = $full_name;
    $last_name = '';
}

// ADD CACHE BUSTER FOR PHOTO
$photo_path = "uploads/profile_pics/" . $photo;
$photo_version = file_exists($photo_path) ? filemtime($photo_path) : time();
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBE ELMS - Student Dashboard</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* FORCE SMALL IMAGES - ADD THIS */
        .user-info-sidebar img {
            width: 45px !important;
            height: 45px !important;
            border-radius: 50% !important;
            object-fit: cover !important;
            border: 2px solid #0056b3 !important;
        }
        
        /* Sidebar user info */
        .user-info-sidebar {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        
        .user-names h3 {
            font-size: 12px;
            margin: 0;
            color: #333;
        }
        
        .user-names p {
            font-size: 10px;
            margin: 3px 0 0 0;
            color: #666;
        }
        
        /* Dashboard card */
        .dashboard-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
    </style>
</head>
<body>

   <?php include 'sidebar_student.php'; ?>
    <div class="content-wrapper">
        <header class="top-header">
            <div class="welcome-top">
                <p>Mwanza Campus | <strong>Today:</strong> <?php echo date("d M, Y"); ?></p>
                <i class="fas fa-bell"></i>
            </div>
        </header>

        <main>
            <div class="panel-title">Student Panel</div>
            
            <div class="info-banner">
                <span><strong>Registration No:</strong> <?php echo $registration_no; ?></span>
                <span><strong>System Status:</strong> Online</span>
                <span><strong>User:</strong> <?php echo $first_name; ?></span>
            </div>

            <div class="dashboard-card">
                <h3 style="color: #0056b3; margin-bottom: 15px;">Welcome to Student Information System</h3>
                <div class="student-details">
                    <p>Welcome back, <strong><?php echo $full_name; ?></strong>. Use the sidebar to navigate through your academic resources.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSub(id) {
            const menu = document.getElementById(id);
            if (menu) {
                const isActive = menu.classList.contains('active');
                document.querySelectorAll('.submenu').forEach(s => s.classList.remove('active'));
                if (!isActive) menu.classList.add('active');
            }
        }
    </script>

</body>
</html>