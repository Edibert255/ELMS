<?php
include 'config.php';
session_start();

// CHECK IF STUDENT IS LOGGED IN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// FETCH STUDENT DATA - IMPROVED
$sql = "SELECT s.*, u.full_name, u.email, u.username, u.photo 
        FROM users u 
        LEFT JOIN students s ON u.id = s.user_id 
        WHERE u.id = '$user_id' AND u.role = 'student'";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $student_data = mysqli_fetch_assoc($result);
    $full_name = $student_data['full_name'] ?? 'Student';
    $photo = $student_data['photo'] ?? 'default.png';
    $registration_no = $student_data['registration_no'] ?? 'Not Assigned';
    $first_name = explode(' ', $full_name)[0];
    $last_name = isset(explode(' ', $full_name)[1]) ? explode(' ', $full_name)[1] : '';
} else {
    // If no data found, create default
    $full_name = $_SESSION['full_name'] ?? 'Student';
    $photo = 'default.png';
    $registration_no = 'Not Assigned';
    $first_name = $full_name;
    $last_name = '';
}

// ADD CACHE BUSTER FOR PHOTO
$photo_path = "uploads/profile_pics/" . $photo;
$photo_version = file_exists($photo_path) ? filemtime($photo_path) : time();
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recent Assignments - CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .assignments-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            min-height: 400px;
        }
        
        .assignment-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #0056b3;
        }
        
        .assignment-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .assignment-course {
            color: #0056b3;
            font-size: 14px;
            margin: 5px 0;
        }
        
        .assignment-details {
            display: flex;
            gap: 20px;
            margin: 10px 0;
        }
        
        .btn-submit {
            background: #28a745;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .btn-submit:hover {
            background: #218838;
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
            <div class="panel-title">Recent Assignments</div>
            
            <div class="info-banner">
                <span><strong>Registration No:</strong> <?php echo $student['registration_no'] ?? 'N/A'; ?></span>
                <span><strong>System Status:</strong> Online</span>
                <span><strong>User:</strong> <?php echo $student['first_name'] ?? 'Student'; ?></span>
            </div>

            <div class="assignments-container">
                <?php if (empty($pending_assignments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>No pending assignments! All tasks are completed.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($pending_assignments as $assignment): ?>
                        <div class="assignment-card">
                            <div class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></div>
                            <div class="assignment-course">📚 <?php echo htmlspecialchars($assignment['course_name']); ?></div>
                            <div class="assignment-details">
                                <span><i class="fas fa-calendar"></i> Due: <?php echo date('d M, Y', strtotime($assignment['due_date'])); ?></span>
                                <span><i class="fas fa-clock"></i> Time: <?php echo date('h:i A', strtotime($assignment['due_time'])); ?></span>
                                <span><i class="fas fa-star"></i> Marks: <?php echo $assignment['total_marks']; ?></span>
                            </div>
                            <div class="assignment-details">
                                <span><i class="fas fa-file-alt"></i> <?php echo substr(htmlspecialchars($assignment['description']), 0, 100) . '...'; ?></span>
                            </div>
                            <button class="btn-submit" onclick="window.location.href='submit_assignment.php?id=<?php echo $assignment['id']; ?>'">
                                <i class="fas fa-upload"></i> Submit Assignment
                            </button>
                        </div>
                    <?php endforeach; ?>
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