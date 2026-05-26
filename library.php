<?php
include 'config.php';
session_start();

// CHECK IF USER IS LOGGED IN
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// FETCH USER DATA
$sql_user = "SELECT * FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $sql_user);
$user = mysqli_fetch_assoc($result_user);

// FETCH STUDENT DATA (if role is student)
$student = [];
if ($role == 'student') {
    $sql_student = "SELECT * FROM students WHERE user_id = '$user_id'";
    $result_student = mysqli_query($conn, $sql_student);
    if ($result_student && mysqli_num_rows($result_student) > 0) {
        $student = mysqli_fetch_assoc($result_student);
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

// FETCH POPULAR MATERIALS (most downloaded)
$popular_materials = [];
$sql_popular = "SELECT * FROM library_materials WHERE status = 'active' ORDER BY download_count DESC LIMIT 5";
$result_popular = mysqli_query($conn, $sql_popular);
if ($result_popular && mysqli_num_rows($result_popular) > 0) {
    while ($row = mysqli_fetch_assoc($result_popular)) {
        $popular_materials[] = $row;
    }
}

// CATEGORIES WITH COUNTS
$categories = [];
$sql_cats = "SELECT category, COUNT(*) as count FROM library_materials WHERE status = 'active' GROUP BY category";
$result_cats = mysqli_query($conn, $sql_cats);
if ($result_cats && mysqli_num_rows($result_cats) > 0) {
    while ($row = mysqli_fetch_assoc($result_cats)) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library - CBE ELMS</title>
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
        
        /* Search Bar */
        .search-section {
            background: linear-gradient(135deg, #0056b3 0%, #003d82 100%);
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            color: white;
        }
        
        .search-section h2 {
            margin-bottom: 15px;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .search-box input {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .search-box button {
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .search-box button:hover {
            background: #218838;
        }
        
        /* Categories */
        .categories {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }
        
        .category-btn {
            background: #f8f9fa;
            color: #333;
            padding: 8px 20px;
            border: 1px solid #ddd;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
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
        
        /* Main Layout */
        .library-grid {
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 25px;
        }
        
        /* Sidebar Categories */
        .sidebar-categories {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            height: fit-content;
        }
        
        .sidebar-categories h3 {
            margin-bottom: 15px;
            color: #0056b3;
        }
        
        .category-list {
            list-style: none;
        }
        
        .category-list li {
            margin-bottom: 10px;
        }
        
        .category-list a {
            display: flex;
            justify-content: space-between;
            color: #333;
            text-decoration: none;
            padding: 8px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .category-list a:hover, .category-list a.active {
            background: #0056b3;
            color: white;
        }
        
        .category-count {
            background: #ddd;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .category-list a:hover .category-count,
        .category-list a.active .category-count {
            background: white;
            color: #0056b3;
        }
        
        /* Popular Section */
        .popular-section {
            background: #fff3cd;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .popular-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid #ffeaa7;
        }
        
        .popular-item i {
            color: #ffc107;
        }
        
        .popular-item a {
            flex: 1;
            color: #333;
            text-decoration: none;
        }
        
        .popular-item a:hover {
            color: #0056b3;
        }
        
        /* Materials Grid */
        .materials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .material-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            transition: transform 0.3s ease;
            border-left: 4px solid #0056b3;
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
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .material-author {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
        }
        
        .material-description {
            color: #555;
            font-size: 13px;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .material-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 12px;
            color: #888;
        }
        
        .category-badge {
            background: #0056b3;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
        }
        
        .btn-download {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            width: 100%;
        }
        
        .btn-download:hover {
            background: #218838;
        }
        
        .btn-preview {
            background: #0056b3;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            width: 48%;
        }
        
        .btn-preview:hover {
            background: #003d82;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #999;
            grid-column: span 3;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .library-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* File type icons */
        .file-pdf { color: #dc3545; }
        .file-doc { color: #0056b3; }
        .-file-ppt { color: #fd7e14; }
        .-file-video { color: #6f42c1; }
        .file-default { color: #6c757d; }
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
            <div class="panel-title">Digital Library</div>
            
            <div class="info-banner">
                <span><strong>Role:</strong> <?php echo ucfirst($role); ?></span>
                <span><strong>Available Resources:</strong> <?php echo count($materials); ?> items</span>
                <span><strong>User:</strong> <?php echo $student['first_name'] ?? $user['full_name']; ?></span>
            </div>

            <div class="library-container">
                
                <!-- Search Section -->
                <div class="search-section">
                    <h2><i class="fas fa-search"></i> Find Learning Resources</h2>
                    <form method="GET" action="library.php" class="search-box">
                        <input type="text" name="search" placeholder="Search by title, author, or description..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fas fa-search"></i> Search</button>
                        <?php if($search != '' || $category != 'all'): ?>
                            <a href="library.php" class="btn-reset" style="background:#6c757d; padding:12px 20px; border-radius:8px; color:white; text-decoration:none;">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="library-grid">
                    
                    <!-- Left Sidebar -->
                    <div>
                        <!-- Categories -->
                        <div class="sidebar-categories">
                            <h3><i class="fas fa-folder"></i> Categories</h3>
                            <ul class="category-list">
                                <li><a href="library.php?category=all" class="<?php echo $category == 'all' ? 'active' : ''; ?>">All Resources <span class="category-count"><?php echo count($materials); ?></span></a></li>
                                <?php foreach($categories as $cat): ?>
                                <li>
                                    <a href="library.php?category=<?php echo urlencode($cat['category']); ?>" class="<?php echo $category == $cat['category'] ? 'active' : ''; ?>">
                                        <?php 
                                            $icon = '';
                                            if($cat['category'] == 'Textbook') $icon = '<i class="fas fa-book"></i>';
                                            elseif($cat['category'] == 'Past Paper') $icon = '<i class="fas fa-file-alt"></i>';
                                            elseif($cat['category'] == 'Notes') $icon = '<i class="fas fa-sticky-note"></i>';
                                            elseif($cat['category'] == 'Reference') $icon = '<i class="fas fa-book-open"></i>';
                                            else $icon = '<i class="fas fa-folder"></i>';
                                            echo $icon . ' ' . $cat['category'];
                                        ?>
                                        <span class="category-count"><?php echo $cat['count']; ?></span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <!-- Popular Materials -->
                        <?php if(!empty($popular_materials)): ?>
                        <div class="popular-section">
                            <h3><i class="fas fa-fire"></i> Most Popular</h3>
                            <?php foreach($popular_materials as $popular): ?>
                            <div class="popular-item">
                                <i class="fas fa-download"></i>
                                <a href="download_material.php?id=<?php echo $popular['id']; ?>"><?php echo htmlspecialchars($popular['title']); ?></a>
                                <small>(<?php echo $popular['download_count']; ?> downloads)</small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Materials Grid -->
                    <div>
                        <?php if(empty($materials)): ?>
                            <div class="empty-state">
                                <i class="fas fa-folder-open"></i>
                                <p>No materials found in this category.</p>
                                <a href="library.php" style="color: #0056b3;">Browse all resources</a>
                            </div>
                        <?php else: ?>
                            <div class="materials-grid">
                                <?php foreach($materials as $material): ?>
                                <div class="material-card">
                                    <div class="material-icon">
                                        <?php
                                        $file_icon = 'fa-file-alt';
                                        if(strpos($material['file_path'], '.pdf') !== false) $file_icon = 'fa-file-pdf';
                                        elseif(strpos($material['file_path'], '.doc') !== false) $file_icon = 'fa-file-word';
                                        elseif(strpos($material['file_path'], '.ppt') !== false) $file_icon = 'fa-file-powerpoint';
                                        elseif(strpos($material['file_path'], '.mp4') !== false) $file_icon = 'fa-file-video';
                                        ?>
                                        <i class="fas <?php echo $file_icon; ?>"></i>
                                    </div>
                                    <div class="material-title"><?php echo htmlspecialchars($material['title']); ?></div>
                                    <div class="material-author"><i class="fas fa-user"></i> <?php echo htmlspecialchars($material['author'] ?? 'CBE Faculty'); ?></div>
                                    <div class="material-description"><?php echo substr(htmlspecialchars($material['description']), 0, 100); ?>...</div>
                                    <div class="material-meta">
                                        <span class="category-badge"><?php echo $material['category']; ?></span>
                                        <span><i class="fas fa-download"></i> <?php echo $material['download_count'] ?? 0; ?></span>
                                    </div>
                                    <div class="btn-group">
                                        <?php if(strpos($material['file_path'], '.pdf') !== false || strpos($material['file_path'], '.doc') !== false): ?>
                                        <button class="btn-preview" onclick="window.open('uploads/library/<?php echo $material['file_path']; ?>', '_blank')">
                                            <i class="fas fa-eye"></i> Preview
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn-download" onclick="window.location.href='download_material.php?id=<?php echo $material['id']; ?>'">
                                            <i class="fas fa-download"></i> Download
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                </div>
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