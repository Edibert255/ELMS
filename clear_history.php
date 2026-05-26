<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "DELETE FROM login_history WHERE user_id = '$user_id' AND action = 'login'";
mysqli_query($conn, $sql);

header("Location: login_history.php?msg=cleared");
exit();
?>