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
$success = "";
$error = "";

// FETCH CURRENT USER DATA
$sql_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $sql_user);
$user = mysqli_fetch_assoc($result_user);

// FETCH STUDENT DATA (if student)
$student = [];
if ($role == 'student') {
    $sql_student = "SELECT * FROM students WHERE user_id = '$user_id'";
    $result_student = mysqli_query($conn, $sql_student);
    if ($result_student && mysqli_num_rows($result_student) > 0) {
        $student = mysqli_fetch_assoc($result_student);
    }
}

// PROCESS PASSWORD CHANGE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // VERIFY CURRENT PASSWORD
    if ($current_password != $user['password']) {
        $error = "Current password is incorrect!";
    } 
    elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long!";
    }
    elseif ($new_password != $confirm_password) {
        $error = "New password and confirm password do not match!";
    }
    else {
        // UPDATE PASSWORD
        $sql_update = "UPDATE users SET password = '$new_password' WHERE id = '$user_id'";
        
        if (mysqli_query($conn, $sql_update)) {
            $success = "Password changed successfully!";
            
            // LOG THE PASSWORD CHANGE
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $sql_log = "INSERT INTO login_history (user_id, action, ip_address, user_agent) 
                        VALUES ('$user_id', 'password_changed', '$ip_address', '$user_agent')";
            mysqli_query($conn, $sql_log);
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .password-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            max-width: 500px;
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
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            border-color: #0056b3;
            outline: none;
        }
        
        .password-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .btn-save {
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        
        .btn-save:hover {
            background: #218838;
        }
        
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .security-tips {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .security-tips h4 {
            color: #0056b3;
            margin-bottom: 10px;
        }
        
        .security-tips ul {
            margin-left: 20px;
            color: #555;
        }
        
        .security-tips li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
   <?php include 'sidebar_student.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="content-wrapper">
        <header class="top-header">
            <div class="welcome-top">
                <p>Mwanza Campus | <strong>Today:</strong> <?php echo date("d M, Y"); ?></p>
                <i class="fas fa-bell"></i>
            </div>
        </header>

        <main>
            <div class="panel-title">Change Password</div>
            
            <div class="info-banner">
                <span><strong>Role:</strong> <?php echo ucfirst($role); ?></span>
                <span><strong>System Status:</strong> Online</span>
                <span><strong>User:</strong> <?php echo $student['first_name'] ?? $user['full_name']; ?></span>
            </div>

            <div class="password-container">
                <?php if($success): ?>
                    <div class="success-msg">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-key"></i> New Password</label>
                        <input type="password" name="new_password" required>
                        <div class="password-hint">Password must be at least 6 characters long</div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-check-double"></i> Confirm New Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Change Password
                    </button>
                </form>
                
                <div class="security-tips">
                    <h4><i class="fas fa-shield-alt"></i> Security Tips</h4>
                    <ul>
                        <li>Use a strong password with letters, numbers, and special characters</li>
                        <li>Don't share your password with anyone</li>
                        <li>Change your password regularly (every 3 months)</li>
                        <li>Avoid using the same password for multiple accounts</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSub(id) {
            const menu = document.getElementById(id);
            if (menu) {
                menu.classList.toggle('active');
            }
        }
    </script>
</body>
</html>