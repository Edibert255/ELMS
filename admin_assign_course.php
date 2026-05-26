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

// ASSIGN COURSE TO STUDENT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_course'])) {
    $student_id = (int)$_POST['student_id'];
    $course_id = (int)$_POST['course_id'];
    
    $check = mysqli_query($conn, "SELECT id FROM course_enrollments WHERE student_id='$student_id' AND course_id='$course_id'");
    if (mysqli_num_rows($check) > 0) {
        $error = "This course is already assigned to this student!";
    } else {
        $sql = "INSERT INTO course_enrollments (student_id, course_id) VALUES ('$student_id', '$course_id')";
        if (mysqli_query($conn, $sql)) {
            $success = "Course assigned to student successfully!";
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}

// REMOVE ASSIGNMENT
if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    mysqli_query($conn, "DELETE FROM course_enrollments WHERE id='$id'");
    $success = "Assignment removed successfully!";
}

// FETCH ALL STUDENTS
$students = [];
$result_students = mysqli_query($conn, "SELECT id, full_name, username FROM users WHERE role='student' ORDER BY full_name ASC");
if ($result_students && mysqli_num_rows($result_students) > 0) {
    while ($row = mysqli_fetch_assoc($result_students)) {
        $students[] = $row;
    }
}

// FETCH ALL COURSES
$courses = [];
$result_courses = mysqli_query($conn, "SELECT id, course_code, course_name FROM courses ORDER BY course_code ASC");
if ($result_courses && mysqli_num_rows($result_courses) > 0) {
    while ($row = mysqli_fetch_assoc($result_courses)) {
        $courses[] = $row;
    }
}

// FETCH CURRENT ASSIGNMENTS
$assignments = [];
$sql = "SELECT ce.*, u.full_name as student_name, c.course_code, c.course_name 
        FROM course_enrollments ce
        JOIN users u ON ce.student_id = u.id
        JOIN courses c ON ce.course_id = c.id
        ORDER BY u.full_name ASC";
$result_assign = mysqli_query($conn, $sql);
if ($result_assign && mysqli_num_rows($result_assign) > 0) {
    while ($row = mysqli_fetch_assoc($result_assign)) {
        $assignments[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Course to Student - Admin | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .assign-container { background: white; border-radius: 12px; padding: 25px; margin-top: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-card { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 25px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: flex-end; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px; }
        .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .btn-assign { background: #28a745; color: white; padding: 10px 25px; border: none; border-radius: 6px; cursor: pointer; }
        .assignments-table { width: 100%; border-collapse: collapse; overflow-x: auto; display: block; }
        .assignments-table th, .assignments-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .assignments-table th { background: #0056b3; color: white; }
        .btn-remove { background: #dc3545; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; display: inline-block; }
        .section-title { font-size: 18px; font-weight: bold; margin: 20px 0; color: #0056b3; border-bottom: 2px solid #0056b3; padding-bottom: 8px; }
        .success-msg, .error-msg { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .success-msg { background: #d4edda; color: #155724; }
        .error-msg { background: #f8d7da; color: #721c24; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
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
            <div class="panel-title">Assign Course to Student</div>
            
            <div class="info-banner">
                <span><strong>Admin:</strong> <?php echo $admin_name; ?></span>
                <span><strong>System Status:</strong> Online</span>
            </div>

            <div class="assign-container">
                <?php if($success): ?><div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
                <?php if($error): ?><div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>

                <div class="form-card">
                    <h3><i class="fas fa-plus-circle"></i> Assign Course to Student</h3>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group"><label>Select Student</label><select name="student_id" required><option value="">-- Select Student --</option><?php foreach($students as $s): ?><option value="<?php echo $s['id']; ?>"><?php echo $s['full_name']; ?> (<?php echo $s['username']; ?>)</option><?php endforeach; ?></select></div>
                            <div class="form-group"><label>Select Course</label><select name="course_id" required><option value="">-- Select Course --</option><?php foreach($courses as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo $c['course_code']; ?> - <?php echo $c['course_name']; ?></option><?php endforeach; ?></select></div>
                            <div><button type="submit" name="assign_course" class="btn-assign"><i class="fas fa-plus-circle"></i> Assign Course</button></div>
                        </div>
                    </form>
                </div>

                <div class="section-title"><i class="fas fa-list"></i> Current Course Assignments</div>
                <?php if(empty($assignments)): ?><p style="text-align:center; padding:30px; color:#999;">No assignments yet.</p>
                <?php else: ?>
                <table class="assignments-table"><thead><tr><th>Student</th><th>Course Code</th><th>Course Name</th><th>Enrolled Date</th><th>Action</th></tr></thead>
                <tbody><?php foreach($assignments as $ass): ?><tr><td><?php echo $ass['student_name']; ?></td><td><?php echo $ass['course_code']; ?></td><td><?php echo $ass['course_name']; ?></td><td><?php echo date('d M Y', strtotime($ass['enrollment_date'])); ?></td><td><a href="?remove=<?php echo $ass['id']; ?>" class="btn-remove" onclick="return confirm('Remove this assignment?')"><i class="fas fa-trash"></i> Remove</a></td></tr><?php endforeach; ?></tbody></table>
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