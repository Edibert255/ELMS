<?php
// ==============================================
// FILE: sidebar_lecturer.php
// ==============================================

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Fetch user data from database
$sql_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $sql_user);
$user_data = mysqli_fetch_assoc($result_user);

// Set variables with defaults
$full_name = $user_data['full_name'] ?? 'Lecturer';
$first_name = explode(' ', $full_name)[0];
$last_name = isset(explode(' ', $full_name)[1]) ? explode(' ', $full_name)[1] : '';
$photo = $user_data['photo'] ?? 'default.png';
$role = 'lecturer';

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
        <img src="uploads/profile_pics/<?php echo $photo; ?>?v=<?php echo $photo_version; ?>" alt="Lecturer Photo">
        <div class="user-names">
            <h3><?php echo strtoupper($first_name . ", " . $last_name); ?></h3>
            <p>Lecturer Panel</p>
        </div>
    </div>

    <nav>
        <a href="lecture_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'lecture_dashboard.php' ? 'active-blue' : ''; ?>">
            <span class="menu-content"><i class="fas fa-home main-icon"></i> Home</span>
        </a>

        <div class="menu-item">
            <button class="menu-btn" onclick="toggleSub('modules')">
                <span class="menu-content"><i class="fas fa-book main-icon"></i> My Modules</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </button>
            <div id="modules" class="submenu">
                <a href="lecture_modules.php">All Modules</a>
                <a href="lecture_modules.php?semester=Semester%201">Semester 1</a>
                <a href="lecture_modules.php?semester=Semester%202">Semester 2</a>
            </div>
        </div>

        <div class="menu-item">
            <button class="menu-btn" onclick="toggleSub('assign')">
                <span class="menu-content"><i class="fas fa-tasks main-icon"></i> Assignments</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </button>
            <div id="assign" class="submenu">
                <a href="lecture_assignments.php">My Assignments</a>
                <a href="lecture_add_assignment.php">Add New Assignment</a>
            </div>
        </div>

        <div class="menu-item">
            <button class="menu-btn" onclick="toggleSub('submissions')">
                <span class="menu-content"><i class="fas fa-check-double main-icon"></i> Submissions</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </button>
            <div id="submissions" class="submenu">
                <a href="lecture_submissions.php?filter=pending">Pending Grading</a>
                <a href="lecture_submissions.php?filter=graded">Graded Submissions</a>
            </div>
        </div>

        <a href="lecture_students.php" class="nav-link">
            <span class="menu-content"><i class="fas fa-users main-icon"></i> Students</span>
        </a>

        <div class="menu-item">
            <button class="menu-btn" onclick="toggleSub('timetable')">
                <span class="menu-content"><i class="fas fa-calendar-alt main-icon"></i> Time Table</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </button>
            <div id="timetable" class="submenu">
                <a href="lecture_class_timetable.php">Class Timetable</a>
                <a href="lecture_exam_timetable.php">Exam Timetable</a>
            </div>
        </div>

        <a href="lecture_live_classes.php" class="nav-link">
            <span class="menu-content"><i class="fas fa-video main-icon"></i> Live Classes</span>
        </a>

        <a href="lecture_library.php" class="nav-link">
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