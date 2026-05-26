<?php
include 'config.php';
session_start();

// CHECK IF LECTURER IS LOGGED IN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    header("Location: login.php");
    exit();
}

$lecturer_id = $_SESSION['user_id'];
$lecturer_name = $_SESSION['full_name'];
$success = "";
$error = "";

// CREATE LIVE CLASS
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_class'])) {
    $course_name = mysqli_real_escape_string($conn, $_POST['course_name']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $scheduled_date = mysqli_real_escape_string($conn, $_POST['scheduled_date']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $meeting_link = mysqli_real_escape_string($conn, $_POST['meeting_link']);
    $recording_link = mysqli_real_escape_string($conn, $_POST['recording_link']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $sql = "INSERT INTO live_classes (course_name, title, description, lecturer_name, scheduled_date, start_time, end_time, meeting_link, recording_link, status, created_by) 
            VALUES ('$course_name', '$title', '$description', '$lecturer_name', '$scheduled_date', '$start_time', '$end_time', '$meeting_link', '$recording_link', '$status', '$lecturer_id')";
    
    if (mysqli_query($conn, $sql)) {
        $success = "Live class created successfully!";
    } else {
        $error = "Database error: " . mysqli_error($conn);
    }
}

// UPDATE CLASS STATUS
if (isset($_GET['update_status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $new_status = mysqli_real_escape_string($conn, $_GET['update_status']);
    $sql = "UPDATE live_classes SET status = '$new_status' WHERE id = '$id' AND created_by = '$lecturer_id'";
    if (mysqli_query($conn, $sql)) {
        $success = "Class status updated!";
    } else {
        $error = "Update failed: " . mysqli_error($conn);
    }
}

// DELETE CLASS
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "DELETE FROM live_classes WHERE id = '$id' AND created_by = '$lecturer_id'";
    if (mysqli_query($conn, $sql)) {
        $success = "Class deleted successfully!";
    } else {
        $error = "Delete failed: " . mysqli_error($conn);
    }
}

// FETCH LECTURER'S LIVE CLASSES
$upcoming_classes = [];
$live_classes = [];
$completed_classes = [];

$sql = "SELECT * FROM live_classes WHERE created_by = '$lecturer_id' ORDER BY scheduled_date ASC, start_time ASC";
$result = mysqli_query($conn, $sql);

// Check if query was successful
if ($result === false) {
    // If column doesn't exist, try without created_by filter
    $sql = "SELECT * FROM live_classes ORDER BY scheduled_date ASC, start_time ASC";
    $result = mysqli_query($conn, $sql);
}

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

// FETCH MODULES FOR DROPDOWN
$modules = [];
$sql_modules = "SELECT * FROM lecturer_modules WHERE lecturer_id = '$lecturer_id'";
$result_modules = mysqli_query($conn, $sql_modules);
if ($result_modules && mysqli_num_rows($result_modules) > 0) {
    while ($row = mysqli_fetch_assoc($result_modules)) {
        $modules[] = $row;
    }
}

// Default values
$photo = 'default.png';
$first_name = explode(' ', $lecturer_name)[0];
$last_name = isset(explode(' ', $lecturer_name)[1]) ? explode(' ', $lecturer_name)[1] : '';
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Classes - Lecturer | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .live-classes-container {
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
        
        .section-title:first-of-type {
            margin-top: 0;
        }
        
        .class-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #0056b3;
            transition: transform 0.3s ease;
        }
        
        .class-card:hover {
            transform: translateX(3px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            display: inline-block;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            display: inline-block;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            display: inline-block;
        }
        
        .btn-complete {
            background: #28a745;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            display: inline-block;
        }
        
        .btn-start:hover, .btn-delete:hover { background: #c82333; }
        .btn-complete:hover { background: #218838; }
        .btn-edit:hover { background: #e0a800; }
        
        /* Form Styles */
        .form-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
            font-size: 13px;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
        }
        
        .btn-save {
            background: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-save:hover {
            background: #218838;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 10px;
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
        
        .full-width {
            grid-column: span 2;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .full-width {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>

     <?php include 'sidebar_lecturer.php'; ?>

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
                <span><strong>Staff ID:</strong> <?php echo $_SESSION['username']; ?></span>
                <span><strong>System Status:</strong> Online</span>
                <span><strong>User:</strong> <?php echo $first_name; ?></span>
            </div>

            <div class="live-classes-container">
                <?php if($success): ?>
                    <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <!-- CREATE LIVE CLASS FORM -->
                <div class="form-card">
                    <h3 style="color: #0056b3; margin-bottom: 20px;"><i class="fas fa-plus-circle"></i> Create New Live Class</h3>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="fas fa-book"></i> Course Name</label>
                                <select name="course_name" required>
                                    <option value="">Select Course</option>
                                    <?php if(!empty($modules)): ?>
                                        <?php foreach($modules as $module): ?>
                                        <option value="<?php echo $module['module_name']; ?>"><?php echo $module['module_code'] . ' - ' . $module['module_name']; ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <option value="Other">Other (Specify below)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-heading"></i> Class Title</label>
                                <input type="text" name="title" placeholder="e.g., Introduction to Programming" required>
                            </div>
                            <div class="form-group full-width">
                                <label><i class="fas fa-align-left"></i> Description</label>
                                <textarea name="description" rows="2" placeholder="What will be covered in this class?"></textarea>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-calendar"></i> Scheduled Date</label>
                                <input type="date" name="scheduled_date" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-clock"></i> Start Time</label>
                                <input type="time" name="start_time" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-clock"></i> End Time</label>
                                <input type="time" name="end_time" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-link"></i> Meeting Link</label>
                                <input type="url" name="meeting_link" placeholder="https://meet.google.com/... or https://zoom.us/..." required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-video"></i> Recording Link (Optional)</label>
                                <input type="url" name="recording_link" placeholder="Add recording link after class">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-flag"></i> Status</label>
                                <select name="status">
                                    <option value="upcoming">Upcoming</option>
                                    <option value="live">Live Now</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="create_class" class="btn-save"><i class="fas fa-save"></i> Create Live Class</button>
                    </form>
                </div>

                <!-- LIVE CLASSES (NOW) -->
                <?php if(!empty($live_classes)): ?>
                <div class="section-title">
                    <i class="fas fa-circle" style="color: #dc3545; font-size: 12px;"></i>
                    Live Now
                </div>
                <?php foreach($live_classes as $class): ?>
                <div class="class-card live">
                    <div class="class-header">
                        <div class="class-title"><?php echo htmlspecialchars($class['title']); ?></div>
                        <span class="live-badge"><i class="fas fa-circle"></i> LIVE NOW</span>
                    </div>
                    <div class="class-info">
                        <div class="info-item"><i class="fas fa-book"></i> <?php echo htmlspecialchars($class['course_name']); ?></div>
                        <div class="info-item"><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($class['start_time'])); ?> - <?php echo date('h:i A', strtotime($class['end_time'])); ?></div>
                        <div class="info-item"><i class="fas fa-link"></i> <a href="<?php echo $class['meeting_link']; ?>" target="_blank">Join Meeting</a></div>
                    </div>
                    <div class="class-actions">
                        <a href="<?php echo $class['meeting_link']; ?>" target="_blank" class="btn-start"><i class="fas fa-video"></i> Start Class</a>
                        <a href="?update_status=completed&id=<?php echo $class['id']; ?>" class="btn-complete" onclick="return confirm('Mark this class as completed?')"><i class="fas fa-check"></i> Mark Completed</a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <!-- UPCOMING CLASSES -->
                <?php if(!empty($upcoming_classes)): ?>
                <div class="section-title">
                    <i class="fas fa-calendar-alt"></i>
                    Upcoming Classes
                </div>
                <?php foreach($upcoming_classes as $class): ?>
                <div class="class-card upcoming">
                    <div class="class-header">
                        <div class="class-title"><?php echo htmlspecialchars($class['title']); ?></div>
                        <span class="upcoming-badge"><i class="fas fa-clock"></i> Upcoming</span>
                    </div>
                    <div class="class-info">
                        <div class="info-item"><i class="fas fa-book"></i> <?php echo htmlspecialchars($class['course_name']); ?></div>
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

                <!-- COMPLETED CLASSES -->
                <?php if(!empty($completed_classes)): ?>
                <div class="section-title">
                    <i class="fas fa-check-circle"></i>
                    Completed Classes
                </div>
                <?php foreach($completed_classes as $class): ?>
                <div class="class-card completed">
                    <div class="class-header">
                        <div class="class-title"><?php echo htmlspecialchars($class['title']); ?></div>
                        <span class="completed-badge"><i class="fas fa-check"></i> Completed</span>
                    </div>
                    <div class="class-info">
                        <div class="info-item"><i class="fas fa-book"></i> <?php echo htmlspecialchars($class['course_name']); ?></div>
                        <div class="info-item"><i class="fas fa-calendar"></i> <?php echo date('d M, Y', strtotime($class['scheduled_date'])); ?></div>
                    </div>
                    <div class="class-actions">
                        <?php if(!empty($class['recording_link'])): ?>
                        <a href="<?php echo $class['recording_link']; ?>" target="_blank" class="btn-edit"><i class="fas fa-play"></i> Watch Recording</a>
                        <?php endif; ?>
                        <a href="?delete=<?php echo $class['id']; ?>" class="btn-delete" onclick="return confirm('Delete this class?')"><i class="fas fa-trash"></i> Delete</a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <!-- NO CLASSES MESSAGE -->
                <?php if(empty($live_classes) && empty($upcoming_classes) && empty($completed_classes)): ?>
                <div class="empty-state">
                    <i class="fas fa-video-slash"></i>
                    <p>No live classes created yet.</p>
                    <p style="font-size: 13px;">Use the form above to create your first live class.</p>
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