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

// DELETE ASSIGNMENT
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "DELETE FROM assignments WHERE id = '$id' AND created_by = '$lecturer_id'";
    if (mysqli_query($conn, $sql)) {
        $success = "Assignment deleted successfully!";
    } else {
        $error = "Delete failed!";
    }
}

// FETCH LECTURER'S ASSIGNMENTS
$assignments = [];
$sql = "SELECT * FROM assignments WHERE created_by = '$lecturer_id' ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Get submission count for each assignment
        $submission_count = 0;
        $sql_count = "SELECT COUNT(*) as count FROM assignment_submissions WHERE assignment_id = '{$row['id']}'";
        $result_count = mysqli_query($conn, $sql_count);
        if ($result_count && mysqli_num_rows($result_count) > 0) {
            $submission_count = mysqli_fetch_assoc($result_count)['count'];
        }
        $row['submission_count'] = $submission_count;
        $assignments[] = $row;
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
    <title>My Assignments - Lecturer | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .assignments-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .btn-add {
            background: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .btn-add:hover {
            background: #218838;
        }
        
        .assignment-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #0056b3;
            transition: all 0.3s ease;
        }
        
        .assignment-card:hover {
            transform: translateX(3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        
        .assignment-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .assignment-course {
            background: #0056b3;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .assignment-details {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin: 12px 0;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #666;
        }
        
        .detail-item i {
            color: #0056b3;
        }
        
        .deadline {
            color: #dc3545;
            font-weight: bold;
        }
        
        .submission-stats {
            display: flex;
            gap: 20px;
            margin: 12px 0;
            padding: 10px;
            background: #e8f4fd;
            border-radius: 8px;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #0056b3;
        }
        
        .stat-label {
            font-size: 11px;
            color: #666;
        }
        
        .assignment-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
            padding: 6px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
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
        }
        
        .btn-submissions {
            background: #17a2b8;
            color: white;
            padding: 6px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
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
        
        @media (max-width: 768px) {
            .assignment-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
            <div class="panel-title">My Assignments</div>
            
            <div class="info-banner">
                <span><strong>Staff ID:</strong> <?php echo $_SESSION['username']; ?></span>
                <span><strong>Total Assignments:</strong> <?php echo count($assignments); ?></span>
                <span><strong>User:</strong> <?php echo $first_name; ?></span>
            </div>

            <div class="assignments-container">
                <?php if(isset($success)): ?>
                    <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if(isset($error)): ?>
                    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <div class="header-actions">
                    <h3><i class="fas fa-tasks"></i> All Assignments</h3>
                    <a href="lecture_add_assignment.php" class="btn-add">
                        <i class="fas fa-plus-circle"></i> Add New Assignment
                    </a>
                </div>

                <?php if(empty($assignments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>No assignments created yet.</p>
                        <p style="font-size: 13px;">Click "Add New Assignment" to create your first assignment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($assignments as $assignment): ?>
                    <div class="assignment-card">
                        <div class="assignment-header">
                            <div class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></div>
                            <span class="assignment-course"><?php echo htmlspecialchars($assignment['course_name']); ?></span>
                        </div>
                        
                        <div class="assignment-details">
                            <div class="detail-item"><i class="fas fa-calendar-alt"></i> Due: <?php echo date('d M, Y', strtotime($assignment['due_date'])); ?></div>
                            <div class="detail-item"><i class="fas fa-clock"></i> Time: <?php echo date('h:i A', strtotime($assignment['due_time'])); ?></div>
                            <div class="detail-item"><i class="fas fa-star"></i> Total Marks: <?php echo $assignment['total_marks']; ?></div>
                            <div class="detail-item deadline"><i class="fas fa-hourglass-half"></i> <?php echo $assignment['submission_count']; ?> Submissions</div>
                        </div>
                        
                        <div class="submission-stats">
                            <div class="stat">
                                <div class="stat-number"><?php echo $assignment['submission_count']; ?></div>
                                <div class="stat-label">Total Submissions</div>
                            </div>
                            <div class="stat">
                                <div class="stat-number"><?php echo $assignment['total_marks']; ?></div>
                                <div class="stat-label">Max Marks</div>
                            </div>
                        </div>
                        
                        <div class="assignment-actions">
                            <a href="lecture_submissions.php?assignment_id=<?php echo $assignment['id']; ?>" class="btn-submissions">
                                <i class="fas fa-check-double"></i> View Submissions
                            </a>
                            <a href="lecture_edit_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="?delete=<?php echo $assignment['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this assignment?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
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