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

// Get filter parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';
$assignment_filter = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
$module_filter = isset($_GET['module']) ? mysqli_real_escape_string($conn, $_GET['module']) : '';

// Get assignments for filter dropdown
$assignments_list = [];
$sql_assign = "SELECT id, title, course_name FROM assignments WHERE created_by = '$lecturer_id' ORDER BY created_at DESC";
$result_assign = mysqli_query($conn, $sql_assign);
if ($result_assign && mysqli_num_rows($result_assign) > 0) {
    while ($row = mysqli_fetch_assoc($result_assign)) {
        $assignments_list[] = $row;
    }
}

// Build query based on filter
$sql = "SELECT s.*, u.full_name as student_name, u.email as student_email, 
        a.title as assignment_title, a.course_name, a.total_marks,
        a.due_date, a.due_time
        FROM assignment_submissions s
        JOIN users u ON s.student_id = u.id
        JOIN assignments a ON s.assignment_id = a.id
        WHERE a.created_by = '$lecturer_id'";

if ($filter == 'pending') {
    $sql .= " AND s.marks_obtained IS NULL";
} elseif ($filter == 'graded') {
    $sql .= " AND s.marks_obtained IS NOT NULL";
}

if ($assignment_filter > 0) {
    $sql .= " AND s.assignment_id = '$assignment_filter'";
}

if ($module_filter != '') {
    $sql .= " AND a.course_name LIKE '%$module_filter%'";
}

$sql .= " ORDER BY s.submitted_at DESC";

$result = mysqli_query($conn, $sql);
$submissions = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $submissions[] = $row;
    }
}

