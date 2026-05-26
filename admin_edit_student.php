<?php
include 'config.php';
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'];
$success = "";
$error = "";

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($student_id == 0) { 
    header("Location: admin_students.php"); 
    exit(); 
}

// FETCH STUDENT DATA
$sql = "SELECT u.id, u.full_name, u.email, u.username, u.password, u.status, u.photo,
        s.registration_no, s.program_of_study, s.year_of_study, s.phone, s.gender, s.date_of_birth,
        s.first_name, s.last_name
        FROM users u 
        LEFT JOIN students s ON u.id = s.user_id 
        WHERE u.id = '$student_id' AND u.role = 'student'";
$result = mysqli_query($conn, $sql);
$student = mysqli_fetch_assoc($result);

if (!$student) { 
    header("Location: admin_students.php"); 
    exit(); 
}

// UPDATE STUDENT INFO (including password)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Update basic info
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    $sql1 = "UPDATE users SET full_name = '$full_name', email = '$email' WHERE id = '$student_id'";
    $result1 = mysqli_query($conn, $sql1);
    
    // Update password if provided
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    $password_updated = false;
    
    if (!empty($new_password)) {
        $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
        if (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters!";
        } elseif ($new_password != $confirm_password) {
            $error = "Passwords do not match!";
        } else {
            $sql_pass = "UPDATE users SET password = '$new_password' WHERE id = '$student_id'";
            if (mysqli_query($conn, $sql_pass)) {
                $password_updated = true;
            } else {
                $error = "Password update failed!";
            }
        }
    }
    
    // Update student details
    $registration_no = mysqli_real_escape_string($conn, $_POST['registration_no']);
    $program_of_study = mysqli_real_escape_string($conn, $_POST['program_of_study']);
    $year_of_study = mysqli_real_escape_string($conn, $_POST['year_of_study']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $date_of_birth = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
    $first_name_student = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name_student = mysqli_real_escape_string($conn, $_POST['last_name']);
    
    // Check if student exists in students table
    $check_student = mysqli_query($conn, "SELECT id FROM students WHERE user_id = '$student_id'");
    
    if (mysqli_num_rows($check_student) > 0) {
        $sql2 = "UPDATE students SET 
                  first_name = '$first_name_student',
                  last_name = '$last_name_student',
                  registration_no = '$registration_no',
                  program_of_study = '$program_of_study',
                  year_of_study = '$year_of_study',
                  phone = '$phone',
                  gender = '$gender',
                  date_of_birth = '$date_of_birth'
                  WHERE user_id = '$student_id'";
    } else {
        $sql2 = "INSERT INTO students (user_id, first_name, last_name, registration_no, program_of_study, year_of_study, phone, gender, date_of_birth) 
                 VALUES ('$student_id', '$first_name_student', '$last_name_student', '$registration_no', '$program_of_study', '$year_of_study', '$phone', '$gender', '$date_of_birth')";
    }
    $result2 = mysqli_query($conn, $sql2);
    
    if ($result1 && $result2) {
        if ($password_updated) {
            $success = "Student information and password updated successfully!";
        } else {
            $success = "Student information updated successfully!";
        }
        // Refresh student data
        $student['full_name'] = $full_name;
        $student['email'] = $email;
        $student['registration_no'] = $registration_no;
        $student['program_of_study'] = $program_of_study;
        $student['year_of_study'] = $year_of_study;
        $student['phone'] = $phone;
        $student['gender'] = $gender;
        $student['date_of_birth'] = $date_of_birth;
        $student['first_name'] = $first_name_student;
        $student['last_name'] = $last_name_student;
    } elseif (!$result1) {
        $error = "User update failed: " . mysqli_error($conn);
    } else {
        $error = "Student details update failed: " . mysqli_error($conn);
    }
}

// FETCH ADMIN PHOTO FOR SIDEBAR
$sql_admin = "SELECT * FROM users WHERE id = '{$_SESSION['user_id']}'";
$result_admin = mysqli_query($conn, $sql_admin);
$admin_data = mysqli_fetch_assoc($result_admin);
$admin_photo = $admin_data['photo'] ?? 'default.png';
$first_name_admin = explode(' ', $admin_name)[0];
$last_name_admin = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';

// Photo with cache buster
$photo = $student['photo'] ?? 'default.png';
$photo_path = "uploads/profile_pics/" . $photo;
$photo_version = file_exists($photo_path) ? filemtime($photo_path) : time();
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - Admin | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-top: 20px;
            max-width: 800px;
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
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin: 25px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #ddd;
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
            width: 100%;
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
            margin-top: 10px;
            text-align: center;
            width: 100%;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .photo-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 2px solid #0056b3;
        }
        
        .btn-photo {
            background: #17a2b8;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
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
        
        .readonly-field {
            background: #f5f5f5;
            cursor: not-allowed;
        }
        
        .password-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            border: 1px solid #ddd;
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
            <div class="panel-title">Edit Student Information</div>
            
            <div class="info-banner">
                <span><strong>Admin:</strong> <?php echo $admin_name; ?></span>
                <span><strong>Editing:</strong> <?php echo htmlspecialchars($student['full_name']); ?></span>
            </div>

            <div class="form-container">
                <?php if($success): ?>
                    <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Profile Photo Section -->
                <div style="text-align: center; margin-bottom: 20px;">
                    <img src="uploads/profile_pics/<?php echo $photo; ?>?v=<?php echo $photo_version; ?>" class="photo-preview" alt="Profile Photo">
                    <br>
                    <a href="admin_upload_photo.php?user_id=<?php echo $student_id; ?>&role=student" class="btn-photo"><i class="fas fa-camera"></i> Change Photo</a>
                </div>

                <form method="POST">
                    <h3 class="form-title"><i class="fas fa-user-edit"></i> Student Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name <span class="required">*</span></label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($student['username']); ?>" class="readonly-field" readonly disabled>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <input type="text" value="<?php echo ucfirst($student['status']); ?>" class="readonly-field" readonly disabled>
                        </div>
                    </div>

                    <h3 class="section-title"><i class="fas fa-graduation-cap"></i> Academic Details</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($student['first_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($student['last_name'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Registration Number</label>
                            <input type="text" name="registration_no" value="<?php echo htmlspecialchars($student['registration_no'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Program of Study</label>
                            <input type="text" name="program_of_study" value="<?php echo htmlspecialchars($student['program_of_study'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Year of Study</label>
                            <select name="year_of_study">
                                <option value="">Select Year</option>
                                <option value="1st Year" <?php echo ($student['year_of_study'] == '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                                <option value="2nd Year" <?php echo ($student['year_of_study'] == '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                                <option value="3rd Year" <?php echo ($student['year_of_study'] == '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($student['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($student['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($student['date_of_birth'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Change Password Section (Inside same form) -->
                    <div class="password-section">
                        <h3 class="section-title"><i class="fas fa-key"></i> Change Password (Optional)</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" placeholder="Enter new password">
                                <div class="help-text">Leave blank to keep current password. Minimum 6 characters if changing.</div>
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <input type="password" name="confirm_password" placeholder="Confirm new password">
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 25px;">
                        <button type="submit" name="update_student" class="btn-submit"><i class="fas fa-save"></i> Update Student</button>
                        <a href="admin_students.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
                    </div>
                </form>
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