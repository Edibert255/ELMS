<?php
session_start();
require 'config.php';

// RECORD LOGOUT HISTORY
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $sql_log = "INSERT INTO login_history (user_id, action, ip_address, user_agent) 
                VALUES ('$user_id', 'logout', '$ip_address', '$user_agent')";
    mysqli_query($conn, $sql_log);
}

// DESTROY SESSION
session_destroy();

// REDIRECT TO LOGIN
header("Location: login.php");
exit();
?>