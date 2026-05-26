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
$selected_role = isset($_GET['role']) ? $_GET['role'] : 'student';

// FETCH ADMIN PHOTO
$sql_admin = "SELECT * FROM users WHERE id = '{$_SESSION['user_id']}'";
$result_admin = mysqli_query($conn, $sql_admin);
$admin_data = mysqli_fetch_assoc($result_admin);
$admin_photo = $admin_data['photo'] ?? 'default.png';
$first_name = explode(' ', $admin_name)[0];
$last_name = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' OR email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Username or email already exists!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        $sql = "INSERT INTO users (full_name, username, email, password, role, status) 
                VALUES ('$full_name', '$username', '$email', '$password', '$role', 'active')";
        
        if (mysqli_query($conn, $sql)) {
            $user_id = mysqli_insert_id($conn);
            
            if ($role == 'student') {
                $first_name_student = mysqli_real_escape_string($conn, $_POST['first_name']);
                $last_name_student = mysqli_real_escape_string($conn, $_POST['last_name']);
                $reg_no = mysqli_real_escape_string($conn, $_POST['registration_no']);
                $program = mysqli_real_escape_string($conn, $_POST['program_of_study']);
                $year = mysqli_real_escape_string($conn, $_POST['year_of_study']);
                $phone = mysqli_real_escape_string($conn, $_POST['phone']);
                $gender = mysqli_real_escape_string($conn, $_POST['gender']);
                $dob = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
                
                $sql2 = "INSERT INTO students (user_id, first_name, last_name, registration_no, program_of_study, year_of_study, phone, gender, date_of_birth) 
                         VALUES ('$user_id', '$first_name_student', '$last_name_student', '$reg_no', '$program', '$year', '$phone', '$gender', '$dob')";
                
                if (mysqli_query($conn, $sql2)) {
                    $success = "Student added successfully!";
                    $_POST = array();
                } else {
                    $error = "Student details error: " . mysqli_error($conn);
                }
            } else {
                $success = "User added successfully!";
                $_POST = array();
            }
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}

$result_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$total_users = mysqli_fetch_assoc($result_count)['total'];
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User - Admin | CBE ELMS</title>
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
        .form-title { color: #0056b3; margin-bottom: 25px; padding-bottom: 10px; border-bottom: 2px solid #0056b3; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
        .form-group label .required { color: #dc3545; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .help-text { font-size: 12px; color: #666; margin-top: 5px; }
        .section-title { font-size: 16px; font-weight: bold; margin: 25px 0 15px 0; padding-bottom: 8px; border-bottom: 1px solid #ddd; color: #0056b3; }
        .btn-submit { background: #28a745; color: white; padding: 12px 30px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
        .btn-cancel { background: #6c757d; color: white; padding: 12px 30px; border: none; border-radius: 6px; text-decoration: none; display: inline-block; margin-left: 10px; }
        .role-tabs { display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid #e0e0e0; }
        .role-tab { padding: 10px 25px; background: none; border: none; font-size: 15px; cursor: pointer; text-decoration: none; color: #666; }
        .role-tab.active { color: #0056b3; border-bottom: 3px solid #0056b3; }
        .student-fields { display: none; }
        .student-fields.show { display: block; }
        .success-msg, .error-msg { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .success-msg { background: #d4edda; color: #155724; }
        .error-msg { background: #f8d7da; color: #721c24; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
    </style>
    <script>
        function showRoleFields() {
            var role = document.getElementById('role').value;
            var studentFields = document.getElementById('studentFields');
            if (role === 'student') { studentFields.classList.add('show'); } 
            else { studentFields.classList.remove('show'); }
        }
    </script>
</head>
<body>

   <?php include 'sidebar_admin.php'; ?>

    <div class="content-wrapper">
        <header class="top-header"><div class="welcome-top"><p>Mwanza Campus | Today: <?php echo date("d M, Y"); ?></p></div></header>
        <main>
            <div class="panel-title">Add New User</div>
            <div class="info-banner"><span><strong>Admin:</strong> <?php echo $admin_name; ?></span><span><strong>Total Users:</strong> <?php echo $total_users; ?></span></div>
            <div class="form-container">
                <?php if($success): ?><div class="success-msg"><?php echo $success; ?> <a href="admin_<?php echo $selected_role; ?>s.php" style="color:#155724;">View all →</a></div><?php endif; ?>
                <?php if($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>

                <div class="role-tabs">
                    <a href="?role=student" class="role-tab <?php echo $selected_role == 'student' ? 'active' : ''; ?>"><i class="fas fa-user-graduate"></i> Student</a>
                    <a href="?role=lecturer" class="role-tab <?php echo $selected_role == 'lecturer' ? 'active' : ''; ?>"><i class="fas fa-chalkboard-teacher"></i> Lecturer</a>
                    <a href="?role=admin" class="role-tab <?php echo $selected_role == 'admin' ? 'active' : ''; ?>"><i class="fas fa-user-cog"></i> Admin</a>
                </div>

                <form method="POST">
                    <input type="hidden" name="role" id="role" value="<?php echo $selected_role; ?>">
                    <h3 class="form-title"><i class="fas fa-user-plus"></i> <?php echo ucfirst($selected_role); ?> Information</h3>
                    <div class="form-row">
                        <div class="form-group"><label>Full Name <span class="required">*</span></label><input type="text" name="full_name" required></div>
                        <div class="form-group"><label>Username <span class="required">*</span></label><input type="text" name="username" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Email <span class="required">*</span></label><input type="email" name="email" required></div>
                        <div class="form-group"><label>Password <span class="required">*</span></label><input type="password" name="password" placeholder="Enter password" required><div class="help-text">Minimum 6 characters</div></div>
                    </div>

                    <div id="studentFields" class="student-fields <?php echo $selected_role == 'student' ? 'show' : ''; ?>">
                        <h3 class="section-title"><i class="fas fa-graduation-cap"></i> Student Details</h3>
                        <div class="form-row">
                            <div class="form-group"><label>First Name</label><input type="text" name="first_name"></div>
                            <div class="form-group"><label>Last Name</label><input type="text" name="last_name"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Registration No</label><input type="text" name="registration_no" placeholder="e.g., 2024-0001"></div>
                            <div class="form-group"><label>Program of Study</label><input type="text" name="program_of_study" placeholder="e.g., Diploma in IT"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Year of Study</label><select name="year_of_study"><option>1st Year</option><option>2nd Year</option><option>3rd Year</option></select></div>
                            <div class="form-group"><label>Gender</label><select name="gender"><option>Male</option><option>Female</option></select></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Phone</label><input type="text" name="phone" placeholder="e.g., 0752xxxxxx"></div>
                            <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth"></div>
                        </div>
                    </div>
                    
                    <div><button type="submit" class="btn-submit"><i class="fas fa-save"></i> Add <?php echo ucfirst($selected_role); ?></button><a href="admin_dashboard.php" class="btn-cancel">Cancel</a></div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function toggleSub(id) { const menu = document.getElementById(id); if(menu) { const isActive = menu.classList.contains('active'); document.querySelectorAll('.submenu').forEach(s => s.classList.remove('active')); if (!isActive) menu.classList.add('active'); } }
        document.addEventListener('DOMContentLoaded', function() { showRoleFields(); document.getElementById('role').addEventListener('change', showRoleFields); });
    </script>
</body>
</html>