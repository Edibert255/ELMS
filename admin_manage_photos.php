<?php
include 'config.php';
session_start();

// CHECK IF ADMIN IS LOGGED IN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'];
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$role_filter = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : '';

// FETCH ALL USERS
$users = [];
$sql = "SELECT * FROM users WHERE role != 'admin'";

if ($search != '') {
    $sql .= " AND (full_name LIKE '%$search%' OR email LIKE '%$search%' OR username LIKE '%$search%')";
}
if ($role_filter != '') {
    $sql .= " AND role = '$role_filter'";
}

$sql .= " ORDER BY role ASC, full_name ASC";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}

// Get counts
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='student'"))['c'];
$total_lecturers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='lecturer'"))['c'];

$photo = 'default.png';
$first_name = explode(' ', $admin_name)[0];
$last_name = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User Photos - Admin | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .photos-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stats-grid {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #0056b3, #003d82);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            flex: 1;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .search-bar input, .search-bar select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .search-bar input {
            flex: 2;
        }
        
        .search-bar button {
            background: #0056b3;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            background: #f8f9fa;
            color: #333;
            padding: 6px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            text-decoration: none;
            font-size: 13px;
        }
        
        .filter-btn.active {
            background: #0056b3;
            color: white;
            border-color: #0056b3;
        }
        
        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .user-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e0e0e0;
            transition: transform 0.3s ease;
        }
        
        .user-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .user-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #0056b3;
            margin-bottom: 15px;
        }
        
        .user-name {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .user-email {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .role-student { background: #28a745; color: white; }
        .role-lecturer { background: #ffc107; color: #333; }
        
        .btn-upload {
            background: #17a2b8;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        
        .btn-upload:hover { background: #138496; }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .users-grid { grid-template-columns: 1fr; }
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
            <div class="panel-title">Manage User Photos</div>
            
            <div class="info-banner">
                <span><strong>Admin:</strong> <?php echo $admin_name; ?></span>
                <span><strong>System Status:</strong> Online</span>
                <span><strong>Total Users:</strong> <?php echo count($users); ?></span>
            </div>

            <div class="photos-container">
                <div class="stats-grid">
                    <div class="stat-card"><h3><?php echo $total_students; ?></h3><p><i class="fas fa-user-graduate"></i> Students</p></div>
                    <div class="stat-card"><h3><?php echo $total_lecturers; ?></h3><p><i class="fas fa-chalkboard-teacher"></i> Lecturers</p></div>
                </div>

                <div class="filter-buttons">
                    <a href="admin_manage_photos.php" class="filter-btn <?php echo $role_filter == '' ? 'active' : ''; ?>">All</a>
                    <a href="?role=student" class="filter-btn <?php echo $role_filter == 'student' ? 'active' : ''; ?>">Students Only</a>
                    <a href="?role=lecturer" class="filter-btn <?php echo $role_filter == 'lecturer' ? 'active' : ''; ?>">Lecturers Only</a>
                </div>

                <form method="GET" action="admin_manage_photos.php" class="search-bar">
                    <input type="hidden" name="role" value="<?php echo $role_filter; ?>">
                    <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if($search != ''): ?>
                    <a href="admin_manage_photos.php?role=<?php echo $role_filter; ?>" style="background:#6c757d; color:white; padding:10px 20px; border-radius:6px; text-decoration:none;">Clear</a>
                    <?php endif; ?>
                </form>

                <?php if(empty($users)): ?>
                    <div class="empty-state"><i class="fas fa-users-slash" style="font-size: 48px;"></i><p>No users found.</p></div>
                <?php else: ?>
                    <div class="users-grid">
                        <?php foreach($users as $user): ?>
                        <div class="user-card">
                            <img src="uploads/profile_pics/<?php echo $user['photo'] ?? 'default.png'; ?>" class="user-photo" alt="Photo">
                            <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div class="user-email"><i class="fas fa-envelope"></i> <?php echo $user['email']; ?></div>
                            <span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
                            <div><a href="admin_upload_photo.php?user_id=<?php echo $user['id']; ?>&role=<?php echo $user['role']; ?>" class="btn-upload"><i class="fas fa-camera"></i> Upload Photo</a></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>function toggleSub(id){const menu=document.getElementById(id);if(menu){menu.classList.toggle('active');}}</script>
</body>
</html>