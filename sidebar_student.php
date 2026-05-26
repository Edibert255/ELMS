<?php
// ==============================================
// FILE: sidebar_student.php
// ==============================================

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Fetch user data from database
$sql_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $sql_user);
$user_data = mysqli_fetch_assoc($result_user);

// Fetch student data
$sql_student = "SELECT * FROM students WHERE user_id = '$user_id'";
$result_student = mysqli_query($conn, $sql_student);
$student_data = mysqli_fetch_assoc($result_student);

// Set variables with defaults
$full_name = $user_data['full_name'] ?? 'Student';
$first_name = $student_data['first_name'] ?? explode(' ', $full_name)[0];
$last_name = $student_data['last_name'] ?? (isset(explode(' ', $full_name)[1]) ? explode(' ', $full_name)[1] : '');
$photo = $user_data['photo'] ?? 'default.png';
$role = 'student';

// Photo cache buster
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
        <img src="uploads/profile_pics/<?php echo $photo; ?>?v=<?php echo $photo_version; ?>" alt="Student Photo">
        <div class="user-names">
            <h3><?php echo strtoupper($first_name . ", " . $last_name); ?></h3>
            <p>Student Panel</p>
        </div>
    </div>

    <nav>
        <a href="students_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'students_dashboard.php' ? 'active-blue' : ''; ?>">
            <span class="menu-content"><i class="fas fa-home main-icon"></i> Home</span>
        </a>

        <div class="menu-item">
            <button class="menu-btn" onclick="toggleSub('s-info')">
                <span class="menu-content"><i class="fas fa-user-circle main-icon"></i> Student Info</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </button>
            <div id="s-info" class="submenu">
                <a href="students_profile.php">Student Profile</a>
            </div>
        </div>

        <div class="menu-item">
            <button class="menu-btn" onclick="toggleSub('assign')">
                <span class="menu-content"><i class="fas fa-tasks main-icon"></i> Assignments</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </button>
            <div id="assign" class="submenu">
                <a href="recent_assignments.php">Recent Assignments</a>
                <a href="submitted_assignments.php">Submitted Assignments</a>
            </div>
        </div>

        <div class="menu-item">
            <button class="menu-btn" onclick="toggleSub('modules')">
                <span class="menu-content"><i class="fas fa-book main-icon"></i> Registered Module</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </button>
            <div id="modules" class="submenu">
                <a href="modules.php?semester=1">Semester 1</a>
                <a href="modules.php?semester=2">Semester 2</a>
            </div>
        </div>

        <div class="menu-item">
            <button class="menu-btn" onclick="toggleSub('timetable')">
                <span class="menu-content"><i class="fas fa-calendar-alt main-icon"></i> Time Table</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </button>
            <div id="timetable" class="submenu">
                <a href="class_timetable.php">Class Timetable</a>
                <a href="exam_timetable.php">Exam Timetable</a>
            </div>
        </div>

        <a href="live_classes.php" class="nav-link">
            <span class="menu-content"><i class="fas fa-video main-icon"></i> Live Classes</span>
        </a>

        <a href="library.php" class="nav-link">
            <span class="menu-content"><i class="fas fa-book main-icon"></i> Library</span>
        </a>

        <div class="menu-item">
            <button class="menu-btn" onclick="toggleSub('security')">
                <span class="menu-content"><i class="fas fa-shield-alt main-icon"></i> Security</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </button>
            <div id="security" class="submenu">
                <a href="change_password.php">Change Password</a>
                <a href="login_history.php">Login History</a>
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