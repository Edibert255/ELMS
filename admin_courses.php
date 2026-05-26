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

// FETCH ADMIN DATA FOR SIDEBAR
$sql_admin = "SELECT * FROM users WHERE id = '$admin_id'";
$result_admin = mysqli_query($conn, $sql_admin);
$admin_data = mysqli_fetch_assoc($result_admin);
$photo = isset($admin_data['photo']) ? $admin_data['photo'] : 'default.png';
$first_name = explode(' ', $admin_name)[0];
$last_name = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';

// ADD COURSE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_course'])) {
    $course_code = mysqli_real_escape_string($conn, $_POST['course_code']);
    $course_name = mysqli_real_escape_string($conn, $_POST['course_name']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $level = mysqli_real_escape_string($conn, $_POST['level']);
    
    $check = mysqli_query($conn, "SELECT id FROM courses WHERE course_code = '$course_code'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Course code already exists!";
    } else {
        $sql = "INSERT INTO courses (course_code, course_name, duration, department, level) 
                VALUES ('$course_code', '$course_name', '$duration', '$department', '$level')";
        if (mysqli_query($conn, $sql)) {
            $success = "Course added successfully!";
            $_POST = array();
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}

// EDIT COURSE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_course'])) {
    $course_id = (int)$_POST['course_id'];
    $course_code = mysqli_real_escape_string($conn, $_POST['course_code']);
    $course_name = mysqli_real_escape_string($conn, $_POST['course_name']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $level = mysqli_real_escape_string($conn, $_POST['level']);
    
    $sql = "UPDATE courses SET course_code='$course_code', course_name='$course_name', 
            duration='$duration', department='$department', level='$level' 
            WHERE id='$course_id'";
    if (mysqli_query($conn, $sql)) {
        $success = "Course updated successfully!";
    } else {
        $error = "Update failed!";
    }
}

// DELETE COURSE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "DELETE FROM courses WHERE id = '$id'";
    if (mysqli_query($conn, $sql)) {
        $success = "Course deleted successfully!";
    } else {
        $error = "Delete failed!";
    }
}

// SEARCH
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$level_filter = isset($_GET['level']) ? mysqli_real_escape_string($conn, $_GET['level']) : '';

// FETCH COURSES
$courses = [];
$sql = "SELECT * FROM courses WHERE 1=1";
if ($search != '') {
    $sql .= " AND (course_code LIKE '%$search%' OR course_name LIKE '%$search%')";
}
if ($level_filter != '') {
    $sql .= " AND level = '$level_filter'";
}
$sql .= " ORDER BY 
          CASE level 
              WHEN 'certificate' THEN 1
              WHEN 'diploma' THEN 2
              WHEN 'bachelor' THEN 3
              ELSE 4
          END, course_code ASC";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $courses[] = $row;
    }
}

// GET EDIT COURSE
$edit_course = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $result_edit = mysqli_query($conn, "SELECT * FROM courses WHERE id='$edit_id'");
    if ($result_edit && mysqli_num_rows($result_edit) > 0) {
        $edit_course = mysqli_fetch_assoc($result_edit);
    }
}

