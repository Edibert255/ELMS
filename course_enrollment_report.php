<?php
include 'config.php';
session_start();

// CHECK IF ADMIN IS LOGGED IN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'];
$selected_course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// FETCH ADMIN PHOTO
$sql_admin = "SELECT * FROM users WHERE id = '{$_SESSION['user_id']}'";
$result_admin = mysqli_query($conn, $sql_admin);
$admin_data = mysqli_fetch_assoc($result_admin);
$admin_photo = $admin_data['photo'] ?? 'default.png';
$first_name = explode(' ', $admin_name)[0];
$last_name = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';

// FETCH ALL COURSES BY LEVEL
$certificate_courses = [];
$diploma_courses = [];
$bachelor_courses = [];

$sql_courses = "SELECT * FROM courses ORDER BY course_name ASC";
$result_courses = mysqli_query($conn, $sql_courses);
if ($result_courses && mysqli_num_rows($result_courses) > 0) {
    while ($row = mysqli_fetch_assoc($result_courses)) {
        $name = strtolower($row['course_name']);
        $code = strtolower($row['course_code']);
        
        if (strpos($name, 'certificate') !== false || strpos($code, 'cert') !== false) {
            $certificate_courses[] = $row;
        } elseif (strpos($name, 'diploma') !== false || strpos($code, 'dip') !== false) {
            $diploma_courses[] = $row;
        } elseif (strpos($name, 'bachelor') !== false || strpos($name, 'degree') !== false || strpos($code, 'bsc') !== false || strpos($code, 'ba') !== false) {
            $bachelor_courses[] = $row;
        }
    }
}

// FETCH STUDENTS FOR SELECTED COURSE
$students = [];
$selected_course = null;

if ($selected_course_id > 0) {
    // Get course details
    $sql_course = "SELECT * FROM courses WHERE id = '$selected_course_id'";
    $result_course = mysqli_query($conn, $sql_course);
    if ($result_course && mysqli_num_rows($result_course) > 0) {
        $selected_course = mysqli_fetch_assoc($result_course);
    }
    
    // Get students enrolled in this course
    $sql_students = "SELECT u.id, u.full_name, u.email, u.username, u.photo, u.status,
                    s.registration_no, s.program_of_study, s.year_of_study, s.phone
                    FROM users u 
                    JOIN students s ON u.id = s.user_id 
                    WHERE u.role = 'student' AND s.course_id = '$selected_course_id'
                    ORDER BY u.full_name ASC";
    $result_students = mysqli_query($conn, $sql_students);
    if ($result_students && mysqli_num_rows($result_students) > 0) {
        while ($row = mysqli_fetch_assoc($result_students)) {
            $students[] = $row;
        }
    }
}

