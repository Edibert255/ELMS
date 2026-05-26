<?php
include 'config.php';
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['full_name'];
$admin_username = $_SESSION['username'];
$success = "";
$error = "";

// FETCH ADMIN PHOTO
$sql_admin = "SELECT * FROM users WHERE id = '$admin_id'";
$result_admin = mysqli_query($conn, $sql_admin);
$admin_data = mysqli_fetch_assoc($result_admin);
$admin_photo = $admin_data['photo'] ?? 'default.png';
$first_name = explode(' ', $admin_name)[0];
$last_name = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';

// DELETE STUDENT
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM students WHERE user_id = '$id'");
    mysqli_query($conn, "DELETE FROM users WHERE id = '$id' AND role = 'student'");
    $success = "Student deleted successfully!";
}

// UPDATE STATUS
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    mysqli_query($conn, "UPDATE users SET status = '$status' WHERE id = '$id' AND role = 'student'");
    $success = "Status updated successfully!";
}

// SEARCH
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// FETCH ALL STUDENTS (INCLUDING PASSWORD)
$students = [];
$sql = "SELECT u.id, u.full_name, u.email, u.username, u.password, u.status, u.photo,
        s.registration_no, s.program_of_study, s.year_of_study, s.phone, s.gender, s.date_of_birth
        FROM users u 
        LEFT JOIN students s ON u.id = s.user_id 
        WHERE u.role = 'student'";

if ($search != '') {
    $sql .= " AND (u.full_name LIKE '%$search%' OR s.registration_no LIKE '%$search%' OR u.email LIKE '%$search%')";
}

$sql .= " ORDER BY u.created_at DESC";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
}

$total_students = count($students);
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Admin | CBE ELMS</title>
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
            min-width: 1200px;
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
        
        .student-photo {
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
        
        .btn-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
            padding: 5px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 5px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }
        
        .btn-photo {
            background: #17a2b8;
            color: white;
            padding: 5px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }
        
        .btn-toggle {
            background: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
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
        
        .password-mask {
            font-family: monospace;
            letter-spacing: 1px;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #999;
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
            <div class="panel-title">Manage Students</div>
            
            <div class="info-banner">
                <span><strong>Admin:</strong> <?php echo $admin_name; ?></span>
                <span><strong>Total Students:</strong> <?php echo $total_students; ?></span>
                <span><strong>System Status:</strong> Online</span>
            </div>

            <div class="manage-container">
                <?php if($success): ?>
                    <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>

                <form method="GET" action="admin_students.php" class="search-bar">
                    <input type="text" name="search" placeholder="Search by name, registration number or email..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if($search != ''): ?>
                    <a href="admin_students.php" style="background:#6c757d; color:white; padding:10px 20px; border-radius:6px; text-decoration:none;">Clear</a>
                    <?php endif; ?>
                </form>

                <?php if(empty($students)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-graduate" style="font-size: 48px;"></i>
                        <p>No students found.</p>
                        <p><a href="admin_add_user.php?role=student" style="color: #0056b3;">Add New Student</a></p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Reg No.</th>
                                <th>Program</th>
                                <th>Year</th>
                                <th>Email</th>
                                <th>Password</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $student): ?>
                            <tr>
                                <td><img src="uploads/profile_pics/<?php echo $student['photo'] ?? 'default.png'; ?>?v=<?php echo time(); ?>" class="student-photo" alt="Photo"></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?><br><small><?php echo $student['username']; ?></small></td>
                                <td><?php echo htmlspecialchars($student['registration_no'] ?? 'Not Set'); ?></td>
                                <td><?php echo htmlspecialchars($student['program_of_study'] ?? 'Not Set'); ?></td>
                                <td><?php echo htmlspecialchars($student['year_of_study'] ?? 'Not Set'); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td class="password-mask">••••••••</td>
                                <td><span class="status-badge <?php echo $student['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>"><?php echo ucfirst($student['status']); ?></span></td>
                                <td class="btn-group">
                                    <a href="admin_edit_student.php?id=<?php echo $student['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="admin_upload_photo.php?user_id=<?php echo $student['id']; ?>&role=student" class="btn-photo"><i class="fas fa-camera"></i> Photo</a>
                                    <?php if($student['status'] == 'active'): ?>
                                        <a href="?status=inactive&id=<?php echo $student['id']; ?>" class="btn-toggle" onclick="return confirm('Deactivate this student?')"><i class="fas fa-ban"></i> Deactivate</a>
                                    <?php else: ?>
                                        <a href="?status=active&id=<?php echo $student['id']; ?>" class="btn-toggle" onclick="return confirm('Activate this student?')"><i class="fas fa-check"></i> Activate</a>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo $student['id']; ?>" class="btn-delete" onclick="return confirm('Delete this student?')"><i class="fas fa-trash"></i> Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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