// Get counts by level
$certificate_count = 0;
$diploma_count = 0;
$bachelor_count = 0;
foreach ($courses as $c) {
    if ($c['level'] == 'certificate') $certificate_count++;
    elseif ($c['level'] == 'diploma') $diploma_count++;
    elseif ($c['level'] == 'bachelor') $bachelor_count++;
}
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Admin | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .manage-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        /* Form Styles */
        .form-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
            font-size: 13px;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .btn-save {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .full-width {
            grid-column: span 3;
        }
        
        /* Stats Row */
        .stats-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 8px;
            text-align: center;
            flex: 1;
            border-left: 3px solid #0056b3;
        }
        
        .stat-card h4 {
            font-size: 22px;
            color: #0056b3;
            margin-bottom: 5px;
        }
        
        /* Search and Filter */
        .search-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-filter input {
            flex: 2;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .search-filter select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
        }
        
        .search-filter button {
            background: #0056b3;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        /* Courses Table */
        .courses-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .courses-table th {
            text-align: left;
            padding: 12px;
            background: #0056b3;
            color: white;
            font-weight: 600;
        }
        
        .courses-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .courses-table tr:hover {
            background: #f8f9fa;
        }
        
        .course-code {
            font-weight: bold;
            color: #0056b3;
            width: 100px;
        }
        
        .course-name {
            font-weight: 600;
            color: #333;
        }
        
        .level-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .level-certificate {
            background: #17a2b8;
            color: white;
        }
        
        .level-diploma {
            background: #28a745;
            color: white;
        }
        
        .level-bachelor {
            background: #0056b3;
            color: white;
        }
        
        .btn-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
            padding: 4px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 11px;
            display: inline-block;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 11px;
            display: inline-block;
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
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .full-width {
                grid-column: span 1;
            }
            .courses-table {
                font-size: 12px;
                display: block;
                overflow-x: auto;
            }
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
            <div class="panel-title">Manage Courses</div>
            
            <div class="info-banner">
                <span><strong>Admin:</strong> <?php echo $admin_name; ?></span>
                <span><strong>Total Courses:</strong> <?php echo count($courses); ?></span>
                <span><strong>System Status:</strong> Online</span>
            </div>

            <div class="manage-container">
                <?php if($success): ?>
                    <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Add/Edit Course Form -->
                <div class="form-card">
                    <h3><?php echo $edit_course ? 'Edit Course' : 'Add New Course'; ?></h3>
                    <form method="POST">
                        <?php if($edit_course): ?>
                            <input type="hidden" name="course_id" value="<?php echo $edit_course['id']; ?>">
                        <?php endif; ?>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Course Code</label>
                                <input type="text" name="course_code" value="<?php echo $edit_course['course_code'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Course Name</label>
                                <input type="text" name="course_name" value="<?php echo $edit_course['course_name'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Duration</label>
                                <input type="text" name="duration" value="<?php echo $edit_course['duration'] ?? '2 Years'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Department</label>
                                <input type="text" name="department" value="<?php echo $edit_course['department'] ?? 'ICT'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Level</label>
                                <select name="level" required>
                                    <option value="">-- Select Level --</option>
                                    <option value="certificate" <?php echo ($edit_course['level'] ?? '') == 'certificate' ? 'selected' : ''; ?>>Certificate</option>
                                    <option value="diploma" <?php echo ($edit_course['level'] ?? '') == 'diploma' ? 'selected' : ''; ?>>Diploma</option>
                                    <option value="bachelor" <?php echo ($edit_course['level'] ?? '') == 'bachelor' ? 'selected' : ''; ?>>Bachelor / Degree</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="<?php echo $edit_course ? 'edit_course' : 'add_course'; ?>" class="btn-save">
                            <i class="fas fa-save"></i> <?php echo $edit_course ? 'Update Course' : 'Add Course'; ?>
                        </button>
                        <?php if($edit_course): ?>
                            <a href="admin_courses.php" style="margin-left:10px; background:#6c757d; color:white; padding:10px 20px; border-radius:6px; text-decoration:none;">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Statistics -->
                <div class="stats-row">
                    <div class="stat-card"><h4><?php echo $certificate_count; ?></h4><p>Certificate</p></div>
                    <div class="stat-card"><h4><?php echo $diploma_count; ?></h4><p>Diploma</p></div>
                    <div class="stat-card"><h4><?php echo $bachelor_count; ?></h4><p>Bachelor</p></div>
                </div>

                <!-- Search and Filter -->
                <form method="GET" class="search-filter">
                    <input type="text" name="search" placeholder="Search by course code or name..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="level">
                        <option value="">All Levels</option>
                        <option value="certificate" <?php echo $level_filter == 'certificate' ? 'selected' : ''; ?>>Certificate</option>
                        <option value="diploma" <?php echo $level_filter == 'diploma' ? 'selected' : ''; ?>>Diploma</option>
                        <option value="bachelor" <?php echo $level_filter == 'bachelor' ? 'selected' : ''; ?>>Bachelor</option>
                    </select>
                    <button type="submit"><i class="fas fa-search"></i> Filter</button>
                    <?php if($search != '' || $level_filter != ''): ?>
                    <a href="admin_courses.php" style="background:#6c757d; color:white; padding:10px 20px; border-radius:6px; text-decoration:none;">Clear</a>
                    <?php endif; ?>
                </form>

                <!-- Courses Table -->
                <?php if(empty($courses)): ?>
                    <div class="empty-state">
                        <i class="fas fa-book-open" style="font-size: 48px;"></i>
                        <p>No courses found.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="courses-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Course Name</th>
                                    <th>Duration</th>
                                    <th>Department</th>
                                    <th>Level</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($courses as $course): ?>
                                <tr>
                                    <td class="course-code"><?php echo $course['course_code']; ?></td>
                                    <td class="course-name"><?php echo $course['course_name']; ?></td>
                                    <td><?php echo $course['duration']; ?></td>
                                    <td><?php echo $course['department']; ?></td>
                                    <td>
                                        <?php
                                        if ($course['level'] == 'certificate') {
                                            echo '<span class="level-badge level-certificate">Certificate</span>';
                                        } elseif ($course['level'] == 'diploma') {
                                            echo '<span class="level-badge level-diploma">Diploma</span>';
                                        } elseif ($course['level'] == 'bachelor') {
                                            echo '<span class="level-badge level-bachelor">Bachelor</span>';
                                        } else {
                                            echo '<span class="level-badge level-diploma">Diploma</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="btn-group">
                                        <a href="?edit=<?php echo $course['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="?delete=<?php echo $course['id']; ?>" class="btn-delete" onclick="return confirm('Delete this course?')"><i class="fas fa-trash"></i> Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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