$total_students = count($students);
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Enrollment Report - Admin | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .report-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        /* LEVEL SECTIONS */
        .level-section {
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .level-header {
            background: #f8f9fa;
            padding: 15px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s ease;
        }
        
        .level-header:hover {
            background: #e8f4fd;
        }
        
        .level-header h3 {
            margin: 0;
            color: #0056b3;
            font-size: 18px;
            font-weight: 600;
        }
        
        .level-header h3 i {
            margin-right: 10px;
        }
        
        .level-header span {
            background: #0056b3;
            color: white;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .level-icon {
            font-size: 16px;
            color: #666;
            transition: transform 0.3s ease;
            margin-left: 15px;
        }
        
        .level-content {
            display: none;
            padding: 15px 20px;
            background: white;
            border-top: 1px solid #e0e0e0;
        }
        
        .level-content.show {
            display: block;
        }
        
        /* COURSE LIST - SIMPLE LIST */
        .course-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .course-list li {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s ease;
        }
        
        .course-list li:last-child {
            border-bottom: none;
        }
        
        .course-list li:hover {
            background: #f8f9fa;
        }
        
        .course-list a {
            text-decoration: none;
            display: block;
            color: #333;
        }
        
        .course-code {
            font-size: 13px;
            font-weight: bold;
            color: #0056b3;
            display: inline-block;
            width: 80px;
        }
        
        .course-name {
            font-size: 14px;
            color: #333;
        }
        
        /* Students Table */
        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .students-table th, .students-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .students-table th {
            background: #0056b3;
            color: white;
            font-weight: bold;
        }
        
        .students-table tr:hover {
            background: #f8f9fa;
        }
        
        .student-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .total-badge {
            background: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            display: inline-block;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 15px;
            color: #0056b3;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .selected-course-header {
            background: #e8f4fd;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .students-table {
                font-size: 12px;
                display: block;
                overflow-x: auto;
            }
            .course-code {
                width: 70px;
                font-size: 11px;
            }
            .course-name {
                font-size: 12px;
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
            <div class="panel-title">Course Enrollment Report</div>
            
            <div class="info-banner">
                <span><strong>Admin:</strong> <?php echo $admin_name; ?></span>
                <span><strong>Total Courses:</strong> <?php echo count($certificate_courses) + count($diploma_courses) + count($bachelor_courses); ?></span>
                <span><strong>System Status:</strong> Online</span>
            </div>

            <div class="report-container">
                
                <?php if($selected_course_id > 0 && $selected_course): ?>
                    <!-- BACK BUTTON -->
                    <a href="course_enrollment_report.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to All Courses</a>
                    
                    <!-- SELECTED COURSE HEADER -->
                    <div class="selected-course-header">
                        <h2 style="color: #0056b3; margin-bottom: 10px;"><?php echo htmlspecialchars($selected_course['course_name']); ?></h2>
                        <p><strong>Course Code:</strong> <?php echo $selected_course['course_code']; ?> | 
                           <strong>Duration:</strong> <?php echo $selected_course['duration']; ?> |
                           <strong>Department:</strong> <?php echo $selected_course['department']; ?></p>
                        <p><strong>Total Enrolled Students:</strong> <span class="total-badge"><?php echo $total_students; ?> Students</span></p>
                    </div>
                    
                    <!-- STUDENTS TABLE -->
                    <?php if(empty($students)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-graduate" style="font-size: 48px;"></i>
                            <p>No students enrolled in this course yet.</p>
                            <p><a href="admin_assign_course.php" style="color: #0056b3;">Assign students to this course</a></p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="students-table">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Registration No</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Year of Study</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($students as $student): ?>
                                    <tr>
                                        <td>
                                            <img src="uploads/profile_pics/<?php echo $student['photo'] ?? 'default.png'; ?>" class="student-photo" alt="Photo">
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($student['registration_no'] ?? 'N/A'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($student['year_of_study'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $student['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo ucfirst($student['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    
                    <!-- CERTIFICATE LEVEL -->
                    <div class="level-section">
                        <div class="level-header" onclick="toggleLevel('certificateLevel', 'certificateIcon')">
                            <h3><i class="fas fa-certificate"></i> Certificate Programs</h3>
                            <div>
                                <span><?php echo count($certificate_courses); ?> Courses</span>
                                <i class="fas fa-chevron-down level-icon" id="certificateIcon"></i>
                            </div>
                        </div>
                        <div id="certificateLevel" class="level-content">
                            <?php if(empty($certificate_courses)): ?>
                                <div class="empty-state" style="padding: 20px;">
                                    <p>No certificate courses available.</p>
                                </div>
                            <?php else: ?>
                                <ul class="course-list">
                                    <?php foreach($certificate_courses as $course): ?>
                                    <li>
                                        <a href="?course_id=<?php echo $course['id']; ?>">
                                            <span class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></span>
                                            <span class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></span>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- DIPLOMA LEVEL -->
                    <div class="level-section">
                        <div class="level-header" onclick="toggleLevel('diplomaLevel', 'diplomaIcon')">
                            <h3><i class="fas fa-graduation-cap"></i> Diploma Programs</h3>
                            <div>
                                <span><?php echo count($diploma_courses); ?> Courses</span>
                                <i class="fas fa-chevron-down level-icon" id="diplomaIcon"></i>
                            </div>
                        </div>
                        <div id="diplomaLevel" class="level-content">
                            <?php if(empty($diploma_courses)): ?>
                                <div class="empty-state" style="padding: 20px;">
                                    <p>No diploma courses available.</p>
                                </div>
                            <?php else: ?>
                                <ul class="course-list">
                                    <?php foreach($diploma_courses as $course): ?>
                                    <li>
                                        <a href="?course_id=<?php echo $course['id']; ?>">
                                            <span class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></span>
                                            <span class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></span>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- BACHELOR LEVEL -->
                    <div class="level-section">
                        <div class="level-header" onclick="toggleLevel('bachelorLevel', 'bachelorIcon')">
                            <h3><i class="fas fa-university"></i> Bachelor Programs</h3>
                            <div>
                                <span><?php echo count($bachelor_courses); ?> Courses</span>
                                <i class="fas fa-chevron-down level-icon" id="bachelorIcon"></i>
                            </div>
                        </div>
                        <div id="bachelorLevel" class="level-content">
                            <?php if(empty($bachelor_courses)): ?>
                                <div class="empty-state" style="padding: 20px;">
                                    <p>No bachelor courses available.</p>
                                </div>
                            <?php else: ?>
                                <ul class="course-list">
                                    <?php foreach($bachelor_courses as $course): ?>
                                    <li>
                                        <a href="?course_id=<?php echo $course['id']; ?>">
                                            <span class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></span>
                                            <span class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></span>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                <?php endif; ?>
                
            </div>
        </main>
    </div>

    <script>
        // Track currently open level
        let currentOpenLevel = null;
        let currentOpenIcon = null;
        
        function toggleLevel(levelId, iconId) {
            const content = document.getElementById(levelId);
            const icon = document.getElementById(iconId);
            
            // If there's an open level and it's not the current one, close it
            if (currentOpenLevel !== null && currentOpenLevel !== levelId) {
                const prevContent = document.getElementById(currentOpenLevel);
                const prevIcon = document.getElementById(currentOpenIcon);
                
                prevContent.classList.remove('show');
                if (prevIcon) {
                    prevIcon.style.transform = 'rotate(0deg)';
                }
            }
            
            // Toggle current level
            content.classList.toggle('show');
            
            // Update icon rotation
            if (content.classList.contains('show')) {
                icon.style.transform = 'rotate(180deg)';
                currentOpenLevel = levelId;
                currentOpenIcon = iconId;
            } else {
                icon.style.transform = 'rotate(0deg)';
                currentOpenLevel = null;
                currentOpenIcon = null;
            }
        }
    </script>

</body>
</html>