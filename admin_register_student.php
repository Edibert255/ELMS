<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'];
$success = "";
$error = "";

// FETCH ADMIN PHOTO
$sql_admin = "SELECT * FROM users WHERE id = '{$_SESSION['user_id']}'";
$result_admin = mysqli_query($conn, $sql_admin);
$admin_data = mysqli_fetch_assoc($result_admin);
$admin_photo = $admin_data['photo'] ?? 'default.png';
$first_name_admin = explode(' ', $admin_name)[0];
$last_name_admin = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic user info
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    // Student details
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $registration_no = mysqli_real_escape_string($conn, $_POST['registration_no']);
    $program_of_study = mysqli_real_escape_string($conn, $_POST['program_of_study']);
    $year_of_study = mysqli_real_escape_string($conn, $_POST['year_of_study']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $date_of_birth = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
    $current_address = mysqli_real_escape_string($conn, $_POST['current_address']);
    $permanent_address = mysqli_real_escape_string($conn, $_POST['permanent_address']);
    $nationality = mysqli_real_escape_string($conn, $_POST['nationality']);
    $marital_status = mysqli_real_escape_string($conn, $_POST['marital_status']);
    $kin_name = mysqli_real_escape_string($conn, $_POST['kin_name']);
    $sponsorship = mysqli_real_escape_string($conn, $_POST['sponsorship']);
    $admission_no = mysqli_real_escape_string($conn, $_POST['admission_no']);
    $mode_of_entry = mysqli_real_escape_string($conn, $_POST['mode_of_entry']);
    $study_level = mysqli_real_escape_string($conn, $_POST['study_level']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $entry_year = mysqli_real_escape_string($conn, $_POST['entry_year']);
    $current_academic_year = mysqli_real_escape_string($conn, $_POST['current_academic_year']);
    $course_duration = mysqli_real_escape_string($conn, $_POST['course_duration']);
    $class_stream = mysqli_real_escape_string($conn, $_POST['class_stream']);
    $student_status = mysqli_real_escape_string($conn, $_POST['student_status']);
    
    // Check if username or email already exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' OR email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Username or email already exists!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        // Insert into users table
        $sql1 = "INSERT INTO users (full_name, username, email, password, role, status) 
                 VALUES ('$full_name', '$username', '$email', '$password', 'student', 'active')";
        
        if (mysqli_query($conn, $sql1)) {
            $user_id = mysqli_insert_id($conn);
            
            // Insert into students table with all details
            $sql2 = "INSERT INTO students (
                user_id, first_name, last_name, registration_no, program_of_study, year_of_study,
                phone, gender, date_of_birth, current_address, permanent_address, nationality,
                marital_status, kin_name, sponsorship, admission_no, mode_of_entry, study_level,
                department, entry_year, current_academic_year, course_duration, class_stream, student_status
            ) VALUES (
                '$user_id', '$first_name', '$last_name', '$registration_no', '$program_of_study', '$year_of_study',
                '$phone', '$gender', '$date_of_birth', '$current_address', '$permanent_address', '$nationality',
                '$marital_status', '$kin_name', '$sponsorship', '$admission_no', '$mode_of_entry', '$study_level',
                '$department', '$entry_year', '$current_academic_year', '$course_duration', '$class_stream', '$student_status'
            )";
            
            if (mysqli_query($conn, $sql2)) {
                $success = "Student registered successfully!";
                $_POST = array();
            } else {
                $error = "Student details error: " . mysqli_error($conn);
                // Rollback user insertion
                mysqli_query($conn, "DELETE FROM users WHERE id = '$user_id'");
            }
        } else {
            $error = "User creation failed: " . mysqli_error($conn);
        }
    }
}

