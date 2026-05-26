<?php
include 'config.php';
session_start();

// CHECK IF ADMIN IS LOGGED IN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['full_name'];
$admin_username = $_SESSION['username'];
$success = "";
$error = "";

// FETCH LATEST ADMIN DATA FOR SIDEBAR
$sql_admin = "SELECT * FROM users WHERE id = '$admin_id'";
$result_admin = mysqli_query($conn, $sql_admin);
$admin_data = mysqli_fetch_assoc($result_admin);
$photo = $admin_data['photo'] ?? 'default.png';
$first_name = explode(' ', $admin_name)[0];
$last_name = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';

// UPDATE CLASS STATUS (ADMIN ONLY)
if (isset($_GET['update_status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $new_status = mysqli_real_escape_string($conn, $_GET['update_status']);
    $sql = "UPDATE live_classes SET status = '$new_status' WHERE id = '$id'";
    if (mysqli_query($conn, $sql)) {
        $success = "Class status updated!";
    } else {
        $error = "Update failed!";
    }
}

// DELETE CLASS (ADMIN ONLY)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "DELETE FROM live_classes WHERE id = '$id'";
    if (mysqli_query($conn, $sql)) {
        $success = "Class deleted successfully!";
    } else {
        $error = "Delete failed!";
    }
}

// UPDATE RECORDING LINK (ADMIN ONLY)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_recording'])) {
    $id = (int)$_POST['class_id'];
    $recording_link = mysqli_real_escape_string($conn, $_POST['recording_link']);
    $sql = "UPDATE live_classes SET recording_link = '$recording_link' WHERE id = '$id'";
    if (mysqli_query($conn, $sql)) {
        $success = "Recording link updated successfully!";
    } else {
        $error = "Update failed!";
    }
}

// FETCH ALL LIVE CLASSES
$upcoming_classes = [];
$live_classes = [];
$completed_classes = [];

