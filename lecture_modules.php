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

// GET SEMESTER FILTER
$selected_semester = isset($_GET['semester']) ? $_GET['semester'] : 'all';

// FETCH MODULES TAUGHT BY THIS LECTURER
$modules = [];
$sql = "SELECT * FROM lecturer_modules WHERE lecturer_id = '$lecturer_id'";

if ($selected_semester != 'all' && $selected_semester != '') {
    $sql .= " AND semester = '$selected_semester'";
}

$sql .= " ORDER BY semester ASC, module_code ASC";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $modules[] = $row;
    }
}

// GROUP MODULES BY SEMESTER
$semester1_modules = [];
$semester2_modules = [];

foreach ($modules as $module) {
    if ($module['semester'] == 'Semester 1' || $module['semester'] == 'Semester I') {
        $semester1_modules[] = $module;
    } else {
        $semester2_modules[] = $module;
    }
}

// FETCH STATISTICS
$total_modules = count($modules);
$total_semester1 = count($semester1_modules);
$total_semester2 = count($semester2_modules);

// Default values
$photo = 'default.png';
$first_name = explode(' ', $lecturer_name)[0];
$last_name = isset(explode(' ', $lecturer_name)[1]) ? explode(' ', $lecturer_name)[1] : '';
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Modules - Lecturer | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .modules-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        /* Filter Buttons */
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            background: #f8f9fa;
            color: #333;
            padding: 8px 20px;
            border: 1px solid #ddd;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .filter-btn.active {
            background: #0056b3;
            color: white;
            border-color: #0056b3;
        }
        
        .filter-btn:hover {
            background: #0056b3;
            color: white;
        }
        
        /* Stats Cards */
        .stats-modules {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .stat-module-card {
            background: linear-gradient(135deg, #0056b3 0%, #003d82 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            flex: 1;
            min-width: 150px;
        }
        
        .stat-module-card h3 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .stat-module-card p {
            font-size: 13px;
            opacity: 0.9;
        }
        
        /* Section Title */
        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin: 25px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #0056b3;
            color: #0056b3;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title:first-of-type {
            margin-top: 0;
        }
        
        /* Module Card */
        .module-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #0056b3;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .module-card:hover {
            transform: translateX(3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .module-info {
            flex: 1;
        }
        
        .module-code {
            display: inline-block;
            background: #0056b3;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .module-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        
        .module-details {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 5px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #666;
        }
        
        .detail-item i {
            color: #0056b3;
            width: 14px;
        }
        
        .module-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .btn-sm {
            padding: 6px 15px;
            font-size: 12px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary-sm {
            background: #0056b3;
            color: white;
        }
        
        .btn-primary-sm:hover {
            background: #003d82;
        }
        
        .btn-success-sm {
            background: #28a745;
            color: white;
        }
        
        .btn-success-sm:hover {
            background: #218838;
        }
        
        .btn-warning-sm {
            background: #ffc107;
            color: #333;
        }
        
        .btn-warning-sm:hover {
            background: #e0a800;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .module-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .module-actions {
                margin-top: 15px;
                width: 100%;
            }
            .stats-modules {
                flex-direction: column;
            }
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
            <div class="panel-title">My Modules</div>
            
            <div class="info-banner">
                <span><strong>Staff ID:</strong> <?php echo $_SESSION['username']; ?></span>
                <span><strong>Total Modules:</strong> <?php echo $total_modules; ?></span>
                <span><strong>User:</strong> <?php echo $first_name; ?></span>
            </div>

            <div class="modules-container">
                <!-- Filter Buttons -->
                <div class="filter-buttons">
                    <a href="lecture_modules.php" class="filter-btn <?php echo $selected_semester == 'all' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i> All Modules
                    </a>
                    <a href="lecture_modules.php?semester=Semester%201" class="filter-btn <?php echo $selected_semester == 'Semester 1' ? 'active' : ''; ?>">
                        <i class="fas fa-book-open"></i> Semester 1
                    </a>
                    <a href="lecture_modules.php?semester=Semester%202" class="filter-btn <?php echo $selected_semester == 'Semester 2' ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i> Semester 2
                    </a>
                </div>

                <!-- Statistics -->
                <div class="stats-modules">
                    <div class="stat-module-card">
                        <h3><?php echo $total_modules; ?></h3>
                        <p><i class="fas fa-book"></i> Total Modules</p>
                    </div>
                    <div class="stat-module-card">
                        <h3><?php echo $total_semester1; ?></h3>
                        <p><i class="fas fa-book-open"></i> Semester 1</p>
                    </div>
                    <div class="stat-module-card">
                        <h3><?php echo $total_semester2; ?></h3>
                        <p><i class="fas fa-book"></i> Semester 2</p>
                    </div>
                </div>

                <!-- NO MODULES MESSAGE -->
                <?php if(empty($modules)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>No modules assigned to you yet.</p>
                        <p style="font-size: 13px;">Please contact the administrator to assign modules.</p>
                    </div>
                <?php else: ?>

                    <!-- SEMESTER 1 MODULES -->
                    <?php if(!empty($semester1_modules) && ($selected_semester == 'all' || $selected_semester == 'Semester 1')): ?>
                    <div class="section-title">
                        <i class="fas fa-book-open"></i> Semester 1 Modules
                        <span style="font-size: 14px; background: #0056b3; color: white; padding: 2px 10px; border-radius: 20px;"><?php echo $total_semester1; ?> modules</span>
                    </div>
                    
                    <?php foreach($semester1_modules as $module): ?>
                    <div class="module-card">
                        <div class="module-info">
                            <span class="module-code"><?php echo $module['module_code']; ?></span>
                            <div class="module-name"><?php echo $module['module_name']; ?></div>
                            <div class="module-details">
                                <div class="detail-item"><i class="fas fa-calendar-alt"></i> <?php echo $module['academic_year']; ?></div>
                                <div class="detail-item"><i class="fas fa-star"></i> Credits: <?php echo $module['credits']; ?></div>
                                <div class="detail-item"><i class="fas fa-tag"></i> <?php echo $module['semester']; ?></div>
                            </div>
                        </div>
                        <div class="module-actions">
                            <a href="lecture_assignments.php?module=<?php echo urlencode($module['module_code']); ?>" class="btn-sm btn-primary-sm">
                                <i class="fas fa-plus"></i> Add Assignment
                            </a>
                            <a href="lecture_submissions.php?module=<?php echo urlencode($module['module_code']); ?>" class="btn-sm btn-success-sm">
                                <i class="fas fa-check"></i> Grade
                            </a>
                            <a href="lecture_live_classes.php?course=<?php echo urlencode($module['module_name']); ?>" class="btn-sm btn-warning-sm">
                                <i class="fas fa-video"></i> Live Class
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- SEMESTER 2 MODULES -->
                    <?php if(!empty($semester2_modules) && ($selected_semester == 'all' || $selected_semester == 'Semester 2')): ?>
                    <div class="section-title">
                        <i class="fas fa-book"></i> Semester 2 Modules
                        <span style="font-size: 14px; background: #0056b3; color: white; padding: 2px 10px; border-radius: 20px;"><?php echo $total_semester2; ?> modules</span>
                    </div>
                    
                    <?php foreach($semester2_modules as $module): ?>
                    <div class="module-card">
                        <div class="module-info">
                            <span class="module-code"><?php echo $module['module_code']; ?></span>
                            <div class="module-name"><?php echo $module['module_name']; ?></div>
                            <div class="module-details">
                                <div class="detail-item"><i class="fas fa-calendar-alt"></i> <?php echo $module['academic_year']; ?></div>
                                <div class="detail-item"><i class="fas fa-star"></i> Credits: <?php echo $module['credits']; ?></div>
                                <div class="detail-item"><i class="fas fa-tag"></i> <?php echo $module['semester']; ?></div>
                            </div>
                        </div>
                        <div class="module-actions">
                            <a href="lecture_assignments.php?module=<?php echo urlencode($module['module_code']); ?>" class="btn-sm btn-primary-sm">
                                <i class="fas fa-plus"></i> Add Assignment
                            </a>
                            <a href="lecture_submissions.php?module=<?php echo urlencode($module['module_code']); ?>" class="btn-sm btn-success-sm">
                                <i class="fas fa-check"></i> Grade
                            </a>
                            <a href="lecture_live_classes.php?course=<?php echo urlencode($module['module_name']); ?>" class="btn-sm btn-warning-sm">
                                <i class="fas fa-video"></i> Live Class
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>

                <?php endif; ?>
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
    </script>

</body>
</html>