// Process grading
$grade_success = "";
$grade_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['grade_submission'])) {
    $submission_id = (int)$_POST['submission_id'];
    $marks_obtained = (int)$_POST['marks_obtained'];
    $feedback = mysqli_real_escape_string($conn, $_POST['feedback']);
    $total_marks = (int)$_POST['total_marks'];
    
    if ($marks_obtained > $total_marks) {
        $grade_error = "Marks cannot exceed total marks ($total_marks)!";
    } elseif ($marks_obtained < 0) {
        $grade_error = "Marks cannot be negative!";
    } else {
        $sql_update = "UPDATE assignment_submissions 
                       SET marks_obtained = '$marks_obtained', 
                           feedback = '$feedback', 
                           graded_by = '$lecturer_id', 
                           graded_at = NOW() 
                       WHERE id = '$submission_id'";
        
        if (mysqli_query($conn, $sql_update)) {
            $grade_success = "Submission graded successfully!";
            // Refresh page after 2 seconds
            echo "<script>setTimeout(function(){ window.location.href = 'lecture_submissions.php?filter=$filter'; }, 1500);</script>";
        } else {
            $grade_error = "Database error: " . mysqli_error($conn);
        }
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
    <title>Submissions - Lecturer | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .submissions-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #e0e0e0;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 12px 25px;
            background: none;
            border: none;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .filter-tab.active {
            color: #0056b3;
            border-bottom: 3px solid #0056b3;
        }
        
        .filter-tab:hover {
            color: #0056b3;
        }
        
        .filter-tab.pending {
            color: #dc3545;
        }
        
        .filter-tab.pending.active {
            color: #dc3545;
            border-bottom-color: #dc3545;
        }
        
        .filter-tab.graded.active {
            color: #28a745;
            border-bottom-color: #28a745;
        }
        
        /* Extra Filters */
        .extra-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .extra-filters select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
        }
        
        .badge-count {
            background: #e0e0e0;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 8px;
        }
        
        /* Submission Cards */
        .submission-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #0056b3;
            transition: all 0.3s ease;
        }
        
        .submission-card.pending {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
        }
        
        .submission-card.graded {
            border-left-color: #28a745;
        }
        
        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .student-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .student-avatar {
            width: 50px;
            height: 50px;
            background: #0056b3;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        
        .student-name {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        
        .student-reg {
            font-size: 12px;
            color: #666;
        }
        
        .assignment-title {
            font-size: 14px;
            color: #0056b3;
            font-weight: bold;
        }
        
        .submission-details {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin: 15px 0;
            padding: 10px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #666;
        }
        
        .submission-text {
            background: white;
            padding: 12px;
            border-radius: 8px;
            margin: 10px 0;
            font-size: 13px;
            line-height: 1.5;
            border: 1px solid #eee;
        }
        
        .attachment-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #0056b3;
            text-decoration: none;
            margin: 10px 0;
        }
        
        .grade-form {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border: 1px solid #ddd;
        }
        
        .grade-form-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .grade-field {
            flex: 1;
        }
        
        .grade-field label {
            display: block;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        
        .grade-field input, .grade-field textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .grade-field textarea {
            resize: vertical;
        }
        
        .btn-grade {
            background: #28a745;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .marks-display {
            background: #e8f4fd;
            padding: 10px 15px;
            border-radius: 8px;
            display: inline-block;
        }
        
        .marks-display .score {
            font-size: 20px;
            font-weight: bold;
            color: #0056b3;
        }
        
        .feedback-text {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 13px;
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
            .grade-form-row {
                flex-direction: column;
            }
            .grade-field {
                width: 100%;
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
            <div class="panel-title">
                <?php echo $filter == 'pending' ? 'Pending Grading' : 'Graded Submissions'; ?>
            </div>
            
            <div class="info-banner">
                <span><strong>Staff ID:</strong> <?php echo $_SESSION['username']; ?></span>
                <span><strong>Total:</strong> <?php echo count($submissions); ?> submissions</span>
                <span><strong>User:</strong> <?php echo $first_name; ?></span>
            </div>

            <div class="submissions-container">
                <?php if($grade_success): ?>
                    <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $grade_success; ?></div>
                <?php endif; ?>
                
                <?php if($grade_error): ?>
                    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $grade_error; ?></div>
                <?php endif; ?>

                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <a href="lecture_submissions.php?filter=pending" class="filter-tab pending <?php echo $filter == 'pending' ? 'active' : ''; ?>">
                        <i class="fas fa-hourglass-half"></i> Pending Grading
                        <span class="badge-count">Pending</span>
                    </a>
                    <a href="lecture_submissions.php?filter=graded" class="filter-tab graded <?php echo $filter == 'graded' ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i> Graded Submissions
                        <span class="badge-count">Graded</span>
                    </a>
                </div>

                <!-- Extra Filters -->
                <div class="extra-filters">
                    <select id="assignmentFilter" onchange="window.location.href=this.value">
                        <option value="lecture_submissions.php?filter=<?php echo $filter; ?>">All Assignments</option>
                        <?php foreach($assignments_list as $assign): ?>
                        <option value="lecture_submissions.php?filter=<?php echo $filter; ?>&assignment_id=<?php echo $assign['id']; ?>" 
                            <?php echo ($assignment_filter == $assign['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($assign['title']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if(empty($submissions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No <?php echo $filter == 'pending' ? 'pending' : 'graded'; ?> submissions found.</p>
                        <p style="font-size: 13px;">
                            <?php echo $filter == 'pending' ? 'When students submit assignments, they will appear here.' : 'Graded submissions will appear here.'; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach($submissions as $submission): ?>
                    <div class="submission-card <?php echo $filter == 'pending' ? 'pending' : 'graded'; ?>">
                        <div class="submission-header">
                            <div class="student-info">
                                <div class="student-avatar">
                                    <?php echo strtoupper(substr($submission['student_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="student-name"><?php echo htmlspecialchars($submission['student_name']); ?></div>
                                    <div class="student-reg"><?php echo htmlspecialchars($submission['student_email']); ?></div>
                                </div>
                            </div>
                            <div class="assignment-title">
                                <i class="fas fa-tasks"></i> <?php echo htmlspecialchars($submission['assignment_title']); ?>
                                <br><small><?php echo htmlspecialchars($submission['course_name']); ?></small>
                            </div>
                        </div>
                        
                        <div class="submission-details">
                            <div class="detail-item"><i class="fas fa-calendar-alt"></i> Submitted: <?php echo date('d M, Y h:i A', strtotime($submission['submitted_at'])); ?></div>
                            <div class="detail-item"><i class="fas fa-star"></i> Total Marks: <?php echo $submission['total_marks']; ?></div>
                            <?php if($filter == 'graded'): ?>
                            <div class="detail-item"><i class="fas fa-check-circle" style="color:#28a745;"></i> Graded: <?php echo date('d M, Y', strtotime($submission['graded_at'])); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if(!empty($submission['submission_text'])): ?>
                        <div class="submission-text">
                            <strong><i class="fas fa-comment"></i> Student's Submission:</strong><br>
                            <?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($submission['attachment'])): ?>
                        <div>
                            <a href="uploads/assignments/<?php echo $submission['attachment']; ?>" class="attachment-link" download target="_blank">
                                <i class="fas fa-paperclip"></i> Download Attachment
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($filter == 'graded'): ?>
                        <!-- Graded Submission Display -->
                        <div class="grade-form" style="background: #e8f4fd;">
                            <div class="marks-display">
                                <div class="score"><?php echo $submission['marks_obtained']; ?> / <?php echo $submission['total_marks']; ?></div>
                                <div style="font-size: 11px;">Marks Obtained</div>
                            </div>
                            <?php if(!empty($submission['feedback'])): ?>
                            <div class="feedback-text">
                                <strong><i class="fas fa-comment-dots"></i> Feedback from Lecturer:</strong><br>
                                <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <!-- Pending Grading Form -->
                        <form method="POST" class="grade-form">
                            <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                            <input type="hidden" name="total_marks" value="<?php echo $submission['total_marks']; ?>">
                            
                            <div class="grade-form-row">
                                <div class="grade-field">
                                    <label><i class="fas fa-star"></i> Marks Obtained (Max: <?php echo $submission['total_marks']; ?>)</label>
                                    <input type="number" name="marks_obtained" min="0" max="<?php echo $submission['total_marks']; ?>" required>
                                </div>
                                <div class="grade-field" style="flex:2;">
                                    <label><i class="fas fa-comment-dots"></i> Feedback (Optional)</label>
                                    <textarea name="feedback" rows="2" placeholder="Add comments or feedback for the student..."></textarea>
                                </div>
                                <div class="grade-field">
                                    <button type="submit" name="grade_submission" class="btn-grade">
                                        <i class="fas fa-save"></i> Submit Grade
                                    </button>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>
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