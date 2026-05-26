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
$photo = isset($admin_data['photo']) ? $admin_data['photo'] : 'default.png';
$first_name = explode(' ', $admin_name)[0];
$last_name = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';

// FETCH ALL COURSES FOR DROPDOWN
$courses = [];
$sql_courses = "SELECT id, course_code, course_name FROM courses ORDER BY course_code ASC";
$result_courses = mysqli_query($conn, $sql_courses);
if ($result_courses && mysqli_num_rows($result_courses) > 0) {
    while ($row = mysqli_fetch_assoc($result_courses)) {
        $courses[] = $row;
    }
}

// ADD NEW MATERIAL WITH COURSE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_material'])) {
    $course_id = (int)$_POST['course_id'];
    $module_id = isset($_POST['module_id']) ? (int)$_POST['module_id'] : 0;
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $year_of_study = mysqli_real_escape_string($conn, $_POST['year_of_study']);
    
    $check_course = mysqli_query($conn, "SELECT id FROM courses WHERE id = '$course_id'");
    if (mysqli_num_rows($check_course) == 0) {
        $error = "Invalid course selected!";
    } else {
        $file_path = "";
        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'mp4'];
            $filename = $_FILES['file']['name'];
            $fileext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $filesize = $_FILES['file']['size'];
            
            if ($filesize > 10485760) {
                $error = "File too large! Maximum 10MB allowed.";
            } elseif (in_array($fileext, $allowed)) {
                $new_filename = time() . "_" . preg_replace('/[^a-zA-Z0-9]/', '_', $title) . "." . $fileext;
                $upload_path = "uploads/library/" . $new_filename;
                
                if (!file_exists("uploads/library/")) {
                    mkdir("uploads/library/", 0777, true);
                }
                
                if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_path)) {
                    $file_path = $new_filename;
                } else {
                    $error = "Failed to upload file.";
                }
            } else {
                $error = "File type not allowed.";
            }
        } else {
            $error = "Please select a file to upload.";
        }
        
        if (empty($error) && $file_path != "") {
            $sql = "INSERT INTO library_materials (course_id, module_id, title, author, description, category, file_path, year_of_study, uploaded_by, status) 
                    VALUES ('$course_id', '$module_id', '$title', '$author', '$description', '$category', '$file_path', '$year_of_study', '$admin_id', 'active')";
            
            if (mysqli_query($conn, $sql)) {
                $success = "Material uploaded successfully!";
                $_POST = array();
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        }
    }
}

// DELETE MATERIAL
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql_file = "SELECT file_path FROM library_materials WHERE id = '$id'";
    $result_file = mysqli_query($conn, $sql_file);
    if ($result_file && mysqli_num_rows($result_file) > 0) {
        $material = mysqli_fetch_assoc($result_file);
        $file_path = "uploads/library/" . $material['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    mysqli_query($conn, "DELETE FROM library_materials WHERE id = '$id'");
    $success = "Material deleted!";
}

// UPDATE STATUS
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $new_status = mysqli_real_escape_string($conn, $_GET['status']);
    mysqli_query($conn, "UPDATE library_materials SET status = '$new_status' WHERE id = '$id'");
    $success = "Status updated!";
}

// FILTERS
$course_filter = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$category_filter = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// FETCH MATERIALS WITH COURSE INFO
$materials = [];
$sql_materials = "SELECT l.*, c.course_code, c.course_name 
                  FROM library_materials l 
                  JOIN courses c ON l.course_id = c.id 
                  WHERE 1=1";

if ($course_filter > 0) {
    $sql_materials .= " AND l.course_id = '$course_filter'";
}
if ($category_filter != 'all') {
    $sql_materials .= " AND l.category = '$category_filter'";
}
if ($search != '') {
    $sql_materials .= " AND (l.title LIKE '%$search%' OR l.description LIKE '%$search%')";
}
$sql_materials .= " ORDER BY c.course_code ASC, l.created_at DESC";
$result_materials = mysqli_query($conn, $sql_materials);