$sql = "SELECT * FROM live_classes ORDER BY scheduled_date ASC, start_time ASC";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $today = date('Y-m-d');
        $now = date('H:i:s');
        $class_date = $row['scheduled_date'];
        $start_time = $row['start_time'];
        $end_time = $row['end_time'];
        
        if ($row['status'] == 'live' || ($class_date == $today && $start_time <= $now && $end_time >= $now)) {
            $live_classes[] = $row;
        } elseif ($class_date < $today || ($class_date == $today && $end_time < $now) || $row['status'] == 'completed') {
            $completed_classes[] = $row;
        } else {
            $upcoming_classes[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Classes - Admin | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .live-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin: 25px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #0056b3;
            color: #0056b3;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .class-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #0056b3;
            transition: all 0.3s ease;
        }
        
        .class-card:hover {
            transform: translateX(3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .class-card.live {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
        }
        
        .class-card.upcoming {
            border-left-color: #ffc107;
        }
        
        .class-card.completed {
            border-left-color: #28a745;
            opacity: 0.8;
        }
        
        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        
        .class-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .live-badge {
            background: #dc3545;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            animation: pulse 1.5s infinite;
        }
        
        .upcoming-badge {
            background: #ffc107;
            color: #333;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .completed-badge {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        .class-info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin: 12px 0;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #666;
        }
        
        .info-item i {
            color: #0056b3;
            width: 16px;
        }
        
        .class-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .btn-start {
            background: #dc3545;
            color: white;
            padding: 6px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }
        
        .btn-complete {
            background: #28a745;
            color: white;
            padding: 6px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 6px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }
        
        .btn-view {
            background: #17a2b8;
            color: white;
            padding: 6px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }
        
        .btn-recording {
            background: #6f42c1;
            color: white;
            padding: 6px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .success-msg, .error-msg {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success-msg {
            background: #d4edda;
            color: #155724;
        }
        
        .error-msg {
            background: #f8d7da;
            color: #721c24;
        }
        
        .stats-grid {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #0056b3, #003d82);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            text-align: center;
            flex: 1;
            min-width: 120px;
        }
        
        .stat-card h3 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .info-note {
            background: #e8f4fd;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #0056b3;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .recording-form {
            display: inline-flex;
            gap: 8px;
            align-items: center;
        }
        
        .recording-form input {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 250px;
            font-size: 11px;
        }
        
        .recording-form button {
            background: #6f42c1;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
        }
        
        @media (max-width: 768px) {
            .recording-form {
                flex-wrap: wrap;
            }
            .recording-form input {
                width: 100%;
            }
        }
    </style>
</head>
<body>

   <?php include 'sidebar_admin.php'; ?>

    <div class="content-wrapper">
        <header class="top-header">
            <div class="welcome-top">
                <p>Mwanza Campus | <strong>Today:</strong> <?php echo date("d M, Y"); ?></p>
                <i class="fas fa-bell"></i>
            </div>
        </header>

        <main>
            <div class="panel-title">Live Classes Management</div>
            
            <div class="info-banner">
                <span><strong>Admin:</strong> <?php echo $admin_name; ?></span>
                <span><strong>System Status:</strong> Online</span>
            </div>

            <div class="live-container">
                <?php if($success): ?>
                    <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Info Note -->
                <div class="info-note">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note:</strong> Live classes are created by Lecturers. As Admin, you can <strong>view, manage, update recording links, and delete</strong> classes.
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card"><h3><?php echo count($live_classes); ?></h3><p><i class="fas fa-circle"></i> Live Now</p></div>
                    <div class="stat-card"><h3><?php echo count($upcoming_classes); ?></h3><p><i class="fas fa-calendar"></i> Upcoming</p></div>
                    <div class="stat-card"><h3><?php echo count($completed_classes); ?></h3><p><i class="fas fa-check-circle"></i> Completed</p></div>
                </div>

                <!-- Live Classes (Now) -->
                <?php if(!empty($live_classes)): ?>
                <div class="section-title">
                    <span><i class="fas fa-circle" style="color: #dc3545;"></i> Live Now (<?php echo count($live_classes); ?>)</span>
                </div>
                <?php foreach($live_classes as $class): ?>
                <div class="class-card live">
                    <div class="class-header">
                        <div class="class-title"><?php echo htmlspecialchars($class['title']); ?></div>
                        <span class="live-badge"><i class="fas fa-circle"></i> LIVE NOW</span>
                    </div>
                    <div class="class-info">
                        <div class="info-item"><i class="fas fa-book"></i> <?php echo htmlspecialchars($class['course_name']); ?></div>
                        <div class="info-item"><i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($class['lecturer_name']); ?></div>
                        <div class="info-item"><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($class['start_time'])); ?> - <?php echo date('h:i A', strtotime($class['end_time'])); ?></div>
                        <div class="info-item"><i class="fas fa-calendar"></i> <?php echo date('d M, Y', strtotime($class['scheduled_date'])); ?></div>
                    </div>
                    <div class="class-actions">
                        <a href="<?php echo $class['meeting_link']; ?>" target="_blank" class="btn-start"><i class="fas fa-video"></i> Join Class</a>
                        <a href="?update_status=completed&id=<?php echo $class['id']; ?>" class="btn-complete" onclick="return confirm('Mark this class as completed?')"><i class="fas fa-check"></i> Mark Completed</a>
                        <a href="?delete=<?php echo $class['id']; ?>" class="btn-delete" onclick="return confirm('Delete this class?')"><i class="fas fa-trash"></i> Delete</a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <!-- Upcoming Classes -->
                <?php if(!empty($upcoming_classes)): ?>
                <div class="section-title">
                    <span><i class="fas fa-calendar-alt"></i> Upcoming Classes (<?php echo count($upcoming_classes); ?>)</span>
                </div>
                <?php foreach($upcoming_classes as $class): ?>
                <div class="class-card upcoming">
                    <div class="class-header">
                        <div class="class-title"><?php echo htmlspecialchars($class['title']); ?></div>
                        <span class="upcoming-badge"><i class="fas fa-clock"></i> Upcoming</span>
                    </div>
                    <div class="class-info">
                        <div class="info-item"><i class="fas fa-book"></i> <?php echo htmlspecialchars($class['course_name']); ?></div>
                        <div class="info-item"><i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($class['lecturer_name']); ?></div>
                        <div class="info-item"><i class="fas fa-calendar"></i> <?php echo date('d M, Y', strtotime($class['scheduled_date'])); ?></div>
                        <div class="info-item"><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($class['start_time'])); ?> - <?php echo date('h:i A', strtotime($class['end_time'])); ?></div>
                    </div>
                    <div class="class-actions">
                        <a href="?update_status=live&id=<?php echo $class['id']; ?>" class="btn-start" onclick="return confirm('Start this class now?')"><i class="fas fa-play"></i> Start Class</a>
                        <a href="?delete=<?php echo $class['id']; ?>" class="btn-delete" onclick="return confirm('Delete this class?')"><i class="fas fa-trash"></i> Delete</a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <!-- Completed Classes (Recorded) -->
                <?php if(!empty($completed_classes)): ?>
                <div class="section-title">
                    <span><i class="fas fa-play-circle"></i> Recorded Classes / Completed (<?php echo count($completed_classes); ?>)</span>
                </div>
                <?php foreach($completed_classes as $class): ?>
                <div class="class-card completed">
                    <div class="class-header">
                        <div class="class-title"><?php echo htmlspecialchars($class['title']); ?></div>
                        <span class="completed-badge"><i class="fas fa-check"></i> Completed</span>
                    </div>
                    <div class="class-info">
                        <div class="info-item"><i class="fas fa-book"></i> <?php echo htmlspecialchars($class['course_name']); ?></div>
                        <div class="info-item"><i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($class['lecturer_name']); ?></div>
                        <div class="info-item"><i class="fas fa-calendar"></i> <?php echo date('d M, Y', strtotime($class['scheduled_date'])); ?></div>
                    </div>
                    
                    <?php if(!empty($class['recording_link'])): ?>
                    <div class="class-info">
                        <div class="info-item"><i class="fas fa-link"></i> Recording: <a href="<?php echo $class['recording_link']; ?>" target="_blank">Watch Recording</a></div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Admin can add/update recording link -->
                    <form method="POST" class="recording-form" style="margin-top: 10px;">
                        <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                        <input type="url" name="recording_link" placeholder="Add/Update Recording Link (YouTube, Google Drive, etc.)" value="<?php echo htmlspecialchars($class['recording_link'] ?? ''); ?>">
                        <button type="submit" name="update_recording"><i class="fas fa-save"></i> Update Link</button>
                    </form>
                    
                    <div class="class-actions" style="margin-top: 10px;">
                        <?php if(!empty($class['recording_link'])): ?>
                        <a href="<?php echo $class['recording_link']; ?>" target="_blank" class="btn-view"><i class="fas fa-play"></i> Watch Recording</a>
                        <?php endif; ?>
                        <a href="?delete=<?php echo $class['id']; ?>" class="btn-delete" onclick="return confirm('Delete this class?')"><i class="fas fa-trash"></i> Delete</a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <!-- No Classes Message -->
                <?php if(empty($live_classes) && empty($upcoming_classes) && empty($completed_classes)): ?>
                <div class="empty-state">
                    <i class="fas fa-video-slash"></i>
                    <p>No live classes found.</p>
                    <p style="font-size: 13px;">Lecturers will create live classes, and they will appear here for you to manage.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function toggleSub(id) {
            const menu = document.getElementById(id);
            if (menu) {
                const isActive = menu.classList.contains('active');
                document.querySelectorAll('.submenu').forEach(s => s.classList.remove('active'));
                if (!isActive) menu.classList.add('active');
            }
        }
    </script>

</body>
</html>