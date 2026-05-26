<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'];
$current_semester = "Semester II";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $success = "Semester updated to " . $current_semester;
}

$photo = 'default.png';
$first_name = explode(' ', $admin_name)[0];
$last_name = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semester Settings - Admin</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <aside><?php include 'admin_sidebar_common.php'; ?></aside>
    <div class="content-wrapper">
        <header class="top-header"><div class="welcome-top"><p>Mwanza Campus | Today: <?php echo date("d M, Y"); ?></p></div></header>
        <main>
            <div class="panel-title">Semester Settings</div>
            <div class="info-banner"><span>Current Semester: <strong><?php echo $current_semester; ?></strong></span></div>
            <div style="background:white; border-radius:12px; padding:25px; margin-top:20px;">
                <?php if($success): ?><div style="background:#d4edda; padding:12px; border-radius:8px; margin-bottom:20px;"><?php echo $success; ?></div><?php endif; ?>
                <form method="POST"><div class="form-group"><label>Semester</label><select name="semester" style="width:300px; padding:10px; border:1px solid #ddd; border-radius:6px;"><option <?php echo $current_semester=='Semester I'?'selected':''; ?>>Semester I</option><option <?php echo $current_semester=='Semester II'?'selected':''; ?>>Semester II</option></select></div><button type="submit" style="margin-top:15px; background:#0056b3; color:white; padding:10px 20px; border:none; border-radius:6px;">Save Changes</button></form>
            </div>
        </main>
    </div>
</body>
</html>