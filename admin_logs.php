<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'];
$logs = [];
$sql = "SELECT l.*, u.full_name as user_name FROM login_history l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.login_time DESC LIMIT 100";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
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
    <title>System Logs - Admin</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .table-container { background: white; border-radius: 12px; padding: 25px; margin-top: 20px; overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        .data-table th { background: #0056b3; color: white; }
        .action-login { background: #28a745; color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px; display: inline-block; }
        .action-logout { background: #ffc107; color: #333; padding: 2px 8px; border-radius: 10px; font-size: 10px; display: inline-block; }
        .action-password { background: #17a2b8; color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px; display: inline-block; }
    </style>
</head>
<body>
    <aside><?php include 'admin_sidebar_common.php'; ?></aside>
    <div class="content-wrapper">
        <header class="top-header"><div class="welcome-top"><p>Mwanza Campus | Today: <?php echo date("d M, Y"); ?></p></div></header>
        <main>
            <div class="panel-title">System Logs</div>
            <div class="table-container">
                <table class="data-table"><thead><tr><th>User</th><th>Action</th><th>IP Address</th><th>Time</th></tr></thead>
                <tbody><?php foreach($logs as $log): ?><tr><td><?php echo $log['user_name'] ?? 'Unknown'; ?></td><td><span class="action-<?php echo $log['action']; ?>"><?php echo $log['action']; ?></span></td><td><?php echo $log['ip_address']; ?></td><td><?php echo date('d M Y H:i:s', strtotime($log['login_time'])); ?></td></tr><?php endforeach; ?></tbody></table>
            </div>
        </main>
    </div>
</body>
</html>