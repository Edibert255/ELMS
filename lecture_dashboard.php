<?php
include 'config.php';
session_start();

// CHECK IF LECTURER IS LOGGED IN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    header("Location: login.php");
    exit();
}

$lecturer_id = $_SESSION['user_id'];
$lecturer_name = $_SESSION['full_name'];
$lecturer_username = $_SESSION['username'];

// FETCH LECTURER DATA WITH PHOTO
$sql = "SELECT * FROM users WHERE id = '$lecturer_id'";
$result = mysqli_query($conn, $sql);
$lecturer = mysqli_fetch_assoc($result);

$photo = $lecturer['photo'] ?? 'default.png';
$first_name = explode(' ', $lecturer_name)[0];
$last_name = isset(explode(' ', $lecturer_name)[1]) ? explode(' ', $lecturer_name)[1] : '';

// ADD CACHE BUSTER FOR PHOTO
$photo_path = "uploads/profile_pics/" . $photo;
$photo_version = file_exists($photo_path) ? filemtime($photo_path) : time();

// FETCH MODULES TAUGHT BY THIS LECTURER
$modules = [];
$sql_modules = "SELECT * FROM lecturer_modules WHERE lecturer_id = '$lecturer_id' ORDER BY module_code ASC";
$result_modules = mysqli_query($conn, $sql_modules);
if ($result_modules && mysqli_num_rows($result_modules) > 0) {
    while ($row = mysqli_fetch_assoc($result_modules)) {
        $modules[] = $row;
    }
}

// STATISTICS
$total_modules = count($modules);

// Total assignments
$total_assignments = 0;
$sql_assignments = "SELECT COUNT(*) as total FROM assignments WHERE created_by = '$lecturer_id'";
$result_assignments = mysqli_query($conn, $sql_assignments);
if ($result_assignments && mysqli_num_rows($result_assignments) > 0) {
    $total_assignments = mysqli_fetch_assoc($result_assignments)['total'];
}

// Pending submissions
$pending_submissions = 0;
$sql_pending = "SELECT COUNT(*) as total FROM assignment_submissions WHERE marks_obtained IS NULL";
$result_pending = mysqli_query($conn, $sql_pending);
if ($result_pending && mysqli_num_rows($result_pending) > 0) {
    $pending_submissions = mysqli_fetch_assoc($result_pending)['total'];
}

// Total students
$sql_students = "SELECT COUNT(*) as total FROM users WHERE role = 'student' AND status = 'active'";
$result_students = mysqli_query($conn, $sql_students);
$total_students = ($result_students && mysqli_num_rows($result_students) > 0) ? mysqli_fetch_assoc($result_students)['total'] : 0;
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBE ELMS - Lecturer Dashboard</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* FORCE SMALL PROFILE IMAGE IN SIDEBAR */
        .user-info-sidebar img {
            width: 45px !important;
            height: 45px !important;
            border-radius: 50% !important;
            object-fit: cover !important;
            border: 2px solid #0056b3 !important;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid #e0e0e0;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 55px;
            height: 55px;
            background: #e8f4fd;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .stat-icon i {
            font-size: 28px;
            color: #0056b3;
        }
        
        .stat-info h3 {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            font-size: 13px;
            color: #666;
        }
        
        /* Modules Grid */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .module-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            border-left: 4px solid #0056b3;
            transition: transform 0.3s ease;
        }
        
        .module-card:hover {
            transform: translateX(3px);
        }
        
        .module-code {
            font-size: 12px;
            color: #0056b3;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .module-name {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        
        .module-semester {
            font-size: 11px;
            color: #888;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .quick-btn {
            background: #f8f9fa;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #333;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        
        .quick-btn:hover {
            background: #0056b3;
            color: white;
            border-color: #0056b3;
        }
        
        .quick-btn i {
            font-size: 16px;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #999;
        }
    </style>
</head>
<body>

      <?php include 'sidebar_lecturer.php'; ?>

    <div class="content-wrapper">
        <header class="top-header">
            <div class="welcome-top">
                <p>Mwanza Campus | <strong>Today:</strong> <?php echo date("d M, Y"); ?></p>
                <i class="fas fa-bell"></i>
            </div>
        </header>

        <main>
            <div class="panel-title">Lecturer Panel</div>
            
            <div class="info-banner">
                <span><strong>Staff ID:</strong> <?php echo $lecturer_username; ?></span>
                <span><strong>System Status:</strong> Online</span>
                <span><strong>User:</strong> <?php echo $first_name; ?></span>
            </div>

            <!-- WELCOME CARD -->
            <div class="dashboard-card" style="margin-bottom: 25px;">
                <h3 style="color: #0056b3; margin-bottom: 15px;">Welcome to Lecturer Information System</h3>
                <div class="student-details">
                    <p>Welcome back, <strong><?php echo $lecturer_name; ?></strong>. Manage your courses, assignments, and student submissions from this dashboard.</p>
                </div>
            </div>

            <!-- STATISTICS CARDS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-book"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_modules; ?></h3>
                        <p>My Modules</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_assignments; ?></h3>
                        <p>Assignments</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $pending_submissions; ?></h3>
                        <p>Pending Grading</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_students; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
            </div>

            <!-- MY MODULES SECTION -->
            <div class="dashboard-card" style="margin-bottom: 25px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="color: #0056b3;"><i class="fas fa-book"></i> My Modules</h3>
                    <a href="lecture_modules.php" style="color: #0056b3; text-decoration: none;">View All →</a>
                </div>
                
                <?php if(empty($modules)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open" style="font-size: 48px; margin-bottom: 10px;"></i>
                        <p>No modules assigned yet.</p>
                        <p style="font-size: 13px;">Contact admin to assign modules to you.</p>
                    </div>
                <?php else: ?>
                    <div class="modules-grid">
                        <?php foreach(array_slice($modules, 0, 6) as $module): ?>
                        <div class="module-card">
                            <div class="module-code"><?php echo $module['module_code']; ?></div>
                            <div class="module-name"><?php echo $module['module_name']; ?></div>
                            <div class="module-semester">
                                <i class="fas fa-calendar-alt"></i> <?php echo $module['semester']; ?> | <?php echo $module['academic_year']; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="dashboard-card">
                <h3 style="color: #0056b3; margin-bottom: 15px;"><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="quick-actions">
                    <a href="lecture_add_assignment.php" class="quick-btn">
                        <i class="fas fa-plus-circle" style="color: #28a745;"></i> Create Assignment
                    </a>
                    <a href="lecture_submissions.php?filter=pending" class="quick-btn">
                        <i class="fas fa-check-double" style="color: #ffc107;"></i> Grade Submissions
                    </a>
                    <a href="lecture_students.php" class="quick-btn">
                        <i class="fas fa-users" style="color: #17a2b8;"></i> View Students
                    </a>
                    <a href="lecture_modules.php" class="quick-btn">
                        <i class="fas fa-book" style="color: #0056b3;"></i> My Modules
                    </a>
                    <a href="lecture_live_classes.php" class="quick-btn">
                        <i class="fas fa-video" style="color: #dc3545;"></i> Live Class
                    </a>
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