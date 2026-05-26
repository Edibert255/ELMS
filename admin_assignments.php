<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'];
$assignments = [];
$sql = "SELECT a.*, u.full_name as lecturer_name FROM assignments a LEFT JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $assignments[] = $row;
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
    <title>All Assignments - Admin</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .table-container { background: white; border-radius: 12px; padding: 25px; margin-top: 20px; overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .data-table th { background: #0056b3; color: white; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 15px; font-size: 11px; background: #28a745; color: white; }
        @media (max-width: 768px) { .data-table { font-size: 12px; } }
    </style>
</head>
<body><?php include 'sidebar_admin.php'; ?>
    <div class="content-wrapper">
        <header class="top-header"><div class="welcome-top"><p>Mwanza Campus | Today: <?php echo date("d M, Y"); ?></p></div></header>
        <main>
            <div class="panel-title">All Assignments</div>
            <div class="table-container">
                <table class="data-table"><thead><tr><th>Title</th><th>Course</th><th>Due Date</th><th>Lecturer</th><th>Status</th></tr></thead>
                <tbody><?php foreach($assignments as $a): ?><tr><td><?php echo $a['title']; ?></td><td><?php echo $a['course_name']; ?></td><td><?php echo date('d M Y', strtotime($a['due_date'])); ?></td><td><?php echo $a['lecturer_name'] ?? 'Unknown'; ?></td><td><span class="badge">Active</span></td></tr><?php endforeach; ?></tbody></table>
            </div>
        </main>
    </div>
</body>
</html>