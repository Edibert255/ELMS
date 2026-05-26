<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'];
$success = "";
$error = "";

// DELETE LECTURER
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM users WHERE id='$id' AND role='lecturer'");
    $success = "Lecturer deleted successfully!";
}

// UPDATE STATUS
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    mysqli_query($conn, "UPDATE users SET status='$status' WHERE id='$id'");
    $success = "Status updated successfully!";
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$lecturers = [];
$sql = "SELECT * FROM users WHERE role = 'lecturer'";
if ($search != '') {
    $sql .= " AND (full_name LIKE '%$search%' OR email LIKE '%$search%' OR username LIKE '%$search%')";
}
$sql .= " ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $lecturers[] = $row;
    }
}

$photo = 'default.png';
$first_name = explode(' ', $admin_name)[0];
$last_name = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lecturers - Admin | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .manage-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }
        
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .data-table th {
            background: #0056b3;
            color: white;
            font-weight: bold;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .lecturer-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* BUTTON GROUP - SAFI MOJA */
        .btn-group {
            display: flex;
            gap: 6px;
            flex-wrap: nowrap;
        }
        
        .btn-active {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 11px;
            display: inline-block;
            white-space: nowrap;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 11px;
            display: inline-block;
            white-space: nowrap;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 11px;
            display: inline-block;
            white-space: nowrap;
        }
        
        .btn-photo {
            background: #17a2b8;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 11px;
            display: inline-block;
            white-space: nowrap;
        }
        
        .btn-deactivate {
            background: #fd7e14;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 11px;
            display: inline-block;
            white-space: nowrap;
        }
        
        .btn-activate {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 11px;
            display: inline-block;
            white-space: nowrap;
        }
        
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .search-bar input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .search-bar button {
            background: #0056b3;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .btn-group {
                flex-wrap: wrap;
            }
            .data-table {
                font-size: 12px;
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
            <div class="panel-title">Manage Lecturers</div>
            
            <div class="info-banner">
                <span><strong>Admin:</strong> <?php echo $admin_name; ?></span>
                <span><strong>Total Lecturers:</strong> <?php echo count($lecturers); ?></span>
                <span><strong>System Status:</strong> Online</span>
            </div>

            <div class="manage-container">
                <?php if($success): ?>
                    <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>

                <!-- Search Bar -->
                <form method="GET" action="admin_lecturers.php" class="search-bar">
                    <input type="text" name="search" placeholder="Search by name, username or email..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if($search != ''): ?>
                    <a href="admin_lecturers.php" style="background:#6c757d; color:white; padding:10px 20px; border-radius:6px; text-decoration:none;">Clear</a>
                    <?php endif; ?>
                </form>

                <!-- Lecturers Table -->
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($lecturers as $lecturer): ?>
                        <tr>
                            <td>
                                <img src="uploads/profile_pics/<?php echo $lecturer['photo'] ?? 'default.png'; ?>" class="lecturer-photo" alt="Photo">
                            </td>
                            <td><?php echo htmlspecialchars($lecturer['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($lecturer['username']); ?></td>
                            <td><?php echo htmlspecialchars($lecturer['email']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $lecturer['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo ucfirst($lecturer['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <!-- Status Button (Active/Inactive) -->
                                    <?php if($lecturer['status'] == 'active'): ?>
                                        <a href="?status=inactive&id=<?php echo $lecturer['id']; ?>" class="btn-active" onclick="return confirm('Deactivate this lecturer?')">
                                            <i class="fas fa-check-circle"></i> Active
                                        </a>
                                    <?php else: ?>
                                        <a href="?status=active&id=<?php echo $lecturer['id']; ?>" class="btn-activate" onclick="return confirm('Activate this lecturer?')">
                                            <i class="fas fa-play-circle"></i> Activate
                                        </a>
                                    <?php endif; ?>
                                    
                                    <!-- Edit Button -->
                                    <a href="admin_edit_lecturer.php?id=<?php echo $lecturer['id']; ?>" class="btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    
                                    <!-- Delete Button -->
                                    <a href="?delete=<?php echo $lecturer['id']; ?>" class="btn-delete" onclick="return confirm('Delete this lecturer? This action cannot be undone!')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                    
                                    <!-- Photo Button -->
                                    <a href="admin_upload_photo.php?user_id=<?php echo $lecturer['id']; ?>&role=lecturer" class="btn-photo">
                                        <i class="fas fa-camera"></i> Photo
                                    </a>
                                    
                                    <!-- Deactivate/Activate Button (Alternative) -->
                                    <?php if($lecturer['status'] == 'active'): ?>
                                        <a href="?status=inactive&id=<?php echo $lecturer['id']; ?>" class="btn-deactivate" onclick="return confirm('Deactivate this lecturer?')">
                                            <i class="fas fa-ban"></i> Deactivate
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($lecturers)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 50px; color: #999;">
                                <i class="fas fa-chalkboard-teacher" style="font-size: 48px;"></i>
                                <p>No lecturers found.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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