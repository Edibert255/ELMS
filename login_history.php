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

// FETCH USER DATA
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

// FETCH LOGIN HISTORY
$login_history = [];
$sql_history = "SELECT * FROM login_history 
                WHERE user_id = '$user_id' 
                ORDER BY login_time DESC 
                LIMIT 50";
$result_history = mysqli_query($conn, $sql_history);

if ($result_history && mysqli_num_rows($result_history) > 0) {
    while ($row = mysqli_fetch_assoc($result_history)) {
        $login_history[] = $row;
    }
}

// GET STATISTICS
$total_logins = count($login_history);
$last_login = !empty($login_history) ? $login_history[0]['login_time'] : 'No record';
$unique_ips = array_unique(array_column($login_history, 'ip_address'));
$unique_ips_count = count($unique_ips);
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login History - CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .history-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #0056b3 0%, #003d82 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-card i {
            font-size: 30px;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
        }
        
        .stat-card .label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Table Styles */
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .history-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #ddd;
        }
        
        .history-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .history-table tr:hover {
            background: #f5f5f5;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background: #e8f4fd;
            color: #0056b3;
        }
        
        .device-info {
            font-size: 12px;
            color: #666;
            max-width: 250px;
            word-break: break-all;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .btn-clear {
            background: #dc3545;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        
        .btn-clear:hover {
            background: #c82333;
        }
        
        @media (max-width: 768px) {
            .history-table {
                display: block;
                overflow-x: auto;
            }
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
            <div class="panel-title">Login History</div>
            
            <div class="info-banner">
                <span><strong>Role:</strong> <?php echo ucfirst($role); ?></span>
                <span><strong>System Status:</strong> Online</span>
                <span><strong>User:</strong> <?php echo $student['first_name'] ?? $user['full_name']; ?></span>
            </div>

            <div class="history-container">
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-sign-in-alt"></i>
                        <div class="number"><?php echo $total_logins; ?></div>
                        <div class="label">Total Logins</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-clock"></i>
                        <div class="number"><?php echo date('d M Y', strtotime($last_login)); ?></div>
                        <div class="label">Last Login</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-laptop"></i>
                        <div class="number"><?php echo $unique_ips_count; ?></div>
                        <div class="label">Devices Used</div>
                    </div>
                </div>
                
                <!-- Clear History Button -->
                <?php if(!empty($login_history)): ?>
                <div style="text-align: right;">
                    <button class="btn-clear" onclick="clearHistory()">
                        <i class="fas fa-trash"></i> Clear History
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- History Table -->
                <?php if(empty($login_history)): ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <p>No login history found.</p>
                        <p style="font-size: 13px;">Your login activities will appear here after you log in.</p>
                    </div>
                <?php else: ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar"></i> Date & Time</th>
                                <th><i class="fas fa-map-marker-alt"></i> IP Address</th>
                                <th><i class="fas fa-desktop"></i> Device / Browser</th>
                                <th><i class="fas fa-tag"></i> Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($login_history as $history): ?>
                            <tr>
                                <td><?php echo date('d M Y, h:i:s A', strtotime($history['login_time'])); ?></td>
                                <td>
                                    <?php echo $history['ip_address']; ?>
                                    <br>
                                    <small>
                                        <?php 
                                        // Check if IP is local
                                        if(strpos($history['ip_address'], '127.0.0.1') !== false || strpos($history['ip_address'], '::1') !== false) {
                                            echo '<span class="badge badge-info">Local</span>';
                                        }
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="device-info">
                                        <?php 
                                        $ua = $history['user_agent'];
                                        if(strpos($ua, 'Chrome') !== false) echo '<i class="fab fa-chrome"></i> Chrome';
                                        elseif(strpos($ua, 'Firefox') !== false) echo '<i class="fab fa-firefox"></i> Firefox';
                                        elseif(strpos($ua, 'Safari') !== false) echo '<i class="fab fa-safari"></i> Safari';
                                        elseif(strpos($ua, 'Edge') !== false) echo '<i class="fab fa-edge"></i> Edge';
                                        else echo '<i class="fas fa-globe"></i> Other';
                                        
                                        if(strpos($ua, 'Windows') !== false) echo ' on Windows';
                                        elseif(strpos($ua, 'Mac') !== false) echo ' on Mac';
                                        elseif(strpos($ua, 'Linux') !== false) echo ' on Linux';
                                        elseif(strpos($ua, 'Android') !== false) echo ' on Android';
                                        elseif(strpos($ua, 'iPhone') !== false) echo ' on iOS';
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if($history['action'] == 'login'): ?>
                                        <span class="badge badge-success"><i class="fas fa-sign-in-alt"></i> Login</span>
                                    <?php elseif($history['action'] == 'logout'): ?>
                                        <span class="badge badge-warning"><i class="fas fa-sign-out-alt"></i> Logout</span>
                                    <?php elseif($history['action'] == 'password_changed'): ?>
                                        <span class="badge badge-info"><i class="fas fa-key"></i> Password Changed</span>
                                    <?php else: ?>
                                        <span class="badge"><?php echo $history['action']; ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
                <!-- Security Note -->
                <div style="margin-top: 20px; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                    <small style="color: #666;">
                        <i class="fas fa-shield-alt"></i> 
                        If you see any unrecognized login activity, please change your password immediately and contact the system administrator.
                    </small>
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
        
        function clearHistory() {
            if(confirm('Are you sure you want to clear your login history? This action cannot be undone.')) {
                window.location.href = 'clear_history.php';
            }
        }
    </script>
</body>
</html>