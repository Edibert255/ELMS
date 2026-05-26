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

// SEARCH AND FILTER
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$program = isset($_GET['program']) ? mysqli_real_escape_string($conn, $_GET['program']) : '';
$year = isset($_GET['year']) ? mysqli_real_escape_string($conn, $_GET['year']) : '';

// FETCH ALL STUDENTS - Fixed query to avoid email errors
$students = [];
$sql = "SELECT u.id, u.full_name, u.email, u.username, u.status, 
        s.registration_no, s.program_of_study, s.year_of_study, s.photo, s.phone, s.gender
        FROM users u 
        LEFT JOIN students s ON u.id = s.user_id 
        WHERE u.role = 'student'";

if ($search != '') {
    $sql .= " AND (u.full_name LIKE '%$search%' OR s.registration_no LIKE '%$search%' OR u.email LIKE '%$search%')";
}
if ($program != '') {
    $sql .= " AND s.program_of_study = '$program'";
}
if ($year != '') {
    $sql .= " AND s.year_of_study = '$year'";
}

$sql .= " ORDER BY u.full_name ASC";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
}

// FETCH STATISTICS
$total_students = count($students);
$active_students = count(array_filter($students, function($s) { 
    return isset($s['status']) && $s['status'] == 'active'; 
}));

// GET UNIQUE PROGRAMS FOR FILTER
$programs = [];
$sql_prog = "SELECT DISTINCT program_of_study FROM students WHERE program_of_study IS NOT NULL AND program_of_study != ''";
$result_prog = mysqli_query($conn, $sql_prog);
if ($result_prog && mysqli_num_rows($result_prog) > 0) {
    while ($row = mysqli_fetch_assoc($result_prog)) {
        $programs[] = $row['program_of_study'];
    }
}

// GET UNIQUE YEARS FOR FILTER
$years = [];
$sql_years = "SELECT DISTINCT year_of_study FROM students WHERE year_of_study IS NOT NULL AND year_of_study != ''";
$result_years = mysqli_query($conn, $sql_years);
if ($result_years && mysqli_num_rows($result_years) > 0) {
    while ($row = mysqli_fetch_assoc($result_years)) {
        $years[] = $row['year_of_study'];
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
    <title>Students - Lecturer | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .students-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        /* Search Bar */
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .search-bar input {
            flex: 2;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .search-bar select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }
        
        .search-bar button {
            background: #0056b3;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .search-bar button:hover {
            background: #003d82;
        }
        
        .clear-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .clear-btn:hover {
            background: #5a6268;
        }
        
        /* Stats Cards - WHITE BACKGROUND */
        .stats-students {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .stat-student-card {
            background: white;
            color: #333;
            padding: 20px 25px;
            border-radius: 12px;
            text-align: center;
            flex: 1;
            min-width: 180px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            transition: transform 0.3s ease;
        }
        
        .stat-student-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-student-card h3 {
            font-size: 32px;
            margin-bottom: 8px;
            color: #0056b3;
        }
        
        .stat-student-card p {
            font-size: 14px;
            color: #666;
        }
        
        .stat-student-card i {
            font-size: 24px;
            color: #0056b3;
            margin-bottom: 10px;
        }
        
        /* Table */
        .students-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .students-table th, .students-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .students-table th {
            background: #0056b3;
            color: white;
            font-weight: bold;
        }
        
        .students-table tr:hover {
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
        
        .btn-view {
            background: #0056b3;
            color: white;
            padding: 5px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }
        
        .btn-view:hover {
            background: #003d82;
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
        
        @media (max-width: 768px) {
            .students-table {
                font-size: 12px;
                display: block;
                overflow-x: auto;
            }
            .students-table th, .students-table td {
                padding: 8px;
            }
            .search-bar input, .search-bar select {
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
            <div class="panel-title">Students Management</div>
            
            <div class="info-banner">
                <span><strong>Staff ID:</strong> <?php echo $_SESSION['username']; ?></span>
                <span><strong>Total Students:</strong> <?php echo $total_students; ?></span>
                <span><strong>User:</strong> <?php echo $first_name; ?></span>
            </div>

            <div class="students-container">
                <!-- Search and Filter -->
                <form method="GET" action="lecture_students.php" class="search-bar">
                    <input type="text" name="search" placeholder="Search by name, registration number or email..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="program">
                        <option value="">All Programs</option>
                        <?php foreach($programs as $p): ?>
                        <option value="<?php echo htmlspecialchars($p); ?>" <?php echo $program == $p ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="year">
                        <option value="">All Years</option>
                        <?php foreach($years as $y): ?>
                        <option value="<?php echo htmlspecialchars($y); ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($y); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if($search != '' || $program != '' || $year != ''): ?>
                    <a href="lecture_students.php" class="clear-btn">
                        <i class="fas fa-times"></i> Clear
                    </a>
                    <?php endif; ?>
                </form>

                <!-- Statistics Cards - WHITE BACKGROUND -->
                <div class="stats-students">
                    <div class="stat-student-card">
                        <i class="fas fa-users"></i>
                        <h3><?php echo $total_students; ?></h3>
                        <p>Total Students</p>
                    </div>
                    <div class="stat-student-card">
                        <i class="fas fa-user-check"></i>
                        <h3><?php echo $active_students; ?></h3>
                        <p>Active Students</p>
                    </div>
                </div>

                <!-- Students Table -->
                <?php if(empty($students)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-graduate"></i>
                        <p>No students found in the database.</p>
                        <p style="font-size: 13px;">Please add students first or try changing your search criteria.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Reg No.</th>
                                    <th>Program</th>
                                    <th>Year</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['full_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($student['registration_no'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($student['program_of_study'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($student['year_of_study'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo ($student['status'] ?? '') == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo ucfirst($student['status'] ?? 'Inactive'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn-view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
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
                menu.classList.toggle('active');
            }
        }
    </script>

</body>
</html>