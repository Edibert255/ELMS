<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['full_name'];
$admin_username = $_SESSION['username'];
$success = "";
$error = "";

// FETCH LATEST ADMIN DATA FOR SIDEBAR
$sql_admin = "SELECT * FROM users WHERE id = '$admin_id'";
$result_admin = mysqli_query($conn, $sql_admin);
$admin_data = mysqli_fetch_assoc($result_admin);
$photo = $admin_data['photo'] ?? 'default.png';
$first_name = explode(' ', $admin_name)[0];
$last_name = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';

// GET COURSE ID FROM URL
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// VERIFY COURSE EXISTS
$course = null;
if ($course_id > 0) {
    $result = mysqli_query($conn, "SELECT * FROM courses WHERE id = '$course_id'");
    if ($result && mysqli_num_rows($result) > 0) {
        $course = mysqli_fetch_assoc($result);
    }
}

// ADD MODULE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_module'])) {
    $course_id = (int)$_POST['course_id'];
    $module_code = mysqli_real_escape_string($conn, $_POST['module_code']);
    $module_name = mysqli_real_escape_string($conn, $_POST['module_name']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $credits = (int)$_POST['credits'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    $check = mysqli_query($conn, "SELECT id FROM course_modules WHERE course_id='$course_id' AND module_code='$module_code'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Module code already exists for this course!";
    } else {
        $sql = "INSERT INTO course_modules (course_id, module_code, module_name, semester, credits, description) 
                VALUES ('$course_id', '$module_code', '$module_name', '$semester', '$credits', '$description')";
        if (mysqli_query($conn, $sql)) {
            $success = "Module added successfully!";
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}

// DELETE MODULE
if (isset($_GET['delete_module'])) {
    $id = (int)$_GET['delete_module'];
    $course_id = (int)$_GET['course_id'];
    mysqli_query($conn, "DELETE FROM course_modules WHERE id='$id'");
    $success = "Module deleted!";
}

// FETCH MODULES
$sem1_modules = []; $sem2_modules = [];
if ($course_id > 0) {
    $sql = "SELECT * FROM course_modules WHERE course_id='$course_id' ORDER BY module_code ASC";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            if ($row['semester'] == 'Semester 1') $sem1_modules[] = $row;
            else $sem2_modules[] = $row;
        }
    }
}

// FETCH ALL COURSES FOR DROPDOWN
$all_courses = [];
$result_courses = mysqli_query($conn, "SELECT id, course_code, course_name FROM courses ORDER BY course_code ASC");
if ($result_courses && mysqli_num_rows($result_courses) > 0) {
    while ($row = mysqli_fetch_assoc($result_courses)) {
        $all_courses[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Course Modules - Admin | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .modules-container { background: white; border-radius: 12px; padding: 25px; margin-top: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .course-header { background: linear-gradient(135deg, #0056b3, #003d82); color: white; padding: 20px; border-radius: 12px; margin-bottom: 25px; }
        .section-title { font-size: 18px; font-weight: bold; margin: 25px 0 15px 0; color: #0056b3; border-bottom: 2px solid #0056b3; padding-bottom: 8px; display: flex; justify-content: space-between; }
        .modules-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .module-card { background: #f8f9fa; border-radius: 10px; padding: 15px; border-left: 4px solid #28a745; }
        .module-code { font-size: 12px; color: #0056b3; font-weight: bold; }
        .module-name { font-size: 15px; font-weight: bold; color: #333; margin: 5px 0; }
        .module-meta { font-size: 11px; color: #666; margin-top: 8px; }
        .btn-delete-module { background: #dc3545; color: white; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-size: 11px; display: inline-block; margin-top: 8px; }
        .form-card { background: #f8f9fa; padding: 20px; border-radius: 12px; margin-bottom: 25px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .btn-save { background: #28a745; color: white; padding: 10px 25px; border: none; border-radius: 6px; cursor: pointer; }
        .full-width { grid-column: span 2; }
        .stats { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat { background: #f8f9fa; padding: 15px; text-align: center; border-radius: 10px; flex: 1; }
        .stat h3 { font-size: 24px; color: #0056b3; }
        .course-selector { background: #e8f4fd; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .course-selector select { padding: 10px; border-radius: 6px; width: 100%; max-width: 400px; }
        .success-msg, .error-msg { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .success-msg { background: #d4edda; color: #155724; }
        .error-msg { background: #f8d7da; color: #721c24; }
        .back-link { display: inline-block; margin-bottom: 15px; color: #0056b3; text-decoration: none; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .full-width { grid-column: span 1; } }
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
            <div class="panel-title">Manage Course Modules</div>
            
            <div class="info-banner">
                <span><strong>Admin:</strong> <?php echo $admin_name; ?></span>
                <span><strong>System Status:</strong> Online</span>
            </div>

            <div class="modules-container">
                <a href="admin_courses.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Courses</a>

                <?php if($success): ?><div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
                <?php if($error): ?><div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>

                <?php if($course_id == 0 || !$course): ?>
                <div class="course-selector">
                    <h3><i class="fas fa-search"></i> Select a Course First</h3>
                    <select id="courseSelect" onchange="window.location.href='admin_course_modules.php?course_id='+this.value">
                        <option value="">-- Select Course --</option>
                        <?php foreach($all_courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo $c['course_code']; ?> - <?php echo $c['course_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if($course && $course_id > 0): ?>
                <div class="course-header">
                    <h2><i class="fas fa-book"></i> <?php echo $course['course_code']; ?> - <?php echo $course['course_name']; ?></h2>
                    <p><?php echo $course['description']; ?></p>
                </div>

                <div class="form-card">
                    <h3><i class="fas fa-plus-circle"></i> Add New Module</h3>
                    <form method="POST">
                        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                        <div class="form-grid">
                            <div class="form-group"><label>Module Code</label><input type="text" name="module_code" placeholder="e.g., <?php echo $course['course_code']; ?>101" required></div>
                            <div class="form-group"><label>Module Name</label><input type="text" name="module_name" placeholder="e.g., Introduction to Programming" required></div>
                            <div class="form-group"><label>Semester</label><select name="semester"><option>Semester 1</option><option>Semester 2</option></select></div>
                            <div class="form-group"><label>Credits</label><input type="number" name="credits" value="3"></div>
                            <div class="form-group full-width"><label>Description</label><textarea name="description" rows="2"></textarea></div>
                        </div>
                        <button type="submit" name="add_module" class="btn-save"><i class="fas fa-save"></i> Add Module</button>
                    </form>
                </div>

                <div class="stats">
                    <div class="stat"><h3><?php echo count($sem1_modules) + count($sem2_modules); ?></h3><p>Total Modules</p></div>
                    <div class="stat"><h3><?php echo count($sem1_modules); ?></h3><p>Semester 1</p></div>
                    <div class="stat"><h3><?php echo count($sem2_modules); ?></h3><p>Semester 2</p></div>
                </div>

                <div class="section-title">Semester 1 Modules (<?php echo count($sem1_modules); ?>) <small style="color:#28a745;">Target: 5+ modules</small></div>
                <div class="modules-grid">
                    <?php if(empty($sem1_modules)): ?><div style="color:#999; padding:20px;">No modules added yet.</div>
                    <?php else: foreach($sem1_modules as $mod): ?>
                    <div class="module-card"><div class="module-code"><?php echo $mod['module_code']; ?></div><div class="module-name"><?php echo $mod['module_name']; ?></div><div class="module-meta"><i class="fas fa-star"></i> <?php echo $mod['credits']; ?> Credits</div><a href="?delete_module=<?php echo $mod['id']; ?>&course_id=<?php echo $course_id; ?>" class="btn-delete-module" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i> Delete</a></div>
                    <?php endforeach; endif; ?>
                </div>

                <div class="section-title">Semester 2 Modules (<?php echo count($sem2_modules); ?>) <small style="color:#28a745;">Target: 5+ modules</small></div>
                <div class="modules-grid">
                    <?php if(empty($sem2_modules)): ?><div style="color:#999; padding:20px;">No modules added yet.</div>
                    <?php else: foreach($sem2_modules as $mod): ?>
                    <div class="module-card"><div class="module-code"><?php echo $mod['module_code']; ?></div><div class="module-name"><?php echo $mod['module_name']; ?></div><div class="module-meta"><i class="fas fa-star"></i> <?php echo $mod['credits']; ?> Credits</div><a href="?delete_module=<?php echo $mod['id']; ?>&course_id=<?php echo $course_id; ?>" class="btn-delete-module" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i> Delete</a></div>
                    <?php endforeach; endif; ?>
                </div>
                <?php endif; ?>
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