// Get counts
$result_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='student'");
$total_students = mysqli_fetch_assoc($result_count)['total'];
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Student - Admin | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-top: 20px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .form-title {
            color: #0056b3;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0056b3;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }
        
        .form-group label .required {
            color: #dc3545;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin: 25px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #0056b3;
            color: #0056b3;
        }
        
        .btn-submit {
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        
        .btn-submit:hover {
            background: #218838;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
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
        
        .info-banner-small {
            background: #e8f4fd;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
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
            <div class="panel-title">Register New Student</div>
            
            <div class="info-banner">
                <span><strong>Admin:</strong> <?php echo $admin_name; ?></span>
                <span><strong>Total Students:</strong> <?php echo $total_students; ?></span>
                <span><strong>System Status:</strong> Online</span>
            </div>

            <div class="form-container">
                <?php if($success): ?>
                    <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?> 
                        <a href="admin_students.php" style="color:#155724;">View all students →</a>
                    </div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <div class="info-banner-small">
                    <i class="fas fa-info-circle"></i> Fill all required fields marked with <span class="required">*</span>
                </div>

                <form method="POST">
                    <h3 class="form-title"><i class="fas fa-user-plus"></i> Account Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name <span class="required">*</span></label>
                            <input type="text" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label>Username <span class="required">*</span></label>
                            <input type="text" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password <span class="required">*</span></label>
                            <input type="password" name="password" placeholder="Minimum 6 characters" required>
                        </div>
                    </div>

                    <h3 class="form-title"><i class="fas fa-id-card"></i> Personal Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name">
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name">
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth">
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" placeholder="e.g., 0752xxxxxx">
                        </div>
                        <div class="form-group">
                            <label>Nationality</label>
                            <input type="text" name="nationality" placeholder="e.g., Tanzanian">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Marital Status</label>
                            <select name="marital_status">
                                <option value="">Select Status</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Current Address</label>
                            <input type="text" name="current_address" placeholder="e.g., Mwanza">
                        </div>
                        <div class="form-group">
                            <label>Permanent Address</label>
                            <input type="text" name="permanent_address" placeholder="e.g., Kagera">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Next of Kin</label>
                            <input type="text" name="kin_name" placeholder="Guardian/Parent name">
                        </div>
                        <div class="form-group">
                            <label>Sponsorship</label>
                            <select name="sponsorship">
                                <option value="">Select Sponsorship</option>
                                <option value="Parent Support">Parent Support</option>
                                <option value="Self Sponsored">Self Sponsored</option>
                                <option value="Government Loan">Government Loan</option>
                                <option value="Scholarship">Scholarship</option>
                            </select>
                        </div>
                    </div>

                    <h3 class="form-title"><i class="fas fa-graduation-cap"></i> Academic Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Registration Number <span class="required">*</span></label>
                            <input type="text" name="registration_no" placeholder="e.g., 02.0274.01.03.2024" required>
                        </div>
                        <div class="form-group">
                            <label>Admission Number</label>
                            <input type="text" name="admission_no" placeholder="Same as registration number">
                        </div>
                        <div class="form-group">
                            <label>Mode of Entry</label>
                            <select name="mode_of_entry">
                                <option value="">Select Mode</option>
                                <option value="Equivalent">Equivalent</option>
                                <option value="Direct">Direct</option>
                                <option value="Certificate">Certificate</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Study Level</label>
                            <select name="study_level">
                                <option value="">Select Level</option>
                                <option value="Certificate">Certificate</option>
                                <option value="Diploma">Diploma</option>
                                <option value="Degree">Degree</option>
                                <option value="Postgraduate">Postgraduate</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" name="department" placeholder="e.g., Information and Communication Technology">
                        </div>
                        <div class="form-group">
                            <label>Program of Study</label>
                            <input type="text" name="program_of_study" placeholder="e.g., ORDINARY DIPLOMA IN INFORMATION TECHNOLOGY">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Entry Year</label>
                            <input type="text" name="entry_year" placeholder="e.g., 2024/2025">
                        </div>
                        <div class="form-group">
                            <label>Current Academic Year</label>
                            <input type="text" name="current_academic_year" placeholder="e.g., 2025/2026">
                        </div>
                        <div class="form-group">
                            <label>Course Duration</label>
                            <input type="text" name="course_duration" placeholder="e.g., 2 Years">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Year of Study</label>
                            <select name="year_of_study">
                                <option value="">Select Year</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Class Stream</label>
                            <input type="text" name="class_stream" placeholder="e.g., BIT 1">
                        </div>
                        <div class="form-group">
                            <label>Student Status</label>
                            <select name="student_status">
                                <option value="Continue">Continue</option>
                                <option value="Graduated">Graduated</option>
                                <option value="Dropped">Dropped</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Register Student</button>
                        <a href="admin_students.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

</body>
</html>