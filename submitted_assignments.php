<?php
include 'config.php';
session_start();

// $student_id = $_SESSION['user_id']; // Badala yake tumia hii baada ya login
$student_id = 1; // Temporary - badala yake tumia session

// Fetch submitted assignments kwa kutumia columns zako
$sql = "SELECT * FROM submitted_assignments 
        WHERE student_id = $student_id 
        ORDER BY submitted_at DESC";

$result = $conn->query($sql);
$submissions = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $submissions[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submitted Assignments - CBE ELMS</title>
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
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #0056b3;
            color: #0056b3;
        }
        
        .submission-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #28a745;
            transition: all 0.3s ease;
        }
        
        .submission-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .assignment-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .course-name {
            color: #0056b3;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .submission-details {
            display: flex;
            gap: 20px;
            margin: 15px 0;
            flex-wrap: wrap;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #666;
        }
        
        .detail-item i {
            color: #0056b3;
            width: 16px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-submitted {
            background: #ffc107;
            color: #333;
        }
        
        .status-late {
            background: #dc3545;
            color: white;
        }
        
        .status-graded {
            background: #28a745;
            color: white;
        }
        
        .marks-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            display: inline-block;
            min-width: 150px;
        }
        
        .marks-section .score {
            font-size: 24px;
            font-weight: bold;
        }
        
        .feedback-section {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }
        
        .feedback-section strong {
            color: #0056b3;
        }
        
        .submission-text {
            background: #fff;
            padding: 12px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #e0e0e0;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .attachment-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #0056b3;
            text-decoration: none;
            margin-top: 10px;
            padding: 8px 15px;
            background: #e8f4ff;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .attachment-link:hover {
            background: #0056b3;
            color: white;
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
        
        .empty-state a {
            color: #0056b3;
            text-decoration: none;
            margin-top: 10px;
            display: inline-block;
        }
        
        .empty-state a:hover {
            text-decoration: underline;
        }
        
        hr {
            margin: 15px 0;
            border: none;
            border-top: 1px solid #e0e0e0;
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
            <div class="panel-title">Submitted Assignments</div>
            
            <div class="info-banner">
                <span><strong>Current Year:</strong> 2025/2026</span>
                <span><strong>System Status:</strong> Online</span>
                <span><strong>User:</strong> <?php echo $student['first_name'] ?? 'Student'; ?></span>
            </div>

            <div class="submissions-container">
                <?php if (empty($submissions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>You haven't submitted any assignments yet.</p>
                        <a href="recent_assignments.php">
                            <i class="fas fa-arrow-left"></i> Browse Recent Assignments
                        </a>
                    </div>
                <?php else: ?>
                    <div class="section-title">
                        <i class="fas fa-file-alt"></i> My Submissions (<?php echo count($submissions); ?>)
                    </div>
                    
                    <?php foreach($submissions as $submission): ?>
                        <div class="submission-card">
                            <div class="submission-header">
                                <div>
                                    <div class="assignment-title">
                                        Assignment #<?php echo htmlspecialchars($submission['assignment_id']); ?>
                                    </div>
                                    <div class="course-name">
                                        <i class="fas fa-book-open"></i> <?php echo htmlspecialchars($submission['course_name']); ?>
                                    </div>
                                </div>
                                <div>
                                    <?php if($submission['status'] == 'graded'): ?>
                                        <span class="status-badge status-graded">
                                            <i class="fas fa-check-circle"></i> Graded
                                        </span>
                                    <?php elseif($submission['status'] == 'late'): ?>
                                        <span class="status-badge status-late">
                                            <i class="fas fa-exclamation-triangle"></i> Late Submission
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-submitted">
                                            <i class="fas fa-clock"></i> Pending Review
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="submission-details">
                                <div class="detail-item">
                                    <i class="fas fa-calendar-alt"></i> 
                                    Submitted: <?php echo date('d M, Y h:i A', strtotime($submission['submitted_at'])); ?>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-hashtag"></i> 
                                    Submission ID: #<?php echo $submission['id']; ?>
                                </div>
                            </div>
                            
                            <?php if(!empty($submission['submission_text'])): ?>
                                <div class="submission-text">
                                    <strong><i class="fas fa-comment"></i> My Submission:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($submission['attachment'])): ?>
                                <div>
                                    <a href="uploads/assignments/<?php echo $submission['attachment']; ?>" class="attachment-link" download>
                                        <i class="fas fa-paperclip"></i> Download Attachment
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($submission['status'] == 'graded'): ?>
                                <hr>
                                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                                    <?php if(!is_null($submission['marks_obtained'])): ?>
                                        <div class="marks-section">
                                            <div style="font-size: 12px; opacity: 0.9;">Marks Obtained</div>
                                            <div class="score"><?php echo $submission['marks_obtained']; ?> / 100</div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($submission['feedback'])): ?>
                                        <div class="feedback-section">
                                            <strong><i class="fas fa-chalkboard-teacher"></i> Lecturer's Feedback:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
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
            const isActive = menu.classList.contains('active');
            
            document.querySelectorAll('.submenu').forEach(s => {
                if(s.id !== id) s.classList.remove('active');
            });
            
            menu.classList.toggle('active');
        }
    </script>
</body>
</html>