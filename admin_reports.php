<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'];

// Fetch statistics
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='student'"))['c'];
$total_lecturers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='lecturer'"))['c'];
$total_courses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM courses"))['c'];
$total_modules = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM course_modules"))['c'];
$total_assignments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM assignments"))['c'];
$total_submissions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM assignment_submissions"))['c'];

$photo = 'default.png';
$first_name = explode(' ', $admin_name)[0];
$last_name = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .reports-container { background: white; border-radius: 12px; padding: 25px; margin-top: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #0056b3, #003d82); color: white; padding: 20px; border-radius: 10px; text-align: center; }
        .stat-card h3 { font-size: 32px; margin-bottom: 5px; }
        .report-buttons { display: flex; gap: 15px; flex-wrap: wrap; }
        .btn-report { background: #28a745; color: white; padding: 12px 25px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; }
        .btn-report:hover { background: #218838; }
    </style>
</head>
<body>
    <aside><?php include 'admin_sidebar_common.php'; ?></aside>
    <div class="content-wrapper">
        <header class="top-header"><div class="welcome-top"><p>Mwanza Campus | Today: <?php echo date("d M, Y"); ?></p></div></header>
        <main>
            <div class="panel-title">System Reports</div>
            <div class="reports-container">
                <div class="stats-grid">
                    <div class="stat-card"><h3><?php echo $total_students; ?></h3><p>Total Students</p></div>
                    <div class="stat-card"><h3><?php echo $total_lecturers; ?></h3><p>Total Lecturers</p></div>
                    <div class="stat-card"><h3><?php echo $total_courses; ?></h3><p>Total Courses</p></div>
                    <div class="stat-card"><h3><?php echo $total_modules; ?></h3><p>Total Modules</p></div>
                    <div class="stat-card"><h3><?php echo $total_assignments; ?></h3><p>Assignments</p></div>
                    <div class="stat-card"><h3><?php echo $total_submissions; ?></h3><p>Submissions</p></div>
                </div>
                <div class="report-buttons">
                    <a href="export_students.php" class="btn-report"><i class="fas fa-file-excel"></i> Export Students</a>
                    <a href="export_assignments.php" class="btn-report"><i class="fas fa-file-pdf"></i> Export Assignments</a>
                    <a href="print_reports.php" class="btn-report" onclick="window.print()"><i class="fas fa-print"></i> Print Report</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>