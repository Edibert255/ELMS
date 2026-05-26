<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// FETCH STUDENT DATA FOR SIDEBAR
$student = [];
$sql_student = "SELECT s.*, u.full_name, u.photo 
                FROM students s 
                JOIN users u ON s.user_id = u.id 
                WHERE s.user_id = '$user_id'";
$result_student = mysqli_query($conn, $sql_student);
if ($result_student && mysqli_num_rows($result_student) > 0) {
    $student = mysqli_fetch_assoc($result_student);
} else {
    $student = [
        'first_name' => 'Student',
        'last_name' => '',
        'photo' => 'default.png',
        'full_name' => 'Student'
    ];
}

// FETCH LIVE CLASSES - ALL ACTIVE CLASSES
$upcoming_classes = [];
$live_classes = [];
$completed_classes = [];

// Debug: Check what's in the database
$sql_check = "SELECT COUNT(*) as total FROM live_classes";
$result_check = mysqli_query($conn, $sql_check);
$total_classes = mysqli_fetch_assoc($result_check)['total'];

$sql = "SELECT * FROM live_classes ORDER BY scheduled_date ASC, start_time ASC";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $today = date('Y-m-d');
        $now = date('H:i:s');
        $class_date = $row['scheduled_date'];
        $start_time = $row['start_time'];
        $end_time = $row['end_time'];
        
        // Check if class is live now
        if ($row['status'] == 'live' || ($class_date == $today && $start_time <= $now && $end_time >= $now)) {
            $live_classes[] = $row;
        } 
        // Check if class is completed
        elseif ($class_date < $today || ($class_date == $today && $end_time < $now) || $row['status'] == 'completed') {
            $completed_classes[] = $row;
        } 
        // Upcoming classes
        else {
            $upcoming_classes[] = $row;
        }
    }
}

$photo = $student['photo'] ?? 'default.png';
$photo_path = "uploads/profile_pics/" . $photo;
$photo_version = file_exists($photo_path) ? filemtime($photo_path) : time();
$first_name = $student['first_name'] ?? 'Student';
$last_name = $student['last_name'] ?? '';
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Classes - CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .classes-container {
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
            align-items: center;
            gap: 10px;
        }
        
        .class-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #0056b3;
            transition: transform 0.3s ease;
        }
        
        .class-card.live {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.2); }
            50% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
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
            margin-bottom: 10px;
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
        
        .btn-join {
            background: #28a745;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-join:hover {
            background: #218838;
        }
        
        .btn-watch {
            background: #17a2b8;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
            text-decoration: none;
            display: inline-block;
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
        
        .debug-info {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 12px;
            color: #666;
            display: none; /* Hide by default, set to block for debugging */
        }
    </style>
</head>
<body>

    <?php include 'sidebar_student.php'; ?>

    <div class="content-wrapper">
        <header class="top-header">
            <div class="welcome-top">
                <p>Mwanza Campus | <strong>Today:</strong> <?php echo date("d M, Y"); ?></p>
                <i class="fas fa-bell"></i>
            </div>
        </header>

        <main>
            <div class="panel-title">Live Classes</div>
            
            <div class="info-banner">
                <span><strong>Program:</strong> <?php echo $student['program_of_study'] ?? 'All Programs'; ?></span>
                <span><strong>System Status:</strong> Online</span>
                <span><strong>User:</strong> <?php echo $first_name; ?></span>
            </div>

            <!-- Debug Info (Remove after testing) -->
            <div class="debug-info">
                <strong>Debug:</strong> Total classes in database: <?php echo $total_classes; ?> | 
                Live: <?php echo count($live_classes); ?> | 
                Upcoming: <?php echo count($upcoming_classes); ?> | 
                Completed: <?php echo count($completed_classes); ?>
            </div>

            <div class="classes-container">
                
                <!-- LIVE CLASSES SECTION -->
                <div class="section-title">
                    <i class="fas fa-circle" style="color: #dc3545; font-size: 12px;"></i>
                    Live Now
                </div>
                
                <?php if(empty($live_classes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-video-slash"></i>
                        <p>No live classes at the moment.</p>
                    </div>
                <?php else: ?>
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
                        </div>
                        <div class="class-info">
                            <div class="info-item"><i class="fas fa-link"></i> Meeting Link Available</div>
                        </div>
                        <a href="<?php echo $class['meeting_link']; ?>" target="_blank" class="btn-join">
                            <i class="fas fa-video"></i> Join Live Class
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- UPCOMING CLASSES SECTION -->
                <div class="section-title">
                    <i class="fas fa-calendar-alt"></i>
                    Upcoming Classes
                </div>
                
                <?php if(empty($upcoming_classes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-week"></i>
                        <p>No upcoming classes scheduled.</p>
                    </div>
                <?php else: ?>
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
                            <div class="info-item"><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($class['start_time'])); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- RECORDED CLASSES SECTION -->
                <?php if(!empty($completed_classes)): ?>
                <div class="section-title">
                    <i class="fas fa-play-circle"></i>
                    Recorded Classes (Watch Anytime)
                </div>
                
                <?php foreach($completed_classes as $class): ?>
                <div class="class-card completed">
                    <div class="class-header">
                        <div class="class-title"><?php echo htmlspecialchars($class['title']); ?></div>
                        <span class="completed-badge"><i class="fas fa-check"></i> Recorded</span>
                    </div>
                    <div class="class-info">
                        <div class="info-item"><i class="fas fa-book"></i> <?php echo htmlspecialchars($class['course_name']); ?></div>
                        <div class="info-item"><i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($class['lecturer_name']); ?></div>
                        <div class="info-item"><i class="fas fa-calendar"></i> <?php echo date('d M, Y', strtotime($class['scheduled_date'])); ?></div>
                    </div>
                    <?php if(!empty($class['recording_link'])): ?>
                    <a href="<?php echo $class['recording_link']; ?>" target="_blank" class="btn-watch">
                        <i class="fas fa-play"></i> Watch Recording
                    </a>
                    <?php else: ?>
                    <button class="btn-watch" onclick="alert('Recording will be available soon after the class.')">
                        <i class="fas fa-hourglass-half"></i> Coming Soon
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- NO CLASSES AT ALL -->
                <?php if(empty($live_classes) && empty($upcoming_classes) && empty($completed_classes)): ?>
                <div class="empty-state">
                    <i class="fas fa-video-slash"></i>
                    <p>No live classes available at the moment.</p>
                    <p style="font-size: 13px;">Please check back later or contact your lecturer.</p>
                </div>
                <?php endif; ?>
                
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
    </script>

</body>
</html>