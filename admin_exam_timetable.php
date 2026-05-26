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

// FETCH ADMIN DATA
$sql_admin = "SELECT * FROM users WHERE id = '$admin_id'";
$result_admin = mysqli_query($conn, $sql_admin);
$admin_data = mysqli_fetch_assoc($result_admin);
$photo = isset($admin_data['photo']) ? $admin_data['photo'] : 'default.png';
$first_name = explode(' ', $admin_name)[0];
$last_name = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';

// FETCH COURSES
$courses = [];
$sql_courses = "SELECT id, course_code, course_name FROM courses ORDER BY course_code ASC";
$result_courses = mysqli_query($conn, $sql_courses);
if ($result_courses && mysqli_num_rows($result_courses) > 0) {
    while ($row = mysqli_fetch_assoc($result_courses)) {
        $courses[] = $row;
    }
}

// ADD EXAM
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_exam'])) {
    $course_id = (int)$_POST['course_id'];
    $course_name = mysqli_real_escape_string($conn, $_POST['course_name']);
    $exam_date = mysqli_real_escape_string($conn, $_POST['exam_date']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $venue = mysqli_real_escape_string($conn, $_POST['venue']);
    $exam_type = mysqli_real_escape_string($conn, $_POST['exam_type']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $academic_year = mysqli_real_escape_string($conn, $_POST['academic_year']);
    $instructions = mysqli_real_escape_string($conn, $_POST['instructions']);
    
    $sql = "INSERT INTO exam_timetable (course_id, course_name, exam_date, start_time, end_time, venue, exam_type, semester, academic_year, instructions, created_by) 
            VALUES ('$course_id', '$course_name', '$exam_date', '$start_time', '$end_time', '$venue', '$exam_type', '$semester', '$academic_year', '$instructions', '$admin_id')";
    
    if (mysqli_query($conn, $sql)) {
        $success = "Exam added to timetable successfully!";
        $_POST = array();
    } else {
        $error = "Database error: " . mysqli_error($conn);
    }
}

// DELETE EXAM
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM exam_timetable WHERE id = '$id'");
    $success = "Exam deleted successfully!";
}

// FETCH ALL EXAMS
$exams = [];
$sql_exams = "SELECT * FROM exam_timetable ORDER BY exam_date ASC, start_time ASC";
$result_exams = mysqli_query($conn, $sql_exams);
if ($result_exams && mysqli_num_rows($result_exams) > 0) {
    while ($row = mysqli_fetch_assoc($result_exams)) {
        $exams[] = $row;
    }
}

