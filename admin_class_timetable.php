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

// FETCH COURSES FOR DROPDOWN
$courses = [];
$sql_courses = "SELECT id, course_code, course_name FROM courses ORDER BY course_code ASC";
$result_courses = mysqli_query($conn, $sql_courses);
if ($result_courses && mysqli_num_rows($result_courses) > 0) {
    while ($row = mysqli_fetch_assoc($result_courses)) {
        $courses[] = $row;
    }
}

// FETCH LECTURERS FOR DROPDOWN
$lecturers = [];
$sql_lec = "SELECT id, full_name FROM users WHERE role = 'lecturer' AND status = 'active' ORDER BY full_name ASC";
$result_lec = mysqli_query($conn, $sql_lec);
if ($result_lec && mysqli_num_rows($result_lec) > 0) {
    while ($row = mysqli_fetch_assoc($result_lec)) {
        $lecturers[] = $row;
    }
}

// ADD CLASS
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_class'])) {
    $course_id = (int)$_POST['course_id'];
    $course_name = mysqli_real_escape_string($conn, $_POST['course_name']);
    $lecturer_name = mysqli_real_escape_string($conn, $_POST['lecturer_name']);
    $day_of_week = mysqli_real_escape_string($conn, $_POST['day_of_week']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $venue = mysqli_real_escape_string($conn, $_POST['venue']);
    $class_stream = mysqli_real_escape_string($conn, $_POST['class_stream']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $academic_year = mysqli_real_escape_string($conn, $_POST['academic_year']);
    
    $sql = "INSERT INTO class_timetable (course_id, course_name, lecturer_name, day_of_week, start_time, end_time, venue, class_stream, semester, academic_year, created_by) 
            VALUES ('$course_id', '$course_name', '$lecturer_name', '$day_of_week', '$start_time', '$end_time', '$venue', '$class_stream', '$semester', '$academic_year', '$admin_id')";
    
    if (mysqli_query($conn, $sql)) {
        $success = "Class added to timetable successfully!";
        $_POST = array();
    } else {
        $error = "Database error: " . mysqli_error($conn);
    }
}

// DELETE CLASS
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM class_timetable WHERE id = '$id'");
    $success = "Class deleted successfully!";
}

// FETCH ALL CLASSES
$classes = [];
$sql_classes = "SELECT * FROM class_timetable ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), start_time ASC";
$result_classes = mysqli_query($conn, $sql_classes);
if ($result_classes && mysqli_num_rows($result_classes) > 0) {
    while ($row = mysqli_fetch_assoc($result_classes)) {
        $classes[] = $row;
    }
}

$total_classes = count($classes);
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Timetable Management - Admin | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .timetable-container { background: white; border-radius: 12px; padding: 25px; margin-top: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-card { background: #f8f9fa; border-radius: 12px; padding: 25px; margin-bottom: 30px; border: 1px solid #e0e0e0; }
        .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .btn-save { background: #28a745; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; }
        .classes-table { width: 100%; border-collapse: collapse; }
        .classes-table th, .classes-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .classes-table th { background: #0056b3; color: white; }
        .btn-delete { background: #dc3545; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; }
        .stats-grid { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-card { background: linear-gradient(135deg, #0056b3, #003d82); color: white; padding: 15px 20px; border-radius: 10px; text-align: center; flex: 1; }
        .success-msg, .error-msg { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .success-msg { background: #d4edda; color: #155724; }
        .error-msg { background: #f8d7da; color: #721c24; }
        .full-width { grid-column: span 3; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .full-width { grid-column: span 1; } }
    </style>
</head>
<body>

    <?php include 'sidebar_admin.php'; ?>>

    <div class="content-wrapper">
        <header class="top-header"><div class="welcome-top"><p>Mwanza Campus | Today: <?php echo date("d M, Y"); ?></p></div></header>
        <main>
            <div class="panel-title">Class Timetable Management</div>
            <div class="info-banner"><span><strong>Admin:</strong> <?php echo $admin_name; ?></span><span><strong>Total Classes:</strong> <?php echo $total_classes; ?></span></div>

            <div class="timetable-container">
                <?php if($success): ?><div class="success-msg"><?php echo $success; ?></div><?php endif; ?>
                <?php if($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>

                <!-- Add Class Form -->
                <div class="form-card">
                    <h3><i class="fas fa-plus-circle"></i> Add Class to Timetable</h3>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group"><label>Course</label><select name="course_id" required>
                                <option value="">Select Course</option><?php foreach($courses as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo $c['course_code']; ?> - <?php echo $c['course_name']; ?></option><?php endforeach; ?>
                            </select></div>
                            <div class="form-group"><label>Course Name</label><input type="text" name="course_name" placeholder="Course Name" required></div>
                            <div class="form-group"><label>Lecturer</label><select name="lecturer_name" required>
                                <option value="">Select Lecturer</option><?php foreach($lecturers as $l): ?><option value="<?php echo $l['full_name']; ?>"><?php echo $l['full_name']; ?></option><?php endforeach; ?>
                            </select></div>
                            <div class="form-group"><label>Day of Week</label><select name="day_of_week" required><option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option><option>Saturday</option></select></div>
                            <div class="form-group"><label>Start Time</label><input type="time" name="start_time" required></div>
                            <div class="form-group"><label>End Time</label><input type="time" name="end_time" required></div>
                            <div class="form-group"><label>Venue</label><input type="text" name="venue" placeholder="e.g., Lab 1, Hall A" required></div>
                            <div class="form-group"><label>Class Stream</label><input type="text" name="class_stream" placeholder="e.g., BIT 1, BCS 2"></div>
                            <div class="form-group"><label>Semester</label><select name="semester"><option>Semester 1</option><option>Semester 2</option></select></div>
                            <div class="form-group full-width"><label>Academic Year</label><input type="text" name="academic_year" placeholder="e.g., 2025/2026" value="2025/2026" required></div>
                        </div>
                        <button type="submit" name="add_class" class="btn-save"><i class="fas fa-save"></i> Add Class</button>
                    </form>
                </div>

                <!-- Statistics -->
                <div class="stats-grid"><div class="stat-card"><h3><?php echo $total_classes; ?></h3><p>Total Classes</p></div></div>

                <!-- Classes Table -->
                <?php if(empty($classes)): ?>
                    <div class="empty-state" style="text-align:center; padding:50px; color:#999;">No classes added yet.</div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="classes-table">
                            <thead><tr><th>Course</th><th>Lecturer</th><th>Day</th><th>Time</th><th>Venue</th><th>Semester</th><th>Action</th></tr></thead>
                            <tbody><?php foreach($classes as $class): ?>
                            <tr><td><?php echo $class['course_name']; ?></td><td><?php echo $class['lecturer_name']; ?></td><td><?php echo $class['day_of_week']; ?></td><td><?php echo date('h:i A', strtotime($class['start_time'])); ?> - <?php echo date('h:i A', strtotime($class['end_time'])); ?></td><td><?php echo $class['venue']; ?></td><td><?php echo $class['semester']; ?></td><td><a href="?delete=<?php echo $class['id']; ?>" class="btn-delete" onclick="return confirm('Delete this class?')">Delete</a></td></tr>
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