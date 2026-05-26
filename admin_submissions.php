<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'];
$submissions = [];
$sql = "SELECT s.*, u.full_name as student_name, a.title as assignment_title 
        FROM assignment_submissions s
        JOIN users u ON s.student_id = u.id
        JOIN assignments a ON s.assignment_id = a.id
        ORDER BY s.submitted_at DESC";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $submissions[] = $row;
    }
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
    <title>All Submissions - Admin</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .table-container { background: white; border-radius: 12px; padding: 25px; margin-top: 20px; overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .data-table th { background: #0056b3; color: white; }
        .status-pending { background: #ffc107; color: #333; padding: 3px 10px; border-radius: 15px; font-size: 11px; }
        .status-graded { background: #28a745; color: white; padding: 3px 10px; border-radius: 15px; font-size: 11px; }
    </style>
</head>
<body>
    <aside><?php include 'admin_sidebar_common.php'; ?></aside>
    <div class="content-wrapper">
        <header class="top-header"><div class="welcome-top"><p>Mwanza Campus | Today: <?php echo date("d M, Y"); ?></p></div></header>
        <main>
            <div class="panel-title">All Submissions</div>
            <div class="table-container">
                <table class="data-table"><thead><tr><th>Student</th><th>Assignment</th><th>Submitted</th><th>Marks</th><th>Status</th></tr></thead>
                <tbody><?php foreach($submissions as $sub): ?><tr><td><?php echo $sub['student_name']; ?></td><td><?php echo $sub['assignment_title']; ?></td><td><?php echo date('d M Y', strtotime($sub['submitted_at'])); ?></td><td><?php echo $sub['marks_obtained'] ?? '-'; ?></td><td><span class="<?php echo $sub['marks_obtained'] ? 'status-graded' : 'status-pending'; ?>"><?php echo $sub['marks_obtained'] ? 'Graded' : 'Pending'; ?></span></td></tr><?php endforeach; ?></tbody></table>
            </div>
        </main>
    </div>
</body>
</html>