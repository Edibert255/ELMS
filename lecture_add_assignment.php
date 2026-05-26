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

// FETCH LECTURER'S MODULES FOR DROPDOWN
$modules = [];
$sql_modules = "SELECT * FROM lecturer_modules WHERE lecturer_id = '$lecturer_id' ORDER BY module_code ASC";
$result_modules = mysqli_query($conn, $sql_modules);
if ($result_modules && mysqli_num_rows($result_modules) > 0) {
    while ($row = mysqli_fetch_assoc($result_modules)) {
        $modules[] = $row;
    }
}

// PROCESS ADD ASSIGNMENT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_assignment'])) {
    $course_name = mysqli_real_escape_string($conn, $_POST['course_name']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    $due_time = mysqli_real_escape_string($conn, $_POST['due_time']);
    $total_marks = (int)$_POST['total_marks'];
    
    // Handle file upload
    $attachment = "";
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'zip', 'rar'];
        $filename = $_FILES['attachment']['name'];
        $fileext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $filesize = $_FILES['attachment']['size'];
        
        if ($filesize > 5242880) { // 5MB max
            $error = "File too large! Maximum 5MB allowed.";
        } elseif (in_array($fileext, $allowed)) {
            $new_filename = time() . "_" . preg_replace('/[^a-zA-Z0-9]/', '_', $title) . "." . $fileext;
            $upload_path = "uploads/assignments/" . $new_filename;
            
            // Create directory if not exists
            if (!file_exists("uploads/assignments/")) {
                mkdir("uploads/assignments/", 0777, true);
            }
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_path)) {
                $attachment = $new_filename;
            } else {
                $error = "Failed to upload file.";
            }
        } else {
            $error = "File type not allowed. Allowed: PDF, DOC, DOCX, ZIP, RAR";
        }
    }
    
    if (empty($error)) {
        $sql = "INSERT INTO assignments (course_name, title, description, due_date, due_time, total_marks, attachment, created_by, status) 
                VALUES ('$course_name', '$title', '$description', '$due_date', '$due_time', '$total_marks', '$attachment', '$lecturer_id', 'active')";
        
        if (mysqli_query($conn, $sql)) {
            $success = "Assignment created successfully!";
            // Clear form
            $_POST = array();
        } else {
            $error = "Database error: " . mysqli_error($conn);
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
    <title>Add New Assignment - Lecturer | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            max-width: 800px;
        }
        
        .form-title {
            color: #0056b3;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0056b3;
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
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
        }
        
        .form-group textarea {
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
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
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
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
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #0056b3;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
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
            <div class="panel-title">Add New Assignment</div>
            
            <div class="info-banner">
                <span><strong>Staff ID:</strong> <?php echo $_SESSION['username']; ?></span>
                <span><strong>System Status:</strong> Online</span>
                <span><strong>User:</strong> <?php echo $first_name; ?></span>
            </div>

            <div class="form-container">
                <a href="lecture_assignments.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to My Assignments</a>
                
                <h3 class="form-title"><i class="fas fa-plus-circle"></i> Create New Assignment</h3>
                
                <?php if($success): ?>
                    <div class="success-msg">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <p style="margin-top: 10px;"><a href="lecture_assignments.php" style="color: #155724;">View all assignments →</a></p>
                    </div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label><i class="fas fa-book"></i> Course Name *</label>
                        <select name="course_name" required>
                            <option value="">Select Course</option>
                            <?php foreach($modules as $module): ?>
                            <option value="<?php echo htmlspecialchars($module['module_name']); ?>" <?php echo (isset($_POST['course_name']) && $_POST['course_name'] == $module['module_name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($module['module_code'] . ' - ' . $module['module_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-heading"></i> Assignment Title *</label>
                        <input type="text" name="title" placeholder="e.g., Midterm Exam, Final Project, Assignment 1" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Description / Instructions</label>
                        <textarea name="description" rows="5" placeholder="Describe the assignment requirements, instructions for students..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Due Date *</label>
                            <input type="date" name="due_date" value="<?php echo isset($_POST['due_date']) ? $_POST['due_date'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Due Time *</label>
                            <input type="time" name="due_time" value="<?php echo isset($_POST['due_time']) ? $_POST['due_time'] : '23:59'; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-star"></i> Total Marks *</label>
                            <input type="number" name="total_marks" value="<?php echo isset($_POST['total_marks']) ? $_POST['total_marks'] : '100'; ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-paperclip"></i> Attachment (Optional)</label>
                            <input type="file" name="attachment">
                            <div class="help-text">Allowed: PDF, DOC, DOCX, ZIP, RAR (Max 5MB)</div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 25px;">
                        <button type="submit" name="add_assignment" class="btn-submit">
                            <i class="fas fa-save"></i> Create Assignment
                        </button>
                        <a href="lecture_assignments.php" class="btn-cancel">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
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