$total_exams = count($exams);
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Timetable Management - Admin | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .timetable-container { background: white; border-radius: 12px; padding: 25px; margin-top: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-card { background: #f8f9fa; border-radius: 12px; padding: 25px; margin-bottom: 30px; border: 1px solid #e0e0e0; }
        .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .btn-save { background: #28a745; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; }
        .exams-table { width: 100%; border-collapse: collapse; }
        .exams-table th, .exams-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .exams-table th { background: #dc3545; color: white; }
        .btn-delete { background: #dc3545; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; }
        .stats-grid { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-card { background: linear-gradient(135deg, #dc3545, #b02a37); color: white; padding: 15px 20px; border-radius: 10px; text-align: center; flex: 1; }
        .exam-type { display: inline-block; padding: 3px 10px; border-radius: 15px; font-size: 11px; font-weight: bold; }
        .type-Midterm { background: #ffc107; color: #333; }
        .type-Final { background: #dc3545; color: white; }
        .type-Quiz { background: #17a2b8; color: white; }
        .type-Practical { background: #6f42c1; color: white; }
        .full-width { grid-column: span 3; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .full-width { grid-column: span 1; } }
    </style>
</head>
<body>
    <aside>
        <div class="sidebar-top"><div class="logo-circle">CBE</div><div class="cbe-text"><h2>College of Business Education</h2><p>Electronic Learning Management System</p></div></div>
        <div class="user-info-sidebar"><img src="uploads/profile_pics/<?php echo $photo; ?>"><div class="user-names"><h3><?php echo strtoupper($first_name . ", " . $last_name); ?></h3><p>Administrator</p></div></div>
        <nav>
            <a href="admin_dashboard.php" class="nav-link">Home</a>
            <div class="menu-item"><button class="menu-btn active-blue" onclick="toggleSub('timetable')"><span class="menu-content"><i class="fas fa-calendar-alt main-icon"></i> Time Table</span><i class="fas fa-chevron-right arrow-icon"></i></button>
                <div id="timetable" class="submenu active"><a href="admin_class_timetable.php">Class Timetable</a><a href="admin_exam_timetable.php" style="background:#e8f4ff;">Exam Timetable</a></div>
            </div>
            <a href="logout.php" class="nav-link" style="color:#d9534f; margin-top:15px;">Logout</a>
        </nav>
    </aside>

    <div class="content-wrapper">
        <header class="top-header"><div class="welcome-top"><p>Mwanza Campus | Today: <?php echo date("d M, Y"); ?></p></div></header>
        <main>
            <div class="panel-title">Exam Timetable Management</div>
            <div class="info-banner"><span><strong>Admin:</strong> <?php echo $admin_name; ?></span><span><strong>Total Exams:</strong> <?php echo $total_exams; ?></span></div>

            <div class="timetable-container">
                <?php if($success): ?><div class="success-msg"><?php echo $success; ?></div><?php endif; ?>
                <?php if($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>

                <div class="form-card">
                    <h3><i class="fas fa-plus-circle"></i> Add Exam to Timetable</h3>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group"><label>Course</label><select name="course_id" required><option value="">Select Course</option><?php foreach($courses as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo $c['course_code']; ?> - <?php echo $c['course_name']; ?></option><?php endforeach; ?></select></div>
                            <div class="form-group"><label>Course Name</label><input type="text" name="course_name" placeholder="Course Name" required></div>
                            <div class="form-group"><label>Exam Date</label><input type="date" name="exam_date" required></div>
                            <div class="form-group"><label>Start Time</label><input type="time" name="start_time" required></div>
                            <div class="form-group"><label>End Time</label><input type="time" name="end_time" required></div>
                            <div class="form-group"><label>Venue</label><input type="text" name="venue" placeholder="e.g., Hall A, Lab 1" required></div>
                            <div class="form-group"><label>Exam Type</label><select name="exam_type"><option>Midterm</option><option>Final</option><option>Quiz</option><option>Practical</option></select></div>
                            <div class="form-group"><label>Semester</label><select name="semester"><option>Semester 1</option><option>Semester 2</option></select></div>
                            <div class="form-group"><label>Academic Year</label><input type="text" name="academic_year" placeholder="e.g., 2025/2026" value="2025/2026" required></div>
                            <div class="form-group full-width"><label>Instructions</label><textarea name="instructions" rows="2" placeholder="Exam instructions for students..."></textarea></div>
                        </div>
                        <button type="submit" name="add_exam" class="btn-save"><i class="fas fa-save"></i> Add Exam</button>
                    </form>
                </div>

                <div class="stats-grid"><div class="stat-card"><h3><?php echo $total_exams; ?></h3><p>Total Exams</p></div></div>

                <?php if(empty($exams)): ?>
                    <div class="empty-state" style="text-align:center; padding:50px; color:#999;">No exams added yet.</div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="exams-table">
                            <thead><tr><th>Course</th><th>Exam Date</th><th>Time</th><th>Venue</th><th>Type</th><th>Action</th></tr></thead>
                            <tbody><?php foreach($exams as $exam): ?>
                            <tr><td><?php echo $exam['course_name']; ?></td><td><?php echo date('d M, Y', strtotime($exam['exam_date'])); ?></td><td><?php echo date('h:i A', strtotime($exam['start_time'])); ?> - <?php echo date('h:i A', strtotime($exam['end_time'])); ?></td><td><?php echo $exam['venue']; ?></td><td><span class="exam-type type-<?php echo $exam['exam_type']; ?>"><?php echo $exam['exam_type']; ?></span></td><td><a href="?delete=<?php echo $exam['id']; ?>" class="btn-delete" onclick="return confirm('Delete this exam?')">Delete</a></td></tr>
                            <?php endforeach; ?></tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>function toggleSub(id){const m=document.getElementById(id);if(m){const a=m.classList.contains('active');document.querySelectorAll('.submenu').forEach(s=>s.classList.remove('active'));if(!a)m.classList.add('active');}}</script>
</body>
</html>