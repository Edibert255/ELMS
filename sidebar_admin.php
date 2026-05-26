<?php
// ==============================================
// FILE: sidebar_admin.php
// ==============================================

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Fetch user data from database
$sql_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $sql_user);
$user_data = mysqli_fetch_assoc($result_user);

// Set variables with defaults
$full_name = $user_data['full_name'] ?? 'Administrator';
$first_name = explode(' ', $full_name)[0];
$last_name = isset(explode(' ', $full_name)[1]) ? explode(' ', $full_name)[1] : '';
$photo = $user_data['photo'] ?? 'default.png';
$role = 'admin';

// Photo cache buster - handles missing files
$photo_path = "uploads/profile_pics/" . $photo;
if (file_exists($photo_path)) {
    $photo_version = filemtime($photo_path);
} else {
    $photo_version = time();
}
?>

<aside>
    <div class="sidebar-top">
        <div class="logo-circle">CBE</div>
        <div class="cbe-text">
            <h2>College of Business Education</h2>
            <p>Electronic Learning Management System</p>
        </div>
    </div>

    <div class="user-info-sidebar">
        <img src="uploads/profile_pics/<?php echo $photo; ?>?v=<?php echo $photo_version; ?>" alt="Admin Photo">
        <div class="user-names">
            <h3><?php echo strtoupper($first_name . ", " . $last_name); ?></h3>
            <p>Administrator</p>
        </div>
    </div>

    <nav>
        <a href="admin_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active-blue' : ''; ?>">
            <span class="menu-content"><i class="fas fa-home main-icon"></i> Home</span>
        </a>

        <div class="menu-item">
            <button class="menu-btn" onclick="toggleSub('profile')">
                <span class="menu-content"><i class="fas fa-user-circle main-icon"></i> My Profile</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </button>
            <div id="profile" class="submenu">
                <a href="admin_profile.php">Admin Profile</a>
            </div>
        </div>

        <div class="menu-item">
            <button class="menu-btn" onclick="toggleSub('users')">
                <span class="menu-content"><i class="fas fa-users main-icon"></i> User Management</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </button>
            <div id="users" class="submenu">
                <a href="admin_students.php">Manage Students</a>
                <a href="admin_lecturers.php">Manage Lecturers</a>
                <a href="admin_add_user.php">Add New User</a>
                <a href="admin_register_student.php">Register Student</a>
                <a href="admin_manage_photos.php">Manage Photos</a>
            </div>
        </div>

        <div class="menu-item">
            <button class="menu-btn" onclick="toggleSub('courses')">
                <span class="menu-content"><i class="fas fa-book main-icon"></i> Course Management</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </button>
            <div id="courses" class="submenu">
                <a href="admin_courses.php">Manage Courses</a>
                <a href="admin_course_modules.php">Manage Course Modules</a>
                <a href="admin_assign_course.php">Assign Course to Student</a>
            </div>
        </div>

        <div class="menu-item">
            <button class="menu-btn" onclick="toggleSub('modules')">
                <span class="menu-content"><i class="fas fa-layer-group main-icon"></i> Modules</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </button>
            <div id="modules" class="submenu">
                <a href="admin_modules.php">Manage Modules</a>
                <a href="admin_assign_module.php">Assign Module to Lecturer</a>
            </div>
        </div>

        <div class="menu-item">
            <button class="menu-btn" onclick="toggleSub('assignments')">
                <span class="menu-content"><i class="fas fa-tasks main-icon"></i> Assignments</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </button>
            <div id="assignments" class="submenu">
                <a href="admin_assignments.php">All Assignments</a>
                <a href="admin_submissions.php">All Submissions</a>
            </div>
        </div>

        <div class="menu-item">
            <button class="menu-btn" onclick="toggleSub('timetable')">
                <span class="menu-content"><i class="fas fa-calendar-alt main-icon"></i> Time Table</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </button>
            <div id="timetable" class="submenu">
                <a href="admin_class_timetable.php">Class Timetable</a>
                <a href="admin_exam_timetable.php">Exam Timetable</a>
            </div>
        </div>

        <a href="admin_live_classes.php" class="nav-link">
            <span class="menu-content"><i class="fas fa-video main-icon"></i> Live Classes</span>
        </a>

        <a href="admin_library.php" class="nav-link">
            <span class="menu-content"><i class="fas fa-book main-icon"></i> Library</span>
        </a>
<a href="course_enrollment_report.php" class="nav-link">
    <span class="menu-content"><i class="fas fa-chart-line main-icon"></i> Enrollment Report</span>
</a>
        <a href="admin_reports.php" class="nav-link">
            <span class="menu-content"><i class="fas fa-chart-bar main-icon"></i> Reports</span>
        </a>

        <div class="menu-item">
            <button class="menu-btn" onclick="toggleSub('settings')">
                <span class="menu-content"><i class="fas fa-cog main-icon"></i> Settings</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </button>
            <div id="settings" class="submenu">
                <a href="admin_academic_year.php">Academic Year</a>
                <a href="admin_semester.php">Semester Settings</a>
            </div>
        </div>

        <div class="menu-item">
            <button class="menu-btn" onclick="toggleSub('security')">
                <span class="menu-content"><i class="fas fa-shield-alt main-icon"></i> Security</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </button>
            <div id="security" class="submenu">
                <a href="change_password.php">Change Password</a>
                <a href="admin_logs.php">System Logs</a>
            </div>
        </div>

        <a href="logout.php" class="nav-link" style="color: #d9534f; margin-top: 15px;">
            <span class="menu-content"><i class="fas fa-sign-out-alt main-icon"></i> Log Out</span>
        </a>
    </nav>
</aside>

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