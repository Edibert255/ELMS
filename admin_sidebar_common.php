<?php
// This file should be saved as admin_sidebar_common.php
// Then include it in all admin pages using: <?php include 'admin_sidebar_common.php'; ?>
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
    <?php
    // Get photo with cache buster
    $photo = $student['photo'] ?? 'default.png';
    $photo_path = "uploads/profile_pics/" . $photo;
    $photo_version = file_exists($photo_path) ? filemtime($photo_path) : time();
    ?>
    <img src="uploads/profile_pics/<?php echo $photo; ?>?v=<?php echo $photo_version; ?>" alt="Student Photo">
    <div class="user-names">
        <h3><?php echo strtoupper(($student['first_name'] ?? 'Guest') . ", " . ($student['last_name'] ?? 'User')); ?></h3>
        <p>Student Panel</p>
    </div>
</div>
    <nav>
        <a href="admin_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active-blue' : ''; ?>"><span class="menu-content"><i class="fas fa-home main-icon"></i> Home</span></a>
        <div class="menu-item"><button class="menu-btn" onclick="toggleSub('users')"><span class="menu-content"><i class="fas fa-users main-icon"></i> User Management</span><i class="fas fa-chevron-right arrow-icon"></i></button>
            <div id="users" class="submenu"><a href="admin_students.php">Manage Students</a><a href="admin_lecturers.php">Manage Lecturers</a><a href="admin_add_user.php">Add New User</a></div>
        </div>
        <div class="menu-item"><button class="menu-btn" onclick="toggleSub('courses')"><span class="menu-content"><i class="fas fa-book main-icon"></i> Course Management</span><i class="fas fa-chevron-right arrow-icon"></i></button>
            <div id="courses" class="submenu"><a href="admin_courses.php">Manage Courses</a><a href="admin_course_modules.php">Manage Course Modules</a><a href="admin_assign_course.php">Assign Course to Student</a></div>
        </div>
        <div class="menu-item"><button class="menu-btn" onclick="toggleSub('assignments')"><span class="menu-content"><i class="fas fa-tasks main-icon"></i> Assignments</span><i class="fas fa-chevron-right arrow-icon"></i></button>
            <div id="assignments" class="submenu"><a href="admin_assignments.php">All Assignments</a><a href="admin_submissions.php">All Submissions</a></div>
        </div>
        <div class="menu-item"><button class="menu-btn" onclick="toggleSub('timetable')"><span class="menu-content"><i class="fas fa-calendar-alt main-icon"></i> Time Table</span><i class="fas fa-chevron-right arrow-icon"></i></button>
            <div id="timetable" class="submenu"><a href="admin_class_timetable.php">Class Timetable</a><a href="admin_exam_timetable.php">Exam Timetable</a></div>
        </div>
        <a href="admin_live_classes.php" class="nav-link"><span class="menu-content"><i class="fas fa-video main-icon"></i> Live Classes</span></a>
        <a href="admin_library.php" class="nav-link"><span class="menu-content"><i class="fas fa-book main-icon"></i> Library</span></a>
        <a href="admin_reports.php" class="nav-link"><span class="menu-content"><i class="fas fa-chart-bar main-icon"></i> Reports</span></a>
        <div class="menu-item"><button class="menu-btn" onclick="toggleSub('settings')"><span class="menu-content"><i class="fas fa-cog main-icon"></i> Settings</span><i class="fas fa-chevron-right arrow-icon"></i></button>
            <div id="settings" class="submenu"><a href="admin_academic_year.php">Academic Year</a><a href="admin_semester.php">Semester Settings</a></div>
        </div>
        <div class="menu-item"><button class="menu-btn" onclick="toggleSub('security')"><span class="menu-content"><i class="fas fa-shield-alt main-icon"></i> Security</span><i class="fas fa-chevron-right arrow-icon"></i></button>
            <div id="security" class="submenu"><a href="change_password.php">Change Password</a><a href="admin_logs.php">System Logs</a></div>
        </div>
        <a href="logout.php" class="nav-link" style="color: #d9534f; margin-top: 15px;"><span class="menu-content"><i class="fas fa-sign-out-alt main-icon"></i> Log Out</span></a>
    </nav>
</aside>
<script>
function toggleSub(id) {
    const menu = document.getElementById(id);
    if (menu) {
        document.querySelectorAll('.submenu').forEach(s => s.classList.remove('active'));
        menu.classList.toggle('active');
    }
}
</script>