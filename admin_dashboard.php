<?php
include 'config.php';
session_start();

// CHECK IF ADMIN IS LOGGED IN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['full_name'];
$admin_username = $_SESSION['username'];

// FETCH LATEST ADMIN DATA (INCLUDING PHOTO)
$sql = "SELECT * FROM users WHERE id = '$admin_id'";
$result = mysqli_query($conn, $sql);
$admin_data = mysqli_fetch_assoc($result);

// UPDATE SESSION WITH LATEST PHOTO
$_SESSION['photo'] = $admin_data['photo'];

// FETCH STATISTICS
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='student' AND status='active'"))['c'];
$total_lecturers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='lecturer' AND status='active'"))['c'];
$total_courses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM courses"))['c'];
$total_modules = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM course_modules"))['c'];
$total_assignments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM assignments"))['c'];
$total_library = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM library_materials"))['c'];

// Recent Registrations
$recent_users = [];
$result_recent = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
if ($result_recent && mysqli_num_rows($result_recent) > 0) {
    while ($row = mysqli_fetch_assoc($result_recent)) {
        $recent_users[] = $row;
    }
}

// GET PHOTO - First from database, then from session
$photo = $admin_data['photo'] ?? 'default.png';
$first_name = explode(' ', $admin_name)[0];
$last_name = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid #e0e0e0;
            transition: transform 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .stat-icon { width: 55px; height: 55px; background: #e8f4fd; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        .stat-icon i { font-size: 28px; color: #0056b3; }
        .stat-info h3 { font-size: 24px; color: #333; margin-bottom: 5px; }
        .stat-info p { font-size: 13px; color: #666; }
        .dashboard-row { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px; }
        .dashboard-card { background: white; border-radius: 12px; padding: 20px; border: 1px solid #e0e0e0; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #0056b3; }
        .card-header h3 { color: #0056b3; font-size: 16px; }
        .btn-sm { background: #0056b3; color: white; padding: 5px 12px; border-radius: 5px; text-decoration: none; font-size: 12px; }
        .user-list { list-style: none; }
        .user-list li { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee; }
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 35px; height: 35px; background: #0056b3; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .role-badge { display: inline-block; padding: 3px 10px; border-radius: 15px; font-size: 10px; font-weight: bold; }
        .role-admin { background: #dc3545; color: white; }
        .role-lecturer { background: #ffc107; color: #333; }
        .role-student { background: #28a745; color: white; }
        .quick-actions { display: flex; flex-direction: column; gap: 10px; }
        .quick-btn { background: #f8f9fa; padding: 12px 15px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 12px; color: #333; border: 1px solid #e0e0e0; transition: all 0.3s ease; }
        .quick-btn:hover { background: #0056b3; color: white; border-color: #0056b3; }
        .quick-btn i { width: 25px; }
        @media (max-width: 768px) { .dashboard-row { grid-template-columns: 1fr; } }
        
        /* Force refresh for photo */
        .user-info-sidebar img {
            object-fit: cover;
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
            <div class="panel-title">Administrator Dashboard</div>
            
            <div class="info-banner">
                <span><strong>Admin ID:</strong> <?php echo $admin_username; ?></span>
                <span><strong>System Status:</strong> Online</span>
                <span><strong>User:</strong> <?php echo $first_name; ?></span>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-user-graduate"></i></div><div class="stat-info"><h3><?php echo $total_students; ?></h3><p>Total Students</p></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div><div class="stat-info"><h3><?php echo $total_lecturers; ?></h3><p>Total Lecturers</p></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-book"></i></div><div class="stat-info"><h3><?php echo $total_courses; ?></h3><p>Total Courses</p></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-layer-group"></i></div><div class="stat-info"><h3><?php echo $total_modules; ?></h3><p>Total Modules</p></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-tasks"></i></div><div class="stat-info"><h3><?php echo $total_assignments; ?></h3><p>Assignments</p></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-database"></i></div><div class="stat-info"><h3><?php echo $total_library; ?></h3><p>Library Items</p></div></div>
            </div>

            <div class="dashboard-row">
                <div class="dashboard-card">
                    <div class="card-header"><h3><i class="fas fa-user-plus"></i> Recent Registrations</h3><a href="admin_students.php" class="btn-sm">View All</a></div>
                    <?php if(empty($recent_users)): ?>
                        <p style="color: #999; text-align: center; padding: 20px;">No recent registrations</p>
                    <?php else: ?>
                        <ul class="user-list">
                            <?php foreach($recent_users as $user): ?>
                            <li><div class="user-info"><div class="user-avatar"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></div><div><strong><?php echo $user['full_name']; ?></strong><br><small><?php echo $user['username']; ?></small></div></div><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="dashboard-card">
                    <div class="card-header"><h3><i class="fas fa-bolt"></i> Quick Actions</h3></div>
                    <div class="quick-actions">
                        <a href="admin_add_user.php" class="quick-btn"><i class="fas fa-user-plus"></i> Add New User</a>
                        <a href="admin_courses.php" class="quick-btn"><i class="fas fa-plus-circle"></i> Add New Course</a>
                        <a href="admin_course_modules.php" class="quick-btn"><i class="fas fa-layer-group"></i> Add Module to Course</a>
                        <a href="admin_assign_module.php" class="quick-btn"><i class="fas fa-user-tie"></i> Assign Module to Lecturer</a>
                        <a href="admin_profile.php" class="quick-btn"><i class="fas fa-camera"></i> Upload My Photo</a>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header"><h3><i class="fas fa-info-circle"></i> System Information</h3></div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <div><p><strong>System Name:</strong> CBE ELMS</p><p><strong>Version:</strong> 2.0</p><p><strong>Current Academic Year:</strong> 2025/2026</p></div>
                    <div><p><strong>Current Semester:</strong> Semester II</p><p><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p><p><strong>Admin:</strong> <?php echo $admin_name; ?></p></div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSub(id) {
            const menu = document.getElementById(id);
            if (menu) {
                const isActive = menu.classList.contains('active');
                document.querySelectorAll('.submenu').forEach(s => {
                    s.classList.remove('active');
                });
                if (!isActive) {
                    menu.classList.add('active');
                }
            }
        }
    </script>

</body>
</html>