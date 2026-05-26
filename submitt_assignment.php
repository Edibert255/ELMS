<?php
include 'config.php';
session_start();

// CHECK IF STUDENT IS LOGGED IN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// GET ASSIGNMENT ID FROM URL
$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// FETCH ASSIGNMENT DETAILS
$sql_assignment = "SELECT * FROM assignments WHERE id = '$assignment_id'";
$result_assignment = mysqli_query($conn, $sql_assignment);

if ($result_assignment && mysqli_num_rows($result_assignment) > 0) {
    $assignment = mysqli_fetch_assoc($result_assignment);
} else {
    header("Location: recent_assignments.php");
    exit();
}

// CHECK IF ALREADY SUBMITTED
$sql_check = "SELECT * FROM submitted_assignments WHERE assignment_id = '$assignment_id' AND student_id = '$user_id'";
$result_check = mysqli_query($conn, $sql_check);

if ($result_check && mysqli_num_rows($result_check) > 0) {
    header("Location: submitted_assignments.php?msg=already_submitted");
    exit();
}

// PROCESS SUBMISSION
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submission_text = mysqli_real_escape_string($conn, $_POST['submission_text']);
    $attachment = "";
    
    // HANDLE FILE UPLOAD
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'zip', 'rar', 'txt'];
        $filename = $_FILES['attachment']['name'];
        $fileext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($fileext, $allowed)) {
            $new_filename = time() . "_" . $user_id . "_" . $assignment_id . "." . $fileext;
            $upload_path = "uploads/assignments/" . $new_filename;
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_path)) {
                $attachment = $new_filename;
            } else {
                $error = "Failed to upload file.";
            }
        } else {
            $error = "File type not allowed. Allowed: PDF, DOC, DOCX, ZIP, RAR, TXT";
        }
    }
    
    if (empty($error)) {
        $status = 'submitted';
        
        $sql_insert = "INSERT INTO submitted_assignments (assignment_id, course_name, student_id, submission_text, attachment, status) 
                       VALUES ('$assignment_id', '{$assignment['course_name']}', '$user_id', '$submission_text', '$attachment', '$status')";
        
        if (mysqli_query($conn, $sql_insert)) {
            $success = "Assignment submitted successfully!";
            echo "<script>setTimeout(function(){ window.location.href = 'submitted_assignments.php'; }, 2000);</script>";
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}

// FETCH STUDENT DATA
$sql_student = "SELECT * FROM students WHERE user_id = '$user_id'";
$result_student = mysqli_query($conn, $sql_student);
$student = mysqli_fetch_assoc($result_student);
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Assignment - CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .submit-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .assignment-info {
            background: #e8f4fd;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid #0056b3;
        }
        
        .assignment-info h3 {
            color: #0056b3;
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: vertical;
            font-family: inherit;
        }
        
        .form-group input[type="file"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 100%;
        }
        
        .btn-submit {
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-submit:hover {
            background: #218838;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-text {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
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
            <div class="panel-title">Submit Assignment</div>
            
            <div class="info-banner">
                <span><strong>Registration No:</strong> <?php echo $student['registration_no'] ?? 'N/A'; ?></span>
                <span><strong>System Status:</strong> Online</span>
                <span><strong>User:</strong> <?php echo $student['first_name'] ?? 'Student'; ?></span>
            </div>

            <div class="submit-container">
                <?php if($success): ?>
                    <div class="success-msg">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?> Redirecting...
                    </div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="assignment-info">
                    <h3><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($assignment['title']); ?></h3>
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($assignment['course_name']); ?></p>
                    <p><strong>Due Date:</strong> <?php echo date('d M, Y h:i A', strtotime($assignment['due_date'] . ' ' . $assignment['due_time'])); ?></p>
                    <p><strong>Total Marks:</strong> <?php echo $assignment['total_marks']; ?></p>
                    <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label><i class="fas fa-comment"></i> Your Submission</label>
                        <textarea name="submission_text" rows="8" placeholder="Write your answer or explanation here..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-paperclip"></i> Attachment (Optional)</label>
                        <input type="file" name="attachment">
                        <p class="info-text">Allowed formats: PDF, DOC, DOCX, ZIP, RAR, TXT (Max 5MB)</p>
                    </div>
                    
                    <div>
                        <button type="submit" class="btn-submit"><i class="fas fa-upload"></i> Submit Assignment</button>
                        <a href="recent_assignments.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function toggleSub(id) {
            const menu = document.getElementById(id);
            document.querySelectorAll('.submenu').forEach(s => {
                if(s.id !== id) s.classList.remove('active');
            });
            if(menu) menu.classList.toggle('active');
        }
    </script>
</body>
</html>