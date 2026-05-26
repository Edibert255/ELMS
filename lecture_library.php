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

// ADD NEW MATERIAL
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_material'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    
    // Handle file upload
    $file_path = "";
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png'];
        $filename = $_FILES['file']['name'];
        $fileext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $filesize = $_FILES['file']['size'];
        
        if ($filesize > 10485760) { // 10MB max
            $error = "File too large! Maximum 10MB allowed.";
        } elseif (in_array($fileext, $allowed)) {
            $new_filename = time() . "_" . preg_replace('/[^a-zA-Z0-9]/', '_', $title) . "." . $fileext;
            $upload_path = "uploads/library/" . $new_filename;
            
            // Create directory if not exists
            if (!file_exists("uploads/library/")) {
                mkdir("uploads/library/", 0777, true);
            }
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_path)) {
                $file_path = $new_filename;
            } else {
                $error = "Failed to upload file.";
            }
        } else {
            $error = "File type not allowed. Allowed: PDF, DOC, PPT, TXT, Images";
        }
    } else {
        $error = "Please select a file to upload.";
    }
    
    if (empty($error) && $file_path != "") {
        $sql = "INSERT INTO library_materials (title, author, description, category, file_path, uploaded_by, status) 
                VALUES ('$title', '$author', '$description', '$category', '$file_path', '$lecturer_id', 'active')";
        
        if (mysqli_query($conn, $sql)) {
            $success = "Material uploaded successfully!";
            // Clear form
            $_POST = array();
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}

// DELETE MATERIAL
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get file path first
    $sql_file = "SELECT file_path FROM library_materials WHERE id = '$id'";
    $result_file = mysqli_query($conn, $sql_file);
    if ($result_file && mysqli_num_rows($result_file) > 0) {
        $material = mysqli_fetch_assoc($result_file);
        $file_path = "uploads/library/" . $material['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    $sql = "DELETE FROM library_materials WHERE id = '$id'";
    if (mysqli_query($conn, $sql)) {
        $success = "Material deleted successfully!";
    } else {
        $error = "Delete failed!";
    }
}

// GET CATEGORY FILTER
$category = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// FETCH LIBRARY MATERIALS
$materials = [];
$sql_materials = "SELECT * FROM library_materials WHERE status = 'active'";

if ($category != 'all') {
    $sql_materials .= " AND category = '$category'";
}

if ($search != '') {
    $sql_materials .= " AND (title LIKE '%$search%' OR description LIKE '%$search%' OR author LIKE '%$search%')";
}

$sql_materials .= " ORDER BY created_at DESC";
$result_materials = mysqli_query($conn, $sql_materials);

if ($result_materials && mysqli_num_rows($result_materials) > 0) {
    while ($row = mysqli_fetch_assoc($result_materials)) {
        $materials[] = $row;
    }
}

// FETCH CATEGORY COUNTS
$categories = [];
$sql_cats = "SELECT category, COUNT(*) as count FROM library_materials WHERE status = 'active' GROUP BY category";
$result_cats = mysqli_query($conn, $sql_cats);
if ($result_cats && mysqli_num_rows($result_cats) > 0) {
    while ($row = mysqli_fetch_assoc($result_cats)) {
        $categories[] = $row;
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
    <title>Library - Lecturer | CBE ELMS</title>
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
        
        /* Upload Form */
        .upload-form {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }
        
        .upload-form h3 {
            color: #0056b3;
            margin-bottom: 15px;
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
        
        .btn-upload {
            background: #28a745;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-upload:hover {
            background: #218838;
        }
        
        .full-width {
            grid-column: span 2;
        }
        
        /* Search and Filter */
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
        
        .categories {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .category-btn {
            background: #f8f9fa;
            color: #333;
            padding: 6px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
        }
        
        .category-btn.active {
            background: #0056b3;
            color: white;
            border-color: #0056b3;
        }
        
        .category-btn:hover {
            background: #0056b3;
            color: white;
        }
        
        /* Materials Grid */
        .materials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .material-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 18px;
            border-left: 4px solid #0056b3;
            transition: transform 0.3s ease;
        }
        
        .material-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .material-icon {
            font-size: 40px;
            color: #0056b3;
            margin-bottom: 10px;
        }
        
        .material-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .material-author {
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .material-description {
            font-size: 12px;
            color: #555;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        
        .material-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-size: 11px;
        }
        
        .category-badge {
            background: #0056b3;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 10px;
        }
        
        .download-count {
            color: #888;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-download {
            background: #28a745;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-view {
            background: #17a2b8;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
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
            .form-grid {
                grid-template-columns: 1fr;
            }
            .full-width {
                grid-column: span 1;
            }
            .materials-grid {
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
            <div class="panel-title">Library Management</div>
            
            <div class="info-banner">
                <span><strong>Staff ID:</strong> <?php echo $_SESSION['username']; ?></span>
                <span><strong>Total Materials:</strong> <?php echo count($materials); ?></span>
                <span><strong>User:</strong> <?php echo $first_name; ?></span>
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
                    <h3><i class="fas fa-upload"></i> Upload New Material</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Title *</label>
                                <input type="text" name="title" placeholder="e.g., Introduction to Programming" required>
                            </div>
                            <div class="form-group">
                                <label>Author</label>
                                <input type="text" name="author" placeholder="e.g., Dr. John Mwita">
                            </div>
                            <div class="form-group">
                                <label>Category *</label>
                                <select name="category" required>
                                    <option value="Textbook">📚 Textbook</option>
                                    <option value="Past Paper">📝 Past Paper</option>
                                    <option value="Notes">📖 Notes</option>
                                    <option value="Reference">📑 Reference</option>
                                    <option value="Article">📄 Article</option>
                                    <option value="Video">🎥 Video</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>File *</label>
                                <input type="file" name="file" required>
                                <small style="color:#666;">Allowed: PDF, DOC, PPT, TXT, Images (Max 10MB)</small>
                            </div>
                            <div class="form-group full-width">
                                <label>Description</label>
                                <textarea name="description" rows="3" placeholder="Brief description of the material..."></textarea>
                            </div>
                        </div>
                        <button type="submit" name="add_material" class="btn-upload">
                            <i class="fas fa-cloud-upload-alt"></i> Upload Material
                        </button>
                    </form>
                </div>

                <!-- Search and Filter -->
                <div class="search-section">
                    <form method="GET" action="lecture_library.php" class="search-box">
                        <input type="text" name="search" placeholder="Search by title, author or description..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fas fa-search"></i> Search</button>
                        <?php if($search != '' || $category != 'all'): ?>
                        <a href="lecture_library.php" class="btn-delete" style="background:#6c757d; padding:10px 20px;">
                            <i class="fas fa-times"></i> Clear
                        </a>
                        <?php endif; ?>
                    </form>
                    
                    <div class="categories">
                        <a href="lecture_library.php" class="category-btn <?php echo $category == 'all' ? 'active' : ''; ?>">All</a>
                        <?php foreach($categories as $cat): ?>
                        <a href="lecture_library.php?category=<?php echo urlencode($cat['category']); ?>" class="category-btn <?php echo $category == $cat['category'] ? 'active' : ''; ?>">
                            <?php echo $cat['category']; ?> (<?php echo $cat['count']; ?>)
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Materials Grid -->
                <?php if(empty($materials)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>No materials found.</p>
                        <p style="font-size: 13px;">Upload your first learning material using the form above.</p>
                    </div>
                <?php else: ?>
                    <div class="materials-grid">
                        <?php foreach($materials as $material): ?>
                        <div class="material-card">
                            <div class="material-icon">
                                <?php
                                $icon = 'fa-file-alt';
                                if(strpos($material['file_path'], '.pdf') !== false) $icon = 'fa-file-pdf';
                                elseif(strpos($material['file_path'], '.doc') !== false) $icon = 'fa-file-word';
                                elseif(strpos($material['file_path'], '.ppt') !== false) $icon = 'fa-file-powerpoint';
                                elseif(strpos($material['file_path'], '.jpg') !== false || strpos($material['file_path'], '.png') !== false) $icon = 'fa-file-image';
                                elseif(strpos($material['file_path'], '.mp4') !== false) $icon = 'fa-file-video';
                                ?>
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                            <div class="material-title"><?php echo htmlspecialchars($material['title']); ?></div>
                            <div class="material-author"><i class="fas fa-user"></i> <?php echo htmlspecialchars($material['author'] ?? 'CBE Faculty'); ?></div>
                            <div class="material-description"><?php echo substr(htmlspecialchars($material['description']), 0, 100); ?>...</div>
                            <div class="material-meta">
                                <span class="category-badge"><?php echo $material['category']; ?></span>
                                <span class="download-count"><i class="fas fa-download"></i> <?php echo $material['download_count'] ?? 0; ?> downloads</span>
                            </div>
                            <div class="card-actions">
                                <a href="uploads/library/<?php echo $material['file_path']; ?>" class="btn-view" target="_blank">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="uploads/library/<?php echo $material['file_path']; ?>" class="btn-download" download>
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <a href="?delete=<?php echo $material['id']; ?>" class="btn-delete" onclick="return confirm('Delete this material?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
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