<?php
session_start();
require 'config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username' AND status='active' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if ($password == $user['password']) {
            
            // RECORD LOGIN HISTORY
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $sql_log = "INSERT INTO login_history (user_id, action, ip_address, user_agent) 
                        VALUES ('" . $user['id'] . "', 'login', '$ip_address', '$user_agent')";
            mysqli_query($conn, $sql_log);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];

            // ROLE REDIRECT
            if ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
                exit();
            } elseif ($user['role'] == 'lecturer') {
                header("Location: lecture_dashboard.php");
                exit();
            } elseif ($user['role'] == 'student') {
                header("Location: students_dashboard.php");
                exit();
            }
        } else {
            $error = "Wrong password!";
        }
    } else {
        $error = "User not found or inactive!";
    }
    
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ELMS Login</title>
<link rel="stylesheet" href="login.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>

<div class="login-container">
  <div class="login-box">

    <!-- LOGO -->
    <div class="logo">
      <img src="images/ELMS-logo.png" alt="ELMS Logo">
    </div>

    <h2>Welcome to ELMS</h2>
    <p>Login to access your dashboard</p>

    <!-- ERROR MESSAGE -->
    <?php if($error != "") { ?>
        <p style="color:red; font-size:13px;">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </p>
    <?php } ?>

    <!-- LOGIN FORM -->
    <form method="POST">

      <input type="text" name="username" placeholder="Enter Username" required>

      <input type="password" name="password" placeholder="Enter Password" required>

      <button type="submit">Login</button>

    </form>

    <!-- FORGOT PASSWORD -->
    <p class="forgot">
      <a href="#">Forgot Password?</a>
    </p>

    <!-- INSTRUCTIONS -->
    <div class="instructions">
      <h3>Instructions</h3>
      <p>
        Enter correct username and password.<br>
        System will redirect you based on your role.<br>
        Contact admin if you face issues.
      </p>
    </div>

  </div>
</div>

</body>
</html>