if ($result_materials && mysqli_num_rows($result_materials) > 0) {
    while ($row = mysqli_fetch_assoc($result_materials)) {
        $materials[] = $row;
    }
}

// GET COURSES FOR FILTER
$all_courses = [];
$sql_c = "SELECT id, course_code, course_name FROM courses ORDER BY course_code ASC";
$result_c = mysqli_query($conn, $sql_c);
if ($result_c && mysqli_num_rows($result_c) > 0) {
    while ($row = mysqli_fetch_assoc($result_c)) {
        $all_courses[] = $row;
    }
}

$total_materials = count($materials);
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management - Admin | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .library-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .upload-form {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }
        
        .upload-form h3 {
            color: #0056b3;
            margin-bottom: 20px;
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
        }
        
        .btn-upload {
            background: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .full-width {
            grid-column: span 2;
        }
        
        .stats-grid {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #0056b3, #003d82);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            text-align: center;
            flex: 1;
        }
        
        .stat-card h3 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .search-section {
            margin-bottom: 25px;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .search-box input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .search-box button {
            background: #0056b3;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-row select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
        }
        
        .materials-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .materials-table th, .materials-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .materials-table th {
            background: #0056b3;
            color: white;
        }
        
        .materials-table tr:hover {
            background: #f8f9fa;
        }
        
        .course-badge {
            background: #0056b3;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            display: inline-block;
        }
        
        .category-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .category-Textbook { background: #28a745; color: white; }
        .category-PastPaper { background: #dc3545; color: white; }
        .category-Notes { background: #ffc107; color: #333; }
        .category-Reference { background: #17a2b8; color: white; }
        .category-Article { background: #6f42c1; color: white; }
        
        .btn-group {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .btn-view {
            background: #17a2b8;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 11px;
        }
        
        .btn-download {
            background: #28a745;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 11px;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 11px;
        }
        
        .btn-toggle {
            background: #ffc107;
            color: #333;
            padding: 4px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 11px;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #999;
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

     <?php include 'sidebar_admin.php'; ?>

    <div class="content-wrapper">
        <header class="top-header">
            <div class="welcome-top">
                <p>Mwanza Campus | <strong>Today:</strong> <?php echo date("d M, Y"); ?></p>
                <i class="fas fa-bell"></i>
            </div>
        </header>

        <main>
            <div class="panel-title">Library Management</div>
            
            <div class="info-banner">
                <span><strong>Admin:</strong> <?php echo $admin_name; ?></span>
                <span><strong>Total Materials:</strong> <?php echo $total_materials; ?></span>
                <span><strong>System Status:</strong> Online</span>
            </div>

            <div class="library-container">
                <?php if($success): ?>
                    <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Upload Form -->
                <div class="upload-form">
                    <h3><i class="fas fa-cloud-upload-alt"></i> Upload Material to Course</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Select Course *</label>
                                <select name="course_id" required>
                                    <option value="">-- Select Course --</option>
                                    <?php foreach($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo $c['course_code']; ?> - <?php echo $c['course_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Module (Optional)</label>
                                <select name="module_id">
                                    <option value="0">-- General (No specific module) --</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Title *</label>
                                <input type="text" name="title" required>
                            </div>
                            <div class="form-group">
                                <label>Author</label>
                                <input type="text" name="author">
                            </div>
                            <div class="form-group">
                                <label>Category *</label>
                                <select name="category" required>
                                    <option value="Textbook">📚 Textbook</option>
                                    <option value="Past Paper">📝 Past Paper</option>
                                    <option value="Notes">📖 Notes</option>
                                    <option value="Reference">📑 Reference</option>
                                    <option value="Article">📄 Article</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Year of Study</label>
                                <select name="year_of_study">
                                    <option value="">All Years</option>
                                    <option value="1st Year">1st Year</option>
                                    <option value="2nd Year">2nd Year</option>
                                    <option value="3rd Year">3rd Year</option>
                                </select>
                            </div>
                            <div class="form-group full-width">
                                <label>File *</label>
                                <input type="file" name="file" required>
                                <small>Allowed: PDF, DOC, PPT, TXT, Images, MP4 (Max 10MB)</small>
                            </div>
                            <div class="form-group full-width">
                                <label>Description</label>
                                <textarea name="description" rows="2"></textarea>
                            </div>
                        </div>
                        <button type="submit" name="add_material" class="btn-upload">
                            <i class="fas fa-cloud-upload-alt"></i> Upload Material
                        </button>
                    </form>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card"><h3><?php echo $total_materials; ?></h3><p>Total Materials</p></div>
                    <div class="stat-card"><h3><?php echo count($all_courses); ?></h3><p>Courses</p></div>
                </div>

                <!-- Filters -->
                <div class="search-section">
                    <form method="GET" class="search-box">
                        <input type="text" name="search" placeholder="Search materials..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fas fa-search"></i> Search</button>
                        <?php if($search != ''): ?>
                        <a href="admin_library.php" style="background:#6c757d; color:white; padding:10px 20px; border-radius:6px; text-decoration:none;">Clear</a>
                        <?php endif; ?>
                    </form>
                    <div class="filter-row">
                        <select name="course" onchange="window.location.href='admin_library.php?course='+this.value">
                            <option value="0">All Courses</option>
                            <?php foreach($all_courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($course_filter == $c['id']) ? 'selected' : ''; ?>><?php echo $c['course_code']; ?> - <?php echo $c['course_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="category" onchange="window.location.href='admin_library.php?course=<?php echo $course_filter; ?>&category='+this.value">
                            <option value="all">All Categories</option>
                            <option value="Textbook" <?php echo $category_filter == 'Textbook' ? 'selected' : ''; ?>>Textbook</option>
                            <option value="Past Paper" <?php echo $category_filter == 'Past Paper' ? 'selected' : ''; ?>>Past Paper</option>
                            <option value="Notes" <?php echo $category_filter == 'Notes' ? 'selected' : ''; ?>>Notes</option>
                            <option value="Reference" <?php echo $category_filter == 'Reference' ? 'selected' : ''; ?>>Reference</option>
                            <option value="Article" <?php echo $category_filter == 'Article' ? 'selected' : ''; ?>>Article</option>
                        </select>
                        <?php if($course_filter != 0 || $category_filter != 'all'): ?>
                        <a href="admin_library.php" class="btn-delete" style="background:#6c757d; padding:8px 15px;">Clear Filters</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Materials Table -->
                <?php if(empty($materials)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>No materials found.</p>
                        <p>Upload materials using the form above.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="materials-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Downloads</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($materials as $material): ?>
                                <tr>
                                    <td><span class="course-badge"><?php echo $material['course_code']; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($material['title']); ?></strong><br><small><?php echo htmlspecialchars($material['author']); ?></small></td>
                                    <td><span class="category-badge category-<?php echo $material['category']; ?>"><?php echo $material['category']; ?></span></td>
                                    <td><?php echo substr(htmlspecialchars($material['description']), 0, 60); ?>...\(
                                    <td><?php echo $material['download_count']; ?>\(
                                    <td><span class="category-badge <?php echo $material['status'] == 'active' ? 'category-Textbook' : 'category-PastPaper'; ?>"><?php echo ucfirst($material['status']); ?></span>\(
                                    <td class="btn-group">
                                        <a href="uploads/library/<?php echo $material['file_path']; ?>" class="btn-view" target="_blank"><i class="fas fa-eye"></i> View</a>
                                        <a href="uploads/library/<?php echo $material['file_path']; ?>" class="btn-download" download><i class="fas fa-download"></i> Download</a>
                                        <?php if($material['status'] == 'active'): ?>
                                        <a href="?status=inactive&id=<?php echo $material['id']; ?>" class="btn-toggle"><i class="fas fa-ban"></i> Deactivate</a>
                                        <?php else: ?>
                                        <a href="?status=active&id=<?php echo $material['id']; ?>" class="btn-toggle"><i class="fas fa-check"></i> Activate</a>
                                        <?php endif; ?>
                                        <a href="?delete=<?php echo $material['id']; ?>" class="btn-delete" onclick="return confirm('Delete this material?')"><i class="fas fa-trash"></i> Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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