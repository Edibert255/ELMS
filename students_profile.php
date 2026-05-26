<?php
include 'config.php';
session_start();

// CHECK IF USER IS LOGGED IN
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// FETCH USER DATA FROM users TABLE
$sql_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = $conn->query($sql_user);

if ($result_user && $result_user->num_rows > 0) {
    $user = $result_user->fetch_assoc();
} else {
    header("Location: login.php");
    exit();
}

// IF ROLE IS STUDENT, FETCH DETAILS FROM students TABLE
if ($role == 'student') {
    $sql_student = "SELECT * FROM students WHERE user_id = '$user_id'";
    $result_student = $conn->query($sql_student);
    
    if ($result_student && $result_student->num_rows > 0) {
        $student = $result_student->fetch_assoc();
    } else {
        $student = []; // Empty array if no details found
    }
}

// ========== PHOTO WITH CACHE BUSTER ==========
$photo = $user['photo'] ?? 'default.png';
$photo_path = "uploads/profile_pics/" . $photo;
$photo_version = file_exists($photo_path) ? filemtime($photo_path) : time();
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Same styles as before */
        .profile-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #0056b3;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .profile-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .profile-title i {
            font-size: 32px;
            color: #0056b3;
        }
        
        .profile-title h2 {
            color: #333;
            font-size: 22px;
        }
        
        .btn-edit {
            background: #0056b3;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-edit:hover {
            background: #003d82;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .info-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #0056b3;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .section-header i {
            font-size: 20px;
            color: #0056b3;
        }
        
        .section-header h3 {
            color: #333;
            font-size: 16px;
            margin: 0;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 12px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .info-label {
            width: 140px;
            font-weight: bold;
            color: #555;
            flex-shrink: 0;
        }
        
        .info-value {
            color: #333;
            flex: 1;
        }
        
        .info-value.empty {
            color: #999;
            font-style: italic;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .role-badge.admin {
            background: #dc3545;
            color: white;
        }
        
        .role-badge.student {
            background: #28a745;
            color: white;
        }
        
        .role-badge.lecturer {
            background: #ffc107;
            color: #333;
        }
        
        .full-width {
            grid-column: span 2;
        }
        
        /* PROFILE PHOTO STYLES - ADDED */
        .profile-photo-wrapper {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .profile-photo {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #0056b3;
            background: #f8f9fa;
        }
        
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            .full-width {
                grid-column: span 1;
            }
            .info-label {
                width: 120px;
            }
        }
    </style>
</head>
<body>

<?php include 'sidebar_student.php'; ?>

    <div class="content-wrapper">
        <header class="top-header">
            <div class="welcome-top">
                <p>Mwanza Campus | <strong>Today:</strong> <?php echo date("d M, Y"); ?></p>
                <i class="fas fa-bell"></i>
            </div>
        </header>

        <main>
            <div class="panel-title">My Profile</div>
            
            <div class="info-banner">
                <span><strong>Role:</strong> <?php echo ucfirst($role); ?></span>
                <span><strong>System Status:</strong> Online</span>
                <span><strong>Logged in as:</strong> <?php echo $user['username']; ?></span>
            </div>

            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-title">
                        <i class="fas fa-id-card"></i>
                        <h2>Personal & Academic Information</h2>
                    </div>
                    <button class="btn-edit" onclick="window.location.href='edit_profile.php'">
                        <i class="fas fa-edit"></i> Edit My Info
                    </button>
                </div>

                <!-- ========== PROFILE PHOTO - ADDED WITH CACHE BUSTER ========== -->
                <div class="profile-photo-wrapper">
                    <img src="uploads/profile_pics/<?php echo $photo; ?>?v=<?php echo $photo_version; ?>" class="profile-photo" alt="Profile Photo">
                </div>

                <?php if($role == 'student'): ?>
                <!-- STUDENT PROFILE - Data kutoka students table -->
                <div class="profile-grid">
                    <!-- Personal Information Section -->
                    <div class="info-section">
                        <div class="section-header">
                            <i class="fas fa-user"></i>
                            <h3>Personal Information</h3>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Full Name:</div>
                            <div class="info-value"><?php echo ($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Gender:</div>
                            <div class="info-value"><?php echo $student['gender'] ?? 'Not Provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Date of Birth:</div>
                            <div class="info-value"><?php echo isset($student['date_of_birth']) ? date('d-m-Y', strtotime($student['date_of_birth'])) : 'Not Provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Nationality:</div>
                            <div class="info-value"><?php echo $student['nationality'] ?? 'Not Provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Marital Status:</div>
                            <div class="info-value"><?php echo $student['marital_status'] ?? 'Not Provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Current Address:</div>
                            <div class="info-value"><?php echo $student['current_address'] ?? 'Not Provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Permanent Address:</div>
                            <div class="info-value"><?php echo $student['permanent_address'] ?? 'Not Provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Phone:</div>
                            <div class="info-value"><?php echo $student['phone'] ?? 'Not Provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Email:</div>
                            <div class="info-value"><?php echo $user['email']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Kin Name:</div>
                            <div class="info-value"><?php echo $student['kin_name'] ?? 'Not Provided'; ?></div>
                        </div>
                    </div>

                    <!-- Academic Information Section -->
                    <div class="info-section">
                        <div class="section-header">
                            <i class="fas fa-graduation-cap"></i>
                            <h3>Academic Information</h3>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Reg No.:</div>
                            <div class="info-value"><?php echo $student['registration_no'] ?? 'Not Provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Admission No.:</div>
                            <div class="info-value"><?php echo $student['admission_no'] ?? ($student['registration_no'] ?? 'Not Provided'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Mode of Entry:</div>
                            <div class="info-value"><?php echo $student['mode_of_entry'] ?? 'Not Provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Study Level:</div>
                            <div class="info-value"><?php echo $student['study_level'] ?? 'Not Provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Department:</div>
                            <div class="info-value"><?php echo $student['department'] ?? 'Not Provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Program of Study:</div>
                            <div class="info-value"><?php echo $student['program_of_study'] ?? 'Not Provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Entry Year:</div>
                            <div class="info-value"><?php echo $student['entry_year'] ?? 'Not Provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Current Academic Year:</div>
                            <div class="info-value"><?php echo $student['current_academic_year'] ?? 'Not Provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Course Duration:</div>
                            <div class="info-value"><?php echo $student['course_duration'] ?? 'Not Provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Year of Study:</div>
                            <div class="info-value"><?php echo $student['year_of_study'] ?? 'Not Provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Class Stream:</div>
                            <div class="info-value"><?php echo $student['class_stream'] ?? 'Not Assigned'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Sponsorship:</div>
                            <div class="info-value"><?php echo $student['sponsorship'] ?? 'Not Provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Student Status:</div>
                            <div class="info-value">
                                <span style="color: <?php echo ($student['student_status'] ?? 'Continue') == 'Continue' ? '#28a745' : '#dc3545'; ?>">
                                    <?php echo $student['student_status'] ?? 'Continue'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                
                <?php else: ?>
                <!-- ADMIN/LECTURER PROFILE - Data kutoka users table tu -->
                <div class="profile-grid">
                    <div class="info-section full-width">
                        <div class="section-header">
                            <i class="fas fa-user-circle"></i>
                            <h3>Account Information</h3>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Full Name:</div>
                            <div class="info-value"><?php echo $user['full_name']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Username:</div>
                            <div class="info-value"><?php echo $user['username']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Email:</div>
                            <div class="info-value"><?php echo $user['email']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Role:</div>
                            <div class="info-value">
                                <span class="role-badge <?php echo $role; ?>">
                                    <?php echo ucfirst($role); ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Status:</div>
                            <div class="info-value"><?php echo ucfirst($user['status'] ?? 'Active'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Account Created:</div>
                            <div class="info-value"><?php echo isset($user['created_at']) ? date('d M, Y h:i A', strtotime($user['created_at'])) : 'Not Available'; ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function toggleSub(id) {
            const menu = document.getElementById(id);
            const isActive = menu.classList.contains('active');
            
            document.querySelectorAll('.submenu').forEach(s => {
                if(s.id !== id) s.classList.remove('active');
            });
            
            if (menu) {
                menu.classList.toggle('active');
            }
        }
    </script>

</body>
</html>